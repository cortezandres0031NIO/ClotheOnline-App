# Instalación en cPanel — para quien administra el alojamiento

## Requisitos y alcance

Destino confirmado por el usuario: alojamiento con PHP y MySQL. Proveedor probable HostGator, todavía no identificado con certeza por plan/país. No se han contratado servicios ni publicado la aplicación.

- PHP **8.2 o posterior**; preferir una versión mantenida 8.4 con sus actualizaciones del proveedor. La prueba local utiliza PHP 8.4.23.
- Extensiones: PDO y pdo_mysql, GD con JPEG/PNG/WebP, mbstring, EXIF, ZIP, fileinfo y sesiones. PDO SQLite solo para el modo local.
- MySQL con InnoDB y utf8mb4. El SQL empleado es básico, pero todavía no se ha probado contra el MySQL de ese plan.
- Servidor Apache o compatible que ejecute PHP. Se incluye `.htaccess`; la protección esencial es mantener app, configuración y almacenamiento fuera del directorio público.
- Acceso a Terminal/SSH, o ayuda del soporte para ejecutar la instalación CLI. No se proporciona instalador web público.
- PHP debe poder escribir en una carpeta privada y utilizar bloqueos `flock`. Un solo servidor/almacenamiento compartido por esta instalación; no es una arquitectura para múltiples nodos.
- HTTPS válido y raíz de subdominio dedicada. Esta entrega asume instalación en la raíz de un subdominio, no en una subcarpeta de otra web.

Referencia de versiones mantenidas: https://www.php.net/supported-versions.php

## 1. Crear el subdominio

En cPanel, abre **Dominios → Crear un nuevo dominio**, o la sección de Subdominios equivalente del proveedor. Crea, por ejemplo, `armario.tudominio.com`, sin compartir la raíz de otra web.

Distribución preferida:

```
/home/USUARIO/mi-armario/
  app/              # código privado y config.php
  bin/              # utilidades CLI
  public/           # ÚNICA raíz pública del subdominio
/home/USUARIO/armario-private/
  photos/
  sessions/
  tmp/
  owner.json
```

El document root debe ser `/home/USUARIO/mi-armario/public`. Sube el contenido del ZIP manteniendo esa estructura. **No subas `private-local`, una base SQLite del Mac, su config.php ni el runtime de macOS.** El ZIP preparado ya los excluye.

Si el proveedor obliga a usar un document root bajo `public_html`, coloca únicamente el contenido de `public/` en ese directorio. Guarda `app/` y `bin/` en `/home/USUARIO/mi-armario/` fuera de `public_html` y adapta la línea `require` de `api.php` a la ruta absoluta `/home/USUARIO/mi-armario/app/core.php`. No copies la carpeta app al directorio público para resolver una ruta. Las rutas URL siguen en la raíz del subdominio.

Referencia HostGator: https://www.hostgator.com/help/article/please-read-before-creating-a-subdomain

## 2. Crear la base de datos

En **Bases de datos MySQL**, crea una base y un usuario exclusivo para esta aplicación, con contraseña aleatoria fuerte. Asigna el usuario a esa base. Para instalar requiere CREATE, SELECT, INSERT y UPDATE; el código de esta versión no necesita DROP ni privilegios globales.

El instalador creará `armario_state`, con revisión y documento JSON en LONGTEXT. Se eligió un documento transaccional para el armario de una persona: evita escrituras parciales entre outfit e historial, funciona igual con SQLite local y MySQL publicado, y no depende de funciones JSON específicas del proveedor. Las relaciones y el formato se validan en PHP. Cada modificación usa bloqueo de aplicación y actualización condicional de la revisión; no debe editarse la tabla a mano.

## 3. Configurar PHP y el almacenamiento

Copia `app/config.example.php` a `app/config.php` y completa:

```php
<?php
return [
  'environment' => 'production',
  'origin' => 'https://armario.tudominio.com',
  'storage' => '/home/USUARIO/armario-private',
  'dsn' => 'mysql:host=localhost;dbname=USUARIO_armario;charset=utf8mb4',
  'db_user' => 'USUARIO_armario',
  'db_password' => 'CONTRASEÑA_DE_LA_BASE',
  'timezone' => 'America/Managua',
];
```

`origin` debe coincidir exactamente con la dirección final, sin ruta. No uses modo local en producción. No compartas este archivo ni lo incluyas en un repositorio público.

Usa permisos restrictivos compatibles con el usuario PHP de cPanel: 0700 para almacenamiento y 0600 para configuración, cuenta y fotos cuando PHP corre como el propietario. No uses 0777. La carpeta `public` solo contiene recursos públicos y los dos puntos de entrada PHP.

Ajustes PHP propuestos, sujetos al plan:

```
upload_max_filesize = 128M
post_max_size = 132M
memory_limit = 512M
max_execution_time = 120
session.gc_maxlifetime = 86400
display_errors = Off
log_errors = On
```

La aplicación limita fotos originales a 20 MB y 25 megapíxeles; el navegador reduce normalmente antes de subir. Si el plan no permite esos límites, ajustar los textos y comprobar fotos y respaldos del tamaño real. Para restaurar se necesita espacio adicional para nuevas fotos, ZIP temporal y copia de recuperación.

## 4. Crear la única cuenta

Desde Terminal/SSH, con la misma versión PHP seleccionada para la web:

```sh
cd /home/USUARIO/mi-armario
php bin/install.php
php bin/check.php
```

El instalador pide la contraseña sin mostrarla. Se almacena solo su hash con `password_hash`; no hay contraseña predeterminada ni alta pública. Repite la instalación solo si sabes qué configuración está cargada. `check.php` debe dar OK en las comprobaciones.

Para restablecer la contraseña posteriormente:

```sh
php bin/install.php --reset-password
```

Esto invalida las sesiones existentes. Si el plan no incluye Terminal/SSH, el administrador debe solicitar ejecución al soporte o preparar un procedimiento privado equivalente; **no convertir estas utilidades en un instalador web accesible públicamente**.

## 5. Activar HTTPS

Apunta el DNS del subdominio al alojamiento y activa el certificado incluido mediante **SSL/TLS Status / AutoSSL / Let's Encrypt**, según el panel. Comprueba que cubra el subdominio y que la renovación esté habilitada. Después activa **Force HTTPS Redirect** para ese subdominio.

La API rechaza HTTP en producción y marca las cookies Secure, HttpOnly y SameSite=Strict. No confía en cabeceras de proxy para declarar HTTPS. Si hay un proxy/CDN que termina TLS, debe configurarse correctamente la conexión HTTPS al origen y la variable HTTPS del servidor; no quites esta comprobación.

HostGator documenta SSL incluido en planes elegibles; confirmar en la cuenta concreta antes de asumirlo. No comprar certificados por anticipado:

- https://www.hostgator.com/help/article/hostgator-free-ssl
- https://soporte.hostgator.mx/hc/es-419/articles/28440296869011-C%C3%B3mo-funciona-el-certificado-SSL-gratuito-en-el-alojamiento-de-HostGator

## 6. Comprobar privacidad y trasladar los datos

1. Abrir la dirección HTTPS e iniciar sesión.
2. Crear una prenda temporal con foto y comprobarla en Mac e iPhone.
3. Copiar la URL de esa foto y abrirla en una ventana privada sin sesión: debe rechazar acceso.
4. Verificar que `/app/config.php`, `/private-local/owner.json` y carpetas privadas no sean accesibles por ningún dominio de la cuenta. Comprobar también que el dominio principal no publique accidentalmente el directorio padre.
5. Registrar un outfit, corregir un uso, comprobar gastos de dos monedas y exportar/restaurar un respaldo de prueba.
6. En el Mac local, descargar un respaldo con los datos reales y restaurarlo en el servidor. La vista previa y la confirmación se realizan dentro de la sesión.
7. Guardar un respaldo adicional antes de dejar de usar la copia local.

El servidor compartido y quien administra la cuenta de cPanel pueden acceder a los archivos a nivel de alojamiento. El acceso exclusivo se aplica a la aplicación y a sus URLs; no impide el acceso administrativo del proveedor o del titular de cPanel.

## 7. Añadir al iPhone

Abrir la URL HTTPS en Safari → Compartir → Añadir a pantalla de inicio. Comprobar el icono, pantalla independiente, sesión, cámara y galería en el iPhone real. El service worker no conserva fotos ni registros: solo muestra un mensaje al fallar una navegación sin conexión. No hay sincronización offline.

## Mantenimiento y respaldos

- Actualizar PHP con el proveedor y conservar respaldos periódicos descargados. No se configuró ninguna tarea programada automática.
- Para limpiar fotos huérfanas de formularios abandonados o sustituciones, y temporales antiguos, ejecutar de vez en cuando:

```sh
php bin/maintenance.php
```

Solo borra fotos no referenciadas de más de 7 días y temporales de más de 24 horas. Las fotos de prendas archivadas se conservan. La copia anterior a una restauración se conserva hasta la siguiente restauración, no indefinidamente.

- Exportar y restaurar se validó localmente hasta respaldos pequeños. Antes de acumular muchas fotos, probar tamaños representativos en este alojamiento. Límite actual de restauración: 128 MB comprimidos / 256 MB descomprimidos. Más de 20 000 prendas, outfits o usos por sección requiere ampliar la versión.
- Nunca usar el servidor incorporado `php -S` como servidor público de producción.
