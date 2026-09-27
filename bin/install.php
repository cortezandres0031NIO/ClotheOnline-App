<?php
declare(strict_types=1);
if (PHP_SAPI!=='cli') { http_response_code(403); exit; }
require dirname(__DIR__).'/app/core.php';
foreach (['pdo','gd','mbstring','zip','exif'] as $ext) if (!extension_loaded($ext)) exit("Falta la extensión PHP: $ext\n");
initialize();
if (in_array('--local',$argv,true)) { echo "Almacenamiento local listo. Crea tu contraseña en la aplicación.\n"; exit; }
if (is_file(storage().'/owner.json') && !in_array('--reset-password',$argv,true)) exit("La cuenta ya existe. Para cambiarla usa --reset-password.\n");
echo "Contraseña privada (mínimo 12 caracteres; no se mostrará): ";
system('stty -echo'); try { $password=rtrim(fgets(STDIN),"\r\n"); } finally { system('stty echo'); echo "\n"; }
if (strlen($password)<12||strlen($password)>128) exit("Debe tener entre 12 y 128 caracteres.\n");
locked(function()use($password){file_put_contents(storage().'/owner.json',json_encode(['hash'=>password_hash($password,PASSWORD_DEFAULT),'version'=>uid()]),LOCK_EX);chmod(storage().'/owner.json',0600);});
echo "Instalación lista. La cuenta única ya está creada.\n";
