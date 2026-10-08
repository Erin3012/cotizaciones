# Redacción directa en webmail

## Uso

Cotización guardada → **Redactar en webmail**.
La opción ahora se llama **Correo: webmail / Outlook** y abre un diálogo flotante sin salir de la cotización. Al cerrarlo y reabrirlo se mantienen los campos mientras no se recargue la página. Escape o Cerrar regresan a la cotización; el navegador devuelve el foco al botón.

**Abrir en Outlook** usa `mailto:` con destinatario, asunto y texto, sin adjunto automático. Outlook debe ser la aplicación predeterminada de correo y tener configurada la cuenta correcta; si no lo es, Windows abrirá otra aplicación. Verificar el remitente antes de enviar. [Configuración oficial de Microsoft](https://support.microsoft.com/en-us/outlook/make-outlook-the-default-program-for-email-contacts-and-calendar).

Webmail intenta abrir una ventana independiente, pero el navegador puede convertirla en pestaña o bloquearla. Si la bloquea, se ofrece un enlace alternativo. No se incrusta Roundcube ni Outlook en un iframe: sólo el formulario local de preparación se muestra en el diálogo.

1. Descargar el PDF desde esa pantalla.
2. Revisar destinatario, asunto y mensaje.
3. Iniciar sesión en webmail con `carlos.pedreros@metalrubber.cl` si hace falta.
4. Pulsar **Abrir redacción en webmail**: abre otra pestaña de Roundcube con los campos preparados.
5. Adjuntar el PDF descargado, revisar y enviar manualmente.

El módulo no crea borradores, no envía correos y no registra un envío supuesto. Roundcube puede autoguardar mientras se redacta, según su configuración propia. No se modifican las preferencias del buzón.

El enlace permanente usa `https://metalrubber.cl:2096/3rdparty/roundcube/`, nunca enlaces temporales `cpsess`. La pantalla conserva los campos editables y ofrece copiar cada uno. Si el inicio de sesión de cPanel no conserva los parámetros, volver a abrir la redacción después de iniciar sesión, o usar los botones de copia. Los mensajes cuyo enlace supera 2.000 bytes requieren copia manual para evitar rechazos del proxy. El contenido enviado por enlace puede quedar en el historial del navegador/webmail: no incluir contraseñas ni secretos en el mensaje.

Roundcube no ofrece adjuntos automáticos a través de su enlace estándar de redacción. No se afirma que el PDF está adjunto: el administrador lo agrega manualmente. Referencia: [código oficial de redacción de Roundcube](https://github.com/roundcube/roundcubemail/blob/master/program/actions/mail/compose.php).

## Despliegue

Para esta actualización basta `git pull --ff-only origin main`. No requiere nuevas tablas, extensiones IMAP, credenciales ni cambios al `.env`. Se conservan los registros históricos de borradores, pero su creación nativa queda deshabilitada aunque `QUOTE_MAIL_ENABLED=1` esté configurado. No se borran mensajes existentes.

Si se instala desde una versión anterior a los códigos atómicos: respaldar primero `qlccl_cotizaciones`, importar `migration_numbering_mail.sql` y ejecutar `php /home/qlccl/composer.phar install --no-dev --optimize-autoloader`. Los contadores nunca se disminuyen y los folios históricos se conservan. Mantener `.env` privado (`chmod 600`), fuera de Git y de `public`.

## Verificación

- `php tests/webmail_compose.php`: parámetros, acentos, HTML seguro, URL larga, CSRF/autenticación y bloqueo del buzón nativo. No abre conexiones ni crea mensajes.
- Mantener pruebas de cálculos, clientes, contactos, eliminación, numeración y PDF. Las pruebas heredadas de borradores usan un buzón simulado, no el real.
- Probar la pantalla con la sesión real de Roundcube; el comportamiento después del login depende de cPanel. No enviar correos a clientes reales para probar.
