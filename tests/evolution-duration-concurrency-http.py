"""Parallel PHP workers must retain both recordings and keep waiting bounded."""
import concurrent.futures,fcntl,hashlib,json,os,shutil,socket,subprocess,tempfile,threading,time,sys
from pathlib import Path
import requests
from bs4 import BeautifulSoup
APP=Path(os.environ.get('NADIN_APP_ROOT',Path(__file__).resolve().parents[1])).resolve();PHP=os.environ.get('NADIN_PHP','php');ROUNDS=int(os.environ.get('NADIN_RACE_ROUNDS','32'));checks=0;failures=[];statuses={}
def ok(value,label):
 global checks
 checks+=1
 if not value:failures.append(label);print('FAIL:',label,flush=True)
with tempfile.TemporaryDirectory(prefix='nadin-parallel-duration-') as temporary:
 temp=Path(temporary);app=temp/'nadin';shutil.copytree(APP,app,ignore=shutil.ignore_patterns('.git','vendor'));cfg=app/'config.inc.php';cfg.write_text(cfg.read_text().replace("$salt='???'","$salt='parallel-duration'"));config_bytes=cfg.read_bytes();marker=app/'abo/zip/donotdeletethisfolder.txt';marker_bytes=marker.read_bytes();marker_time=marker.stat().st_mtime_ns;marker.chmod(0o444)
 (app/'userdatei/userdatei.txt').write_text('Admin,admin@example.invalid,31.12.2099,vgigs=1,vlibrary=1,upaudio=1,musers=1,\n');folder=app/'library/Parallel_Audio';folder.mkdir();audio=temp/'source.mp3'
 subprocess.run(['ffmpeg','-hide_banner','-loglevel','error','-f','lavfi','-i','sine=frequency=440:duration=3.2','-ar','8000','-c:a','libmp3lame','-b:a','8k',str(audio)],check=True)
 raw=audio.read_bytes();a_bytes=raw+b'TAG'+b'A'*125;b_bytes=raw+b'TAG'+b'B'*125;processes=[];logs=[];bases=[]
 try:
  for _ in range(2):
   with socket.socket() as sock:sock.bind(('127.0.0.1',0));port=sock.getsockname()[1]
   log=open(temp/f'server-{port}.log','w+');logs.append(log);processes.append(subprocess.Popen([PHP,'-S',f'127.0.0.1:{port}','-t',str(temp)],stdout=log,stderr=log));base=f'http://127.0.0.1:{port}/nadin/';bases.append(base)
   for _ in range(100):
    try:
     if requests.get(base+'login.php',timeout=2).status_code==200:break
    except requests.ConnectionError:pass
    time.sleep(.03)
  clients=[];protected='legacy-write.inc.php' in (app/'nadinmodule/settime.php').read_text()
  for base in bases:
   s=requests.Session();form=BeautifulSoup(s.get(base+'login.php').text,'html.parser').find('form');data={x['name']:x.get('value','') for x in form.select('input[type=hidden][name]')};data.update(u='admin@example.invalid',p=hashlib.sha1(b'admin@example.invalidparallel-duration').hexdigest()[7:13]);ok(s.post(base+'login2.php',data=data,allow_redirects=False).status_code in [302,303],'independent worker/session login')
   token=''
   if protected:
    page=s.get(base+'nadinmodule/AUDfiles.php',params={'name':'Parallel_Audio'});doc=BeautifulSoup(page.text,'html.parser')
    # Obtain the existing CSRF token without inventing a new authentication path.
    token_page=s.get(base+'nadinmodule/upload.php?tune=Parallel_Audio');import re;token=json.loads(re.search(r'var token=("[^"\n]+")',token_page.text).group(1))
   clients.append((s,base,token))
  def request(n,name,barrier=None):
   s,base,token=clients[n];data=dict(p='Parallel_Audio',f=name)
   if barrier:barrier.wait()
   return s.post(base+'nadinmodule/settime.php',data=dict(data,csrf=token),timeout=5) if protected else s.get(base+'nadinmodule/settime.php',params=data,timeout=5)
  with concurrent.futures.ThreadPoolExecutor(max_workers=2) as pool:
   for i in range(ROUNDS):
    prefix=f'Race_{i:04d}_RCMD';names=[prefix+'.(01.01).mp3',prefix+'.(02.02).mp3'];(folder/names[0]).write_bytes(a_bytes);(folder/names[1]).write_bytes(b_bytes);barrier=threading.Barrier(2)
    futures=[pool.submit(request,n,names[n],barrier) for n in range(2)];codes=[f.result().status_code for f in futures];key=','.join(map(str,sorted(codes)));statuses[key]=statuses.get(key,0)+1
    remaining=[p.read_bytes() for p in folder.glob(prefix+'*.mp3')]
    ok(a_bytes in remaining and b_bytes in remaining,'parallel collision retains both complete recordings '+str(i))
    ok(sorted(codes) in [[200,409],[200,503]],'parallel collision has one success and a safe conflict/retry '+str(i))
    for p in folder.glob(prefix+'*.mp3'):p.unlink()
   names=['Independent_A.mp3','Independent_B.mp3']
   for name,data in zip(names,[a_bytes,b_bytes]):(folder/name).write_bytes(data)
   barrier=threading.Barrier(2);futures=[pool.submit(request,n,names[n],barrier) for n in range(2)];codes=[f.result().status_code for f in futures]
   ok(codes==[200,200],'unrelated recording names both complete normally')
   remaining=[p.read_bytes() for p in folder.glob('Independent*.mp3')];ok(a_bytes in remaining and b_bytes in remaining,'unrelated recordings keep exact bytes')
  name='Commit_Busy.mp3';source=folder/name;source.write_bytes(raw);started=time.monotonic()
  holder=subprocess.Popen([sys.executable,'-c',"import fcntl,sys;f=open(sys.argv[1],'rb');fcntl.flock(f,fcntl.LOCK_EX);print('locked',flush=True);sys.stdin.read()",str(marker)],stdin=subprocess.PIPE,stdout=subprocess.PIPE,text=True)
  try:
   assert holder.stdout.readline().strip()=='locked';response=request(0,name);ok(response.status_code==503,'a held commit lock returns a retryable response');ok(time.monotonic()-started<.5,'commit-lock waiting is bounded');ok(source.exists() and source.read_bytes()==raw,'busy commit leaves the source unchanged')
  finally:holder.kill();holder.wait(timeout=5)
  ok(request(0,name).status_code==200 and not source.exists(),'retry succeeds after the locking process is killed')
  ok(marker.read_bytes()==marker_bytes and marker.stat().st_mtime_ns==marker_time,'read-only directory marker bytes and modification time are preserved')
  ok(cfg.read_bytes()==config_bytes,'coordination never changes installation configuration bytes')
  ok(not list(folder.glob('.*')),'coordination creates no persistent metadata or lock files')
  result=dict(branch=APP.name,checks=checks,failures=failures,rounds=ROUNDS,parallel_requests=ROUNDS*2,statuses=statuses)
  if os.environ.get('NADIN_PROBE_EVIDENCE_DIR'):
   out=Path(os.environ['NADIN_PROBE_EVIDENCE_DIR']);out.mkdir(parents=True,exist_ok=True);(out/f'concurrency-{APP.name}.json').write_text(json.dumps(result,indent=2)+'\n')
  print(('FAIL' if failures else 'PASS')+f': {checks} parallel duration and bounded-lock checks ({APP.name})',flush=True)
 finally:
  for process in processes:process.terminate();process.wait(timeout=5)
  for log in logs:log.close()
raise SystemExit(bool(failures))
