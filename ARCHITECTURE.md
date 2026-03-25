# Documentación de arquitectura

## 1. Objetivo

Este repositorio implementa la **ruta crítica** de un sistema para cadenas de gimnasios: el **registro síncrono de acceso (check-in)** tiene prioridad absoluta; la **asignación de contenido motivacional** y la **consulta de historial para el dashboard** se resuelven con **consistencia eventual**, sin bloquear ni revertir el check-in por fallos de red o de integraciones externas.

La solución es un **monolito modular**: un solo código base y despliegue coherente, con **bounded contexts** explícitos y bajo acoplamiento entre dominios. **No** es una arquitectura de microservicios independientes.

---

## 2. Mapa de bounded contexts y propiedad de datos

| Contexto | Responsabilidad principal | Tablas / datos que posee | Frontera estricta |
|----------|-------------------------|---------------------------|-------------------|
| **AccessControl** | Registrar acceso físico de forma transaccional y emitir el hecho de negocio como evento persistido (outbox). | `checkins`, `outbox_messages` | No conoce frases, APIs externas ni persistencia de Engagement. |
| **Engagement** | Reaccionar al evento de check-in ya ocurrido: integrar proveedor de frases (ACL), persistir asignación y **proyectar** el modelo de lectura del dashboard. | `daily_quotes`, `dashboard_checkin_view` | No escribe en tablas de AccessControl ni participa en la transacción del check-in. |
| **Shared** | Abstracciones transversales: tiempo, identificadores, transacciones de aplicación, contrato de publicación de mensajes (`EventBus`). | Ninguna tabla de negocio propia | Evita duplicar infraestructura; no concentra reglas de dominio de negocio. |

**Regla de oro:** ningún controlador, handler o repositorio de `AccessControl` invoca repositorios, casos de uso o clientes HTTP de `Engagement`. La única integración cruzada permitida es la **mensajería asíncrona** tras el outbox.

---

## 3. Comunicación inter-modular

El patrón es **event-driven** con **broker** (RabbitMQ):

1. Cliente → `POST /api/check-ins`.
2. **AccessControl** ejecuta el command (`RegisterCheckIn` / `RegisterCheckInHandler`): en **una transacción** persiste el check-in y las filas correspondientes en `outbox_messages` para los eventos de dominio liberados (p. ej. `CheckInRegistered`).
3. La respuesta HTTP **201** no depende del broker ni de la API de frases.
4. Un proceso separado ejecuta periódicamente `outbox:publish`: lee mensajes con `published_at` nulo, publica en RabbitMQ y **solo entonces** marca `published_at` si la publicación fue exitosa.
5. El comando `engagement:consume-checkins` consume mensajes de la cola, valida el contrato del mensaje y ejecuta el caso de uso de Engagement que integra el proveedor y actualiza persistencia + read model.

Los nombres de exchange, colas y claves de enrutamiento son configurables vía `config/messaging.php` y variables `RABBITMQ_*` en el entorno.

---

## 4. Transactional Outbox y atomicidad del command

**Problema que resuelve:** si se confirmara el check-in en base de datos pero el evento se perdiera por una caída momentánea del broker, tendríamos **accesos fantasma** (acceso registrado sin posibilidad de fidelización asíncrona).

**Enfoque:** el evento **no** se publica dentro de la misma petición HTTP. Se **persiste** en `outbox_messages` en la **misma transacción** que `checkins`. La publicación al broker es un paso posterior, idempotente respecto al reintento de filas no publicadas.

| Resultado | Comportamiento |
|-----------|----------------|
| Commit de la transacción | Existen simultáneamente fila en `checkins` y en `outbox_messages`. |
| Rollback | No hay check-in ni outbox; el cliente recibe error. |
| Broker caído al publicar | `published_at` permanece nulo; el scheduler / `outbox:publish` reintenta sin tocar el check-in ya confirmado. |

---

## 5. ACL (Anti-Corruption Layer) e inversión de dependencias

El dominio de **Engagement** expresa el concepto de frase mediante el puerto:

- `QuoteProvider::fetchRandom(): MotivationalQuote`

La implementación concreta (`DummyJsonQuoteProvider`, capa de infraestructura):

- Realiza la llamada HTTP (Laravel HTTP client), aplica **timeout**, valida el **contrato JSON** del proveedor y traduce a `MotivationalQuote`.
- Elevación controlada de fallos: `QuoteProviderUnavailable` (red, HTTP de error), `QuotePayloadMalformed` (JSON inválido o campos esperados ausentes).

El dominio **no** menciona HTTP, URLs ni estructura JSON del tercero. La URL por defecto es DummyJSON; puede sobreescribirse con `DUMMYJSON_QUOTE_URL` para pruebas o entornos controlados (sin ensuciar el dominio).

---

## 6. CQRS: modelos de escritura y lectura

| Rol | Tablas | Uso |
|-----|--------|-----|
| **Escritura (write model)** | `checkins`, `daily_quotes` | Consistencia transaccional y auditoría de negocio. |
| **Lectura (read model)** | `dashboard_checkin_view` | Tabla **desnormalizada** preparada para consultas del dashboard sin JOINs pesados entre log de accesos y frases en tiempo de la petición GET. |

La actualización de `dashboard_checkin_view` ocurre en el **flujo asíncrono** del consumidor (proyección tras éxito del proveedor y reglas de aplicación), no en el endpoint de lectura.

**Endpoint de consulta:** `GET /api/dashboard/{userId}/check-ins` delega únicamente en el query handler que lee el repositorio del read model (`dashboard_checkin_view`).

---

## 7. Resiliencia y consistencia eventual

| Escenario | Comportamiento esperado |
|-----------|-------------------------|
| API externa lenta, 500 o contrato roto | El check-in **ya está** guardado. El fallo se manifiesta en el consumidor; se aplican reintentos vía cola de retry y, tras agotar intentos, el mensaje puede dirigirse a **DLQ** para inspección. |
| Broker o consumidor detenidos horas | Los mensajes permanecen en `outbox_messages` (sin `published_at`) y/o en colas RabbitMQ hasta recuperación; no se anula el check-in. |
| Error inesperado en el consumidor | Estrategia de `nack` con requeue según el tipo de error documentado en el código del consumidor, para no perder mensajes por fallos transitorios. |

La **alta disponibilidad del torno** (check-in) está desacoplada de la disponibilidad del proveedor de frases y del procesamiento asíncrono posterior.

---

## 8. Automatización operativa

- **Publisher del outbox:** comando `outbox:publish`, agendado desde `routes/console.php` (scheduler de Laravel). En desarrollo se usa `php artisan schedule:work`; en producción, el mecanismo habitual es `schedule:run` vía cron cada minuto (Laravel agrupa las tareas definidas; la frecuencia `everySecond` es adecuada para demos locales y debe ajustarse en producción según carga y política de reintentos).
- **Consumidor:** `php artisan engagement:consume-checkins` — proceso de larga duración que debe ejecutarse como worker (systemd, supervisord, contenedor dedicado, etc.) según el entorno.

El **controlador HTTP de check-in no** invoca Artisan ni el publisher: solo orquesta el command y la respuesta.

---

## 9. Infraestructura y despliegue

El archivo `docker-compose.yml` levanta el ecosistema de referencia de la prueba técnica:

- **app:** PHP-FPM + Laravel (imagen definida en `Dockerfile`).
- **nginx:** terminación HTTP hacia `public/`.
- **mysql:** base de datos relacional.
- **rabbitmq:** broker con **management UI** (puerto típico `15672` mapeado al host).

Es posible ejecutar la aplicación también en el host (p. ej. `php artisan serve`) usando SQLite u otra BD, siempre que RabbitMQ sea alcanzable (`RABBITMQ_HOST=127.0.0.1` cuando el broker corre en Docker con puertos publicados).

---

## 10. Por qué monolito modular y no microservicios

- **Un solo repositorio y despliegue** simplifica transacciones locales, migraciones y trazabilidad.
- Los **límites de dominio** se respetan por namespaces, dependencias y contratos (eventos + broker), no por procesos HTTP separados.
- Un evolución futura hacia servicios separados sería un **cambio de despliegue**, no obligatorio para cumplir el enunciado, que prohíbe explícitamente microservicios en esta entrega.

---

## 11. Pruebas y calidad

- Pruebas enfocadas en el **adaptador HTTP** del proveedor: `tests/Unit/Engagement/Infrastructure/External/DummyJsonQuoteProviderTest.php`, usando `Http::fake()` para respuesta válida, fallo de red, HTTP 500 y JSON inválido — demuestra **DIP** y manejo de fallos sin acoplar el dominio a HTTP.

Para ejecutar tests: `php artisan test` (o dentro del contenedor `app` si se usa Docker).

---

## 12. Referencias rápidas en código (orientación)

| Concepto | Ubicación aproximada |
|----------|----------------------|
| Command check-in | `src/AccessControl/Application/` |
| Outbox writer | `src/AccessControl/Infrastructure/Persistence/PdoOutboxRepository.php` |
| Publisher | `src/AccessControl/Application/Outbox/PublishOutboxMessagesHandler.php` |
| Bus RabbitMQ | `src/Shared/Infrastructure/Messaging/RabbitMqEventBus.php` |
| Consumidor | `src/Engagement/Interfaces/Console/ConsumeCheckInRegisteredCommand.php` |
| ACL frases | `src/Engagement/Domain/Port/QuoteProvider.php`, `src/Engagement/Infrastructure/External/DummyJsonQuoteProvider.php` |
| Read model dashboard | `src/Engagement/Infrastructure/Persistence/PdoDashboardCheckInViewRepository.php` |
| Config mensajería | `config/messaging.php`, variables `RABBITMQ_*`, `DUMMYJSON_QUOTE_URL` |

