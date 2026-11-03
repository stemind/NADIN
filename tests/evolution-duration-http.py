"""Real MP3 duration recognition must preserve recordings and collision copies."""
import hashlib, json, os, re, shutil, socket, subprocess, tempfile, time
from pathlib import Path
import requests
from bs4 import BeautifulSoup
ROOT=Path(os.environ.get('NADIN_APP_ROOT',Path(__file__).resolve().parents[1])).resolve()
PHP=os.environ.get('NADIN_PHP','php');checks=0;failures=[]
def ok(value,label):
    global checks
    checks+=1
    if not value:failures.append(label);print('FAIL:',label,flush=True)
with tempfile.TemporaryDirectory(prefix='nadin-duration-contract-') as temporary:
    temp=Path(temporary);app=temp/'nadin';shutil.copytree(ROOT,app,ignore=shutil.ignore_patterns('.git','vendor'))
    config=app/'config.inc.php';config.write_text(config.read_text().replace("$salt='???'","$salt='duration-contract'"))
    (app/'userdatei/userdatei.txt').write_text('Audio editor,editor@example.invalid,31.12.2099,vgigs=1,vlibrary=1,upaudio=1,\n')
    audio=temp/'recording.mp3'
    subprocess.run(['ffmpeg','-hide_banner','-loglevel','error','-f','lavfi','-i','sine=frequency=440:duration=0.7','-c:a','libmp3lame','-b:a','128k',str(audio)],check=True)
    raw=audio.read_bytes();tune=app/'library/Duration_Normal';tune.mkdir();(tune/'Recording.mp3').write_bytes(raw)
    broken=app/'library/Duration_Broken';broken.mkdir();bad=b'\xff\xff\xfc\x00'+b'\x00'*40;(broken/'Broken.mp3').write_bytes(bad)
    with socket.socket() as sock:sock.bind(('127.0.0.1',0));port=sock.getsockname()[1]
    log=open(temp/'server.log','w+');server=subprocess.Popen([PHP,'-S',f'127.0.0.1:{port}','-t',str(temp)],stdout=log,stderr=log)
    base=f'http://127.0.0.1:{port}/nadin/'
    try:
        for _ in range(100):
            try:
                if requests.get(base+'login.php',timeout=2).status_code==200:break
            except requests.ConnectionError:pass
            time.sleep(.04)
        s=requests.Session();f=BeautifulSoup(s.get(base+'login.php').text,'html.parser').find('form')
        data={x['name']:x.get('value','') for x in f.select('input[type=hidden][name]')};data.update(u='editor@example.invalid',p=hashlib.sha1(b'editor@example.invalidduration-contract').hexdigest()[7:13])
        ok(s.post(base+'login2.php',data=data,allow_redirects=False).status_code in [302,303],'audio editor login')
        protected='legacy-write.inc.php' in (app/'nadinmodule/settime.php').read_text()
        token=''
        if protected:
            page=s.get(base+'nadinmodule/upload.php?tune=Duration_Normal')
            token=json.loads(re.search(r'var token=("[^"\n]+")',page.text).group(1))
        def measure(tune,file):
            data=dict(p=tune,f=file)
            if protected:return s.post(base+'nadinmodule/settime.php',data=dict(data,csrf=token))
            return s.get(base+'nadinmodule/settime.php',params=data)
        result=measure('Duration_Normal','Recording.mp3')
        timed=[p for p in tune.glob('Recording.*.mp3')]
        ok(result.status_code==200 and len(timed)==1,'ordinary MP3 duration still works through its established transport')
        if not timed:raise AssertionError('normal duration route failed: '+result.text[:1600])
        timed_name=timed[0].name
        ok(timed[0].read_bytes()==raw and not (tune/'Recording.mp3').exists(),'duration annotation preserves exact MP3 bytes')
        for rate in [48000,44100,32000,24000,22050,16000,12000,11025,8000]:
            filename='Rate_'+str(rate)+'.mp3';source=tune/filename
            subprocess.run(['ffmpeg','-hide_banner','-loglevel','error','-f','lavfi','-i','sine=frequency=660:duration=3.2','-ar',str(rate),'-c:a','libmp3lame','-b:a','32k',str(source)],check=True)
            source_bytes=source.read_bytes()
            measured=float(subprocess.check_output(['ffprobe','-v','error','-show_entries','format=duration','-of','default=noprint_wrappers=1:nokey=1',str(source)],text=True))
            seconds=int(measured+.5);expected=filename[:-4]+f'.({seconds//60:02d}.{seconds%60:02d}).mp3'
            response=measure('Duration_Normal',filename)
            ok(response.status_code==200 and (tune/expected).exists(),'duration agrees with independent ffprobe at '+str(rate)+' Hz')
            ok(any(p.read_bytes()==source_bytes for p in tune.glob(filename[:-4]+'*.mp3')),'duration retains exact bytes at '+str(rate)+' Hz')
        short=tune/'Short.mp3'
        subprocess.run(['ffmpeg','-hide_banner','-loglevel','error','-f','lavfi','-i','sine=frequency=440:duration=0.1','-ar','44100','-c:a','libmp3lame','-b:a','128k',str(short)],check=True)
        short_bytes=short.read_bytes();response=measure('Duration_Normal','Short.mp3')
        ok(response.status_code==200 and (tune/'Short.(00.00).mp3').exists(),'valid subsecond audio keeps its established rounded 00.00 annotation')
        ok((tune/'Short.(00.00).mp3').read_bytes()==short_bytes,'subsecond duration preserves exact source bytes')
        for letter in ['A','B']:
            folder=app/'library'/('Duration_Collision_'+letter);folder.mkdir()
            (folder/'Recording.mp3').write_bytes(raw);(folder/timed_name).write_bytes(('UNIQUE EXISTING RECORDING '+letter).encode())
            response=measure(folder.name,'Recording.mp3')
            ok(response.status_code==409,'duration collision is reported without replacing another recording '+letter)
            ok((folder/'Recording.mp3').exists() and (folder/'Recording.mp3').read_bytes()==raw,'collision retains the unannotated recording '+letter)
        retained=[p.read_bytes() for directory in ['library','filesdeleted'] for p in (app/directory).rglob('*') if p.is_file()]
        for letter in ['A','B']:ok(('UNIQUE EXISTING RECORDING '+letter).encode() in retained,'same-name collisions across tunes retain every original '+letter)
        response=measure('Duration_Broken','Broken.mp3')
        ok(response.status_code==422,'invalid reserved MP3 header is handled without a PHP fatal error')
        ok((broken/'Broken.mp3').exists() and (broken/'Broken.mp3').read_bytes()==bad,'unreadable duration does not rename or modify the source')
        log.flush();log.seek(0);errors=log.read()
        ok('Fatal error' not in errors and 'Parse error' not in errors,'no PHP fatal/parse errors')
        print(('FAIL' if failures else 'PASS')+f': {checks} duration preservation checks ({ROOT.name})',flush=True)
        if failures:raise AssertionError('; '.join(failures))
    finally:server.terminate();server.wait(timeout=5);log.close()
