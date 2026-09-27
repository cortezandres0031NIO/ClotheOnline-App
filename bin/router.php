<?php
$path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
$allowed=['/','/index.php','/api.php','/app.js','/app.css','/stats.js','/icon.svg','/icon-192.png','/icon-512.png','/manifest.webmanifest','/sw.js'];
if (!in_array($path,$allowed,true)) {http_response_code(404);header('Content-Type: text/plain; charset=utf-8');echo 'No encontrado';return true;}
return false;
