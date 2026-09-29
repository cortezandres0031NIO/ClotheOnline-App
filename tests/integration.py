#!/usr/bin/env python3
"""Tests HTTP aislados. No leen ni modifican private-local ni config.php."""
import argparse, pathlib, tempfile, subprocess, os, time, json, urllib.request, urllib.error, http.cookiejar, uuid, zipfile, io, zlib, struct, hashlib
p=argparse.ArgumentParser();p.add_argument('--php',required=True);p.add_argument('--work',required=True);p.add_argument('--port',type=int,default=8766);p.add_argument('--keep',action='store_true');args=p.parse_args()
app=pathlib.Path(__file__).resolve().parents[1]; work=pathlib.Path(args.work).resolve(); work.mkdir(parents=True,exist_ok=True)
private=pathlib.Path(tempfile.mkdtemp(prefix='armario-test-',dir=work)); origin=f'http://127.0.0.1:{args.port}'
config=private/'config.php';config.write_text("<?php return "+"['environment'=>'local','origin'=>"+repr(origin)+",'storage'=>"+repr(str(private))+",'dsn'=>"+repr('sqlite:'+str(private/'test.sqlite'))+",'timezone'=>'America/Managua'];")
env=dict(os.environ,ARMARIO_CONFIG=str(config));php=str(pathlib.Path(args.php).resolve());subprocess.run([php,str(app/'bin/install.php'),'--local'],env=env,check=True,stdout=subprocess.DEVNULL)
log=open(private/'server.log','w');server=subprocess.Popen([php,'-d','upload_max_filesize=128M','-d','post_max_size=132M','-d','memory_limit=512M','-S',f'127.0.0.1:{args.port}','-t',str(app/'public'),str(app/'bin/router.php')],env=env,stdout=log,stderr=log,start_new_session=True)
class Client:
 def __init__(self):self.opener=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()));self.csrf=''
 def request(self,action,body=None,files=None,csrf=True):
  headers={};raw=None
  if body is not None:raw=json.dumps(body).encode();headers['Content-Type']='application/json'
  if files:
   boundary=uuid.uuid4().hex;parts=[]
   for field,(name,content,typ) in files.items():parts.append(f'--{boundary}\r\nContent-Disposition: form-data; name="{field}"; filename="{name}"\r\nContent-Type: {typ}\r\n\r\n'.encode()+content+b'\r\n')
   raw=b''.join(parts)+f'--{boundary}--\r\n'.encode();headers['Content-Type']='multipart/form-data; boundary='+boundary
  if raw is not None and csrf:headers['X-CSRF-Token']=self.csrf;headers['Origin']=origin
  req=urllib.request.Request(origin+'/api.php?action='+action,raw,headers)
  try:r=self.opener.open(req);code=r.status
  except urllib.error.HTTPError as e:r=e;code=e.code
  content=r.read();typ=r.headers.get('Content-Type','');out=json.loads(content) if 'application/json' in typ else content
  return code,out,r.headers
 def session(self):code,s,_=self.request('session');assert code==200,(code,s);self.csrf=s['csrf'];return s
 def ok(self,action,body=None,files=None):code,v,_=self.request(action,body,files);assert code==200,(action,code,v);return v
c=Client();checks=[]
def check(name,condition):assert condition,name;checks.append(name)
def png(w=2300,h=1200):
 def chunk(t,d):return struct.pack('!I',len(d))+t+d+struct.pack('!I',zlib.crc32(t+d)&0xffffffff)
 raw=(b'\x00'+b'\x28\x49\xab'*w)*h
 return b'\x89PNG\r\n\x1a\n'+chunk(b'IHDR',struct.pack('!2I5B',w,h,8,2,0,0,0))+chunk(b'IDAT',zlib.compress(raw))+chunk(b'IEND',b'')
def save(kind,item,revision=None):return c.ok('save',{'kind':kind,'item':item,'revision':c.ok('state')['revision'] if revision is None else revision})['id']
def makezip(b,extras={}):
 out=io.BytesIO()
 with zipfile.ZipFile(out,'w',zipfile.ZIP_DEFLATED) as z:
  for name,value in b.items():z.writestr(name,value)
  for name,value in extras.items():z.writestr(name,value)
 return out.getvalue()
try:
 for _ in range(50):
  try:s=c.session();break
  except OSError:time.sleep(.1)
 check('Inicialización local sin registro público',s['setup'] is True)
 check('API privada sin sesión',c.request('state')[0]==401)
 check('CSRF obligatorio',c.request('setup',{'password':'prueba-armario-privada'},csrf=False)[0]==403)
 c.ok('setup',{'password':'prueba-armario-privada'});c.session()
 check('No se puede crear una segunda cuenta',c.request('setup',{'password':'otra-clave-privada'})[0]==403)
 check('Armario personal empieza vacío',c.ok('state')['garments']==[])
 image=png();(private/'foto-prueba.png').write_bytes(image)
 pid=c.ok('upload',files={'photo':('prueba.png',image,'image/png')})['id']
 code,jpg,headers=c.request('photo&id='+pid)
 check('Foto optimizada a JPEG y acceso autenticado',code==200 and jpg.startswith(b'\xff\xd8') and len(jpg)<len(image)*20)
 check('Fotos sin caché pública','no-store' in headers['Cache-Control'])
 anon=Client();anon.session();check('Foto directa bloqueada sin sesión',anon.request('photo&id='+pid)[0]==401)
 check('Se rechazan archivos disfrazados de foto',c.request('upload',files={'photo':('x.jpg',b'<?php echo 123;?>','image/jpeg')})[0]==422)
 today=s['today'];month=today[:7];g={'name':'Camisa de prueba','category':'Camisas','color':'Azul','notes':'Solo prueba aislada','photo':pid,'purchase_date':month+'-01','store':'Tienda prueba','price_minor':2500,'currency':'USD','archived':False}
 gid=save('garments',g);g['id']=gid
 g2={**g,'name':'Pantalón de prueba','category':'Pantalones','price_minor':180000,'currency':'NIO'};del g2['id'];gid2=save('garments',g2);g2['id']=gid2
 check('Prendas con compra en dos monedas',len(c.ok('state')['garments'])==2)
 # Optional structured fields upgrade existing JSON records without a SQL migration.
 g.update(size='Talla antigua',tags=['Personal']);save('garments',g)
 g2.update(size='42',pants_type='Cargo',tags=[]);save('garments',g2)
 legacy={k:v for k,v in g.items() if k not in ['size','tags']};save('garments',legacy)
 stored=c.ok('state')['garments'][0]
 check('Cliente antiguo conserva talla y etiquetas existentes',stored['size']=='Talla antigua' and stored['tags']==['Personal'])
 check('Tipo de pantalón estructurado guardado',c.ok('state')['garments'][1]['pants_type']=='Cargo')
 check('No se infiere tipo de pantalón',stored['pants_type']=='')
 invalid={**g2,'pants_type':'Inventado'}
 check('Tipo de pantalón no válido rechazado',c.request('save',{'kind':'garments','item':invalid,'revision':c.ok('state')['revision']})[0]==422)
 check('Etiquetas malformadas rechazadas',c.request('save',{'kind':'garments','item':{**g,'tags':'Casual'},'revision':c.ok('state')['revision']})[0]==422)

 bad={**g,'price_minor':25.5};check('Precios exactos: se rechazan fracciones de unidad mínima',c.request('save',{'kind':'garments','item':bad,'revision':c.ok('state')['revision']})[0]==422)
 oid=save('outfits',{'name':'Oficina de prueba','notes':'','photo':None,'garment_ids':[gid,gid2]})
 check('Crear outfit no registra uso',len(c.ok('state')['wears'])==0)
 check('Outfit anterior sin etiquetas permanece sin etiquetar',c.ok('state')['outfits'][0]['tags']==[])
 save('outfits',{'id':oid,'name':'Oficina de prueba','notes':'','photo':None,'garment_ids':[gid,gid2],'tags':['Casual','Para la casa','Mi etiqueta','casual']})
 check('Varias etiquetas, personalizadas y sin duplicados',c.ok('state')['outfits'][0]['tags']==['Casual','Para la casa','Mi etiqueta'])

 wid=save('wears',{'date':today,'notes':'Primer uso','outfit_id':oid,'garment_ids':[gid,gid2]})
 old=c.ok('state')['wears'][0]
 save('outfits',{'id':oid,'name':'Oficina modificada','notes':'','photo':None,'garment_ids':[gid2]})
 check('Editar outfit no modifica historial',c.ok('state')['wears'][0]==old)
 check('Cliente antiguo conserva etiquetas del outfit',c.ok('state')['outfits'][0]['tags']==['Casual','Para la casa','Mi etiqueta'])
 save('wears',{'id':wid,'date':month+'-02','notes':'Corregido','outfit_id':oid,'garment_ids':[gid]})
 state=c.ok('state');check('Corregir uso cambia fecha y prendas sin cambiar outfit',state['wears'][0]['date']==month+'-02' and len(state['wears'][0]['items'])==1 and state['outfits'][0]['garment_ids']==[gid2])
 save('wears',{'date':today,'notes':'Segundo uso','outfit_id':oid,'garment_ids':[gid]})
 g['archived']=True;save('garments',g)
 state=c.ok('state');check('Archivar conserva usos y foto',state['garments'][0]['archived'] and len(state['wears'])==2 and state['garments'][0]['photo']==pid)
 counts={x['id']:sum(any(it['id']==x['id'] for it in w['items']) for w in state['wears']) for x in state['garments']}
 check('Contadores tras corrección: 2 usos y prenda nunca usada',counts=={gid:2,gid2:0})
 check('Costo histórico esperado: 12,50 USD por uso',g['price_minor']/counts[gid]==1250)
 stale=state['revision'];g['notes']='Cambio simultáneo';save('garments',g)
 check('Conflicto entre dispositivos no sobrescribe cambios',c.request('save',{'kind':'garments','item':g,'revision':stale})[0]==409)
 code,backup,_=c.request('export');check('Exportación ZIP con fotos',code==200 and backup.startswith(b'PK'))
 (private/'respaldo-prueba.zip').write_bytes(backup)
 with zipfile.ZipFile(io.BytesIO(backup)) as z:files={name:z.read(name) for name in z.namelist()};exported=json.loads(files['data.json'])
 check('Respaldo excluye contraseña y sesiones',set(files)=={'data.json','photos/'+pid+'.jpg'})
 before=c.ok('state');badzip=makezip(files,{'../owner.json':b'{}'})
 check('Respaldo con rutas peligrosas rechazado',c.request('preview-import',files={'backup':('bad.zip',badzip,'application/zip')})[0]==422)
 badb=json.loads(files['data.json']);badb['data']['outfits'][0]['garment_ids']=['a'*32];bf={**files,'data.json':json.dumps(badb).encode()}
 check('Respaldo con referencias rotas rechazado',c.request('preview-import',files={'backup':('bad.zip',makezip(bf),'application/zip')})[0]==422)
 check('Importación inválida no cambia datos',c.ok('state')==before)
 oldbackup=json.loads(files['data.json'])
 for item in oldbackup['data']['garments']+oldbackup['data']['outfits']:
  for key in ['size','pants_type','tags']:item.pop(key,None)
 oldfiles={**files,'data.json':json.dumps(oldbackup).encode()}
 check('Respaldo anterior sin campos nuevos compatible',c.request('preview-import',files={'backup':('old.zip',makezip(oldfiles),'application/zip')})[0]==200)

 # Restaurar primero vacío y luego completo: verifica una instalación sin registros.
 empty=makezip({'data.json':json.dumps({'format':'mi-armario','version':1,'data':{'garments':[],'outfits':[],'wears':[]}}).encode()})
 preview=c.ok('preview-import',files={'backup':('empty.zip',empty,'application/zip')})
 c.ok('restore',{'token':preview['token'],'revision':c.ok('state')['revision']});check('Restauración vacía correcta',c.ok('state')['garments']==[])
 preview=c.ok('preview-import',files={'backup':('backup.zip',backup,'application/zip')});check('Vista previa cuenta datos y fotos',preview['garments']==2 and preview['wears']==2 and preview['photos']==1)
 c.ok('restore',{'token':preview['token'],'revision':c.ok('state')['revision']});restored=c.ok('state');restored.pop('revision');expected=exported['data'];newpid=restored['garments'][0]['photo']
 check('Foto restaurada idéntica',c.request('photo&id='+newpid)[1]==files['photos/'+pid+'.jpg'])
 for item in restored['garments']+restored['outfits']:
  if item['photo']:item['photo']=pid
 check('Restauración conserva todos los datos',restored==expected)
 check('Copia de recuperación disponible',c.request('recovery')[0]==200)
 state=c.ok('state');c.ok('delete-wear',{'id':state['wears'][0]['id'],'revision':state['revision']});check('Eliminar uso recalcula fuente de estadísticas',len(c.ok('state')['wears'])==1)
 # Reponer fixture completo para verificación visual aislada.
 preview=c.ok('preview-import',files={'backup':('backup.zip',backup,'application/zip')});c.ok('restore',{'token':preview['token'],'revision':c.ok('state')['revision']})
 (private/'state-for-stats.json').write_text(json.dumps(c.ok('state')))
 c.ok('logout',{});check('Cerrar sesión revoca acceso',c.request('state')[0]==401)
 c.session();c.ok('login',{'password':'prueba-armario-privada'});check('Inicio de sesión recupera datos',len(c.ok('state')['garments'])==2)
 check('No se sirve la configuración privada',urllib.request.urlopen(origin+'/').status==200)
 for path in ['/app/config.php','/private-local/owner.json','/../app/config.php']:
  try:r=urllib.request.urlopen(origin+path);assert r.status!=200,path
  except urllib.error.HTTPError as e:assert e.code==404,path
 check('Archivos privados fuera del directorio público',True)
 report={'passed':len(checks),'checks':checks,'test_directory':str(private),'server_pid':server.pid,'origin':origin}
 (work/'integration-report.json').write_text(json.dumps(report,ensure_ascii=False,indent=2));print(json.dumps(report,ensure_ascii=False,indent=2))
finally:
 if not args.keep:server.terminate();server.wait(timeout=5)
 log.close()
