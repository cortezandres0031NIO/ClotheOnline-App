# Mi armario: definición de la primera versión

Estado actualizado: primera versión PHP construida para alojamiento PHP/MySQL confirmado. Prueba local con SQLite. Consulta mi-armario/LEEME.md y mi-armario/PRUEBAS.md para conocer lo implementado y lo pendiente; no se ha publicado ni contratado ningún servicio.

## Pantallas

Interfaz en español, fotos protagonistas sobre fondo blanco, texto oscuro y acento azul. En el iPhone, navegación inferior; en el Mac, navegación lateral. Controles cómodos para tocar, etiquetas visibles y formularios de una columna en móvil.

1. **Hoy:** botón «Registrar lo que llevo», selector visual de outfits y fecha de hoy preseleccionada. Elegir outfit → revisar prendas → guardar. Cambiar prendas o añadir notas es opcional. No se guarda un uso hasta pulsar Guardar.
2. **Mi ropa:** galería, búsqueda por nombre, filtros por categoría, color y estado. Formulario con foto desde cámara o galería, nombre, categoría, color, notas y compra opcional. Prendas archivadas fuera del selector habitual, disponibles en historial y estadísticas.
3. **Outfits:** combinaciones guardadas, nombre, notas y foto opcional. Cuando no haya foto del conjunto, mostrar las fotos de las prendas. Edición de composición independiente del historial.
4. **Calendario:** vista mensual y lista de usos del día. Admite varios registros por fecha. Permite corregir fecha, notas y prendas, y eliminar un registro con confirmación.
5. **Compras y estadísticas:** periodo y categoría seleccionables; compras por mes con totales separados por moneda; gráficos de prendas más y menos usadas, outfits repetidos y usos mensuales. Estado vacío honesto cuando no existan registros.
6. **Ajustes:** cerrar sesión, descargar respaldo e importar respaldo con vista previa de cantidades y confirmación antes de sustituir datos.

## Datos y relaciones

Este modelo es independiente de la base de datos y del lenguaje que se elijan.

| Entidad | Información |
| --- | --- |
| Propietario | Única cuenta habilitada, credencial protegida y datos de sesión. No existe registro público. |
| Prenda | Identificador, nombre, categoría, color, notas, foto, fecha de compra opcional, tienda opcional, importe opcional, moneda, fecha de archivo y fechas de creación/edición. |
| Outfit | Identificador, nombre, notas, foto opcional y fechas de creación/edición. |
| Prenda de outfit | Relación entre outfit y prenda, sin repetir una prenda dentro de la misma combinación. |
| Uso | Identificador, fecha local de calendario, notas, outfit de origen opcional y nombre del outfit al registrarlo. |
| Prenda de uso | Relación independiente entre un uso y cada prenda realmente llevada; conserva también nombre y categoría al registrarla. |
| Foto | Identificador, ubicación privada, tipo validado, dimensiones, tamaño y relación con su registro. |

Los importes se almacenarán de manera exacta, sin números decimales aproximados. Un precio requiere moneda. El importe cero es válido y se distingue de un precio desconocido.

## Reglas del historial

- Guardar un uso copia la selección actual de prendas a su propia lista. No calcula la composición consultando el outfit cada vez.
- Editar un outfit no agrega usos ni modifica listas de usos anteriores.
- Ajustar las prendas de un uso no cambia el outfit de origen.
- Una prenda cuenta una vez por registro de uso, aunque aparezca duplicada por error en una selección. Si se lleva en dos registros distintos del mismo día, cuenta dos usos.
- Corregir o eliminar un uso actualiza todas las estadísticas derivadas.
- Archivar una prenda conserva sus fotos, compras y usos. Su ficha sigue siendo accesible desde el historial.
- La fecha de uso se trata como fecha local, sin desplazarla al convertir zonas horarias.

## Cálculos

- **Frecuencia:** cantidad de registros de uso que incluyen cada prenda en el periodo.
- **Nunca usadas:** prendas sin ningún uso en todo el historial. «Sin uso en este periodo» es un indicador distinto.
- **Último uso:** fecha más reciente de uso hasta hoy; si no existe, mostrar «Nunca usada».
- **Outfits repetidos:** número de registros vinculados a cada outfit. Los registros con prendas ajustadas mantienen su origen y su lista propia de prendas.
- **Evolución mensual:** registros de uso por mes, no cantidad de prendas incluidas.
- **Filtro por categoría:** para prendas cuenta solo las de la categoría seleccionada; para usos y outfits cuenta registros que incluyan al menos una prenda de esa categoría. Se usa la categoría conservada en cada uso para evitar cambiar retrospectivamente el historial.
- **Costo por uso:** precio de compra dividido entre todos los usos de la prenda, con etiqueta «histórico». El filtro temporal no altera este denominador. Si no existe precio o no hay usos, mostrar «No disponible».
- **Compras:** agrupar por fecha de compra y moneda. Compras sin fecha aparecen aparte y no se asignan artificialmente a un mes. Precios desconocidos no se convierten en cero.
- **Monedas:** no se convierten ni se suman entre sí en la primera versión.
- **Demostraciones:** ningún registro ficticio en los datos personales. Las pruebas utilizarán almacenamiento separado.

## Privacidad, fotos y respaldos

- Comprobar sesión en todas las operaciones sobre registros, fotos y respaldos, incluida la descarga directa de una imagen.
- Guardar fotos fuera del directorio público o en almacenamiento privado equivalente, según el alojamiento confirmado.
- Proteger sesiones, intentos de inicio de sesión y operaciones de modificación. No incluir contraseñas predeterminadas en la aplicación.
- Validar el contenido de las imágenes, limitar tamaño y dimensiones, corregir orientación, quitar metadatos de ubicación y reducir resolución a una calidad útil para consultar ropa.
- Comprobar compatibilidad real con las fotos del iPhone; no prometer soporte HEIC antes de elegir y probar el procesamiento de imágenes.
- Respaldo descargable con datos, fotos y versión del formato. No incluye sesiones ni contraseñas. Contiene información privada y deberá guardarse en un lugar seguro.
- Restauración completa, no fusión: validar formato, relaciones, fotos, tamaños y rutas antes de modificar datos; mostrar vista previa, pedir confirmación y conservar el estado anterior si falla.

## Conexión y PWA

La versión publicada necesitará conexión para iniciar sesión, consultar y guardar datos y fotos, y descargar o restaurar respaldos. Los dos dispositivos consultarán el mismo almacenamiento del servidor. No se promete sincronización sin conexión.

La instalación en la pantalla de inicio del iPhone se preparará con iconos y manifiesto y se verificará sobre HTTPS. No se guardarán fotos ni datos privados en una caché offline de forma implícita.

La prueba local en el Mac usará el servidor local. Para abrirla desde el iPhone habrá que preparar acceso de red y comprobar las condiciones de HTTPS; la dirección localhost del Mac no funciona como dirección del Mac en el iPhone.

## Criterios de aceptación (resultados en mi-armario/PRUEBAS.md)

1. Crear prenda con foto, recuperarla tras reiniciar y comprobar orientación y tamaño.
2. Crear outfit: el contador de usos permanece en cero.
3. Registrar outfit: aparece un uso por cada prenda incluida.
4. Editar outfit: el registro histórico no cambia.
5. Corregir prendas y fecha de un uso: cambia el historial y se recalculan estadísticas.
6. Eliminar uso: desaparece de contadores y gráficos.
7. Archivar prenda: conserva historial y compras.
8. Verificar filtros, prendas nunca usadas, costo por uso y totales separados de dos monedas.
9. Exportar y restaurar en una instalación vacía: coinciden datos y fotos; un respaldo inválido no altera los datos existentes.
10. Sin sesión, intentar consultar registros y una URL directa de foto: acceso denegado.
11. En iPhone real: cámara, galería, formatos, controles táctiles e instalación en inicio.
12. En alojamiento real: HTTPS, sesión, archivos privados, límites de subida, persistencia y acceso desde iPhone y Mac.

## Información pendiente del alojamiento

El usuario identifica cPanel y un proveedor con mascota de cocodrilo, posiblemente HostGator. Su tío es programador y puede encargarse de la instalación. Posteriormente confirmó que el alojamiento admite PHP y MySQL. Quedan por comprobar las versiones, extensiones, espacio disponible y límites de archivos. No hace falta compartir credenciales.
