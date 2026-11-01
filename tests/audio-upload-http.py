"""V18: real FFmpeg conversions and authenticated uploads on disposable local data."""
import concurrent.futures, fcntl, hashlib, io, json, math, os, shutil, socket, struct, subprocess, tempfile, time, wave, zipfile
from pathlib import Path
from urllib.parse import urljoin
import requests
from bs4 import BeautifulSoup

ROOT=Path(__file__).resolve().parents[1]
PHP=os.environ.get('NADIN_PHP','php'); FFMPEG=shutil.which('ffmpeg'); FFPROBE=shutil.which('ffprobe')
if not FFMPEG or not FFPROBE: raise SystemExit('This conversion suite requires FFmpeg with libmp3lame and ffprobe.')
checks=0

def ok(value,label):
    global checks
    assert value,label
    checks+=1

def make_audio(folder,seconds=.3):
    wav=folder/'source.wav'
    with wave.open(str(wav),'wb') as w:
        w.setparams((1,2,16000,0,'NONE','not compressed'))
        w.writeframes(b''.join(struct.pack('<h',int(12000*math.sin(2*math.pi*440*i/16000))) for i in range(int(seconds*16000))))
    return wav

with tempfile.TemporaryDirectory(prefix='nadin-audio-v18-') as temporary:
    temp=Path(temporary);app=temp/'nadin';shutil.copytree(ROOT,app,ignore=shutil.ignore_patterns('.git','vendor'))
    original=(app/'config.inc.php').read_text().replace("$salt='???'","$salt='audio-fixture'")
    def settings(**values):
        text=original
        for name,value in values.items():text=text.replace('$'+name+"='auto';",'$'+name+'='+json.dumps(value)+';') if name=='audioffmpeg' else text.replace('$'+name+'=90;' if name=='audioconversiontimeout' else '$'+name+'=1800;','$'+name+'='+str(value)+';')
        (app/'config.inc.php').write_text(text)
    settings()
    rights='vgigs,vsaxes,vbones,vtrumpets,vrhythm,vother,vscore,vreclist,varchiv,vlibrary,upaudio,uppdf,mgigs,musers'.split(',')
    (app/'userdatei/userdatei.txt').write_text('Admin,admin@example.invalid,31.12.2099,'+','.join(r+'=1' for r in rights)+'\nViewer,viewer@example.invalid,31.12.2099,vgigs=1,vsaxes=1,\n')
    tune=app/'library/Audio_Test';tune.mkdir()
    (app/'gigreps/2099-01-01 Audio.txt').write_text('Info\n* - * - * - * * * - * - * - *\nAudio_Test')
    source=make_audio(temp)
    codecs={'aif':'pcm_s16be','flac':'flac','m4a':'aac','aac':'aac','ogg':'libvorbis','opus':'libopus','wma':'wmav2','mp3':'libmp3lame'}
    inputs={'wav':source}
    for extension,codec in codecs.items():
        path=temp/('source.'+extension)
        subprocess.run([FFMPEG,'-hide_banner','-loglevel','error','-y','-i',str(source),'-threads','1','-c:a',codec,'-metadata','title=Practice fixture',str(path)],check=True)
        inputs[extension]=path
    inputs['aiff']=inputs['aif'];inputs['oga']=inputs['ogg']
    with socket.socket() as sock:sock.bind(('127.0.0.1',0));port=sock.getsockname()[1]
    private=temp/'private';private.mkdir();server_env=dict(os.environ,TMPDIR=str(private))
    log=open(temp/'server.log','w+');process=subprocess.Popen([PHP,'-d','upload_max_filesize=2M','-d','post_max_size=8M','-S',f'127.0.0.1:{port}','-t',str(temp)],stdout=log,stderr=log,env=server_env)
    base=f'http://127.0.0.1:{port}/nadin'
    try:
        for attempt in range(100):
            try:
                if requests.get(base+'/login.php').status_code==200:break
            except requests.ConnectionError:pass
            time.sleep(.04)
        def login(email):
            session=requests.Session();doc=BeautifulSoup(session.get(base+'/login.php').text,'html.parser');form=doc.find('form');data={i['name']:i.get('value','') for i in form.select('input[type=hidden][name]')};data.update(u=email,p=hashlib.sha1((email+'audio-fixture').encode()).hexdigest()[7:13]);response=session.post(base+'/login2.php',data=data,allow_redirects=False);ok(response.status_code==303,'real login '+email);return session
        admin=login('admin@example.invalid');viewer=login('viewer@example.invalid')
        token=admin.get(base+'/nadinmodule/media-token.php').json()['csrf']
        def upload(name,data,session=admin,csrf=token,asynchronous=True):
            fields={'csrf':csrf,'name':'Audio_Test'}
            if asynchronous:fields['_nadin_async']='1'
            return session.post(base+'/nadinmodule/AUDsendfile2.php',data=fields,files={'userfile':(name,data,'application/octet-stream')},timeout=15)
        def probe(path):
            return json.loads(subprocess.check_output([FFPROBE,'-v','error','-show_streams','-show_format','-of','json',str(path)]))
        def clean():return not list(tune.glob('.upload-*'))
        form=BeautifulSoup(admin.get(base+'/nadinmodule/uploadaudio.php?tune=Audio_Test').text,'html.parser')
        ok('.wav' in form.select_one('[type=file]')['accept'] and form.select_one('[data-audio-conversion=yes]'),'conversion-enabled form advertises supported formats')
        mp3=inputs['mp3'].read_bytes();response=upload('Exact.MP3',mp3)
        ok(response.status_code==200 and (tune/'Exact.mp3').read_bytes()==mp3,'existing MP3 is normalised only in filename; all bytes remain unchanged')
        for extension,path in inputs.items():
            if extension=='mp3':continue
            response=upload('Converted_'+extension+'.'+extension,path.read_bytes())
            ok(response.status_code==200 and response.json()['ok'],'conversion succeeds for '+extension+': '+response.text[:500])
            info=probe(tune/('Converted_'+extension+'.mp3'))
            ok(info['streams'][0]['codec_name']=='mp3' and float(info['format']['duration'])>.2,'actual output is playable MP3 with duration for '+extension)
        ok(probe(tune/'Converted_flac.mp3')['format'].get('tags',{}).get('title')=='Practice fixture','conversion retains title metadata')
        (tune/'Replace.mp3').write_bytes(mp3)
        response=upload('Replace.wav',source.read_bytes())
        replacement=(tune/'Replace.mp3').read_bytes()
        ok(response.status_code==200 and replacement!=mp3,'converted recording replaces the matching MP3 filename')
        ok(any(p.read_bytes()==mp3 for p in tune.glob('zzDEL!*Replace.mp3')),'previous recording remains recoverable')
        (tune/'Timed.(00.30).mp3').write_bytes(mp3)
        response=upload('Timed.wav',source.read_bytes())
        ok(response.status_code==200 and (tune/'Timed.mp3').exists() and not (tune/'Timed.(00.30).mp3').exists(),'duration-annotated recording is replaced rather than duplicated')
        ok(any(p.read_bytes()==mp3 for p in tune.glob('zzDEL!*Timed.(00.30).mp3')),'duration-annotated original remains recoverable with its original name')
        (tune/'Ambiguous.mp3').write_bytes(mp3);(tune/'Ambiguous.(00.30).mp3').write_bytes(mp3)
        response=upload('Ambiguous.wav',source.read_bytes())
        ok(response.status_code==409 and (tune/'Ambiguous.mp3').read_bytes()==mp3 and (tune/'Ambiguous.(00.30).mp3').read_bytes()==mp3,'ambiguous pre-existing recording names are preserved instead of guessed')
        (tune/'Ambiguous.mp3').unlink();(tune/'Ambiguous.(00.30).mp3').unlink()
        before=set(tune.iterdir());response=upload('Replace.wav',b'not an audio file')
        ok(response.status_code==422 and (tune/'Replace.mp3').read_bytes()==replacement and set(tune.iterdir())==before,'failed conversion preserves original, backups and folder contents')
        response=upload('Wrong.wav',mp3)
        ok(response.status_code==422 and not (tune/'Wrong.mp3').exists(),'renamed non-WAV input is rejected by the selected audio demuxer')
        response=upload('Playlist.m3u',b'#EXTM3U\nhttps://example.invalid/file.mp3')
        ok(response.status_code==400 and not (tune/'Playlist.mp3').exists(),'playlist/network inputs are rejected')
        response=upload('shell;$(touch INJECTED).wav',source.read_bytes())
        ok(response.status_code==200 and not (app/'INJECTED').exists() and not (tune/'INJECTED').exists(),'filename shell characters are data and cannot execute commands')
        ok(upload('Forbidden.wav',source.read_bytes(),session=viewer).status_code==403,'view-only account cannot convert or upload')
        ok(upload('Forbidden.wav',source.read_bytes(),csrf='bad').status_code==403,'conversion retains CSRF protection')
        response=upload('Listening_RCMD.wav',source.read_bytes())
        ok(response.status_code==200,'recommended recording marker survives conversion')
        gig=BeautifulSoup(admin.get(base+'/?m=gigreps&c=2099-01-01%20Audio.txt').text,'html.parser')
        audio_link=next(a for a in gig.select('a[href*="get/file.php"]') if 'Listening_RCMD' in a['href'])
        downloaded=admin.get(urljoin(base+'/',audio_link['href']))
        ok(downloaded.status_code==200 and downloaded.content==(tune/'Listening_RCMD.mp3').read_bytes(),'existing direct listening/download uses converted MP3 bytes')
        zip_link=next(a for a in gig.select('a[href*="get/zip.php"]') if 'alllistento' in a['href'])
        zipped=admin.get(urljoin(base+'/',zip_link['href']))
        with zipfile.ZipFile(io.BytesIO(zipped.content)) as archive:
            ok(archive.read('Audio_Test/Listening_RCMD.mp3')==downloaded.content,'existing audio ZIP contains the converted recommended recording')
        response=upload('Native.wav',source.read_bytes(),asynchronous=False)
        ok(response.status_code==200 and 'text/html' in response.headers['Content-Type'] and 'Audio converted to MP3' in response.text,'conversion works without JavaScript')
        settings(audiomaxseconds=1);long=make_audio(temp,3);response=upload('Replace.wav',long.read_bytes())
        ok(response.status_code==413 and (tune/'Replace.mp3').read_bytes()==replacement,'excessive duration is rejected rather than silently truncated')
        settings(audioffmpeg='');page=BeautifulSoup(admin.get(base+'/nadinmodule/uploadaudio.php?tune=Audio_Test').text,'html.parser')
        ok(page.select_one('[type=file]')['accept']=='audio/mpeg,.mp3' and 'conversion is unavailable' in page.get_text(),'disabled conversion presents honest MP3-only fallback')
        ok(upload('Fallback.wav',source.read_bytes()).status_code==503 and not (tune/'Fallback.mp3').exists(),'disabled conversion rejects other formats without storing them')
        ok(upload('Fallback.mp3',mp3).status_code==200 and (tune/'Fallback.mp3').read_bytes()==mp3,'MP3 upload remains available without FFmpeg')
        settings(audioffmpeg='/not-installed/ffmpeg')
        ok(upload('Unavailable.wav',source.read_bytes()).status_code==503,'missing executable has safe MP3-only fallback')
        wrapper=temp/'slow converter';wrapper.write_text('#!/usr/bin/env python3\nimport sys,time\nif "-encoders" in sys.argv: print(" A..... libmp3lame MP3 encoder")\nelse: time.sleep(20)\n');wrapper.chmod(0o700)
        settings(audioffmpeg=str(wrapper),audioconversiontimeout=1);start=time.monotonic();response=upload('Replace.wav',source.read_bytes())
        ok(response.status_code==408 and time.monotonic()-start<4 and (tune/'Replace.mp3').read_bytes()==replacement,'converter deadline terminates a stuck process and preserves existing recording')
        # Explicit executable paths can contain spaces; no shell quoting is needed.
        binary=temp/'ffmpeg with spaces';binary.symlink_to(FFMPEG);settings(audioffmpeg=str(binary))
        ok(upload('With_spaces.wav',source.read_bytes()).status_code==200,'configured executable path with spaces works')
        flag=temp/'converter-running';release=temp/'converter-release';parallel=temp/'parallel converter'
        parallel.write_text('#!/usr/bin/env python3\nimport os,sys,time\nfrom pathlib import Path\nif "-encoders" in sys.argv:\n print(" A..... libmp3lame MP3 encoder");sys.exit(0)\nPath('+repr(str(flag))+').write_text("ready")\nwhile not Path('+repr(str(release))+').exists(): time.sleep(.02)\nos.execv('+repr(FFMPEG)+',['+repr(FFMPEG)+']+sys.argv[1:])\n');parallel.chmod(0o700)
        settings(audioffmpeg=str(parallel),audioconversiontimeout=5)
        with concurrent.futures.ThreadPoolExecutor(max_workers=1) as pool:
            pending=pool.submit(upload,'Parallel.wav',source.read_bytes())
            for attempt in range(200):
                if flag.exists():break
                time.sleep(.01)
            ok(flag.exists(),'parallel-operation fixture reaches conversion')
            with (app/'userdatei/admin-v12-lock.php').open('r+b') as lock:
                try:
                    fcntl.flock(lock,fcntl.LOCK_EX|fcntl.LOCK_NB)
                    ok(True,'slow conversion releases the global media-write lock for other tasks')
                finally:fcntl.flock(lock,fcntl.LOCK_UN);release.write_text('continue')
            ok(pending.result().status_code==200,'conversion reacquires the write lock and commits successfully')
        oversized=temp/'oversized.wav'
        with oversized.open('wb') as sparse:sparse.truncate(100*1024*1024+1)
        result=subprocess.run([PHP,'-r',"require $argv[1]; try { nadin_audio_convert($argv[2], 'wav'); exit(1); } catch (NadinAudioException $e) { echo $e->status; }",str(app/'nadinmodule/audio-convert.inc.php'),str(oversized)],capture_output=True,text=True)
        ok(result.returncode==0 and result.stdout=='413','100 MB conversion source limit is checked before decoding')
        settings();ok(clean() and not list(private.iterdir()),'private conversion output and upload staging are cleaned after successes and failures')

        if os.environ.get('NADIN_AUDIO_BROWSER')=='1':
            env=dict(os.environ,NADIN_AUDIO_URL=base,NADIN_AUDIO_APP=str(app),NADIN_AUDIO_WAV=str(source),NADIN_AUDIO_MP3=str(inputs['mp3']),NADIN_AUDIO_COOKIES=json.dumps(admin.cookies.get_dict()))
            subprocess.run(['node','tests/audio-upload-browser.cjs'],cwd=ROOT,env=env,check=True,timeout=120)
        # A typical restricted hosting account disables process creation entirely.
        process.terminate();process.wait(timeout=5)
        process=subprocess.Popen([PHP,'-d','disable_functions=proc_open','-d','upload_max_filesize=2M','-d','post_max_size=8M','-S',f'127.0.0.1:{port}','-t',str(temp)],stdout=log,stderr=log,env=server_env)
        for attempt in range(100):
            try:
                if requests.get(base+'/login.php').status_code==200:break
            except requests.ConnectionError:pass
            time.sleep(.04)
        restricted=BeautifulSoup(admin.get(base+'/nadinmodule/uploadaudio.php?tune=Audio_Test').text,'html.parser')
        ok(restricted.select_one('[type=file]')['accept']=='audio/mpeg,.mp3','disabled proc_open is detected without PHP warnings')
        ok(upload('Restricted.mp3',mp3).status_code==200 and (tune/'Restricted.mp3').read_bytes()==mp3,'MP3 upload still works when PHP process creation is disabled')
        ok(upload('Restricted.wav',source.read_bytes()).status_code==503 and (tune/'Restricted.mp3').read_bytes()==mp3,'restricted hosting does not replace files while rejecting conversion')
        log.flush();log.seek(0);server_log=log.read();ok(not any(x in server_log for x in ['PHP Warning:','PHP Fatal error:','PHP Deprecated:']),'audio uploads produce no PHP warnings, errors or deprecations')
        print('PASS:',checks,'V18 audio conversion/HTTP checks')
    finally:
        process.terminate();process.wait(timeout=5);log.close()
