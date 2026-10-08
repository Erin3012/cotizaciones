# Códigos y borradores en webmail

## Actualización

1. Respaldar la base `qlccl_cotizaciones` y comprobar que el respaldo es recuperable.
2. Importar `migration_numbering_mail.sql` antes de utilizar el nuevo formulario. La migración es reejecutable: nunca disminuye los contadores. El año del código es el año de guardado en Chile, no la fecha de emisión. Los folios históricos no cambian.
3. Actualizar el código con `git pull --ff-only origin main`, preservando `.env`, archivos y cambios locales. Si `composer.lock` del servidor es anterior y no está versionado, moverlo a una carpeta privada de respaldo antes de actualizar; no borrarlo.
4. Ejecutar `php /home/qlccl/composer.phar install --no-dev --optimize-autoloader`. No ejecutar `composer update` en producción. Composer ya no carga automáticamente `bootstrap.php`, evitando su redeclaración durante PDF.
5. Comprobar `dom`, `mbstring`, `gd`, `openssl` e `imap` tanto en CLI como en el PHP del subdominio. `storage/pdf` se crea fuera de `public`, con permisos restringidos. No configurar el document root por encima de `public`.

## Configuración privada del buzón

Agregar al `.env` existente (no reemplazar el archivo ni alterar sus credenciales de base de datos):

```dotenv
QUOTE_MAIL_ENABLED=0
QUOTE_IMAP_HOST=mail.metalrubber.cl
QUOTE_IMAP_PORT=993
QUOTE_IMAP_DRAFTS=INBOX.Drafts
QUOTE_IMAP_SENT=INBOX.Sent
QUOTE_IMAP_PASSWORD="configurar-privadamente"
```

El host y las carpetas son valores iniciales: confirmar los datos IMAP en cPanel → Cuentas de correo → Connect Devices / Configurar cliente de correo y la carpeta especial de Borradores y Enviados de Roundcube. Se exige TLS con certificado válido; no desactivar su verificación. El remitente/usuario es siempre `carlos.pedreros@metalrubber.cl`. No se necesitan credenciales SMTP porque el sistema no envía correos.

Configurar la contraseña por SSH o el editor privado de cPanel. No pegarla en el chat, argumentos de shell, repositorio ni registros. Conservar `chmod 600 .env` y verificar el acceso del PHP web. Activar `QUOTE_MAIL_ENABLED=1` solamente cuando estén verificadas las extensiones, las carpetas y la conexión al buzón. No crear borradores ni enviar mensajes de prueba a destinatarios reales sin autorización.

## Uso

Abrir una cotización guardada → **Preparar correo en webmail** → revisar destinatario, asunto y mensaje → **Preparar borrador con PDF**. Abrir `https://metalrubber.cl:2096`, iniciar sesión en la cuenta compartida y revisar el mensaje en Borradores. El envío final se realiza allí.

Los dobles clics y reintentos del mismo documento/mensaje reutilizan la operación registrada. Para un contenido nuevo, confirmar la creación de otro borrador; el anterior se conserva. Si no puede determinarse el resultado de un APPEND, se bloquea otro intento ciego: revisar Borradores/Enviados y solicitar revisión técnica del `message_id` registrado. No borrar manualmente el registro para forzar un reintento.

Un borrador no es evidencia de envío: no se cambian estados de cotización ni se muestra «correo enviado». Descargar PDF e imprimir siguen disponibles sin configuración de correo.

## Pruebas

- `php tests/numbering.php` (PDO SQLite).
- `php tests/document_mail.php --render` genera documentos sintéticos en `tmp/pdfs`, sin insertar clientes ni enviar mensajes.
- `php tests/draft_retry.php` usa un buzón simulado para comprobar desconexiones e idempotencia.
- Validar concurrencia real en MySQL y PDF en PHP 8.1 antes de habilitar la función.
- `php tests/mysql_concurrency.php --run` reserva y limpia exclusivamente un contador de prueba del año 9998; no crea clientes ni cotizaciones.
