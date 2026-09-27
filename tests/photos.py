"""Prueba de orientación y optimización; requiere Pillow solo para las pruebas."""
from PIL import Image,ImageOps
import pathlib,tempfile,subprocess,json,sys
app=pathlib.Path(__file__).resolve().parents[1];php=str(pathlib.Path(sys.argv[1]).resolve());work=pathlib.Path(sys.argv[2]).resolve();d=pathlib.Path(tempfile.mkdtemp(prefix='photos-test-',dir=work));(d/'photos').mkdir();config=d/'config.php';config.write_text("<?php return ['storage'=>"+repr(str(d))+",'timezone'=>'America/Managua'];")
base=Image.new('RGB',(120,80));colors=[(230,30,20),(30,200,50),(40,50,210),(220,180,40),(180,40,170),(30,190,190)]
for i,c in enumerate(colors):base.paste(c,((i%3)*40,(i//3)*40,(i%3+1)*40,(i//3+1)*40))
import os
env=dict(os.environ,ARMARIO_CONFIG=str(config));checks=[]
for o in range(1,9):
 p=d/f'orientation-{o}.jpg';exif=Image.Exif();exif[274]=o;exif[270]='Metadatos de prueba';base.save(p,quality=98,exif=exif)
 code="require "+repr(str(app/'app/core.php'))+"; echo optimize("+repr(str(p))+");"
 ident=subprocess.check_output([php,'-r',code],env=env,text=True).strip();got=Image.open(d/'photos'/f'{ident}.jpg');expected=ImageOps.exif_transpose(Image.open(p));assert got.size==expected.size,(o,got.size,expected.size)
 for y in range(20,got.height,40):
  for x in range(20,got.width,40):assert max(abs(a-b) for a,b in zip(got.getpixel((x,y)),expected.getpixel((x,y))))<15,(o,x,y)
 assert len(got.getexif())==0
 checks.append(f'Orientación EXIF {o}: correcta y metadatos eliminados')
p=d/'large.png';Image.new('RGB',(2400,1200),(30,40,90)).save(p)
code="require "+repr(str(app/'app/core.php'))+"; echo optimize("+repr(str(p))+");";ident=subprocess.check_output([php,'-r',code],env=env,text=True).strip();got=Image.open(d/'photos'/f'{ident}.jpg');assert got.size==(1600,800);checks.append('Reducción 2400 × 1200 → 1600 × 800')
(work/'photo-report.json').write_text(json.dumps(checks,ensure_ascii=False,indent=2));print(json.dumps(checks,ensure_ascii=False,indent=2))
