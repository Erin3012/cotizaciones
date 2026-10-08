# Descargar correo con cotización PDF

En el detalle, **Correo: webmail / Outlook** descarga directamente `{folio}.eml`, sin diálogo ni pantalla de preparación. Usa destinatario del contacto, asunto y texto predeterminados de la cotización guardada, y adjunta su PDF. No se cierra la cotización. Si no hay correo válido en sus datos guardados, corregir el contacto/destinatario de esa cotización antes de descargar.

Abrir el archivo descargado en Outlook y verificar remitente, texto y PDF antes de enviar. Algunas versiones del nuevo Outlook pueden abrirlo sólo para lectura o perder el adjunto al editar: se mantiene Descargar PDF como alternativa. No se garantiza la apertura automática de Outlook desde el navegador.

El controlador requiere autenticación, POST y CSRF. El mensaje y el PDF se generan en memoria: no se crea un archivo de correo en el servidor, ni se utiliza IMAP/SMTP, ni se crean borradores en webmail. No se registra un envío supuesto. Los registros históricos de borradores se conservan. Sus credenciales antiguas no se utilizan ni se modifican.

## Actualización

`git pull --ff-only origin main`. Esta simplificación no necesita migraciones, cambios de `.env` ni instalación de nuevas dependencias. Se retiran la pantalla de correo, la ventana flotante y sus scripts/estilos; las cotizaciones, clientes y PDFs no se borran.

Para actualizar desde una versión anterior a los códigos atómicos: respaldar `qlccl_cotizaciones`, importar `migration_numbering_mail.sql` y ejecutar `php /home/qlccl/composer.phar install --no-dev --optimize-autoloader`. Mantener `.env` privado y fuera de `public`/Git.

## Pruebas

- `php tests/document_mail.php`: PDF íntegro incluido en MIME, sin enviar ni crear mensajes.
- `php tests/webmail_compose.php`: validaciones y controlador autenticado, sin formularios intermedios.
- `node tests/eml_ui.cjs`: descarga directa con nombre de archivo, errores recuperables y ausencia de ventanas intermedias, con navegador simulado.
- Mantener pruebas de cálculos, clientes, contactos, eliminación y numeración.
