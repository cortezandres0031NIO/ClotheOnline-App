# Verificación de la primera versión

Fecha: 27 de septiembre de 2026.

Entorno: Mac Apple Silicon, PHP 8.4.23 con SQLite para la prueba local, navegador integrado de Codex. Todos los registros y las imágenes de prueba se guardaron en carpetas temporales separadas de `private-local`. No se añadieron datos ficticios al armario personal.

## Pruebas automáticas realizadas

**33 comprobaciones HTTP superadas**, mediante `tests/integration.py`:

- Preparación de la cuenta local, rechazo de una segunda cuenta y estado inicial vacío.
- Sesión requerida para registros y para URL directa de foto; cierre de sesión e inicio posterior.
- Protección CSRF y rechazo de archivos disfrazados de imagen.
- Alta de prendas con foto optimizada, precios exactos y compras en USD/NIO.
- Creación de outfit sin sumar usos.
- Registro de uso; edición posterior del outfit sin modificar el historial.
- Corrección de fecha y prendas del uso sin alterar la combinación original.
- Archivo de prenda con preservación de foto y usos.
- Conflicto de revisión: un formulario antiguo no sobrescribe datos más recientes.
- Exportación ZIP con datos y fotos, sin incluir contraseña ni sesiones.
- Rechazo de respaldos con rutas peligrosas o referencias rotas, conservando los datos existentes.
- Restauración sobre un armario vacío: coincidencia de todos los registros y del contenido de las fotos; se regeneran los identificadores de archivo para evitar colisiones.
- Copia de recuperación previa a la restauración.
- Eliminación de un uso y actualización de la fuente de estadísticas.
- Directorios de configuración y datos fuera del directorio servido.

**9 comprobaciones de fotografías superadas**, mediante `tests/photos.py`:

- Las ocho orientaciones EXIF producen la orientación esperada.
- Se eliminan los metadatos EXIF al generar el JPEG.
- Imagen de 2400 × 1200 reducida a 1600 × 800.

**9 comprobaciones de estadísticas superadas**, mediante `tests/stats.mjs`, ejecutando la función real de la interfaz:

- Conteo de usos y prendas sin uso.
- Separación de importes USD/NIO.
- Exclusión de compras sin fecha del agrupamiento mensual.
- Outfits repetidos y filtros de fecha/categoría.
- Inclusión de meses intermedios con cero usos.
- Categorías históricas conservadas al cambiar la categoría actual de una prenda.
- Estado vacío sin datos inventados.

También se validó sintaxis PHP y JavaScript. Estas pruebas no constituyen una auditoría de seguridad independiente.

## Pruebas realizadas desde la interfaz

En una instalación de prueba aislada:

1. Inicio de sesión.
2. Selección y subida de una imagen, nombre, categoría y color; guardado y aparición de la prenda en la galería.
3. Creación de outfit con dos prendas; tarjeta con cero usos.
4. Registro de ese outfit desde «Lo llevo hoy».
5. Corrección del registro para quitar una prenda y añadir notas.
6. Estadísticas actualizadas: la prenda retirada queda con cero usos, la conservada con uno, y los importes siguen separados por moneda.
7. Filtro de categoría que cambia los contadores y gráficos.
8. Selección de ZIP, vista previa de cantidades, confirmación y restauración completa desde Ajustes. La interfaz vuelve a mostrar los registros restaurados.
9. Revisión visual en ancho de ordenador; sin errores de consola capturados en esa sesión.

Se intentó simular 390 × 844 con el control de viewport disponible, pero el navegador mantuvo un ancho de escritorio. Por tanto **no se declara aprobada una prueba visual móvil**. El CSS incluye los diseños móvil y escritorio; su comprobación en tamaño real queda pendiente.

## Lo que falta comprobar en tu iPhone

- Safari real, diseño y controles a su tamaño de pantalla.
- Cámara y galería; JPEG y HEIC de ese modelo/configuración.
- Orientación y tiempo de subida de fotos reales.
- Añadir a pantalla de inicio, icono y apertura independiente.
- Sesión al volver a abrir la PWA y al cerrar sesión.
- Pérdida y recuperación de conexión sin duplicar un registro al reintentar.

## Lo que falta comprobar en el alojamiento

- Versión PHP, extensiones, límites de memoria/subida y espacio disponible.
- Instalación, persistencia y todos los flujos contra MySQL real. Localmente se probó SQLite con el mismo código de aplicación.
- Permisos de almacenamiento, ejecución de PHP y reglas Apache/cPanel.
- Certificado HTTPS, redirección, renovación, cookies Secure y cabeceras del servidor.
- Acceso a fotos sin sesión desde otra ventana o dispositivo.
- Mismos datos desde iPhone y Mac; ediciones simultáneas con red real.
- Exportación/restauración con el tamaño esperado de tu armario, no solo respaldos pequeños.
- Límites de intentos de acceso bajo condiciones del servidor. La limitación está implementada; no se realizó una prueba de carga.

No se ha publicado la aplicación ni modificado el dominio.

## Repetir las pruebas — para tu tío

Desde la carpeta de la aplicación, con PHP y Python disponibles:

```sh
python3 tests/integration.py --php /ruta/a/php --work /ruta/a/carpeta-temporal --port 8767
python3 tests/photos.py /ruta/a/php /ruta/a/carpeta-temporal
node tests/stats.mjs
```

`photos.py` requiere Pillow en Python. Las otras pruebas usan bibliotecas estándar. No es necesario instalar Python, Node o Pillow para usar la aplicación: solo para repetir estas pruebas.
