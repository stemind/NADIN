"""Render the unchanged PHP views with synthetic data for DOM tests and visual previews.
Usage: NADIN_PHP=php python3 tests/render-preview.py OUTPUT_DIRECTORY
No production data or outgoing mail is used.
"""
from pathlib import Path
import requests,hashlib,shutil,subprocess,time,socket,html,json,sys,os
from bs4 import BeautifulSoup
root=Path(__file__).resolve().parents[1]
output=Path(sys.argv[1]).resolve()
if output==root or root in output.parents: raise SystemExit('Choose a preview directory outside the repository.')
app=output/'app';out=output/'views';out.mkdir(parents=True,exist_ok=True)
shutil.copytree(root,app,ignore=shutil.ignore_patterns('.git'),dirs_exist_ok=True)
p=app/'config.inc.php';p.write_text(p.read_text().replace("$salt='???'","$salt='preview-fixture-only'"))
rights='vgigs,vsaxes,vbones,vtrumpets,vrhythm,vother,vscore,vreclist,varchiv,vlibrary,upaudio,uppdf,mgigs,musers'.split(',')
(app/'userdatei/userdatei.txt').write_text('Demo Admin, demo@example.invalid, 31.12.2099, '+', '.join(r+'=1' for r in rights)+'\nDemo Sax, sax@example.invalid, 31.12.2099, vgigs=1, vsaxes=1, vreclist=1, vlibrary=1,\nDemo Trumpet, trumpet@example.invalid, 31.12.2099, vgigs=1, vtrumpets=1,\nDemo Substitute, substitute@example.invalid, 30.11.2026, vgigs=1, vsaxes=1,\n')
(app/'userdatei/mail-adapter.php').write_text('<?php function nadin_mail_transport($to,$subject,$body) { return true; }')
for f in (app/'gigreps').glob('*.txt'): f.unlink()
tunes=['Anna_Swings_Best','Berta_Has_The_Blues','David_Funks_The_City_Hall','Evening_In_Bern','Friday_Night_Groove','Good_Times_Ahead','Late_Night_Swing','Midnight_Train','On_The_Road','Saxophone_Serenade','The_Last_Dance','Uptown_Shuffle']
source=app/'library/Anna_Swings_Best'
for t in tunes[3:]:
 d=app/'library'/t;d.mkdir(exist_ok=True)
 for f in source.iterdir():
  if f.is_file() and not f.name.startswith('.'):
   shutil.copy2(f,d/f.name.replace('Anna_Swings_Best',t).replace('AnnaSwingsBest',t.replace('_','')))
# Illustrate a tune whose instrumental parts have not been split by section.
d=app/'library/On_The_Road'
for f in list(d.glob('*_SXS_*')): f.rename(d/f.name.replace('_SXS_','_PRTS_'))
for code in ['TBS','TPS','RTM']:
 for f in d.glob('*_'+code+'_*'): f.unlink()
reps=['2026-11-06 Herbstkonzert Bern','2026-11-13 Probe Gesamtband','2026-11-20 Jazznacht Thun','2026-11-27 Probe Rhythm Section','2026-12-04 Winterkonzert','2026-12-11 Probe Gesamtband','2026-12-18 Jahresabschluss','2027-01-15 Neujahrskonzert']
for i,name in enumerate(reps):
 selected=tunes if i==0 else tunes[i%4:]+tunes[:i%4]
 info='Treffpunkt 18:30 | Soundcheck 19:00 | Konzert 20:00\nKulturzentrum Bern - grosser Saal\nTenue: schwarz. Noten und Notenstaender mitbringen.\nSet 1: 20:00 - 20:45 | Pause | Set 2: 21:15 - 22:00'
 (app/'gigreps'/(name+'.txt')).write_text(info+'\n\n* - * - * - * * * - * - * - *\n'+'\n'.join(selected))
for kind in ['alllistento','allscore','allparts','allsaxes','allbones','alltrumpets','allrhythm','allother']:(app/'abo/zip'/kind).mkdir(parents=True,exist_ok=True)

with socket.socket() as sock:sock.bind(('127.0.0.1',0));port=sock.getsockname()[1]
log=open(app.parent/'render.log','w+')
server=subprocess.Popen([os.environ.get('NADIN_PHP','php'),'-S',f'127.0.0.1:{port}','-t',str(app)],stdout=log,stderr=log)
base=f'http://127.0.0.1:{port}'
try:
 for _ in range(100):
  try:
   if requests.get(base+'/login.php').status_code==200:break
  except requests.ConnectionError:pass
  time.sleep(.05)
 s=requests.Session();r=s.get(base+'/login.php');soup=BeautifulSoup(r.text,'html.parser');d={x['name']:x.get('value','') for x in soup.select('input[type=hidden][name]')};d.update(u='demo@example.invalid',p=hashlib.sha1(b'demo@example.invalidpreview-fixture-only').hexdigest()[7:13]);r=s.post(base+'/login2.php',data=d);assert 'demo@example.invalid' in r.text
 for f in app.rglob('*'):
  if f.is_file() and f.suffix.lower() in ['.css','.js','.gif','.png','.jpg','.ico','.svg']:
   dest=out/f.relative_to(app);dest.parent.mkdir(parents=True,exist_ok=True);shutil.copy2(f,dest)
 gig='2026-11-06 Herbstkonzert Bern.txt'
 urls={'gigs':'/index.php?m=gigreps','library':'/index.php?m=library','admin':'/index.php?m=managegigs','users':'/index.php?m=users','password':'/password.php','login':'/login.php','forscore':'/get/forscore.php?m=gigreps&c='+requests.utils.quote(gig)}
 for key,url in urls.items():
  r=s.get(base+url);assert r.status_code==200,(key,r.status_code);r.encoding='utf-8';p=r.text
  if key=='forscore':p=p.replace('href="../style.css"','href="style.css"')
  (out/(key+'.html')).write_text(p)
 # Capture the actual V13 locked response with synthetic credentials only.
 locked=requests.Session();r=locked.get(base+'/login.php');soup=BeautifulSoup(r.text,'html.parser');form={x['name']:x.get('value','') for x in soup.select('input[type=hidden][name]')};form.update(u='sax@example.invalid',p='incorrect-fixture-password')
 for _ in range(6):r=locked.post(base+'/login2.php',data=form)
 assert r.status_code==429
 r.encoding='utf-8';(out/'login-locked.html').write_text(r.text)
 for name,source,width,height in [('mobile','gigs',390,780),('admin-wide','admin',1440,850),('admin-mobile','admin',390,1300),('desktop','gigs',1280,780),('users-wide','users',1280,780),('password-view','password',640,720)]:
  (out/(name+'.html')).write_text('<html><head><meta charset="utf-8"></head><body style="margin:0;background:white"><iframe title="NADIN" src="'+source+'.html" style="display:block;width:'+str(width)+'px;height:'+str(height)+'px;border:0"></iframe></body></html>')
 print(out)
finally:
 server.terminate();server.wait(timeout=5);log.close()
