"""PHP 8.3/8.5 + strict future-error simulation, using disposable local data only."""
import hashlib, os, shutil, socket, subprocess, tempfile, time
from pathlib import Path
import requests
from bs4 import BeautifulSoup
ROOT=Path(__file__).resolve().parents[1]
PHP=os.environ.get('NADIN_PHP','php')
checks=0; failures=[]
def ok(value,label):
 global checks
 checks+=1
 if not value: failures.append(label); print('FAIL:',label,flush=True)
with tempfile.TemporaryDirectory(prefix='nadin-compat-') as tmp:
 tmp=Path(tmp);app=tmp/'nadin';shutil.copytree(ROOT,app,ignore=shutil.ignore_patterns('.git'))
 p=app/'config.inc.php';p.write_text(p.read_text().replace("$salt='???'","$salt='compat-fixture'"))
 rights='vgigs,vsaxes,vbones,vtrumpets,vrhythm,vother,vscore,vreclist,varchiv,vlibrary,upaudio,uppdf,mgigs,musers'.split(',')
 (app/'userdatei/userdatei.txt').write_text('Admin,admin@example.invalid,31.12.2099,'+','.join(x+'=1' for x in rights)+'\nMember,member@example.invalid,31.12.2099,vgigs=1,vsaxes=1,vlibrary=1,vreclist=1,\n')
 (app/'userdatei/mail-adapter.php').write_text('<?php function nadin_mail_transport($to,$subject,$body){return true;}')
 (app/'abo/zip').mkdir(exist_ok=True)
 for name in ['Empty','No_Info']:(app/'library'/name).mkdir()
 (app/'library/No_Info/No_Info_SXS.pdf').write_bytes(b'%PDF-fixture')
 separator='* - * - * - * * * - * - * - *'
 (app/'gigreps/Empty.txt').write_text('No tunes\n'+separator+'\n')
 (app/'gigreps/Compat.txt').write_text('No recordings or info files\n'+separator+'\nEmpty\nNo_Info')
 with socket.socket() as sock:sock.bind(('127.0.0.1',0));port=sock.getsockname()[1]
 log=open(tmp/'server.log','w+')
 server=subprocess.Popen([PHP,'-d','auto_prepend_file='+str(ROOT/'tests/runtime-guard.php'),'-S',f'127.0.0.1:{port}','-t',str(tmp)],stdout=log,stderr=log)
 base=f'http://127.0.0.1:{port}/nadin/'
 try:
  for _ in range(100):
   try:
    if requests.get(base+'login.php').status_code==200:break
   except requests.ConnectionError:pass
   time.sleep(.04)
  def login(email):
   s=requests.Session();soup=BeautifulSoup(s.get(base+'login.php').text,'html.parser');form={x['name']:x.get('value','') for x in soup.select('form input[type=hidden]')};form.update(u=email,p=hashlib.sha1((email+'compat-fixture').encode()).hexdigest()[7:13]);ok(s.post(base+'login2.php',data=form,allow_redirects=False).status_code==303,'login '+email);return s
  admin=login('admin@example.invalid');member=login('member@example.invalid')
  routes=['index.php','index.php?m=gigreps&c=Empty.txt','index.php?m=gigreps&c=Compat.txt','index.php?m=library','index.php?m=library&sort=newest','index.php?m=reclist','index.php?m=gigrepsarchiv','index.php?m=managegigs','index.php?m=users','password.php','preview.php?gig=Compat.txt','preview.php?gig=Empty.txt']
  routes += ['nadinmodule/'+x+'.php?tune=No_Info' for x in ['upload','uploadaudio','uploadpdf','renamefolder','deletefolder','managetune']]
  routes += ['nadinmodule/'+x+'.php?name=No_Info' for x in ['AUDfiles','PDFfiles','AUDsendfile','PDFsendfile']]
  routes += ['nadinmodule/'+x+'.php?gig=Compat' for x in ['uploadatt','ATTfiles','ATTsendfile']]
  routes += ['nadinmodule/new.php','nadinmodule/library.print.php','nadinmodule/library.xls.php']
  routes += ['nadinmodule/gigreps.'+kind+'.php?m=gigreps&c='+gig+'.txt' for kind in ['print','xls'] for gig in ['Empty','Compat']]
  for route in routes:
   r=admin.get(base+route);ok(r.status_code==200 and 'Fatal error' not in r.text and 'Uncaught ' not in r.text,route)
  for route in routes[:6]:
   r=member.get(base+route);ok(r.status_code==200 and 'Uncaught ' not in r.text,'member '+route)
  admin.cookies.set('scrlt',"</script><script>alert('cookie')</script>",path='/')
  r=admin.get(base+'index.php?m=library');ok(r.status_code==200 and "</script><script>alert('cookie')" not in r.text,'scroll preference cannot inject script')
  admin.cookies.pop('scrlt')
  ok('u' not in admin.cookies and 'p' not in admin.cookies,'media forms do not recreate retired password cookies')
  for route in ['index.php?m=users','index.php?m=managegigs','nadinmodule/upload.php?tune=No_Info','nadinmodule/uploadaudio.php?tune=No_Info','nadinmodule/uploadpdf.php?tune=No_Info','nadinmodule/uploadatt.php?gig=Compat']:
   ok(member.get(base+route).status_code==403,'permissions '+route)
  log.flush();log.seek(0);errors=log.read();ok('Uncaught ' not in errors and 'Fatal error' not in errors and 'Parse error' not in errors,'no simulated future errors in server log')
  if failures:print(errors[-12000:])
  print(f'{"FAIL" if failures else "PASS"}: {checks} PHP compatibility HTTP checks')
 finally:server.terminate();server.wait(timeout=5);log.close()
if failures:raise SystemExit(1)
