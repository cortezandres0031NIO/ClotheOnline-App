<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/core.php';
header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('X-Frame-Options: DENY');
header("Content-Security-Policy: default-src 'none'; frame-ancestors 'none'");
function respond(array $v): never { header('Content-Type: application/json; charset=utf-8'); echo json_encode($v,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR); exit; }
function input(): array {
    $raw=file_get_contents('php://input'); if (strlen($raw)>1024*1024) fail('Petición demasiado grande.',413);
    try { $v=json_decode($raw,true,64,JSON_THROW_ON_ERROR); } catch(Throwable) { fail('Datos no válidos.'); }
    if (!is_array($v)) fail('Datos no válidos.'); return $v;
}
function uploaded(string $field): string {
    $f=$_FILES[$field]??null;
    if (!$f || $f['error']!==UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) fail('No se recibió el archivo. Revisa el tamaño máximo permitido por el servidor.');
    return $f['tmp_name'];
}
function owner(): ?array { $p=storage().'/owner.json'; return is_file($p)?json_decode(file_get_contents($p),true,512,JSON_THROW_ON_ERROR):null; }
function setOwner(string $password): void {
    if (strlen($password)<12 || strlen($password)>128) fail('Usa una contraseña de entre 12 y 128 caracteres.');
    $p=storage().'/owner.json';
    $data=json_encode(['hash'=>password_hash($password,PASSWORD_DEFAULT),'version'=>uid()],JSON_THROW_ON_ERROR);
    if (file_put_contents($p,$data,LOCK_EX)===false) fail('No se pudo guardar la cuenta.',500); chmod($p,0600);
}
function exportZip(array $s): string {
    $file=tempnam(storage().'/tmp','export-'); $zip=new ZipArchive();
    if ($zip->open($file,ZipArchive::OVERWRITE)!==true) throw new RuntimeException('No se pudo crear el respaldo.');
    unset($s['revision']);
    $zip->addFromString('data.json',json_encode(['format'=>'mi-armario','version'=>1,'exported_at'=>date(DATE_ATOM),'data'=>$s],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));
    foreach (photoIds($s) as $id) { if (!is_file(storage().'/photos/'.$id.'.jpg')) fail('Falta una foto. No se puede crear un respaldo completo.',500); $zip->addFile(storage().'/photos/'.$id.'.jpg','photos/'.$id.'.jpg'); }
    if (!$zip->close()) fail('No se pudo completar el respaldo.',500); return $file;
}
function inspectZip(string $file): array {
    if (filesize($file)>128*1024*1024) fail('El respaldo supera el límite de 128 MB de esta versión.');
    $zip=new ZipArchive(); if ($zip->open($file)!==true) fail('El archivo no es un ZIP válido.');
    try {
        if ($zip->numFiles>40001) fail('El respaldo tiene demasiados archivos.');
        $photos=[]; $seen=[]; $total=0;
        for($i=0;$i<$zip->numFiles;$i++) {
            $st=$zip->statIndex($i); $name=$st['name'];
            if (isset($seen[$name])) fail('El respaldo contiene archivos repetidos.'); $seen[$name]=true;
            $total+=$st['size']; if ($total>256*1024*1024) fail('El respaldo descomprimido supera 256 MB.');
            if ($name==='data.json') { if ($st['size']>20*1024*1024) fail('Los datos son demasiado grandes.'); continue; }
            if (!preg_match('~^photos/([a-f0-9]{32})\.jpg$~D',$name,$m) || $st['size']>20*1024*1024) fail('El respaldo contiene un archivo inesperado.');
            $bytes=$zip->getFromIndex($i); $info=@getimagesizefromstring($bytes);
            if (!$info||$info[2]!==IMAGETYPE_JPEG||max($info[0],$info[1])>1600) fail('Foto no válida en el respaldo.');
            $photos[$m[1]]=true;
        }
        $raw=$zip->getFromName('data.json'); if ($raw===false) fail('Falta data.json.');
        try { $b=json_decode($raw,true,64,JSON_THROW_ON_ERROR); } catch(Throwable) { fail('No se pudieron leer los datos del respaldo.'); }
        if (!is_array($b)) fail('Datos de respaldo no válidos.');
        $s=validateBackup($b,$photos);
        if (count(photoIds($s))!==count($photos)) fail('El respaldo contiene fotos sin registro.');
        return $s;
    } finally { $zip->close(); }
}
try {
    $c=config(); $local=($c['environment']??'production')==='local' && PHP_SAPI==='cli-server' && in_array($_SERVER['REMOTE_ADDR']??'',['127.0.0.1','::1'],true);
    if (!$local && (empty($_SERVER['HTTPS'])||$_SERVER['HTTPS']==='off')) fail('Esta aplicación requiere HTTPS.',403);
    if (!$local) header('Strict-Transport-Security: max-age=31536000');
    if (!is_dir(storage().'/sessions')) fail('La instalación no está terminada.',503);
    session_save_path(storage().'/sessions');
    ini_set('session.use_strict_mode','1'); ini_set('session.use_only_cookies','1'); ini_set('session.gc_maxlifetime','86400');
    session_name('armario_'.substr(hash('sha256',$c['origin']),0,12)); session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>!$local,'httponly'=>true,'samesite'=>'Strict']); session_start();
    $_SESSION['csrf']??=bin2hex(random_bytes(32));
    $method=$_SERVER['REQUEST_METHOD']; $action=$_GET['action']??'session';
    $account=owner();
    $authenticated=$account && isset($_SESSION['owner_version']) && hash_equals($account['version'],$_SESSION['owner_version']) && time()-($_SESSION['last_seen']??0)<86400;
    if ($authenticated) $_SESSION['last_seen']=time();
    if ($method==='POST') {
        if (!hash_equals($_SESSION['csrf'],$_SERVER['HTTP_X_CSRF_TOKEN']??'')) fail('La sesión del formulario expiró. Recarga la página.',403);
        if (isset($_SERVER['HTTP_ORIGIN']) && rtrim($_SERVER['HTTP_ORIGIN'],'/')!==rtrim($c['origin'],'/')) fail('Origen no permitido.',403);
    } elseif ($method!=='GET') fail('Método no permitido.',405);
    if ($action==='session' && $method==='GET') respond(['authenticated'=>(bool)$authenticated,'setup'=>$local && !$account,'csrf'=>$_SESSION['csrf'],'categories'=>categories(),'currencies'=>currencies(),'today'=>date('Y-m-d'),'local'=>$local]);
    if ($action==='setup' && $method==='POST') {
        if (!$local) fail('La cuenta se crea durante la instalación privada.',403);
        $v=input(); locked(function()use($v){ if (owner()) fail('La cuenta ya existe.',403); setOwner($v['password']??''); });
        session_regenerate_id(true); $_SESSION['owner_version']=owner()['version']; $_SESSION['last_seen']=time(); respond(['ok'=>true]);
    }
    if ($action==='login' && $method==='POST') {
        $v=input(); $password=$v['password']??''; if (!is_string($password)||strlen($password)>128) fail('Contraseña incorrecta.',401);
        locked(function()use($account,$password){
            $file=storage().'/login-attempts.json'; $attempts=is_file($file)?json_decode(file_get_contents($file),true):[];
            $attempts=array_values(array_filter($attempts??[],fn($t)=>$t>time()-900));
            if(count($attempts)>=10) fail('Demasiados intentos. Espera 15 minutos antes de volver a intentar.',429);
            if (!$account || !password_verify($password,$account['hash'])) { $attempts[]=time(); file_put_contents($file,json_encode($attempts),LOCK_EX); chmod($file,0600); fail('Contraseña incorrecta.',401); }
            file_put_contents($file,'[]',LOCK_EX);
        });
        session_regenerate_id(true); $_SESSION['owner_version']=$account['version']; $_SESSION['last_seen']=time(); respond(['ok'=>true]);
    }
    if (!$authenticated) fail('Inicia sesión para continuar.',401);
    if ($action==='logout' && $method==='POST') { $_SESSION=[]; session_destroy(); setcookie(session_name(),'', ['expires'=>time()-3600,'path'=>'/','secure'=>!$local,'httponly'=>true,'samesite'=>'Strict']); respond(['ok'=>true]); }
    if ($action==='state' && $method==='GET') respond(readState());
    if ($action==='photo' && $method==='GET') {
        $id=$_GET['id']??''; if (!validId($id)||!is_file(storage().'/photos/'.$id.'.jpg')) fail('Foto no encontrada.',404);
        header('Content-Type: image/jpeg'); header('Content-Length: '.filesize(storage().'/photos/'.$id.'.jpg')); readfile(storage().'/photos/'.$id.'.jpg'); exit;
    }
    if ($action==='upload' && $method==='POST') respond(['id'=>optimize(uploaded('photo'))]);
    if ($action==='save' && $method==='POST') {
        $v=input(); $result=locked(function()use($v){
            $s=readState(); if (($v['revision']??null)!==$s['revision']) fail('Los datos cambiaron. Cierra el formulario y pulsa Actualizar antes de guardar.',409);
            $kind=$v['kind']??''; if (!in_array($kind,['garments','outfits','wears'],true)) fail('Tipo no válido.');
            $item=$v['item']??null; if (!is_array($item)) fail('Registro no válido.');
            $items=indexById($s[$kind]); $id=$item['id']??uid();
            if (!validId($id) || (isset($item['id'])&&!isset($items[$id]))) fail('Registro no encontrado.',404);
            $item['id']=$id; $gs=indexById($s['garments']); $os=indexById($s['outfits']);
            // Older clients omit optional fields: preserve their stored values.
            foreach ($kind==='garments'?['size','pants_type','tags']:($kind==='outfits'?['tags']:[]) as $field) {
                if (!array_key_exists($field,$item)&&isset($items[$id][$field])) $item[$field]=$items[$id][$field];
            }
            $clean=match($kind){'garments'=>garment($item),'outfits'=>outfit($item,$gs),'wears'=>wear($item,$gs,$os,$items[$id]??null)};
            $items[$id]=$clean; $s[$kind]=array_values($items); if (count($s[$kind])>20000) fail('Se alcanzó el límite de 20 000 registros por sección.');
            saveState($s,$s['revision']); return ['ok'=>true,'id'=>$id];
        }); respond($result);
    }
    if ($action==='delete-wear' && $method==='POST') {
        $v=input(); locked(function()use($v){ $s=readState(); if (($v['revision']??null)!==$s['revision']) fail('Los datos cambiaron. Actualiza la pantalla.',409); $found=false;
            $s['wears']=array_values(array_filter($s['wears'],function($w)use($v,&$found){if($w['id']===($v['id']??null)){$found=true;return false;}return true;}));
            if (!$found) fail('Uso no encontrado.',404); saveState($s,$s['revision']); }); respond(['ok'=>true]);
    }
    if ($action==='export' && $method==='GET') {
        $file=locked(fn()=>exportZip(readState()));
        header('Content-Type: application/zip'); header('Content-Disposition: attachment; filename="mi-armario-'.date('Y-m-d-His').'.zip"'); header('Content-Length: '.filesize($file));
        try { readfile($file); } finally { unlink($file); } exit;
    }
    if ($action==='preview-import' && $method==='POST') {
        $file=uploaded('backup'); $s=inspectZip($file); $token=uid();
        if (isset($_SESSION['import_token'])) @unlink(storage().'/tmp/import-'.$_SESSION['import_token'].'.zip');
        if (!move_uploaded_file($file,storage().'/tmp/import-'.$token.'.zip')) fail('No se pudo preparar la restauración.',500);
        chmod(storage().'/tmp/import-'.$token.'.zip',0600); $_SESSION['import_token']=$token; $_SESSION['import_time']=time();
        respond(['token'=>$token,'garments'=>count($s['garments']),'outfits'=>count($s['outfits']),'wears'=>count($s['wears']),'photos'=>count(photoIds($s))]);
    }
    if ($action==='restore' && $method==='POST') {
        $v=input(); $token=$v['token']??'';
        if (!validId($token)||$token!==($_SESSION['import_token']??null)||time()-($_SESSION['import_time']??0)>1800) fail('Vuelve a seleccionar el respaldo. La vista previa expiró.');
        $file=storage().'/tmp/import-'.$token.'.zip'; $s=inspectZip($file);
        locked(function()use($v,$s,$file){
            $old=readState(); if (($v['revision']??null)!==$old['revision']) fail('Los datos cambiaron. Actualiza y vuelve a revisar el respaldo.',409);
            $zip=new ZipArchive(); $zip->open($file); $map=[]; $newFiles=[];
            try {
                foreach(photoIds($s) as $id) { $new=uid(); $path=storage().'/photos/'.$new.'.jpg'; $bytes=$zip->getFromName('photos/'.$id.'.jpg'); if ($bytes===false||file_put_contents($path,$bytes)===false) throw new RuntimeException('No se pudo guardar una foto.'); chmod($path,0600); $newFiles[]=$path; $map[$id]=$new; }
                foreach(['garments','outfits'] as $kind) foreach($s[$kind] as &$item) if($item['photo']) $item['photo']=$map[$item['photo']]; unset($item);
                $recovery=exportZip($old); if(!rename($recovery,storage().'/before-restore.zip')) throw new RuntimeException('No se pudo conservar la copia anterior.'); chmod(storage().'/before-restore.zip',0600);
                saveState($s,$old['revision']);
            } catch(Throwable $e) { foreach($newFiles as $path) @unlink($path); throw $e; } finally { $zip->close(); }
        });
        unlink($file); unset($_SESSION['import_token'],$_SESSION['import_time']); respond(['ok'=>true]);
    }
    if ($action==='recovery' && $method==='GET') { $file=storage().'/before-restore.zip'; if(!is_file($file)) fail('No hay copia anterior a una restauración.',404); header('Content-Type: application/zip'); header('Content-Disposition: attachment; filename="antes-de-restaurar.zip"'); readfile($file); exit; }
    fail('Operación no encontrada.',404);
} catch(AppError $e) { http_response_code($e->getCode()?:422); respond(['error'=>$e->getMessage()]); }
catch(Throwable $e) { error_log('Armario: '.$e->getMessage()); http_response_code(500); respond(['error'=>'No se pudo completar la operación. Tus cambios no se han confirmado. Revisa el almacenamiento o consulta con quien instaló la aplicación.']); }
