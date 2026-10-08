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

- Para actualizar folios atómicos, PDF y redacción directa en webmail, seguir [database/WEBMAIL.md](database/WEBMAIL.md). El módulo no crea borradores ni envía mensajes; el PDF se adjunta manualmente.
- Para eliminar una cotización, abrir su detalle → **Eliminar** y confirmar. Para eliminar un cliente, abrir su ficha → **Eliminar cliente**. El servidor exige eliminar previamente todas sus cotizaciones, una por una; después elimina empresa, contactos y solicitudes internas vacías, conservando la auditoría. Si quedan solicitudes con adjuntos, se bloquea el borrado para proteger los respaldos. La eliminación definitiva no se puede deshacer: respaldar la base antes de usarla.
- Desde **Clientes → Nuevo cliente** se registra una empresa con RUT, nombre o razón social y dirección, sin crear una cotización. Al guardar se abre su ficha para agregar contactos por persona y área. Los RUT duplicados se rechazan sin sobrescribir datos. Esta opción no necesita una migración adicional a la de múltiples contactos.
- El panel administrativo requiere autenticación y permite crear directamente cotizaciones para los clientes.
- Cada cotización guarda el cliente, la solicitud interna, sus ítems, condiciones y PDF consultable.
- Las cotizaciones usan IVA fijo de 19%, folio `COT-AAAA-####` y fecha de vencimiento obligatoria.
- No se envían correos automáticamente en esta versión.

## Estructura

Para actualizar empresas con múltiples contactos, seguir [database/CONTACTOS.md](database/CONTACTOS.md). La actualización requiere importar la migración sobre una base previamente respaldada. Las cotizaciones conservan una copia del destinatario y los contactos se administran en la ficha de clientes.

- `app/`: configuración, seguridad, autenticación, consultas y PDF.
- `database/schema.sql`: esquema exclusivo de esta aplicación.
- `public/index.php`: entrada pública y administrativa.
- `public/assets/style.css`: sistema visual responsive.
- `public/uploads/`: adjuntos protegidos.
- `tests/`: pruebas de cálculo y validación.
