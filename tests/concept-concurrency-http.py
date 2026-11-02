"""Two editing windows must not cross notes into a recreated tune directory."""
import fcntl, hashlib, json, os, re, shutil, socket, subprocess, tempfile, time
from pathlib import Path
import requests
from bs4 import BeautifulSoup

ROOT=Path(os.environ.get('NADIN_APP_ROOT',Path(__file__).resolve().parents[1])).resolve()
PHP=os.environ.get('NADIN_PHP','php');checks=0;failures=[]
SEP='* - * - * - * * * - * - * - *'
def ok(value,label):
    global checks
    checks+=1
    if not value:failures.append(label);print('FAIL:',label,flush=True)
with tempfile.TemporaryDirectory(prefix='nadin-edit-conflicts-') as temporary:
    temp=Path(temporary);app=temp/'nadin';shutil.copytree(ROOT,app,ignore=shutil.ignore_patterns('.git','vendor'))
    config=app/'config.inc.php';config.write_text(config.read_text().replace("$salt='???'","$salt='conflict-concepts'"))
    (app/'userdatei/userdatei.txt').write_text('Editor,editor@example.invalid,31.12.2099,vgigs=1,vlibrary=1,vsaxes=1,upaudio=1,uppdf=1,mgigs=1,musers=1,\n')
    tune=app/'library/Concurrent_Tune';tune.mkdir();(tune/'infos.txt').write_text('Original notes\n'+SEP+'\nhttps://example.invalid/')
    with socket.socket() as sock:sock.bind(('127.0.0.1',0));port=sock.getsockname()[1]
    log=open(temp/'server.log','w+');server=subprocess.Popen([PHP,'-S',f'127.0.0.1:{port}','-t',str(temp)],stdout=log,stderr=log)
    base=f'http://127.0.0.1:{port}/nadin/'
    try:
        for attempt in range(100):
            try:
                if requests.get(base+'login.php',timeout=2).status_code==200:break
            except requests.ConnectionError:pass
            time.sleep(.04)
        def login():
            s=requests.Session();doc=BeautifulSoup(s.get(base+'login.php').text,'html.parser');form=doc.find('form')
            data={x['name']:x.get('value','') for x in form.select('input[type=hidden][name]')};data.update(u='editor@example.invalid',p=hashlib.sha1(b'editor@example.invalidconflict-concepts').hexdigest()[7:13])
            ok(s.post(base+'login2.php',data=data,allow_redirects=False).status_code in [302,303],'independent editor login');return s
        a=login();b=login()
        def form(session):
            page=session.get(base+'nadinmodule/upload.php?tune=Concurrent_Tune');doc=BeautifulSoup(page.text,'html.parser');f=doc.find('form',attrs={'name':'form'})
            data={x['name']:x.get('value','') for x in f.select('input[name]')}
            data.update({x['name']:x.get_text() for x in f.select('textarea[name]')})
            for key in ['token','revision']:
                match=re.search(r'var '+key+r'=("[^"\n]*")',page.text)
                if match:data['csrf' if key=='token' else key]=json.loads(match[1])
            ok(bool(data.get('csrf')) and bool(data.get('revision')),'notes form carries conflict and CSRF tokens');return data
        fa=form(a);fb=form(b)
        result=a.post(base+'nadinmodule/uploadsave.php',data=dict(fa,infos='First editor change'),headers={'Accept':'application/json'})
        ok(result.status_code==200,'first editor saves normally');first=(tune/'infos.txt').read_bytes()
        rejected=b.post(base+'nadinmodule/uploadsave.php',data=dict(fb,infos='Stale second editor change'),headers={'Accept':'application/json'})
        ok(rejected.status_code==409,'stale second editor is rejected');ok((tune/'infos.txt').read_bytes()==first,'stale save preserves the first editor bytes')
        fb=form(b);moved=tune.with_name('Renamed_Concurrent_Tune')
        with (app/'userdatei/admin-v12-lock.php').open('r+b') as lock:
            fcntl.flock(lock,fcntl.LOCK_EX)
            try:tune.rename(moved);tune.mkdir();(tune/'infos.txt').write_bytes(first);(tune/'new-identity.txt').write_bytes(b'NEW TUNE')
            finally:fcntl.flock(lock,fcntl.LOCK_UN)
        rejected=b.post(base+'nadinmodule/uploadsave.php',data=dict(fb,infos='Must never reach the recreated tune'),headers={'Accept':'application/json'})
        ok(rejected.status_code==409,'stale editor rejects a recreated tune even when its notes bytes are identical')
        ok((tune/'infos.txt').read_bytes()==first,'recreated tune keeps its notes untouched')
        ok((moved/'infos.txt').read_bytes()==first,'renamed original tune keeps its notes untouched')
        fresh=form(b);saved=b.post(base+'nadinmodule/uploadsave.php',data=dict(fresh,infos='Fresh editor on the new tune'),headers={'Accept':'application/json'})
        ok(saved.status_code==200 and b'Fresh editor on the new tune' in (tune/'infos.txt').read_bytes(),'reopening the form permits the new tune to be edited')
        if 'application/json' in saved.headers.get('Content-Type',''):
            fresh['revision']=saved.json()['revision']
            saved=b.post(base+'nadinmodule/uploadsave.php',data=dict(fresh,infos='Next save in the same open editor'),headers={'Accept':'application/json'})
            ok(saved.status_code==200 and b'Next save in the same open editor' in (tune/'infos.txt').read_bytes(),'returned revision supports the next save without reloading')
        before=(tune/'infos.txt').read_bytes();anonymous=requests.post(base+'nadinmodule/uploadsave.php',data=dict(fresh,infos='Anonymous write'),allow_redirects=False)
        ok((tune/'infos.txt').read_bytes()==before,'a form token without its session cannot write notes')
        ok((moved/'infos.txt').read_bytes()==first,'later editing never modifies the renamed original')
        log.flush();log.seek(0);errors=log.read();ok('Fatal error' not in errors and 'Parse error' not in errors,'no PHP fatal/parse errors')
        print(('FAIL' if failures else 'PASS')+f': {checks} independent-editor conflict checks ({ROOT.name})',flush=True)
        if failures:raise AssertionError('; '.join(failures))
    finally:server.terminate();server.wait(timeout=5);log.close()
