"""Independent follow-up probes for long recordings and metadata variants."""
import base64,csv,hashlib,io,json,os,re,shutil,socket,subprocess,tempfile,time
from pathlib import Path
from urllib.parse import urljoin
import requests
from bs4 import BeautifulSoup
from mutagen.id3 import ID3,TIT2,APIC
from mutagen.apev2 import APEv2,APEBinaryValue

APP=Path(os.environ.get('NADIN_APP_ROOT',Path(__file__).resolve().parents[1])).resolve();PHP=os.environ.get('NADIN_PHP','php')
FIXTURE_TEMP=tempfile.TemporaryDirectory(prefix='nadin-audio-probe-fixtures-');F=Path(os.environ.get('NADIN_PROBE_FIXTURES_DIR',FIXTURE_TEMP.name));F.mkdir(parents=True,exist_ok=True)
OUT=Path(os.environ['NADIN_PROBE_EVIDENCE_DIR']) if os.environ.get('NADIN_PROBE_EVIDENCE_DIR') else None
if OUT:OUT.mkdir(parents=True,exist_ok=True)
def ff(args):subprocess.run(['ffmpeg','-hide_banner','-loglevel','error','-y',*args],check=True)
short=F/'short.mp3';long=F/'long-over-100min.mp3';vbr=F/'vbr.mp3'
if not short.exists():ff(['-f','lavfi','-i','sine=frequency=440:duration=3.2','-ar','8000','-c:a','libmp3lame','-b:a','8k',str(short)])
if not long.exists():ff(['-stream_loop','-1','-i',str(short),'-t','6504.2','-c','copy',str(long)])
if not vbr.exists():ff(['-f','lavfi','-i','sine=frequency=660:duration=28.2','-c:a','libmp3lame','-q:a','2',str(vbr)])
checks=0;failures=[];events=[]
def ok(value,label):
 global checks
 checks+=1;events.append(dict(label=label,pass_=bool(value)))
 if not value:failures.append(label);print('FAIL:',label,flush=True)
def duration(path):return float(subprocess.check_output(['ffprobe','-v','error','-show_entries','format=duration','-of','default=noprint_wrappers=1:nokey=1',str(path)],text=True))
SEP='* - * - * - * * * - * - * - *'
with tempfile.TemporaryDirectory(prefix='nadin-closure-audio-') as temporary:
 temp=Path(temporary);app=temp/'nadin';shutil.copytree(APP,app,ignore=shutil.ignore_patterns('.git','vendor'))
 config=app/'config.inc.php';config.write_text(config.read_text().replace("$salt='???'","$salt='closure-probe'"))
 rights='vgigs,vsaxes,vbones,vtrumpets,vrhythm,vother,vscore,vreclist,varchiv,vlibrary,upaudio,uppdf,mgigs,musers'.split(',')
 (app/'userdatei/userdatei.txt').write_text('Admin,admin@example.invalid,31.12.2099,'+','.join(r+'=1' for r in rights)+',\nMember,member@example.invalid,31.12.2099,vgigs=1,vlibrary=1,vsaxes=1,\n')
 tune=app/'library/Closure_Audio';tune.mkdir();(tune/'Closure_SXS.pdf').write_bytes(b'%PDF-public')
 (app/'gigreps/2099-07-01 Closure.txt').write_text('Long recording and metadata\n'+SEP+'\nClosure_Audio')
 cases={
  'Long_RCMD.mp3':long.read_bytes(),
  'VBR_RCMD.mp3':vbr.read_bytes(),
  'Mixed Case_RCMD.MP3':short.read_bytes(),
  'Space & plus+_RCMD.mp3':short.read_bytes(),
  'Session (2026.11)_RCMD.mp3':short.read_bytes(),
  'Session.(2026.11)_RCMD.mp3':short.read_bytes(),
  'Concert.(123.45)_RCMD.mp3':vbr.read_bytes(),
  'LongUpper_RCMD.MP3':long.read_bytes(),
 }
 for name,raw in cases.items():(tune/name).write_bytes(raw)
 tagged=tune/'ID3_RCMD.mp3';tagged.write_bytes(vbr.read_bytes());tags=ID3(tagged);tags.add(TIT2(encoding=3,text=['Grüsse & résumé']));tags.add(APIC(encoding=3,mime='image/png',type=3,desc='cover',data=base64.b64decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aC0cAAAAASUVORK5CYII=')));tags.save(tagged,v2_version=4)
 cases[tagged.name]=tagged.read_bytes()
 ape=tune/'APEv2_RCMD.mp3';ape.write_bytes(short.read_bytes());tags=APEv2();tags['Private binary']=APEBinaryValue(b'0'*256+b'\xff\xff\xfc\x00'+b'0'*128);tags.save(ape);cases[ape.name]=ape.read_bytes()
 with socket.socket() as sock:sock.bind(('127.0.0.1',0));port=sock.getsockname()[1]
 log=open(temp/'server.log','w+');server=subprocess.Popen([PHP,'-d','opcache.enable=0','-S',f'127.0.0.1:{port}','-t',str(temp)],stdout=log,stderr=log)
 base=f'http://127.0.0.1:{port}/nadin/'
 try:
  for _ in range(100):
   try:
    if requests.get(base+'login.php',timeout=2).status_code==200:break
   except requests.ConnectionError:pass
   time.sleep(.04)
  def login(email):
   s=requests.Session();form=BeautifulSoup(s.get(base+'login.php').text,'html.parser').find('form');d={x['name']:x.get('value','') for x in form.select('input[type=hidden][name]')};d.update(u=email,p=hashlib.sha1((email+'closure-probe').encode()).hexdigest()[7:13]);ok(s.post(base+'login2.php',data=d,allow_redirects=False).status_code in [302,303],'login '+email);return s
  admin=login('admin@example.invalid');member=login('member@example.invalid');protected='legacy-write.inc.php' in (app/'nadinmodule/settime.php').read_text();token=''
  if protected:
   page=admin.get(base+'nadinmodule/upload.php?tune=Closure_Audio');token=json.loads(re.search(r'var token=("[^"\n]+")',page.text).group(1))
  def measure(name):
   d=dict(p=tune.name,f=name)
   return admin.post(base+'nadinmodule/settime.php',data=dict(d,csrf=token),timeout=20) if protected else admin.get(base+'nadinmodule/settime.php',params=d,timeout=20)
  def sheet_time():
   response=member.get(base+'nadinmodule/gigreps.xls.php',params=dict(m='gigreps',c='2099-07-01 Closure.txt'));rows=list(csv.reader(io.StringIO(response.content.decode('utf-8-sig')),delimiter=';'));row=next(r for r in rows if tune.name in r);return row[row.index(tune.name)+10]
  original_tune=tune
  for index,(name,raw) in enumerate(cases.items()):
   tune=app/'library'/('Closure_Case_'+str(index));tune.mkdir();(original_tune/name).rename(tune/name);(tune/'Closure_SXS.pdf').write_bytes(b'%PDF-public')
   (app/'gigreps/2099-07-01 Closure.txt').write_text('Independent single-recording fixture\n'+SEP+'\n'+tune.name)
   for session,role in [(admin,'editor'),(member,'musician')]:
    before=session.get(base+'index.php',params=dict(m='gigreps',c='2099-07-01 Closure.txt'));ok(before.status_code==200 and 'Fatal error' not in before.text,'unannotated valid recording leaves the gig view usable for '+role+': '+name)
   ok(sheet_time()=='','unknown duration stays blank in the actual XLS export: '+name)
   source=tune/name;reference=short if name=='APEv2_RCMD.mp3' else source;seconds=int(duration(reference)+.5);stem,ext=name.rsplit('.',1);expected=f'{stem}.({seconds//60:02d}.{seconds%60:02d}).{ext}'
   if name in ['VBR_RCMD.mp3','Space & plus+_RCMD.mp3','Session.(2026.11)_RCMD.mp3','Concert.(123.45)_RCMD.mp3']:
    listing=admin.get(base+'nadinmodule/AUDfiles.php',params={'name':tune.name,'m':''});document=BeautifulSoup(listing.text,'html.parser');form=next((x for x in document.select('form[action]') if 'settime.php' in x['action']),None);frame=next((x for x in document.select('iframe[src]') if 'settime.php' in x['src']),None)
    ok(form is not None or frame is not None,'the real audio file list schedules annotation: '+name)
    if form is not None:
     action=urljoin(listing.url,form['action']);fields={x['name']:x.get('value','') for x in form.select('input[name]')};auto=admin.post(action,data=fields,timeout=20)
    elif frame is not None:auto=admin.get(urljoin(listing.url,frame['src']),timeout=20)
    else:auto=None
    ok(auto is not None and auto.status_code==200 and (tune/expected).exists(),'follow the exact form/iframe produced by the audio editor: '+name)
   response=measure(name) if (tune/name).exists() else None;ok((response is None or response.status_code==200) and (tune/expected).exists(),'FFprobe agrees with annotation: '+name)
   if not (tune/expected).exists():continue
   ok((tune/expected).read_bytes()==raw,'exact audio/tag bytes retained: '+name)
   ok(sheet_time()=='0:'+f'{seconds//60:02d}:{seconds%60:02d}','known duration stays correct in the actual XLS export: '+name)
   first=sorted(x.name for x in tune.iterdir());response=measure(expected)
   ok(response.status_code==200 and first==sorted(x.name for x in tune.iterdir()),'repeating annotation is a genuine no-op: '+name)
   if name=='Long_RCMD.mp3':
    ok(seconds>=6000,'long-recording fixture independently exceeds 100 minutes')
    page=admin.get(base+'index.php',params=dict(m='gigreps',c='2099-07-01 Closure.txt'));doc=BeautifulSoup(page.text,'html.parser')
    pending=[x for x in doc.select('iframe[src],form[action]') if 'settime.php' in x.get('src',x.get('action','')) and ('Long_RCMD' in str(x) or x.find('input',attrs={'name':'f','value':expected}))]
    ok(not pending,'a valid 100-minute annotation does not schedule an endless automatic retiming loop')
    view=member.get(base+'index.php',params=dict(m='gigreps',c='2099-07-01 Closure.txt'));ok(view.status_code==200 and 'Fatal error' not in view.text,'ordinary musician can still view the long-recording gig')
  log.flush();log.seek(0);text=log.read();ok('Fatal error' not in text and 'Parse error' not in text,'probe server has no fatal/parse errors')
  result=dict(branch=APP.name,checks=checks,failures=failures,events=events,long_ffprobe_seconds=duration(long))
  if OUT:(OUT/f'audio-{APP.name}.json').write_text(json.dumps(result,indent=2)+'\n')
  print(('FAIL' if failures else 'PASS')+f': {checks} audio workflow and boundary checks ({APP.name})',flush=True)
  if failures:print(json.dumps(failures),flush=True)
 finally:server.terminate();server.wait(timeout=5);log.close()
raise SystemExit(bool(failures))
