"""Challenge repairs with valid older workflows; use independent MP3 metadata.

Real HTTP on disposable copies. No production state and no outbound mail.
"""
import fcntl,hashlib,json,os,re,shutil,socket,subprocess,tempfile,time
from pathlib import Path
import requests
from bs4 import BeautifulSoup
from mutagen.apev2 import APEv2,APEBinaryValue

ROOT=Path(os.environ.get('NADIN_APP_ROOT',Path(__file__).resolve().parents[1])).resolve()
PHP=os.environ.get('NADIN_PHP','php');checks=0;failures=[]
SEP='* - * - * - * * * - * - * - *'
def ok(value,label):
    global checks
    checks+=1
    if not value:failures.append(label);print('FAIL:',label,flush=True)
with tempfile.TemporaryDirectory(prefix='nadin-countercheck-') as temporary:
    temp=Path(temporary);app=temp/'nadin';shutil.copytree(ROOT,app,ignore=shutil.ignore_patterns('.git','vendor'))
    config=app/'config.inc.php';original_config=config.read_text().replace("$salt='???'","$salt='countercheck'");config.write_text(original_config)
    rights='vgigs,vsaxes,vbones,vtrumpets,vrhythm,vother,vscore,vreclist,varchiv,vlibrary,upaudio,uppdf,mgigs,musers'.split(',')
    (app/'userdatei/userdatei.txt').write_text('Admin,admin@example.invalid,31.12.2099,'+','.join(r+'=1' for r in rights)+',\nMember,member@example.invalid,31.12.2099,vgigs=1,vlibrary=1,vsaxes=1,\n')
    tune=app/'library/Counter_Tune';tune.mkdir();(tune/'Tune_SXS.pdf').write_bytes(b'%PDF-public')
    (tune/'infos.txt').write_bytes(b'');(tune/'old-notes.txt').write_text('CANONICAL_OLD_METADATA_TEXT\n'+SEP+'\nhttps://example.invalid/old')
    hidden=app/'library/Counter_Hidden';hidden.mkdir();(hidden/'Draft_SXS_HDDN.pdf').write_bytes(b'%PDF-hidden')
    (app/'gigreps/2099-03-01 Counter.txt').write_text('Countercheck\n'+SEP+'\nCounter_Tune\nCounter_Hidden')
    audio=temp/'source.mp3'
    subprocess.run(['ffmpeg','-hide_banner','-loglevel','error','-f','lavfi','-i','sine=duration=3.2','-c:a','libmp3lame',str(audio)],check=True)
    raw=audio.read_bytes();tagged=temp/'tagged.mp3';tagged.write_bytes(raw)
    tags=APEv2();tags['Private binary']=APEBinaryValue(b'0'*256+b'\xff\xff\xfc\x00'+b'0'*128);tags.save(tagged)
    probe=json.loads(subprocess.check_output(['ffprobe','-v','error','-select_streams','a:0','-show_entries','stream=codec_name,duration','-of','json',str(tagged)],text=True))
    ok(probe['streams'][0]['codec_name']=='mp3' and float(probe['streams'][0]['duration'])>3,'independent FFprobe validates the MP3 with legal binary APEv2 metadata')
    (tune/'Tagged.mp3').write_bytes(tagged.read_bytes());(tune/'Busy.mp3').write_bytes(raw)
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
            s=requests.Session();f=BeautifulSoup(s.get(base+'login.php').text,'html.parser').find('form')
            data={x['name']:x.get('value','') for x in f.select('input[type=hidden][name]')};data.update(u=email,p=hashlib.sha1((email+'countercheck').encode()).hexdigest()[7:13])
            ok(s.post(base+'login2.php',data=data,allow_redirects=False).status_code in [302,303],'login '+email);return s
        a=login('admin@example.invalid');b=login('admin@example.invalid');member=login('member@example.invalid')
        protected='legacy-write.inc.php' in (app/'nadinmodule/settime.php').read_text();media_token=''
        if protected:
            page=a.get(base+'nadinmodule/upload.php?tune=Counter_Tune')
            media_token=json.loads(re.search(r'var token=("[^"\n]+")',page.text).group(1))
        def measure(file,timeout=4):
            data=dict(p='Counter_Tune',f=file)
            if protected:return a.post(base+'nadinmodule/settime.php',data=dict(data,csrf=media_token),timeout=timeout)
            return a.get(base+'nadinmodule/settime.php',params=data,timeout=timeout)
        response=measure('Tagged.mp3')
        expected=tune/'Tagged.(00.03).mp3'
        ok(response.status_code==200 and expected.exists(),'valid MP3 with binary trailing metadata keeps duration annotation')
        ok(any(p.read_bytes()==tagged.read_bytes() for p in tune.glob('Tagged*.mp3')),'valid metadata and MP3 bytes are preserved exactly')
        busy=tune/'Busy.mp3';start=time.monotonic();response=None
        with busy.open('rb') as lock:
            fcntl.flock(lock,fcntl.LOCK_EX)
            try:
                try:response=measure('Busy.mp3',.8)
                except requests.Timeout:pass
                ok(response is not None and response.status_code==503,'an already locked recording returns immediately instead of blocking the worker')
                ok(time.monotonic()-start<.7,'busy-recording handling has a bounded immediate response')
                ok(busy.exists() and busy.read_bytes()==raw,'a busy recording stays unchanged')
            finally:fcntl.flock(lock,fcntl.LOCK_UN)
        a.get(base+'login.php',timeout=4)
        if busy.exists():
            response=measure('Busy.mp3');ok(response.status_code==200 and (tune/'Busy.(00.03).mp3').read_bytes()==raw,'retry after release succeeds without a persistent lock')
        else:ok(False,'retry after release succeeds without a persistent lock')
        page=member.get(base+'index.php',params={'m':'gigreps','c':'2099-03-01 Counter.txt'})
        ok(page.status_code==200 and 'CANONICAL_OLD_METADATA_TEXT' not in BeautifulSoup(page.text,'html.parser').get_text(),'an intentionally empty infos.txt does not revive stale legacy notes')
        query={'c':'Counter_Hidden/Draft_SXS_HDDN.pdf'}
        ok(a.get(base+'get/file.php',params=query).content==b'%PDF-hidden','authorised administrator still downloads hidden working files')
        denied=member.get(base+'get/file.php',params=query)
        ok(denied.content!=b'%PDF-hidden' and 'application/pdf' not in denied.headers.get('Content-Type',''),'hidden working-file permission stays enforced through the branch-specific existing denial response')
        changed=original_config.replace("$hidden='HDDN'","$hidden=''");ok(changed!=original_config,'empty hidden marker is an explicit supported configuration case');config.write_text(changed)
        ok(member.get(base+'get/file.php',params=query).content==b'%PDF-hidden','empty hidden marker retains its established no-hiding meaning')
        config.write_text(original_config)
        if (app/'nadinmodule/admin-api.php').exists():
            def api(data,session=a,post=False):
                data=dict(data,area='gigs')
                if post:
                    data['csrf']=session.get(base+'nadinmodule/admin-api.php',params={'area':'gigs','action':'token'}).json()['csrf'];return session.post(base+'nadinmodule/admin-api.php',json=data)
                return session.get(base+'nadinmodule/admin-api.php',params=data)
            result=api(dict(action='save',id='',date='2099-04-01',title='Noop preservation',infos='Unchanged information',tunes=['Counter_Tune','Break / Pause','Counter_Tune']),post=True)
            ok(result.status_code==200,'create independent no-change-save fixture');g=result.json()['gig'];p=app/'gigreps'/g['id'];before=p.read_bytes();identity=p.stat().st_ino;backups=set((app/'userdatei/revisions').glob('*'))
            result=api(dict(action='save',id=g['id'],revision=g['revision'],infos=g['infos'],tunes=g['tunes']),post=True)
            ok(result.status_code==200,'unchanged save remains successful')
            ok(result.json()['gig']['revision']==g['revision'],'an unchanged save does not invalidate the other open editor')
            ok(p.read_bytes()==before and p.stat().st_ino==identity,'unchanged save keeps the same gig object and bytes')
            ok(set((app/'userdatei/revisions').glob('*'))==backups,'unchanged save creates no redundant recovery copy')
            second=api(dict(action='save',id=g['id'],revision=g['revision'],infos='Legitimate change from the other editor',tunes=g['tunes']),session=b,post=True)
            ok(second.status_code==200 and b'Legitimate change from the other editor' in p.read_bytes(),'other editor can still save after a genuine no-op')
            if second.status_code==200:
                stale=api(dict(action='save',id=g['id'],revision=g['revision'],infos='Stale real overwrite',tunes=g['tunes']),post=True)
                ok(stale.status_code==409,'an actual content change still rejects the genuinely stale editor')
            else:ok(False,'an actual content change still rejects the genuinely stale editor')
        log.flush();log.seek(0);errors=log.read();ok('Fatal error' not in errors and 'Parse error' not in errors,'counterchecks produce no PHP fatal/parse errors')
        print(('FAIL' if failures else 'PASS')+f': {checks} independent repair counterchecks ({ROOT.name})',flush=True)
        if failures:raise AssertionError('; '.join(failures))
    finally:server.terminate();server.wait(timeout=5);log.close()
