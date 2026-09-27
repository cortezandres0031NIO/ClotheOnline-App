# Mi armario

Primera versión funcional y privada para prendas, outfits, compras e historial. Interfaz en español. PHP sin servicios externos ni cuotas añadidas. No está publicada en internet todavía.

## Probarla ahora en tu Mac

1. Abre la dirección **http://127.0.0.1:8765** mientras la aplicación esté encendida.
2. La primera vez, elige una contraseña de al menos 12 caracteres y repítela. Guárdala en tu gestor de contraseñas. No tienes que registrarte en ningún servicio.
3. En **Mi ropa**, pulsa **Añadir prenda**. Selecciona una foto y completa nombre, categoría y color.
4. En **Outfits**, pulsa **Crear outfit** y marca las prendas que quieres combinar.
5. Pulsa **Lo llevo hoy** en el outfit. Revisa las prendas y pulsa **Guardar uso**. Solo este último paso registra que lo llevaste.
6. En **Historial**, elige el día y pulsa **Corregir** si necesitas cambiar fecha, prendas o notas.
7. En **Estadísticas**, verás los cambios y los gastos separados por moneda.
8. En **Ajustes**, descarga un respaldo cuando quieras. Incluye las fotos.

Para volver a encenderla otro día, abre la carpeta `mi-armario` y haz doble clic en **Abrir-armario.command**. Mantén abierta la ventana de Terminal que aparezca. Para apagarla, pulsa **Control + C** en esa ventana. Si ya está encendida, basta con abrir su dirección; no necesitas encender una segunda copia.

El paquete local incluye PHP para esta Mac con Apple Silicon. No modifica la instalación de macOS. Si macOS bloquea la apertura del archivo, pide ayuda a tu tío para revisar el aviso y ejecutar el lanzador; no desactives las protecciones del sistema. En un Mac Intel hace falta instalar una versión compatible de PHP.

## Dónde están tus cosas

En esta prueba, los datos y las fotos se guardan en la carpeta **private-local**, dentro de `mi-armario`. No borres esa carpeta ni cambies su ubicación sin hacer un respaldo. No se guardan únicamente en el navegador: cerrar Safari o borrar su historial no elimina tus prendas.

Esta dirección solo funciona en el Mac donde se está ejecutando. Para consultar la misma información en el iPhone, falta instalar la aplicación en el alojamiento. Después podrás trasladar tus datos con **Descargar respaldo** en el Mac y **Restaurar respaldo** en la versión publicada. La contraseña se configura de nuevo en el servidor; los respaldos no la incluyen.

## Qué hace esta versión

- Fotos desde cámara o galería, datos de prendas, búsqueda, filtro por categoría/estado y búsqueda por color o notas.
- Edición y archivo de prendas con conservación del historial.
- Outfits reutilizables y foto opcional del conjunto.
- Usos con fecha, notas y selección de prendas independiente del outfit original.
- Corrección y eliminación de usos, con recálculo de estadísticas.
- Compras por mes, monedas separadas, prendas más y menos usadas, outfits repetidos y evolución mensual.
- Prendas nunca usadas, días desde el último uso y costo por uso histórico.
- Cuenta única, cierre de sesión, fotos privadas y respaldos ZIP con vista previa antes de restaurar.
- Iconos y configuración PWA, preparados para añadir a inicio desde Safari con HTTPS.

## Límites que conviene conocer

- La versión publicada requiere internet. No guarda cambios ni sincroniza datos sin conexión. Si se corta la conexión mientras estás guardando, lee el mensaje antes de repetir la operación.
- Localmente puedes usarla sin internet mientras el servidor del Mac siga encendido. Eso no equivale a disponer de modo offline en el iPhone.
- Las fotos se reducen a un máximo de 1600 píxeles por lado y se convierten a JPEG. Se quitan metadatos de ubicación. No se conservan los originales de máxima resolución.
- Se aceptan JPEG, PNG y WebP. HEIC depende de que el navegador pueda convertirlo; si no puede, la aplicación lo indica. En el iPhone puedes elegir **Ajustes → Cámara → Formatos → Más compatible**, o exportar la foto como JPEG. La cámara y los formatos necesitan una comprobación en tu iPhone real.
- El precio requiere moneda. Hay una lista inicial de 10 monedas; no existe conversión automática. Costo por uso usa el total histórico de usos aunque filtres un periodo.
- No se admiten fechas futuras: esta aplicación registra uso y compras reales, no planifica outfits futuros.
- Restaurar sustituye tus registros; no mezcla dos armarios. Se conserva una copia de los datos anteriores a la última restauración, descargable desde Ajustes. Guarda también un respaldo tuyo antes de restaurar.
- Límite de restauración: ZIP de 128 MB, hasta 256 MB descomprimido. El alojamiento puede tener un límite menor. Para armarios que superen esto hará falta ampliar el mecanismo de restauración con tu tío antes de depender de él.
- Solo hay una cuenta; no existe recuperación por correo. Tu tío puede ejecutar la herramienta privada para restablecer tu contraseña.
- Si editas desde dos dispositivos a la vez, la aplicación rechaza el guardado desactualizado para no sobrescribir el otro. Cierra el formulario, actualiza y vuelve a introducir ese cambio.

## Publicación

Entrega a tu tío el archivo **mi-armario-para-alojamiento.zip** y la guía **INSTALACION-CPANEL.md**. El ZIP de instalación no contiene datos personales, contraseña ni fotos de prueba. La publicación requiere comprobar la versión de PHP, extensiones y límites del plan; no se ha accedido a su cuenta ni cambiado el dominio.

Las pruebas realizadas y las pendientes están en **PRUEBAS.md**.
