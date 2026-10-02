# Actualización: contactos por empresa

1. Respaldar `qlccl_cotizaciones` desde phpMyAdmin antes de actualizar.
2. Verificar que no existan empresas con el mismo RUT normalizado:

```sql
SELECT REPLACE(REPLACE(REPLACE(UPPER(rut),'.',''),'-',''),' ','') AS rut_normalizado,
       COUNT(*) AS empresas
FROM clients GROUP BY rut_normalizado HAVING COUNT(*) > 1;
```

Si hay resultados, resolver esos registros antes de importar. Esta actualización no fusiona ni elimina empresas automáticamente.

3. En una ventana de mantenimiento, actualizar el repositorio con `git pull --ff-only origin main` e importar **una sola vez** `database/migration_client_contacts.sql` sobre `qlccl_cotizaciones`. La migración contiene DDL que MySQL no revierte en una transacción; si falla, revisar qué instrucciones ya se ejecutaron antes de repetir.
4. Abrir Clientes → Ver ficha y contactos. Los contactos heredados tendrán área pendiente: completar el área antes de usarlos en nuevas cotizaciones.
5. Crear una cotización con un contacto de Ventas y otra con uno de Recursos Humanos de la misma empresa. Confirmar que cada una conserva su destinatario. Revisar persona, área, correo y teléfono en el detalle y la impresión.

Las cotizaciones antiguas conservan el destinatario guardado. Si no tenían copia del contacto, la migración congela los datos actuales de la empresa; no puede reconstruir valores que se sobrescribieron antes de esta actualización. La opción «Conservar destinatario original» permite editarlas sin cambiar el contacto. Para cambiarlo, seleccionar otro contacto o agregar uno nuevo.

La ficha administra razón social y dirección. El RUT identifica la empresa y tiene una restricción de unicidad normalizada. Desactivar un contacto no elimina cotizaciones ni modifica sus datos históricos. Los nuevos contactos no reciben cuentas de acceso.
