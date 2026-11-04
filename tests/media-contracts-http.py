"""Contracts derived from Main's real media forms, independent of earlier suites.

Synthetic local installations only. Preserve source bytes and retained versions.
"""
import concurrent.futures,fcntl,hashlib,json,os,re,shutil,signal,socket,subprocess,tempfile,time
from pathlib import Path
from urllib.parse import urljoin
import requests
from bs4 import BeautifulSoup

ROOT=Path(os.environ.get('NADIN_APP_ROOT',Path(__file__).resolve().parents[1])).resolve();PHP=os.environ.get('NADIN_PHP','php')
OUT=Path(os.environ['NADIN_PROBE_EVIDENCE_DIR']) if os.environ.get('NADIN_PROBE_EVIDENCE_DIR') else None
checks=0;failures=[];events=[];SEP='* - * - * - * * * - * - * - *'
def ok(value,label,detail=None):
 global checks
 checks+=1;events.append(dict(label=label,passed=bool(value),detail=detail))
 if not value:failures.append(label);print('FAIL:',label,flush=True)
def pdf(label):return b'%PDF-1.4\n%'+label.encode()+b'\n1 0 obj << /Type /Catalog >> endobj\n%%EOF\n'
def mp3(label):
 base=fixture.read_bytes()
 return base+b'TAG'+label.encode()[:30].ljust(30,b' ')+b'Fixture artist'.ljust(30,b' ')+b'Fixture album'.ljust(30,b' ')+b'2026'+b'contract'.ljust(30,b' ')+b'\x00'
with tempfile.TemporaryDirectory(prefix='nadin-independent-media-') as temporary:
 temp=Path(temporary);fixture=Path(os.environ.get('NADIN_VALID_MP3',temp/'source.mp3'))
 if not fixture.exists():subprocess.run(['ffmpeg','-hide_banner','-loglevel','error','-f','lavfi','-i','sine=frequency=660:duration=3.2','-c:a','libmp3lame','-q:a','2',str(fixture)],check=True)
 app=temp/'nadin';shutil.copytree(ROOT,app,ignore=shutil.ignore_patterns('.git','vendor'))
 config=app/'config.inc.php';config.write_bytes(config.read_bytes().replace(b"$salt='???'",b"$salt='media-contracts'"))
 rights='vgigs,vsaxes,vbones,vtrumpets,vrhythm,vother,vscore,vreclist,varchiv,vlibrary,upaudio,uppdf,mgigs,musers'.split(',')
 users=app/'userdatei/userdatei.txt';users.write_text('Editor,editor@example.invalid,31.12.2099,'+','.join(x+'=1' for x in rights)+',\n')
 tune=app/'library/Contract_Tune';tune.mkdir();(tune/'Part_SXS.pdf').write_bytes(pdf('ordinary music'))
 (app/'gigreps/2099-09-01 Contract.txt').write_text('Contract gig\n'+SEP+'\nContract_Tune')
 protected={str(x.relative_to(app)):x.read_bytes() for x in [config,users,app/'gigreps/2099-09-01 Contract.txt']}
 with socket.socket() as sock:sock.bind(('127.0.0.1',0));port=sock.getsockname()[1]
 log=open(temp/'server.log','w+');server=subprocess.Popen([PHP,'-d','opcache.enable=0','-S',f'127.0.0.1:{port}','-t',str(temp)],stdout=log,stderr=log,start_new_session=True,env=dict(os.environ,PHP_CLI_SERVER_WORKERS='4'))
 base=f'http://127.0.0.1:{port}/nadin/'
 try:
  for _ in range(100):
   try:
    if requests.get(base+'login.php',timeout=2).status_code==200:break
   except requests.ConnectionError:pass
   time.sleep(.04)
  session=requests.Session();f=BeautifulSoup(session.get(base+'login.php').text,'html.parser').find('form')
  data={x['name']:x.get('value','') for x in f.select('input[type=hidden][name]')};data.update(u='editor@example.invalid',p=hashlib.sha1(b'editor@example.invalidmedia-contracts').hexdigest()[7:13])
  ok(session.post(base+'login2.php',data=data,allow_redirects=False).status_code in [302,303],'real fixture login')
  outer=session.get(base+'nadinmodule/uploadaudio.php',params={'tune':'Contract_Tune'})
  token=''
  if (app/'nadinmodule/media-token.php').exists():token=session.get(base+'nadinmodule/media-token.php').json()['csrf']
  else:
   match=re.search(r'var token=("[^"\n]*")',outer.text)
   if match:token=json.loads(match[1])
  upload_actions={}
  for kind in ['AUD','PDF']:
   response=session.get(base+'nadinmodule/'+kind+'sendfile.php',params={'name':'Contract_Tune'})
   if not token:
    match=re.search(r'var token=("[^"\n]*")',response.text)
    if match:token=json.loads(match[1])
   form=BeautifulSoup(response.text,'html.parser').find('form')
   ok(response.status_code==200 and form is not None and form.get('method','').lower()=='post','real '+kind+' upload form uses POST')
   upload_actions[kind]=(urljoin(response.url,form['action']),{x['name']:x.get('value','') for x in form.select('input[type=hidden][name]')})
  def mutation(handler,fields):
   source=(app/'nadinmodule'/handler).read_text(errors='replace')
   protected=any(x in source for x in ['legacy-write.inc.php','media-action.inc.php'])
   if protected:return session.post(base+'nadinmodule/'+handler,data=dict(fields,csrf=token),timeout=10)
   return session.get(base+'nadinmodule/'+handler,params=fields,timeout=10)
  def upload(kind,items):
   action,fields=upload_actions[kind];fields=dict(fields,csrf=token)
   files=[('userfile[]' if kind=='PDF' else 'userfile',(name,raw,'application/pdf' if kind=='PDF' else 'audio/mpeg')) for name,raw in items]
   return session.post(action,data=fields,files=files,timeout=10)
  # Reject collision on the final name, after duration removal, not a raw spelling.
  from_raw=mp3('rename source');keep_raw=mp3('existing target')
  (tune/'From.mp3').write_bytes(from_raw);(tune/'Keep.mp3').write_bytes(keep_raw)
  response=mutation('AUDrenamefile.php',dict(p=tune.name,f='From.mp3',n='Keep(01.23).mp3'))
  ok((tune/'Keep.mp3').read_bytes()==keep_raw,'normalised audio rename preserves the occupied destination',{'status':response.status_code})
  ok((tune/'From.mp3').is_file() and (tune/'From.mp3').read_bytes()==from_raw,'normalised audio rename conflict preserves its source')
  # Isolate the valid counterpart even when the preceding implementation lost data.
  (tune/'From.mp3').write_bytes(from_raw)
  response=mutation('AUDrenamefile.php',dict(p=tune.name,f='From.mp3',n='Distinct(01.23).mp3'))
  ok(response.status_code==200 and (tune/'Distinct.mp3').is_file() and (tune/'Distinct.mp3').read_bytes()==from_raw,'legitimate audio rename still removes a supplied duration label')
  raw=pdf('PDF source');occupied=pdf('PDF target');(tune/'From_SXS.pdf').write_bytes(raw);(tune/'Keep_SXS.pdf').write_bytes(occupied)
  response=mutation('PDFrenamefile.php',dict(p=tune.name,f='From_SXS.pdf',n='Keep SXS.pdf'))
  ok((tune/'Keep_SXS.pdf').read_bytes()==occupied and (tune/'From_SXS.pdf').read_bytes()==raw,'normalised PDF rename conflict preserves both sources')
  response=mutation('PDFrenamefile.php',dict(p=tune.name,f='From_SXS.pdf',n='Distinct SXS.pdf'))
  ok(response.status_code==200 and (tune/'Distinct_SXS.pdf').read_bytes()==raw,'legitimate PDF rename remains usable')
  # Multiple tunes may legitimately contain the same recording/PDF basename.
  for kind,extension in [('AUD','mp3'),('PDF','pdf'),('ATT','pdf')]:
   expected=[];sources=[]
   for index in range(6):
    folder=app/'library'/f'Delete_{kind}_{index}';folder.mkdir();payload=mp3(f'delete {index}') if kind=='AUD' else pdf(f'delete {index}')
    source=(app/'abo/zip'/'Shared.pdf') if kind=='ATT' else folder/('Shared.'+extension);source.parent.mkdir(parents=True,exist_ok=True);source.write_bytes(payload);expected.append(payload);sources.append(source)
    response=mutation(kind+'deletefile.php',dict(p=folder.name,f=source.name))
    ok(response.status_code==200 and not source.exists(),'explicit '+kind+' delete moves source '+str(index))
   retained=[x.read_bytes() for x in (app/'filesdeleted').iterdir() if x.is_file()]
   ok(all(payload in retained for payload in expected),'same-basename '+kind+' deletions retain every distinct original',{'retained_matches':sum(payload in retained for payload in expected),'expected':len(expected)})
  # The implementation already promises retained versions through zzDEL files.
  for kind,filename in [('AUD','History.mp3'),('PDF','History_SXS.pdf')]:
   history=[mp3('upload original') if kind=='AUD' else pdf('upload original')]
   (tune/filename).write_bytes(history[0])
   for index in range(1,6):
    raw=mp3('upload version '+str(index)) if kind=='AUD' else pdf('upload version '+str(index))
    response=upload(kind,[(filename,raw)]);history.append(raw)
    ok(response.status_code==200 and (tune/filename).read_bytes()==raw,'successful '+kind+' replacement stores exact new bytes '+str(index))
   retained=[x.read_bytes() for x in tune.glob('zzDEL!*') if x.is_file()]
   ok(all(raw in retained for raw in history[:-1]),'repeated '+kind+' replacements retain every previous version',{'retained_matches':sum(raw in retained for raw in history[:-1]),'expected':len(history)-1})
  original=pdf('before duplicate batch');target=tune/'Batch_SXS.pdf';target.write_bytes(original)
  response=upload('PDF',[('Batch SXS.pdf',pdf('duplicate first')),('Batch_SXS.pdf',pdf('duplicate second'))])
  ok(target.read_bytes()==original,'colliding normalised multi-upload is rejected before changing the current PDF',{'status':response.status_code})
  previous={x.name:x.read_bytes() for x in tune.iterdir() if x.is_file()}
  response=upload('PDF',[('Rejected.txt',b'non-media')])
  after={x.name:x.read_bytes() for x in tune.iterdir() if x.is_file()}
  ok(previous==after,'rejected file type preserves all current and retained tune bytes')
  # Concurrent requests use separate server workers and independent HTTP sessions.
  def parallel(callables):
   with concurrent.futures.ThreadPoolExecutor(max_workers=8) as pool:return list(pool.map(lambda call:call(),callables))
  def concurrent_upload(raw):
   client=requests.Session();client.cookies.update(session.cookies)
   action,fields=upload_actions['AUD'];return client.post(action,data=dict(fields,csrf=token),files={'userfile':('Concurrent.mp3',raw,'audio/mpeg')},timeout=10)
  first=mp3('concurrent original');(tune/'Concurrent.mp3').write_bytes(first)
  versions=[mp3('concurrent '+str(index)) for index in range(8)]
  responses=parallel([lambda raw=raw:concurrent_upload(raw) for raw in versions])
  retained=[x.read_bytes() for x in tune.iterdir() if x.is_file()]
  ok(all(raw in retained for raw in [first]+versions),'simultaneous accepted audio replacements retain every distinct byte sequence',{'statuses':[r.status_code for r in responses]})
  raw_a=mp3('race rename A');raw_b=mp3('race rename B');(tune/'Race_A.mp3').write_bytes(raw_a);(tune/'Race_B.mp3').write_bytes(raw_b)
  def race_rename(name):
   client=requests.Session();client.cookies.update(session.cookies);fields=dict(p=tune.name,f=name,n='Race_Result(02.34).mp3')
   protected=any(x in (app/'nadinmodule/AUDrenamefile.php').read_text(errors='replace') for x in ['legacy-write.inc.php','media-action.inc.php'])
   return client.post(base+'nadinmodule/AUDrenamefile.php',data=dict(fields,csrf=token),timeout=10) if protected else client.get(base+'nadinmodule/AUDrenamefile.php',params=fields,timeout=10)
  parallel([lambda:race_rename('Race_A.mp3'),lambda:race_rename('Race_B.mp3')])
  remaining=[x.read_bytes() for x in tune.glob('Race_*.mp3') if x.is_file()]
  ok(raw_a in remaining and raw_b in remaining,'simultaneous normalised audio renames preserve both recordings')
  # Exercise the existing shared commit marker: a busy duration request is bounded.
  busy=tune/'Busy.mp3';busy_raw=mp3('bounded contention');busy.write_bytes(busy_raw)
  with (app/'abo/zip/donotdeletethisfolder.txt').open('rb') as marker:
   fcntl.flock(marker,fcntl.LOCK_EX);started=time.monotonic();response=mutation('settime.php',dict(p=tune.name,f=busy.name));elapsed=time.monotonic()-started
   ok(response.status_code==503 and elapsed<2 and busy.read_bytes()==busy_raw,'busy duration commit returns promptly and preserves audio',{'status':response.status_code,'seconds':round(elapsed,3)})
  response=mutation('settime.php',dict(p=tune.name,f=busy.name))
  ok(response.status_code==200 and any(x.read_bytes()==busy_raw for x in tune.glob('Busy*mp3')),'duration renaming recovers after the shared lock is released')
  # Cancellation and invalid extensions must preserve current and retained bytes.
  previous={x.name:x.read_bytes() for x in tune.iterdir() if x.is_file()}
  for requested in ['null','', 'Cancelled.txt']:
   mutation('AUDrenamefile.php',dict(p=tune.name,f='Distinct.mp3',n=requested))
  ok(previous=={x.name:x.read_bytes() for x in tune.iterdir() if x.is_file()},'cancelled and invalid audio rename attempts preserve all bytes')
  # A failed recovery move must never turn an explicit deletion into data loss.
  recovery=app/'filesdeleted';saved=temp/'saved-recovery';recovery.rename(saved);recovery.write_bytes(b'unavailable recovery directory')
  try:
   for kind,extension in [('AUD','mp3'),('PDF','pdf'),('ATT','pdf')]:
    folder=tune if kind!='ATT' else app/'abo/zip';folder.mkdir(parents=True,exist_ok=True)
    source=folder/('Recovery_Failure.'+extension);raw=mp3('recovery failure') if kind=='AUD' else pdf('recovery failure');source.write_bytes(raw)
    response=mutation(kind+'deletefile.php',dict(p=tune.name,f=source.name))
    ok(source.is_file() and source.read_bytes()==raw,'failed '+kind+' recovery move preserves the original bytes',{'status':response.status_code})
  finally:recovery.unlink();saved.rename(recovery)
  for name,raw in protected.items():ok((app/name).read_bytes()==raw,'media operations preserve installation-owned bytes: '+name)
  log.flush();log.seek(0);errors=log.read();ok('Fatal error' not in errors and 'Parse error' not in errors,'no PHP fatal or parse error during the independent contracts')
 finally:os.killpg(server.pid,signal.SIGTERM);server.wait(timeout=5);log.close()
 result=dict(branch=ROOT.name,checks=checks,failures=failures,events=events)
 if OUT:OUT.mkdir(parents=True,exist_ok=True);(OUT/('media-'+ROOT.name+'.json')).write_text(json.dumps(result,indent=2)+'\n')
 print(('FAIL' if failures else 'PASS')+f': {checks} independently derived media contracts ({ROOT.name})',flush=True)
 raise SystemExit(bool(failures))
