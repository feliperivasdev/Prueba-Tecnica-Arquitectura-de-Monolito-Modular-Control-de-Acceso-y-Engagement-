
<div align="center">

# Autor

[@feliperivasdev](https://github.com/feliperivasdev)

# Prueba Técnica Arquitectura de Monolito Modular Control de Acceso y Engagement

**Monolito modular** para check-in síncrono, fidelización asíncrona y dashboard orientado a lectura — Laravel, DDD, CQRS, Transactional Outbox y RabbitMQ.

[![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white)](https://laravel.com)
[![RabbitMQ](https://img.shields.io/badge/RabbitMQ-broker-FF6600?logo=rabbitmq&logoColor=white)](https://www.rabbitmq.com/)
[![MySQL](https://img.shields.io/badge/MySQL-8.x-4479A1?logo=mysql&logoColor=white)](https://www.mysql.com/)
[![License](https://img.shields.io/badge/License-MIT-blue.svg)](composer.json)
[![Tests](https://img.shields.io/badge/tests-PHPUnit-6C4FB4?logo=php&logoColor=white)](phpunit.xml)

[Documentación de arquitectura](ARCHITECTURE.md)

</div>

---

## Tabla de contenidos

- [Visión general](#visión-general)
- [Stack y módulos](#stack-y-módulos)
- [Requisitos previos](#requisitos-previos)
- [Puesta en marcha](#puesta-en-marcha)
- [API HTTP](#api-http)
- [Configuración](#configuración)
- [Comandos operativos](#comandos-operativos)
- [Pruebas](#pruebas)
- [Solución de problemas](#solución-de-problemas)
- [Licencia](#licencia)

---

## Visión general

Este proyecto implementa la **ruta crítica** descrita en la prueba técnica: el **check-in** en torno es **síncrono y transaccional**; la **frase motivacional** se obtiene de una API externa (p. ej. DummyJSON) **después**, vía mensajería, sin bloquear ni revertir el acceso. El **dashboard** lee únicamente una **tabla de proyección** (read model), sin ensamblar respuestas con JOINs pesados en tiempo de consulta.

El diseño detallado — bounded contexts, outbox, ACL, CQRS, resiliencia y despliegue — está en [**`ARCHITECTURE.md`**](ARCHITECTURE.md).

---

## Stack y módulos

| Capa / herramienta | Uso en el proyecto |
|--------------------|-------------------|
| **Laravel 13** | Framework HTTP, scheduler, HTTP client, migraciones |
| **PHP 8.3** | Runtime |
| **SQLite / MySQL** | Persistencia (según `.env`) |
| **RabbitMQ** | Broker entre outbox y consumidor de Engagement |
| **Docker Compose** | App (PHP-FPM), Nginx, MySQL y RabbitMQ de referencia |
| **`src/AccessControl`** | Dominio de acceso físico y outbox |
| **`src/Engagement`** | Dominio de frases, proyección y consumidor |
| **`src/Shared`** | Contratos transversales (bus, reloj, transacciones, etc.) |

---

## Requisitos previos

- **PHP** 8.3 o superior y [**Composer**](https://getcomposer.org/)
- Extensión **PDO** acorde a tu BD (`pdo_sqlite` y/o `pdo_mysql`)
- **Docker** y **Compose** (`docker compose` o `docker-compose`) si usas el stack containerizado o solo el broker

---

## Puesta en marcha

### Opción 1 — Stack completo (recomendado para revisión / paridad con la prueba)

```bash
cp .env.example .env
docker compose up -d --build
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
```

| Servicio | URL / notas |
|----------|-------------|
| API (Nginx) | [http://localhost:8080](http://localhost:8080) |
| RabbitMQ Management | [http://localhost:15672](http://localhost:15672) — usuario `guest` / contraseña `guest` |

En **procesos separados** (misma app en contenedor):

```bash
docker compose exec app php artisan schedule:work
docker compose exec app php artisan engagement:consume-checkins
```

> En sistemas con el plugin antiguo: sustituye `docker compose` por `docker-compose`.

**Primera vez / equipo lento:** MySQL y RabbitMQ pueden tardar más en pasar el *healthcheck*. Si `app` no arranca, espera ~1 minuto y ejecuta de nuevo `docker compose up -d` (o `docker-compose up -d`). Revisa con `docker compose ps`.

**Puertos ocupados (otra máquina):** Docker Compose lee un archivo `.env` en la **raíz del repo** para sustituir variables. Puedes añadir al final de tu `.env` (el mismo que usa Laravel) las claves de [`.env.docker-ports.example`](.env.docker-ports.example), por ejemplo `HTTP_PORT=8081`, y volver a levantar los servicios. La app **dentro** de Docker sigue usando `mysql:3306` y `rabbitmq:5672`; solo cambian los puertos expuestos al **host**.

### Opción 2 — Aplicación en el host + RabbitMQ en contenedor

Adecuado para desarrollo diario (p. ej. `php artisan serve` y SQLite).

1. Arrancar solo RabbitMQ:

   ```bash
   docker compose up -d rabbitmq
   ```

2. Configurar en `.env` el acceso al broker expuesto en el host, por ejemplo:

   ```env
   RABBITMQ_HOST=127.0.0.1
   RABBITMQ_PORT=5672
   ```

3. Instalar y migrar localmente:

   ```bash
   composer install
   php artisan key:generate
   php artisan migrate
   php artisan config:clear
   ```

4. Ejecutar en terminales distintas: `php artisan serve`, `php artisan schedule:work`, `php artisan engagement:consume-checkins`.

---

## API HTTP

| Método | Ruta | Descripción |
|--------|------|-------------|
| `POST` | `/api/check-ins` | Cuerpo JSON: `user_id`, `credential_id`, `gym_id` (UUID). Respuesta **201** si el check-in y el outbox se confirman en la misma transacción. |
| `GET` | `/api/dashboard/{userId}/check-ins` | Lista historial y frases desde **`dashboard_checkin_view`** (read model únicamente). |

Ejemplo de check-in:

```bash
curl -s -X POST http://localhost:8080/api/check-ins \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"user_id":"xxxxxxxx-xxxx-4xxx-xxxx-xxxxxxxxxxxx","credential_id":"xxxxxxxx-xxxx-4xxx-xxxx-xxxxxxxxxxxx","gym_id":"xxxxxxxx-xxxx-4xxx-xxxx-xxxxxxxxxxxx"}'
```

(Ajusta el host si usas `php artisan serve`, p. ej. `http://localhost:8000`.)

---

## Configuración

| Grupo | Variables | Descripción |
|-------|-----------|-------------|
| Base de datos | `DB_CONNECTION`, `DB_DATABASE`, … | SQLite o MySQL según entorno |
| Proveedor de frases | `DUMMYJSON_QUOTE_URL` | URL del JSON de citas (por defecto DummyJSON; ver `config/services.php`) |
| Mensajería | `RABBITMQ_HOST`, `RABBITMQ_PORT`, `RABBITMQ_*` | Exchange, colas, DLQ y reintentos (`config/messaging.php`) |

Tras modificar `.env`:

```bash
php artisan config:clear
```

**Prueba de resiliencia:** apunta `DUMMYJSON_QUOTE_URL` a un origen inalcanizable (p. ej. `http://127.0.0.1:9`). El **POST** de check-in debe seguir respondiendo **201**; el enriquecimiento con frase queda sujeto a reintentos / DLQ en el consumidor.

---

## Comandos operativos

| Comando | Propósito |
|---------|-----------|
| `php artisan schedule:work` | Ejecuta el scheduler (incluye publicación periódica del outbox) |
| `php artisan engagement:consume-checkins` | Consumidor RabbitMQ → Engagement |
| `php artisan outbox:publish --limit=100` | Publicación manual del outbox (diagnóstico; no forma parte del flujo HTTP) |

---

## Pruebas

```bash
php artisan test
```

Cobertura enfocada en el **adaptador HTTP** del proveedor externa (inversión de dependencias y manejo de errores):

```bash
php artisan test tests/Unit/Engagement/Infrastructure/External/DummyJsonQuoteProviderTest.php
```

---

## Solución de problemas

| Síntoma | Qué revisar |
|---------|-------------|
| No abre `http://localhost:15672` | Servicio RabbitMQ levantado: `docker compose ps` o `docker-compose ps` |
| `published_at` en outbox siempre `NULL` | `schedule:work` activo, conectividad AMQP, variables `RABBITMQ_*` y `config:clear` |
| Dashboard sin frases nuevas | Consumidor en ejecución; colas en RabbitMQ; logs de `engagement:consume-checkins` |

---



## Licencia

MIT — coherente con la declaración en [`composer.json`](composer.json).
