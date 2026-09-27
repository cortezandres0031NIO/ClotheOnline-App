<?php
declare(strict_types=1);

function config(): array {
    static $c;
    if ($c) return $c;
    $path = getenv('ARMARIO_CONFIG') ?: __DIR__.'/config.php';
    if (!is_file($path)) throw new RuntimeException('Falta configurar la aplicación. Consulta la guía de instalación.');
    $c = require $path;
    date_default_timezone_set($c['timezone'] ?? 'America/Managua');
    return $c;
}
function storage(): string { return rtrim(config()['storage'], '/'); }
function initialize(): void {
    foreach ([storage(),storage().'/photos',storage().'/sessions',storage().'/tmp'] as $dir) {
        if (!is_dir($dir) && !mkdir($dir,0700,true)) throw new RuntimeException('No se pudo crear el almacenamiento privado.');
    }
    db()->exec('CREATE TABLE IF NOT EXISTS armario_state (id INTEGER PRIMARY KEY, revision INTEGER NOT NULL, payload LONGTEXT NOT NULL)');
    $q=db()->prepare('SELECT id FROM armario_state WHERE id=1'); $q->execute();
    if (!$q->fetch()) { $q=db()->prepare('INSERT INTO armario_state (id,revision,payload) VALUES (1,0,?)'); $q->execute([json_encode(emptyState())]); }
}
function db(): PDO {
    static $db;
    if (!$db) { $c=config(); $db=new PDO($c['dsn'],$c['db_user']??null,$c['db_password']??null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>false]); }
    return $db;
}
function emptyState(): array { return ['garments'=>[], 'outfits'=>[], 'wears'=>[]]; }
function locked(callable $fn): mixed {
    $f=fopen(storage().'/write.lock','c');
    if (!$f || !flock($f,LOCK_EX)) throw new RuntimeException('No se pudo bloquear el almacenamiento.');
    try { return $fn(); } finally { flock($f,LOCK_UN); fclose($f); }
}
function readState(): array {
    $row=db()->query('SELECT revision,payload FROM armario_state WHERE id=1')->fetch(PDO::FETCH_ASSOC);
    if (!$row) throw new RuntimeException('La aplicación no está instalada.');
    $s=json_decode($row['payload'],true,512,JSON_THROW_ON_ERROR); $s['revision']=(int)$row['revision']; return $s;
}
function saveState(array $s, int $revision): void {
    unset($s['revision']);
    $q=db()->prepare('UPDATE armario_state SET payload=?, revision=revision+1 WHERE id=1 AND revision=?');
    $q->execute([json_encode($s,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),$revision]);
    if ($q->rowCount()!==1) fail('Los datos cambiaron en otro dispositivo. Cierra este formulario y actualiza la pantalla antes de volver a guardar.',409);
}
function fail(string $message,int $code=422): never { throw new AppError($message,$code); }
class AppError extends RuntimeException {}
function uid(): string { return bin2hex(random_bytes(16)); }
function validId(mixed $x): bool { return is_string($x) && preg_match('/^[a-f0-9]{32}$/D',$x)===1; }
function strvalField(array $x,string $key,int $max=2000,bool $required=false): string {
    $v=$x[$key]??'';
    if (!is_string($v) || mb_strlen($v)>$max || ($required && trim($v)==='')) fail("Revisa el campo $key.");
    return trim($v);
}
function dateField(mixed $v,bool $required=false): ?string {
    if (($v===null || $v==='') && !$required) return null;
    if (!is_string($v)) fail('Fecha no válida.');
    $d=DateTimeImmutable::createFromFormat('!Y-m-d',$v);
    if (!$d || $d->format('Y-m-d')!==$v || $v<'1900-01-01' || $v>date('Y-m-d')) fail('Elige una fecha válida, desde 1900 y no posterior a hoy.');
    return $v;
}
function money(array $v): ?int {
    $p=$v['price_minor']??null;
    if ($p!==null && (!is_int($p)||$p<0||$p>999999999999)) fail('Precio no válido.');
    return $p;
}
function categories(): array { return ['Camisetas','Camisas','Pantalones','Jeans','Vestidos','Faldas','Abrigos','Jerséis','Calzado','Deporte','Accesorios','Otros']; }
function currencies(): array { return ['NIO'=>2,'USD'=>2,'EUR'=>2,'MXN'=>2,'CRC'=>2,'GTQ'=>2,'HNL'=>2,'COP'=>2,'GBP'=>2,'JPY'=>0]; }
function photoField(mixed $v,?array $available=null): ?string {
    if ($v===null||$v==='') return null;
    if (!validId($v) || ($available!==null ? !isset($available[$v]) : !is_file(storage().'/photos/'.$v.'.jpg'))) fail('La foto no está disponible. Vuelve a seleccionarla.');
    return $v;
}
function garment(array $v,?array $photos=null): array {
    $cat=strvalField($v,'category',40,true); if (!in_array($cat,categories(),true)) fail('Categoría no válida.');
    $price=money($v); $currency=strvalField($v,'currency',3);
    if ($price!==null && !array_key_exists($currency,currencies())) fail('Elige la moneda del precio.');
    $photo=photoField($v['photo']??null,$photos); if (!$photo) fail('Añade una foto de la prenda.');
    return ['id'=>$v['id']??uid(),'name'=>strvalField($v,'name',120,true),'category'=>$cat,'color'=>strvalField($v,'color',60,true),'notes'=>strvalField($v,'notes'), 'photo'=>$photo,'purchase_date'=>dateField($v['purchase_date']??null),'store'=>strvalField($v,'store',120),'price_minor'=>$price,'currency'=>$price===null?'':$currency,'archived'=>(bool)($v['archived']??false)];
}
function selection(mixed $ids,array $gs): array {
    if (!is_array($ids)||!array_is_list($ids)||count($ids)<1||count($ids)>100) fail('Selecciona entre 1 y 100 prendas.');
    $seen=[];
    foreach ($ids as $id) { if (!validId($id)||!isset($gs[$id])||isset($seen[$id])) fail('La selección contiene prendas no válidas o repetidas.'); $seen[$id]=true; }
    return $ids;
}
function indexById(array $items): array { return array_column($items,null,'id'); }
function outfit(array $v,array $gs,?array $photos=null): array {
    return ['id'=>$v['id']??uid(),'name'=>strvalField($v,'name',120,true),'notes'=>strvalField($v,'notes'),'photo'=>photoField($v['photo']??null,$photos),'garment_ids'=>selection($v['garment_ids']??null,$gs)];
}
function wear(array $v,array $gs,array $os,?array $previous=null): array {
    $oid=$v['outfit_id']??null;
    if ($oid!==null && (!validId($oid)||!isset($os[$oid]))) fail('Outfit no válido.');
    $ids=selection($v['garment_ids']??null,$gs); $old=indexById($previous['items']??[]);
    $items=[];
    foreach ($ids as $id) $items[]=$old[$id]??['id'=>$id,'name'=>$gs[$id]['name'],'category'=>$gs[$id]['category']];
    return ['id'=>$v['id']??uid(),'date'=>dateField($v['date']??null,true),'notes'=>strvalField($v,'notes'),'outfit_id'=>$oid,'outfit_name'=>$previous && $previous['outfit_id']===$oid ? $previous['outfit_name'] : ($oid?$os[$oid]['name']:''),'items'=>$items];
}
function photoIds(array $s): array {
    $ids=[]; foreach (array_merge($s['garments'],$s['outfits']) as $v) if ($v['photo']) $ids[$v['photo']]=true;
    return array_keys($ids);
}
function optimize(string $file): string {
    if (filesize($file)>20*1024*1024) fail('La foto supera 20 MB. Elige una más pequeña.');
    $info=@getimagesize($file);
    if (!$info || !in_array($info[2],[IMAGETYPE_JPEG,IMAGETYPE_PNG,IMAGETYPE_WEBP],true)) fail('Usa una foto JPEG, PNG o WebP. Si es HEIC, expórtala como JPEG o elige «Más compatible» en los ajustes de cámara del iPhone.');
    if ($info[0]*$info[1]>25000000) fail('La foto supera 25 megapíxeles. Reduce su tamaño antes de subirla.');
    $im=@imagecreatefromstring(file_get_contents($file)); if (!$im) fail('No se pudo leer la foto.');
    if ($info[2]===IMAGETYPE_JPEG && function_exists('exif_read_data')) {
        $e=@exif_read_data($file); $o=(int)($e['Orientation']??1);
        if (in_array($o,[2,4,5,7],true)) imageflip($im,IMG_FLIP_HORIZONTAL);
        $angle=match($o){3,4=>180,6,7=>-90,5,8=>90,default=>0};
        if ($angle) $im=imagerotate($im,$angle,0);
    }
    $w=imagesx($im); $h=imagesy($im); $scale=min(1,1600/max($w,$h));
    $out=imagecreatetruecolor((int)round($w*$scale),(int)round($h*$scale));
    imagefill($out,0,0,imagecolorallocate($out,255,255,255));
    imagecopyresampled($out,$im,0,0,0,0,imagesx($out),imagesy($out),$w,$h);
    $id=uid(); if (!imagejpeg($out,storage().'/photos/'.$id.'.jpg',85)) fail('No se pudo guardar la foto.',500);
    chmod(storage().'/photos/'.$id.'.jpg',0600); return $id;
}
function validateBackup(array $b,array $photos): array {
    if (($b['format']??null)!=='mi-armario'||($b['version']??null)!==1||!isset($b['data'])||!is_array($b['data'])) fail('El respaldo no corresponde a Mi armario versión 1.');
    $s=$b['data'];
    foreach (['garments','outfits','wears'] as $key) {
        if (!isset($s[$key])||!is_array($s[$key])||!array_is_list($s[$key])||count($s[$key])>20000) fail('El respaldo contiene una lista no válida.');
        $seen=[]; foreach ($s[$key] as $item) {
            if (!is_array($item)||!validId($item['id']??null)||isset($seen[$item['id']])) fail('El respaldo contiene identificadores no válidos o repetidos.');
            $seen[$item['id']]=true;
        }
    }
    $gs=[]; foreach ($s['garments'] as $g) $gs[]=garment($g,$photos);
    $gm=indexById($gs); $os=[]; foreach ($s['outfits'] as $o) $os[]=outfit($o,$gm,$photos);
    $om=indexById($os); $ws=[];
    foreach ($s['wears'] as $w) {
        if (!isset($w['items'])||!is_array($w['items'])||!array_is_list($w['items'])) fail('Historial no válido.');
        foreach ($w['items'] as $it) {
            if (!is_array($it)||!validId($it['id']??null)||!in_array($it['category']??null,categories(),true)) fail('Prenda histórica no válida.');
            strvalField($it,'name',120,true);
        }
        $w['garment_ids']=array_column($w['items'],'id'); strvalField($w,'outfit_name',120);
        $ws[]=wear($w,$gm,$om,$w);
    }
    return ['garments'=>$gs,'outfits'=>$os,'wears'=>$ws];
}
