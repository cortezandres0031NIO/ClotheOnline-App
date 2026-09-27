<?php
if(PHP_SAPI!=='cli')exit;
$base=dirname(__DIR__);
$path=$base.'/app/config.php';
if(is_file($path))exit;
$storage=$base.'/private-local';
$c=['environment'=>'local','origin'=>'http://127.0.0.1:8765','storage'=>$storage,'dsn'=>'sqlite:'.$storage.'/armario.sqlite','timezone'=>'America/Managua'];
file_put_contents($path,"<?php\nreturn ".var_export($c,true).";\n"); chmod($path,0600);
