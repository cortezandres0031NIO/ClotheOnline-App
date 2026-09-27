<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli')exit;
require dirname(__DIR__).'/app/core.php';
$checks=['PHP >= 8.2'=>version_compare(PHP_VERSION,'8.2','>=')];
foreach(['pdo','gd','mbstring','zip','exif','fileinfo','session'] as $ext)$checks['Extensión '.$ext]=extension_loaded($ext);
try{$c=config();$checks['Directorio privado fuera de public']=!str_starts_with(realpath(storage())?:storage(),realpath(dirname(__DIR__).'/public').'/');$checks['Directorio privado escribible']=is_dir(storage())&&is_writable(storage());$checks['Base de datos conectada']=db()->query('SELECT 1')->fetchColumn()==1;$checks['Cuenta creada']=is_file(storage().'/owner.json');if($c['environment']==='production'){$checks['Origen HTTPS']=str_starts_with($c['origin'],'https://');$checks['MySQL configurado']=str_starts_with($c['dsn'],'mysql:');}}catch(Throwable $e){$checks['Configuración y conexión']=false;}
foreach($checks as $label=>$ok)echo ($ok?'OK  ':'FALLO  ').$label."\n";
exit(in_array(false,$checks,true)?1:0);
