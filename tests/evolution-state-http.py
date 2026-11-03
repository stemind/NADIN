"""Independent regression: a stale gig form must not affect a replacement gig.

Also compare catalogue badges with real, public PDF/MP3 source files.
Uses only a disposable copy, synthetic members and local HTTP.
"""
import hashlib, json, os, re, shutil, socket, subprocess, tempfile, time
from pathlib import Path
import requests
from bs4 import BeautifulSoup

ROOT=Path(os.environ.get('NADIN_APP_ROOT',Path(__file__).resolve().parents[1])).resolve()
PHP=os.environ.get('NADIN_PHP','php'); checks=0; failures=[]
def ok(value,label):
    global checks
    checks+=1
    if not value: failures.append(label); print('FAIL:',label,flush=True)

with tempfile.TemporaryDirectory(prefix='nadin-gig-identity-') as temporary:
    temp=Path(temporary);app=temp/'nadin'
    shutil.copytree(ROOT,app,ignore=shutil.ignore_patterns('.git','vendor'))
    config=app/'config.inc.php'
    config.write_text(config.read_text().replace("$salt='???'","$salt='evolution-state'"))
    rights='vgigs,vsaxes,vbones,vtrumpets,vrhythm,vother,vscore,vreclist,varchiv,vlibrary,upaudio,uppdf,mgigs,musers'.split(',')
    (app/'userdatei/userdatei.txt').write_text('Admin,admin@example.invalid,31.12.2099,'+','.join(r+'=1' for r in rights)+',\nManager,manager@example.invalid,31.12.2099,vgigs=1,vlibrary=1,mgigs=1,\n')
    fixtures={
        'Badge_Valid':{'SXS_Start.PDF':b'%PDF-valid','RCMD_Start.MP3':b'MP3'},
        'Badge_Midi':{'Only_SXS.mid':b'MIDI'},
        'Badge_Guide':{'Guide_RCMD.pdf':b'%PDF-guide'},
        'Badge_Hidden':{'Only_SXS_HDDN.pdf':b'%PDF-hidden'},
        'Badge_Empty':{'Only_SXS.pdf':b''},
        'Badge_Recovery':{'zzDEL!Only_SXS.pdf':b'%PDF-recovery'},
        'Badge_Symlink':{},
    }
    for tune,files in fixtures.items():
        folder=app/'library'/tune;folder.mkdir()
        for name,data in files.items():(folder/name).write_bytes(data)
    (app/'library/Badge_Symlink/Only_SXS.pdf').symlink_to(app/'library/Badge_Valid/SXS_Start.PDF')
    with socket.socket() as sock:sock.bind(('127.0.0.1',0));port=sock.getsockname()[1]
    log=open(temp/'server.log','w+')
    server=subprocess.Popen([PHP,'-S',f'127.0.0.1:{port}','-t',str(temp)],stdout=log,stderr=log)
    base=f'http://127.0.0.1:{port}/nadin/'
    try:
        for _ in range(100):
            try:
                if requests.get(base+'login.php',timeout=2).status_code==200:break
            except requests.ConnectionError:pass
            time.sleep(.04)
        def login(email):
            s=requests.Session();f=BeautifulSoup(s.get(base+'login.php').text,'html.parser').find('form')
            data={x['name']:x.get('value','') for x in f.select('input[type=hidden][name]')}
            data.update(u=email,p=hashlib.sha1((email+'evolution-state').encode()).hexdigest()[7:13])
            ok(s.post(base+'login2.php',data=data,allow_redirects=False).status_code in [302,303],'login '+email)
            return s
        a=login('admin@example.invalid');b=login('admin@example.invalid');manager=login('manager@example.invalid')
        def api(data,session=a,post=False,files=None):
            data=dict(data,area='gigs')
            if post:
                token=session.get(base+'nadinmodule/admin-api.php',params={'area':'gigs','action':'token'}).json()['csrf']
                data['csrf']=token
                return session.post(base+'nadinmodule/admin-api.php',data=data,files=files) if files else session.post(base+'nadinmodule/admin-api.php',json=data)
            return session.get(base+'nadinmodule/admin-api.php',params=data)
        for session,label in [(a,'admin'),(manager,'gig manager')]:
            response=api({},session)
            ok(response.status_code==200,'catalogue is readable for '+label)
            catalog={x['id']:set(x['files']) for x in response.json()['catalog']}
            ok(catalog['Badge_Valid']=={'sx','Audio'},'valid uppercase PDF/MP3 and prefix markers recognised for '+label)
            for tune in list(fixtures)[1:]:ok(catalog[tune]==set(),'no false available-part badge: '+tune+'/'+label)
        def create(title,session=a):
            response=api(dict(action='save',id='',date='2099-02-01',title=title,infos='Original gig information',tunes=['Badge_Valid','Set / Pause','Badge_Valid']),session,True)
            ok(response.status_code==200,'create '+title)
            return response.json()['gig']
        for action in ['save','archive','trash','copy','rename','attachment']:
            original=create('Identity '+action)
            path=app/'gigreps'/original['id'];original_bytes=path.read_bytes()
            moved=api(dict(action='rename',id=original['id'],mode='current',revision=original['revision'],date='2099-02-01',title='Moved '+action),b,True)
            ok(moved.status_code==200,'second editor renames '+action)
            moved_id=moved.json()['gig']['id'];moved_path=app/'gigreps'/moved_id
            replacement=create('Identity '+action,b)
            ok(path.read_bytes()==original_bytes,'replacement deliberately has identical original gig bytes '+action)
            before={str(p.relative_to(app)):p.read_bytes() for directory in ['gigreps','gigrepsarchiv','gigrepsdeleted','abo/zip'] for p in (app/directory).rglob('*') if p.is_file()}
            payload=dict(action=action,id=original['id'],mode='current',revision=original['revision'])
            if action=='save':payload.update(infos='STALE FORM MUST NOT WRITE',tunes=['Badge_Empty'])
            if action in ['copy','rename']:payload.update(date='2099-02-02',title='Stale '+action)
            if action=='attachment':
                payload.pop('mode')
                response=api(payload,a,True,files={'pdf':('stale.pdf',b'%PDF-stale','application/pdf')})
            else:response=api(payload,a,True)
            ok(response.status_code==409,'stale '+action+' rejects an identically named replacement gig')
            after={str(p.relative_to(app)):p.read_bytes() for directory in ['gigreps','gigrepsarchiv','gigrepsdeleted','abo/zip'] for p in (app/directory).rglob('*') if p.is_file()}
            ok(before==after,'stale '+action+' preserves all gig and attachment bytes and filenames')
            ok(moved_path.read_bytes()==original_bytes,'stale '+action+' preserves renamed original')
            if path.exists():
                fresh=api(dict(action='gig',mode='current',id=replacement['id']),b).json()['gig']
                saved=api(dict(action='save',id=fresh['id'],revision=fresh['revision'],infos='Fresh form on replacement',tunes=fresh['tunes']),b,True)
                ok(saved.status_code==200 and b'Fresh form on replacement' in path.read_bytes(),'fresh form still saves replacement '+action)
                fresh=saved.json()['gig']
                again=api(dict(action='save',id=fresh['id'],revision=fresh['revision'],infos='Second fresh save',tunes=fresh['tunes']),b,True)
                ok(again.status_code==200 and b'Second fresh save' in path.read_bytes(),'returned revision supports consecutive legitimate saves '+action)
        log.flush();log.seek(0);errors=log.read()
        ok('Fatal error' not in errors and 'Parse error' not in errors,'no PHP fatal/parse errors')
        print(('FAIL' if failures else 'PASS')+f': {checks} gig identity and catalogue checks ({ROOT.name})',flush=True)
        if failures:raise AssertionError('; '.join(failures))
    finally:server.terminate();server.wait(timeout=5);log.close()
