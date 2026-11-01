"""Main layout contract: real PHP requests plus software DOM execution (not browser visual QA)."""
import hashlib,json,os,re,shutil,socket,subprocess,tempfile,time
from pathlib import Path
import requests
from bs4 import BeautifulSoup
ROOT=Path(__file__).resolve().parents[1]
PHP=os.environ.get('NADIN_PHP','php')
checks=0

def ok(value,label):
 global checks
 assert value,label
 checks+=1

def fields(response):
 form=BeautifulSoup(response.text,'html.parser').find('form')
 assert form is not None
 return {e['name']:e.get('value','') for e in form.select('input[type=hidden][name]')}

with tempfile.TemporaryDirectory(prefix='nadin-gig-layout-') as temp:
 temp=Path(temp);app=temp/'band/nadin'
 shutil.copytree(ROOT,app,ignore=shutil.ignore_patterns('.git'))
 config=app/'config.inc.php';original=config.read_text().replace("$salt='???'","$salt='layout-fixture'")
 original=original.replace("$offerrcmdmp3saszip='yes'","$offerrcmdmp3saszip='no'").replace("$offersheetsaszip='yes'","$offersheetsaszip='no'")
 (app/'abo/zip').mkdir(parents=True,exist_ok=True)
 rights='vgigs=1,varchiv=1,vlibrary=1,vsaxes=1,'
 (app/'userdatei/userdatei.txt').write_text('Member,member@example.invalid,31.12.2099,'+rights+'\nAdmin,admin@example.invalid,31.12.2099,'+rights+'mgigs=1,musers=1,\n')
 tune=app/'library/Layout_Tune';tune.mkdir(exist_ok=True);(tune/'Layout_Tune_SXS.pdf').write_bytes(b'%PDF-1.4\nfixture\n%%EOF')
 for folder in ['gigreps','gigrepsarchiv']:
  for name in ['Layout One.txt','Layout Two.txt']:(app/folder/name).write_text('Fixture\n* - * - * - * * * - * - * - *\nStart:\nLayout_Tune\n')
 config.write_text(original)
 with socket.socket() as s:s.bind(('127.0.0.1',0));port=s.getsockname()[1]
 log=open(temp/'server.log','w+')
 proc=subprocess.Popen([PHP,'-d','opcache.enable=0','-d','opcache.enable_cli=0','-S',f'127.0.0.1:{port}','-t',str(temp)],stdout=log,stderr=log)
 base=f'http://127.0.0.1:{port}/band/nadin'
 cases=[]
 try:
  for _ in range(100):
   try:
    if requests.get(base+'/login.php',timeout=2).status_code==200:break
   except requests.ConnectionError:pass
   time.sleep(.04)
  else:raise AssertionError('PHP did not start')
  for role in ['member','admin']:
   session=requests.Session();data=fields(session.get(base+'/login.php'))
   user=role+'@example.invalid';data.update(u=user,p=hashlib.sha1((user+'layout-fixture').encode()).hexdigest()[7:13])
   response=session.post(base+'/login2.php',data=data,allow_redirects=False)
   ok(response.status_code in [302,303],'fixture login works: '+role)
   for side in ['yes','no','missing','','auto']:
    for width in [240,375]:
     text=re.sub(r"(?m)^\$rendergiglistontheleft\s*=.*$",'' if side=='missing' else "$rendergiglistontheleft='"+side+"';",original)
     text=re.sub(r"(?m)^(\$ifleftthenthiswidth\s*=\s*)'[^']*'",r"\g<1>'"+str(width)+"px'",text)
     config.write_text(text)
     for module in ['gigreps','gigrepsarchiv']:
      response=session.get(base+'/',params={'m':module,'c':'Layout One.txt'})
      soup=BeautifulSoup(response.text,'html.parser');modern=soup.select_one('.nadin-layout') is not None
      ok(response.status_code==200 and soup.find(id='includes') is not None,'gig view opens: '+module+'/'+side+'/'+role)
      nav=soup.select_one('[data-sidebar]') if modern else soup.find(id='nadin-gig-list')
      ok(nav is not None and 'Layout One' in nav.get_text().replace('\xa0', ' '),'actual configured gig list is present')
      if modern:
       ok(('nadin-gig-list-top' in soup.select_one('.nadin-layout').get('class',[]))==(side=='no'),'PHP applies config orientation before scripts')
       ok('--nadin-sidebar-width:'+str(width)+'px' in soup.select_one('.nadin-layout')['style'],'configured desktop width is retained')
      else:
       ok((nav.find('br') is not None)==(side!='no'),'PHP renders vertical separators without JavaScript')
      cases.append({'html':response.text,'side':side,'width':width,'module':module,'role':role,'modern':modern})
  (temp/'cases.json').write_text(json.dumps(cases))
  script=ROOT/'tests/gig-layout-dom.cjs'
  env=dict(os.environ,NADIN_LAYOUT_JS=str(app/'nadinmodule/layout.js'))
  subprocess.run(['node',str(script),str(temp/'cases.json')],env=env,check=True)
  log.flush();log.seek(0);text=log.read();ok('Fatal error' not in text and 'Parse error' not in text,'no PHP errors during layout switching')
  print(f'PASS: {checks} Main layout HTTP checks')
 finally:proc.terminate();proc.wait(timeout=5);log.close()
