# Actualización del panel y los formularios

## Publicar en cPanel

1. En Git Version Control, pulsa **Update from Remote** y después **Deploy HEAD Commit**.
2. Recarga la página del armario; cierra formularios abiertos antes de recargar.

**No hay migración SQL ni comando adicional.** Los nuevos campos opcionales viven en el JSON de `armario_state.payload`, dentro de la base de datos existente. No se cambia ninguna tabla, columna ni registro al abrir el panel. Se guardan los campos al editar una prenda u outfit. La lista de despliegue existente ya incluye todos los archivos de aplicación modificados.

Se mantienen `/home/qmascore/mi-armario`, `public/`, PHP 8.4, el inicio de sesión, las fotos privadas y las reglas HTTPS y CloudLinux. No se modifica ni despliega `app/config.php`.

## Datos nuevos y compatibilidad

- Prendas: `size` (cadena, vacía si falta), `pants_type` (texto seleccionable o personalizado, vacío si falta), `pants_model` (modelo/referencia), `waist` (cintura) y `length` (largo) y `tags` (lista vacía si falta).
- Outfits: `tags` (lista vacía si falta), con varias etiquetas reutilizables y etiquetas personalizadas.
- Las sugerencias son opciones del formulario: nunca crean etiquetas ni asignaciones por sí solas.
- Las tallas antiguas fuera del catálogo aparecen como una opción adicional y se conservan. El servidor admite estas tallas para preservar los respaldos.
- Para Pantalones y Jeans, cintura y largo se introducen por separado y se muestran como `30x36` cuando ambos están guardados. No se extraen medidas de la talla antigua ni se convierten unidades. La talla antigua permanece guardada y se muestra en el formulario. Las demás categorías conservan el selector de talla.
- Tipo, cintura y largo ofrecen sugerencias de prendas guardadas; admiten valores personalizados. No se guardan sugerencias por sí solas.
- El tipo aparece para Pantalones y Jeans. Cambiar de categoría lo oculta sin borrar un tipo seleccionado previamente; solo se analiza en categorías de pantalones.
- Las peticiones de clientes anteriores que omiten los nuevos campos conservan sus valores existentes. Se recomienda recargar ambos dispositivos tras desplegar.
- Los respaldos completos incluyen estos campos y fotos. Los respaldos antiguos sin ellos siguen siendo válidos. Restaura los respaldos nuevos en esta versión o una posterior para conservar los campos nuevos.

## Cómo se calculan las cifras

- Total de prendas: todas, con desglose de activas y archivadas.
- Prendas usadas este mes: identificadores distintos en usos del mes actual, incluidas archivadas.
- Gasto registrado: precios conocidos, separados por moneda; incluye prendas archivadas y compras sin fecha. Sin precios, muestra «Sin datos».
- Costo por uso: por moneda, suma del precio de prendas con precio y al menos un uso dividida entre sus usos. Excluye prendas sin precio o sin usos. No es una conversión ni un promedio entre monedas.
- Tallas y tipos: prendas activas; valores ausentes se muestran como «Sin especificar».
- Uso por categoría: usos de cada prenda según su categoría guardada en el registro histórico.
- Evolución del gasto: compras con precio, moneda y fecha. Últimos 30 días incluye hoy y los 29 días anteriores, agrupados por día. Los periodos de 3, 6 y 12 meses incluyen el mes actual hasta hoy y los meses anteriores necesarios, agrupados por mes. Todo el historial agrupa por mes. Cada moneda tiene su propia gráfica. Solo hay puntos donde existen compras fechadas; no se añaden puntos para fechas sin registros. Un precio cero guardado es válido. Sin compras elegibles, se muestra un estado vacío. La curva une observaciones sin sobrepasar sus valores y no estima compras en los intervalos. Los importes exactos están en una tabla desplegable.
- Etiquetas: etiquetas asignadas actualmente a outfits; un outfit puede aparecer en varias. El insight de uso agrupa usos por las etiquetas actuales del outfit, no por etiquetas históricas.
- Menos usadas y prendas sin uso reciente: activas. «Sin uso reciente» requiere último uso conocido de hace al menos 90 días. Las nunca usadas se cuentan por separado.
- Últimos outfits llevados: registros reales con outfit de origen, ordenados por fecha de uso. No se inventan fechas de creación.

Las estadísticas existentes conservan sus filtros por fechas y categoría. No hay datos de demostración en la aplicación ni imágenes externas. Los tests usan únicamente almacenamiento temporal y archivos de prueba aislados, nunca `private-local` ni la base de producción.

## Verificación

Ejecuta `sh scripts/check.sh` con PHP, Python y Node disponibles (Node solo se usa en pruebas, no en producción). Incluye pruebas HTTP de alta con foto, edición, historial, protección de sesión, conflictos, exportación/restauración y compatibilidad de campos; pruebas de cálculos del panel y estadísticas; y pruebas de optimización de fotos.

La revisión local se realiza con SQLite usando la misma tabla JSON y consultas preparadas. La comprobación final de MySQL, PHP Selector, Safari/cámara/PWA en un iPhone real y HTTPS se realiza después del despliegue. No se implementa funcionamiento sin conexión.
