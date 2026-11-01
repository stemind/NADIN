"""Run V17 end-to-end scenarios against a disposable rendered installation."""
import os, shutil, socket, subprocess, tempfile, time
from pathlib import Path
import requests

ROOT=Path(__file__).resolve().parents[1]
PHP=os.environ.get('NADIN_PHP','php')
with tempfile.TemporaryDirectory(prefix='nadin-v17-browser-') as temp:
    temp=Path(temp)
    subprocess.run(['python','tests/render-preview.py',str(temp/'fixture')],cwd=ROOT,check=True)
    app=temp/'fixture/app'
    with (app/'userdatei/userdatei.txt').open('a') as file:file.write('Expired fixture,expired@example.invalid,01.01.2000,vgigs=1,vsaxes=1,\n')
    legacy=os.fsencode(app/'library')+b'/Legacy_M\xfcller';os.mkdir(legacy)
    with open(legacy+b'/Legacy_SXS.pdf','wb') as file:file.write(b'%PDF-legacy-fixture')
    with socket.socket() as sock:sock.bind(('127.0.0.1',0));port=sock.getsockname()[1]
    log=open(temp/'browser-server.log','w+')
    process=subprocess.Popen([PHP,'-S',f'127.0.0.1:{port}','-t',str(app)],stdout=log,stderr=log)
    base=f'http://127.0.0.1:{port}'
    try:
        for _ in range(100):
            try:
                if requests.get(base+'/login.php').status_code==200:break
            except requests.ConnectionError:pass
            time.sleep(.04)
        env=dict(os.environ,NADIN_V17_URL=base,NADIN_V17_APP=str(app))
        subprocess.run(['node','tests/v17-browser.cjs'],cwd=ROOT,env=env,check=True,timeout=240)
        log.flush();log.seek(0);text=log.read()
        errors=[line for line in text.splitlines() if any(word in line for word in ['PHP Warning:','PHP Fatal error:','PHP Deprecated:'])]
        if errors:raise AssertionError('\n'.join(errors))
        print('PASS: V17 browser server produced no PHP warnings, errors or deprecations')
    finally:
        process.terminate();process.wait(timeout=5);log.close()
