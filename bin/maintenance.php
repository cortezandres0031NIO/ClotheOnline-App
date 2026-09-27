<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli')exit;
require dirname(__DIR__).'/app/core.php';
$removed=locked(function(){
 $references=array_fill_keys(photoIds(readState()),true);$n=0;$cutoff=time()-7*86400;
 foreach(glob(storage().'/photos/*.jpg') as $file)if(!isset($references[basename($file,'.jpg')])&&filemtime($file)<$cutoff){if(unlink($file))$n++;}
 foreach(glob(storage().'/tmp/*') as $file)if(is_file($file)&&filemtime($file)<time()-86400){if(unlink($file))$n++;}
 return $n;
});
echo "Archivos temporales antiguos eliminados: $removed\n";
