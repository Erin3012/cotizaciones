# Cotizaciones Metalrubber

Sistema independiente de solicitudes y cotizaciones para Metalrubber Ltda.

## Requisitos

- PHP 8.1+ con PDO MySQL, Fileinfo, Mbstring y, para PDF, Dompdf.
- MySQL/MariaDB.
- HTTPS en producción.

## Instalación en cPanel

1. Crear la base de datos `qlccl_cotizaciones` y un usuario con privilegios.
2. Apuntar `cotizaciones.metalrubber.cl` a la carpeta `public/`.
3. Copiar `.env.example` como `.env` y completar las credenciales reales. Nunca subir `.env` a GitHub.
4. Importar `database/schema.sql` desde phpMyAdmin.
5. Ejecutar `composer install --no-dev --optimize-autoloader` o subir `vendor/` generado localmente.
6. Confirmar permisos de escritura para `public/uploads/` y conservar su `.htaccess`.
7. Abrir `/index.php?page=setup` solo si la tabla `users` está vacía y crear el primer administrador.
8. Activar HTTPS antes de utilizar el formulario público.

## Flujo

- El formulario público crea clientes, solicitudes y adjuntos.
- El panel requiere autenticación y permite revisar solicitudes, administrar clientes, crear cotizaciones y descargar PDF.
- Las cotizaciones usan IVA fijo de 19%, folio `COT-AAAA-####` y fecha de vencimiento obligatoria.
- No se envían correos automáticamente en esta versión.

## Estructura

- `app/`: configuración, seguridad, autenticación, consultas y PDF.
- `database/schema.sql`: esquema exclusivo de esta aplicación.
- `public/index.php`: entrada pública y administrativa.
- `public/assets/style.css`: sistema visual responsive.
- `public/uploads/`: adjuntos protegidos.
- `tests/`: pruebas de cálculo y validación.

