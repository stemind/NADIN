"""Real-browser form encoding through HTTP, in a disposable NADIN installation."""
import hashlib, io, json, os, re, shutil, socket, subprocess, tempfile, time, zipfile
from pathlib import Path
from urllib.parse import quote_from_bytes, urljoin, urlencode, parse_qs, urlsplit
import requests
from bs4 import BeautifulSoup
ROOT=Path(__file__).resolve().parents[1]
PHP=os.environ.get('NADIN_PHP','php'); checks=0; failures=[]
def ok(value,label):
 global checks
 checks+=1
 if not value:failures.append(label);print('FAIL:',label,flush=True)
def soup(response,label):
 ok(response.status_code==200,label+' status')
 try:text=response.content.decode('utf-8-sig')
 except UnicodeDecodeError as error:
  ok(False,label+' UTF-8 bytes');print(repr(response.content[max(0,error.start-100):error.end+100]));text=response.content.decode('utf8',errors='replace')
 else:ok(True,label+' UTF-8 bytes')
 return BeautifulSoup(text,'html.parser')
with tempfile.TemporaryDirectory(prefix='nadin-encoding-http-') as tmp:
 tmp=Path(tmp);app=tmp/'nadin';shutil.copytree(ROOT,app,ignore=shutil.ignore_patterns('.git'))
 config=app/'config.inc.php';config.write_bytes(re.sub(rb"\$salt='[^']*'",b"$salt='encoding-fixture'",config.read_bytes()))
 rights='vgigs,vsaxes,vbones,vtrumpets,vrhythm,vother,vscore,vreclist,varchiv,vlibrary,upaudio,uppdf,mgigs,musers'.split(',')
 (app/'userdatei/userdatei.txt').write_bytes(b'M\xfcller,admin@example.invalid,31.12.2099,'+','.join(r+'=1' for r in rights).encode()+b'\n')
 separator=b'* - * - * - * * * - * - * - *'
 names=[(b'\xc4ltere_M\xfcller','Ältere_Müller'),('Österreich_Grüsse'.encode(),'Österreich_Grüsse')]
 old_notes=b'\x84Gr\xfcezi\x93 \x96 25 \x80; B\xe4r & '+ 'Été'.encode()
 os.makedirs(app/'abo/zip',exist_ok=True)
 for raw,label in names:
  folder=os.fsencode(app/'library')+b'/'+raw;os.mkdir(folder)
  for filename,content in [(raw+b'_SXS.pdf',b'%PDF-encoding'),(b'Gr\xfcsse.mp3',b'fixture'),(b'infos.txt',old_notes+b'\n'+separator+b'\nhttps://example.invalid/')]:
   with open(folder+b'/'+filename,'wb') as f:f.write(content)
 gig=b'2026-11-01 Z\xfcrich.txt';gig_path=os.fsencode(app/'gigreps')+b'/'+gig
 with open(gig_path,'wb') as f:f.write(old_notes+b'\n'+separator+b'\n'+b'\n'.join(n[0] for n in names))
 with open(os.fsencode(app/'abo/zip')+b'/'+gig[:-4]+b'.pdf','wb') as f:f.write(b'%PDF-attachment')
 with socket.socket() as sock:sock.bind(('127.0.0.1',0));port=sock.getsockname()[1]
 log=open(tmp/'server.log','w+')
 server=subprocess.Popen([PHP,'-S',f'127.0.0.1:{port}','-t',str(tmp)],stdout=log,stderr=log)
 base=f'http://127.0.0.1:{port}/nadin/'
 try:
  for _ in range(100):
   try:
    if requests.get(base+'login.php').status_code==200:break
   except requests.ConnectionError:pass
   time.sleep(.04)
  session=requests.Session(); page=BeautifulSoup(session.get(base+'login.php').text,'html.parser')
  fields={x['name']:x.get('value','') for x in page.select('form input[type=hidden]')}
  fields.update(u='admin@example.invalid',p=hashlib.sha1(b'admin@example.invalidencoding-fixture').hexdigest()[7:13])
  login=session.post(base+'login2.php',data=fields);ok(login.status_code==200,'login')
  routes=['index.php?m=library','index.php?m=gigreps&c='+quote_from_bytes(gig),'nadinmodule/library.print.php','nadinmodule/gigreps.print.php?m=gigreps&c='+quote_from_bytes(gig)]
  for route in routes:
   page=soup(session.get(base+route),route)
   for _,label in names:ok(label in page.get_text(),route+' displays '+label)
   ok('MÃ¼ller' not in page.get_text() and '\ufffd' not in page.get_text(),route+' has no broken umlauts')
  page=soup(session.get(base+'index.php?m=library'),'library alphabet')
  letters=[x.get('data-library-letter') for x in page.select('[data-library-letter]')]
  if letters:ok('Ä' in letters and 'Ö' in letters,'alphabet keeps complete umlauts')
  for route in ['nadinmodule/library.xls.php','nadinmodule/gigreps.xls.php?m=gigreps&c='+quote_from_bytes(gig)]:
   r=session.get(base+route);ok(r.status_code==200,'CSV status');text=r.content.decode('utf-8-sig')
   ok(r.content.startswith(b'\xef\xbb\xbf'),'CSV UTF-8 BOM')
   for _,label in names:ok(label in text,'CSV label '+label)
   ok('„Grüezi“ – 25 €; Bär & Été'.replace(';',',.') in text,'CSV mixed-encoding notes')
  raw,label=names[0]
  for route in ['upload.php','uploadaudio.php','uploadpdf.php','renamefolder.php','deletefolder.php']:
   page=soup(session.get(base+'nadinmodule/'+route+'?tune='+quote_from_bytes(raw)),route)
   ok(label.replace('_',' ') in page.get_text(),route+' readable tune title')
   for frame in page.select('iframe[src]'):
    result=session.get(urljoin(base+'nadinmodule/'+route,frame['src']));soup(result,route+' child frame')
  page=soup(session.get(base+'nadinmodule/upload.php?tune='+quote_from_bytes(raw)),'notes editor')
  ok(page.find('textarea',attrs={'name':'infos'}).get_text().strip()=='„Grüezi“ – 25 €; Bär & Été','notes textarea decodes legacy text exactly')
  form=page.find('form',attrs={'name':'form'})
  data={x['name']:x.get('value','') for x in form.select('input[name]')};data.update(infos='Grüsse für Müller – déjà vu €',links='https://example.invalid/')
  # Execute the server-issued CSRF/revision values normally added by legacy-ui.js.
  token=re.search(r'var token=("[^"\n]*")',str(page));revision=re.search(r'var revision=("[^"\n]*")',str(page))
  if token:data['csrf']=json.loads(token[1])
  if revision:data['revision']=json.loads(revision[1])
  # A visually identical UTF-8 directory must never receive this legacy form's write.
  collision=app/'library'/label;collision.mkdir();(collision/'infos.txt').write_text('DO NOT TOUCH')
  saved=session.post(urljoin(base+'nadinmodule/upload.php',form['action']),data=data);ok(saved.status_code==200,'notes save status')
  with open(os.fsencode(app/'library')+b'/'+raw+b'/infos.txt','rb') as f:stored=f.read()
  ok(data['infos'].encode() in stored,'notes saved under exact original legacy directory')
  ok((collision/'infos.txt').read_text()=='DO NOT TOUCH','similarly displayed UTF-8 directory untouched')
  page=soup(session.get(base+'nadinmodule/upload.php?tune='+quote_from_bytes(raw)),'reopened notes')
  ok(page.find('textarea',attrs={'name':'infos'}).get_text().strip()==data['infos'],'notes save/reopen is not double encoded')
  for kind in ['PDF','AUD']:
   page=soup(session.get(base+'nadinmodule/'+kind+'sendfile.php?name='+quote_from_bytes(raw)),kind+' upload form')
   inputs={x['name']:x.get('value','') for x in page.select('input[type=hidden][name]')}
   ok(inputs.get('name')==quote_from_bytes(raw,safe='') and inputs.get('_nadin_path_encoding[name]')=='url',kind+' upload target retains raw bytes')
  # Media mutation URLs pass through the same URLSearchParams operation as the browser.
  if (app/'nadinmodule/media-list.inc.php').exists():
   page=soup(session.get(base+'nadinmodule/AUDfiles.php?name='+quote_from_bytes(raw)),'audio file list')
   script=r"""const {JSDOM,VirtualConsole}=require('jsdom');const fs=require('fs');let posted;
const dom=new JSDOM(fs.readFileSync(0,'utf8'),{url:process.argv[1],runScripts:'dangerously',virtualConsole:new VirtualConsole(),beforeParse(w){w.prompt=()=> 'Erneuert.mp3';w.HTMLFormElement.prototype.submit=function(){posted={url:this.action,data:Object.fromEntries(new w.FormData(this))}}}});
dom.window.document.querySelector('button[data-rename]').click();console.log(JSON.stringify(posted));dom.window.close();"""
   submitted=json.loads(subprocess.check_output(['node','-e',script,base+'nadinmodule/AUDfiles.php'],input=str(page).encode()))
   result=session.post(submitted['url'],data=submitted['data'])
   ok(result.status_code==200 and os.path.exists(os.fsencode(app/'library')+b'/'+raw+b'/Erneuert.mp3'),'actual JavaScript rename preserves legacy folder and filename bytes')
  if (app/'nadinmodule/admin.inc.php').exists():
   api=base+'nadinmodule/admin-api.php';r=session.get(api,params={'area':'gigs'});ok(r.status_code==200,'admin JSON status');payload=r.json()
   ok(any(x['title']=='2026-11-01 Zürich' for x in payload['gigs']),'admin gig name')
   r=session.get(api,params={'area':'people'});ok(any(x['name']=='Müller' for x in r.json()['rows']),'admin legacy person name')
  (collision/'infos.txt').unlink();collision.rmdir()
  if (app/'nadinmodule/admin.inc.php').exists():
   api=base+'nadinmodule/admin-api.php'
   details=session.get(api,params={'area':'gigs','action':'gig','id':'2026-11-01 Zürich.txt'}).json()['gig']
   ok(details['infos']=='„Grüezi“ – 25 €; Bär & Été','JSON editor preserves mixed encodings')
   token=session.get(api,params={'area':'gigs','action':'token'}).json()['csrf']
   result=session.post(api,json=dict(details,area='gigs',action='save',csrf=token,infos='Neue Grüsse aus Zürich'))
   ok(result.status_code==200,'JSON editor saves umlaut gig')
  elif (app/'nadinmodule/composer.php').exists():
   page=soup(session.get(base+'nadinmodule/composer.php?gig='+quote_from_bytes(gig)),'legacy gig editor')
   select=page.find('select',attrs={'name':'gig'});option=select.find('option',selected=True)
   fields={'gig':option['value'],'infos':'Neue Grüsse aus Zürich','tunes':'\r\n'.join(n[1] for n in names)}
   fields.update({x['name']:x.get('value','') for x in page.select('input[type=hidden][name]')})
   result=session.post(base+'nadinmodule/savegig.php',data=fields)
   ok(result.status_code==200,'legacy gig editor saves umlaut gig')
  with open(gig_path,'rb') as f:content=f.read()
  ok('Neue Grüsse aus Zürich'.encode() in content,'gig information saves as UTF-8')
  ok(all(n[0] in content.split(separator,1)[1] for n in names),'saving gig retains exact tune-reference bytes')
  # Download attachment bytes are untouched; UTF-8 filename labels are valid.
  if (app/'get/setlist.php').exists():
   r=session.get(base+'get/setlist.php?m=gigreps&c='+quote_from_bytes(gig[:-4]+b'.pdf'))
   ok(r.content==b'%PDF-attachment','PDF attachment unchanged')
   ok("filename*=UTF-8''2026-11-01%20Z%C3%BCrich.pdf" in r.headers.get('Content-Disposition',''),'PDF download name declared and encoded UTF-8')
  r=session.get(base+'get/file.php?c='+quote_from_bytes(raw+b'/'+raw+b'_SXS.pdf'))
  ok(r.content==b'%PDF-encoding','sheet music download retains original path and bytes')
  if "filename*=UTF-8''" in r.headers.get('Content-Disposition',''):
   ok('%C3%84ltere_M%C3%BCller_SXS.pdf' in r.headers['Content-Disposition'],'sheet music filename is UTF-8')
  if not (app/'nadinmodule/zip.inc.php').exists():
   for kind in ['alllistento','allscore','allparts','allsaxes','allbones','alltrumpets','allrhythm','allother']:(app/'abo/zip'/kind).mkdir(exist_ok=True)
   session.get(base+'preview.php?gig='+quote_from_bytes(gig)+'&zip=1')
  result=session.get(base+'get/zip.php?c='+quote_from_bytes(b'zip/allsaxes/'+gig[:-4]+b'_allsaxes.zip'))
  ok(result.status_code==200 and result.content[:2]==b'PK','ZIP download generated for umlaut gig')
  if result.content[:2]==b'PK':
   with zipfile.ZipFile(io.BytesIO(result.content)) as archive:
    for _,label in names:
     matches=[x for x in archive.namelist() if x.endswith(label+'_SXS.pdf')]
     ok(len(matches)==1 and archive.read(matches[0])==b'%PDF-encoding','ZIP entry names and contents: '+label)
  empty=b'L\xf6schbar';os.mkdir(os.fsencode(app/'library')+b'/'+empty)
  page=soup(session.get(base+'nadinmodule/deletefolder.php?tune='+quote_from_bytes(empty)),'delete empty legacy folder')
  form=page.find('form',attrs={'name':'form'});data={x['name']:x.get('value','') for x in form.select('input[name]')}
  if '_nadin_path_encoding[tune]' not in data:data['tune']='Löschbar'
  token=re.search(r'var token=("[^"\n]*")',str(page))
  if token:data['csrf']=json.loads(token[1])
  result=session.post(urljoin(base+'nadinmodule/deletefolder.php',form['action']),data=data)
  ok(result.status_code==200 and not os.path.isdir(os.fsencode(app/'library')+b'/'+empty),'explicit delete addresses original legacy directory')
  log.flush();log.seek(0);errors=log.read();ok('Fatal error' not in errors and 'Parse error' not in errors,'no PHP fatal errors')
  print(f'{"FAIL" if failures else "PASS"}: {checks} HTTP encoding checks',flush=True)
  if failures:raise AssertionError('; '.join(failures))
 finally:server.terminate();server.wait(timeout=5);log.close()
