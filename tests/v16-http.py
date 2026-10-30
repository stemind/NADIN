"""V16 regressions found by comparing Main with V15: downloads, media editing and exports."""
import hashlib, io, json, os, re, shutil, socket, subprocess, tempfile, time, urllib.parse, zipfile
from pathlib import Path
import requests
from bs4 import BeautifulSoup
ROOT=Path(__file__).resolve().parents[1];PHP=os.environ.get('NADIN_PHP','php');checks=0

def ok(v,label):
 global checks
 assert v,label
 checks+=1
with tempfile.TemporaryDirectory(prefix='nadin-v16-http-') as temp:
 temp=Path(temp);app=temp/'nadin';shutil.copytree(ROOT,app,ignore=shutil.ignore_patterns('.git'))
 config=(app/'config.inc.php').read_text().replace("$salt='???'","$salt='fixture'");(app/'config.inc.php').write_text(config)
 rights='vgigs,vsaxes,vbones,vtrumpets,vrhythm,vother,vscore,vreclist,varchiv,vlibrary,upaudio,uppdf,mgigs,musers'.split(',');(app/'userdatei/userdatei.txt').write_text('Admin,admin@example.invalid,31.12.2099,'+','.join(x+'=1' for x in rights)+'\nMember,member@example.invalid,31.12.2099,vgigs=1,vsaxes=1,\nAudio,audio@example.invalid,31.12.2099,vreclist=1,\n')
 (app/'abo/zip').mkdir(exist_ok=True)
 separator='* - * - * - * * * - * - * - *';tune='Special[A]';d=app/'library'/tune;d.mkdir();(d/'Special_SXS & one.pdf').write_bytes(b'%PDF-fixture-one');(d/'Special_PRTS.pdf').write_bytes(b'%PDF-unsplit');(d/'Special_TPS.pdf').write_bytes(b'%PDF-trumpet')
 trumpet=app/'library/Trumpet_Only';trumpet.mkdir();(trumpet/'Solo_TPS.pdf').write_bytes(b'%PDF-only-trumpet')
 audio=next((app/'library/Anna_Swings_Best').glob('*RCMD*.mp3'));shutil.copy(audio,d/'Special_RCMD.mp3');shutil.copy(audio,d/'XYZ_2026-10-30_Take.mp3');(d/'XYZ_2026-10-30_Private_HDDN.mp3').write_bytes(b'hidden');(d/'XYZ_2026-10-30_NotAudio.pdf').write_bytes(b'%PDF-other')
 (app/'gigreps/Test.txt').write_text('Current <script>alert(1)</script> https://example.invalid/info?x=1&y=2\n'+separator+'\n'+tune+'\nTrumpet_Only');(app/'gigrepsarchiv/Test.txt').write_text('Archived\n'+separator+'\nTrumpet_Only');(app/'abo/zip/Test.pdf').write_bytes(b'%PDF-attachment')
 with socket.socket() as sock:sock.bind(('127.0.0.1',0));port=sock.getsockname()[1]
 log=open(temp/'server.log','w+');server=subprocess.Popen([PHP,'-S',f'127.0.0.1:{port}','-t',str(temp)],stdout=log,stderr=log);base=f'http://127.0.0.1:{port}/nadin'
 try:
  for _ in range(100):
   try:
    if requests.get(base+'/login.php').status_code==200:break
   except requests.ConnectionError:pass
   time.sleep(.04)
  def login(email):
   s=requests.Session();r=s.get(base+'/login.php');form=BeautifulSoup(r.text,'html.parser').find('form');data={x['name']:x.get('value','') for x in form.select('input[type=hidden]')};data.update(u=email,p=hashlib.sha1((email+'fixture').encode()).hexdigest()[7:13]);r=s.post(base+'/login2.php',data=data,allow_redirects=False);ok(r.status_code==303,'login '+email);return s
  admin=login('admin@example.invalid');member=login('member@example.invalid');listener=login('audio@example.invalid')
  r=admin.get(base+'/index.php?m=gigreps&c=Test.txt');soup=BeautifulSoup(r.text,'html.parser');ok(r.status_code==200 and soup.select_one('img[src="nadin.svg"]'),'V16 SVG used in actual page');ok(all(not h.has_attr('onclick') for h in soup.select('.nadin-tune-cell h5')),'tune headings no longer call retired composer');ok('Trumpet_Only' in r.text and [e.text.strip() for e in soup.select('.nadin-number')]==['1','2'],'trumpet-only tune numbered');ok(not soup.find('script',string=re.compile(r'^alert\(1\)')) and soup.select_one('a[href="https://example.invalid/info?x=1&y=2"]'),'gig info is escaped while links survive');ok('Fatal error' not in r.text and soup.select('form[action$="settime.php"]'),'untimed audio renders and uses POST duration form')
  filelink=next(x['href'] for x in soup.select('a[href*="get/file.php"]') if 'SXS' in x['href']);ok(admin.get(urllib.parse.urljoin(base+'/',filelink)).content==b'%PDF-fixture-one','brackets and ampersand download correctly')
  ziplink=next(x['href'] for x in soup.select('.nadin-zip-link') if 'allsaxes' in x['href']);r=member.get(urllib.parse.urljoin(base+'/',ziplink));ok(r.status_code==200 and int(r.headers['Content-Length'])==len(r.content),'ZIP streams exact length');z=zipfile.ZipFile(io.BytesIO(r.content));ok(z.namelist()==['Special[A]/Special_SXS & one.pdf'],'ZIP includes exact permitted section');ok('m=gigreps' in ziplink,'ZIP link carries explicit list')
  arch=admin.get(base+'/index.php?m=gigrepsarchiv&c=Test.txt');archsoup=BeautifulSoup(arch.text,'html.parser');archzip=next(x['href'] for x in archsoup.select('.nadin-zip-link') if 'alltrumpets' in x['href']);r=admin.get(urllib.parse.urljoin(base+'/',archzip));ok(zipfile.ZipFile(io.BytesIO(r.content)).namelist()==['Trumpet_Only/Solo_TPS.pdf'],'same-name archive gives its own setlist');ok(member.get(urllib.parse.urljoin(base+'/',archzip)).status_code==403,'archive ZIP denied even when current name exists')
  attached=archsoup.select_one('a[href*="get/setlist.php"]')['href'];ok('gigrepsarchiv' in attached and member.get(urllib.parse.urljoin(base+'/',attached)).status_code==403,'archive attachment link retains rights context');ok(admin.get(urllib.parse.urljoin(base+'/',attached)).content==b'%PDF-attachment','archive attachment works')
  (app/'config.inc.php').write_text(config.replace("$offersheetsaszip='yes'","$offersheetsaszip='no'").replace("$offerrcmdmp3saszip='yes'","$offerrcmdmp3saszip='no'"));ok(member.get(urllib.parse.urljoin(base+'/',ziplink)).status_code==403,'disabled sheet ZIP rejected server-side');ok(admin.get(base+'/get/zip.php?c=zip/alllistento/Test_alllistento.zip').status_code==403,'disabled audio ZIP rejected server-side');(app/'config.inc.php').write_text(config)
  for endpoint in ('gigreps.print.php','gigreps.xls.php'):
   ok(member.get(base+'/nadinmodule/'+endpoint,params={'m':'gigrepsarchiv','c':'Test.txt'}).status_code==403,'archive permission '+endpoint)
   ok(admin.get(base+'/nadinmodule/'+endpoint,params={'m':'userdatei','c':'userdatei.txt'}).status_code==404,'export module whitelist '+endpoint)
   ok(admin.get(base+'/nadinmodule/'+endpoint,params={'m':'gigreps','c':'../../config.inc.php'}).status_code==404,'export traversal rejected '+endpoint)
   ok(admin.get(base+'/nadinmodule/'+endpoint,params={'m':'gigreps','c':'Test.txt'}).status_code==200,'valid export retained '+endpoint)
  r=admin.get(base+'/index.php',params={'m':'library','x':'\" onmouseover="alert(1)'});soup=BeautifulSoup(r.text,'html.parser');ok(not soup.select_one('#library-print-link').has_attr('onmouseover'),'library export URL cannot inject attributes')
  for endpoint,part in (('PDFfiles.php','SXS'),('AUDfiles.php','RCMD')):
   r=admin.get(base+'/nadinmodule/'+endpoint,params={'name':tune});soup=BeautifulSoup(r.text,'html.parser');url=next(x['href'] for x in soup.select('a[href*="get/file.php"]') if part in x['href']);download=admin.get(urllib.parse.urljoin(base+'/nadinmodule/',url));ok(download.status_code==200,'media editor download works '+endpoint)
   ok(admin.get(base+'/nadinmodule/'+endpoint,params={'name':'../userdatei'}).status_code==400,'media listing traversal denied '+endpoint)
  r=admin.get(base+'/nadinmodule/ATTfiles.php?gig=Test');url=BeautifulSoup(r.text,'html.parser').select_one('a')['href'];ok(admin.get(urllib.parse.urljoin(base+'/nadinmodule/',url)).content==b'%PDF-attachment','legacy attachment preview works');ok(member.get(base+'/nadinmodule/ATTfiles.php?gig=Test').status_code==403,'attachment list requires manager')
  r=listener.get(base+'/index.php?m=reclist');ok('Private_HDDN' not in r.text and 'NotAudio' not in r.text and 'Take.mp3' in r.text,'recording list excludes hidden and non-audio files')
  before=hashlib.sha256((d/'Special_RCMD.mp3').read_bytes()).hexdigest();r=requests.get(base+'/nadinmodule/settime.php',params={'p':tune,'f':'Special_RCMD.mp3'},allow_redirects=False);ok(r.status_code==303 and (d/'Special_RCMD.mp3').exists(),'anonymous duration GET does not rename');ok(admin.get(base+'/nadinmodule/settime.php',params={'p':tune,'f':'Special_RCMD.mp3'}).status_code==403,'authenticated GET does not mutate')
  r=admin.get(base+'/nadinmodule/AUDfiles.php',params={'name':tune});form=BeautifulSoup(r.text,'html.parser').select_one('form[action$="settime.php"]');data={x['name']:x.get('value','') for x in form.select('input[name]')};data['f']='Special_RCMD.mp3';r=admin.post(base+'/nadinmodule/settime.php',data=data);renamed=list(d.glob('Special_RCMD*mp3'));ok(r.status_code==200 and len(renamed)==1 and '(00.23)' in renamed[0].name and hashlib.sha256(renamed[0].read_bytes()).hexdigest()==before,'duration POST retains exact MP3 bytes');ok(member.post(base+'/nadinmodule/settime.php',data=data).status_code==403,'member cannot run audio mutation')
  r=admin.get(base+'/nadinmodule/infostatus.php',params={'infodiv':'nadin_session_fake','infostatus':'token'});ok(r.status_code==400 and 'Set-Cookie' not in r.headers,'preference route cannot set authentication cookies')
  r=admin.get(base+'/nadinmodule/infostatus.php',params={'infodiv':'info'+'a'*32,'infostatus':'none'});ok(r.status_code==204 and 'info'+'a'*32 in r.headers['Set-Cookie'],'show/hide preference still works')
  log.flush();log.seek(0);errors=log.read();ok('Fatal error' not in errors and 'Parse error' not in errors,'no fatal/parse errors in audited flows');print(f'PASS: {checks} V16 HTTP regression checks')
 finally:server.terminate();server.wait(timeout=5);log.close()
