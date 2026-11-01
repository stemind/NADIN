"""Render actual current/archive views and check the default layout in Chromium."""
import hashlib,json,os,re,shutil,socket,subprocess,tempfile,time
from pathlib import Path
import requests
from bs4 import BeautifulSoup
ROOT=Path(__file__).resolve().parents[1]
PHP=os.environ.get('NADIN_PHP','php')
checks=0
def ok(value,label):
    global checks
    assert value,label
    checks+=1
with tempfile.TemporaryDirectory(prefix='nadin-default-left-') as directory:
    temp=Path(directory);app=temp/'band/nadin'
    migration=temp/'migration';migration.mkdir()
    for side in ['yes','no','missing','','auto']:
        setting='' if side=='missing' else "$rendergiglistontheleft='"+side+"';\n"
        before="<?php\n"+setting+"$quotehandling='off';\n?>"
        (migration/'config.inc.php').write_text(before)
        shutil.copy2(ROOT/'corr.php',migration/'corr.php')
        subprocess.run([PHP,'corr.php'],cwd=migration,check=True,capture_output=True)
        after=(migration/'config.inc.php').read_text()
        ok(after==before.replace("$quotehandling='off'","$quotehandling='DEPRECATED DO NOT USE'"),
           'legacy migration preserves layout setting: '+repr(side))
        ok(not (migration/'corr.php').exists(),'legacy migration keeps its existing self-cleanup')
    shutil.copytree(ROOT,app,ignore=shutil.ignore_patterns('.git','vendor'))
    config=app/'config.inc.php'
    original=config.read_text().replace("$salt='???'","$salt='layout-fixture'")
    original=original.replace("$offerrcmdmp3saszip='yes'","$offerrcmdmp3saszip='no'").replace("$offersheetsaszip='yes'","$offersheetsaszip='no'")
    original=re.sub(r"(?m)^\$ifleftthenthiswidth\s*=.*$","$ifleftthenthiswidth='240px';",original)
    config.write_text(original)
    (app/'abo/zip').mkdir(parents=True,exist_ok=True)
    rights='vgigs=1,varchiv=1,vlibrary=1,vsaxes=1,'
    (app/'userdatei/userdatei.txt').write_text('Member,member@example.invalid,31.12.2099,'+rights+'\nAdmin,admin@example.invalid,31.12.2099,'+rights+'mgigs=1,musers=1,\n')
    tune=app/'library/Layout_Tune';tune.mkdir(exist_ok=True)
    (tune/'Layout_Tune_SXS.pdf').write_bytes(b'%PDF-1.4\nfixture\n%%EOF')
    for folder in ['gigreps','gigrepsarchiv']:
        for name in ['Layout One.txt','Layout Two.txt']:
            (app/folder/name).write_text('Fixture info\n* - * - * - * * * - * - * - *\nStart:\nLayout_Tune\nLayout_Tune\n')
    with socket.socket() as sock:sock.bind(('127.0.0.1',0));port=sock.getsockname()[1]
    log=open(temp/'server.log','w+')
    process=subprocess.Popen([PHP,'-d','opcache.enable=0','-S',f'127.0.0.1:{port}','-t',str(temp)],stdout=log,stderr=log)
    url=f'http://127.0.0.1:{port}/band/nadin'
    try:
        for _ in range(100):
            try:
                if requests.get(url+'/login.php',timeout=2).status_code==200:break
            except requests.ConnectionError:pass
            time.sleep(.04)
        else:raise AssertionError('PHP did not start')
        accounts={}
        for role in ['member','admin']:
            session=requests.Session();page=session.get(url+'/login.php')
            form=BeautifulSoup(page.text,'html.parser').find('form')
            data={e['name']:e.get('value','') for e in form.select('input[type=hidden][name]')}
            email=role+'@example.invalid';data.update(u=email,p=hashlib.sha1((email+'layout-fixture').encode()).hexdigest()[7:13])
            response=session.post(url+'/login2.php',data=data,allow_redirects=False)
            ok(response.status_code in [302,303],'fixture login: '+role);accounts[role]=session.cookies.get_dict()
        (temp/'fixture.json').write_text(json.dumps(dict(url=url,accounts=accounts,config=str(config),original=original)))
        subprocess.run(['node',str(ROOT/'tests/gig-defaults-browser.cjs'),str(temp/'fixture.json')],check=True)
        log.flush();log.seek(0);errors=log.read()
        ok('Fatal error' not in errors and 'Parse error' not in errors,'no PHP errors')
        print('PASS:',checks,'default-layout HTTP/setup checks')
    finally:process.terminate();process.wait(timeout=5);log.close()
