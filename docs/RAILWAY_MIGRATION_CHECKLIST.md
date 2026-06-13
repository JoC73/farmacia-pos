# Railway Migration Checklist - Farmacia POS

## Objetivo

Migrar Farmacia POS a Railway para uso real con clientes, manteniendo app Laravel y PostgreSQL en el mismo proyecto.

## Estructura recomendada

Un proyecto por cliente:

```text
Railway Workspace
└── farmacia-pos-cliente
    ├── App Service: Laravel/Docker
    └── Database Service: PostgreSQL
```

No mezclar clientes distintos dentro de la misma base de datos.

## Servicios necesarios

1. PostgreSQL.
2. App Service conectado al repositorio GitHub `JoC73/farmacia-pos`.

Railway detectara el `Dockerfile`. El archivo `railway.json` fija Dockerfile, healthcheck `/health` y politica de reinicio.

## Variables obligatorias del App Service

```env
APP_NAME="Farmacia POS"
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:TU_APP_KEY
APP_URL=https://TU-DOMINIO.up.railway.app
ASSET_URL=https://TU-DOMINIO.up.railway.app

DB_CONNECTION=pgsql
DB_URL=${{Postgres.DATABASE_URL}}
DATABASE_URL=${{Postgres.DATABASE_URL}}

LOG_CHANNEL=stderr
LOG_LEVEL=info

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
CACHE_STORE=database
QUEUE_CONNECTION=database

MAIL_MAILER=log
REGISTRATION_ENABLED=false
```

## Variables recomendadas para Super Usuario

Configurar solo si se desea que el deploy cree o actualice automaticamente el Super Usuario:

```env
SUPER_USER_EMAIL=tu-correo@dominio.com
SUPER_USER_PASSWORD=una-clave-segura
SUPER_USER_NAME=Super Usuario
```

## Pasos de despliegue

1. Crear proyecto en Railway.
2. Agregar servicio PostgreSQL.
3. Agregar servicio desde GitHub.
4. Confirmar que Railway use Dockerfile.
5. Agregar variables del App Service.
6. Generar dominio publico en Networking.
7. Actualizar `APP_URL` y `ASSET_URL` con el dominio final.
8. Deploy.
9. Revisar logs de migraciones y seeders.
10. Probar `/health`.
11. Probar login.
12. Probar ventas, caja, inventario, compras y modulos premium.

## Pruebas funcionales minimas antes de entregar a cliente

1. Iniciar sesion como Super Usuario.
2. Crear usuario administrador.
3. Crear/validar sucursales permitidas.
4. Crear producto.
5. Abrir caja.
6. Realizar venta.
7. Confirmar descuento de stock.
8. Registrar egreso de caja si esta activado.
9. Cerrar caja.
10. Confirmar total sistema.
11. Intentar anular venta con caja cerrada y verificar bloqueo.
12. Probar inventario fisico y carga inicial si el modulo premium esta activo.

## Riesgos controlados

1. Registro publico deshabilitado por defecto con `REGISTRATION_ENABLED=false`.
2. Rutas operativas protegidas por autenticacion.
3. Logs enviados a `stderr`.
4. Sesiones, cache y cola usando base de datos.
5. Healthcheck disponible en `/health`.

## Pendientes recomendados para etapa posterior

1. Configurar backups automaticos o manuales de PostgreSQL.
2. Crear ambiente staging por cliente antes de produccion.
3. Implementar pruebas automatizadas con PostgreSQL o instalar `pdo_sqlite` en CI.
4. Documentar proceso de restauracion de backup.
5. Definir politica de soporte y horarios de mantenimiento.
