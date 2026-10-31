"""Real Chromium layout checks on disposable classic NADIN installations.

Select PHP with NADIN_PHP, Chromium with NADIN_CHROMIUM and optionally compare
an older checkout with NADIN_LAYOUT_BASELINE. No production data or mail is used.
"""
import hashlib
import json
import os
from pathlib import Path
import re
import shutil
import socket
import subprocess
import tempfile
import time

import requests
from bs4 import BeautifulSoup

ROOT = Path(__file__).resolve().parents[1]
PHP = os.environ.get('NADIN_PHP', 'php')
SEP = '* - * - * - * * * - * - * - *'
checks = 0


def ok(value, label):
    global checks
    assert value, label
    checks += 1


with tempfile.TemporaryDirectory(prefix='nadin-classic-layout-') as folder:
    temp = Path(folder)
    cases = []
    snapshots = []
    roots = [('release', ROOT)]
    if os.environ.get('NADIN_LAYOUT_BASELINE'):
        roots.append(('baseline', Path(os.environ['NADIN_LAYOUT_BASELINE'])))
    for label, root in roots:
        for side in ['yes', 'no', 'missing']:
            for width in [240, 375]:
                case_id = label + '-' + side + '-' + str(width)
                app = temp / case_id / 'nadin'
                shutil.copytree(root, app, ignore=shutil.ignore_patterns('.git', 'vendor'))
                config = app / 'config.inc.php'
                text = config.read_text().replace("$salt='???'", "$salt='layout-fixture'")
                setting = '' if side == 'missing' else "$rendergiglistontheleft='" + side + "';"
                text = re.sub(r'(?m)^\$rendergiglistontheleft\s*=.*$', setting, text)
                text = re.sub(r"(?m)^\$ifleftthenthiswidth\s*=.*$", "$ifleftthenthiswidth='" + str(width) + "px';", text)
                text = text.replace("$offerrcmdmp3saszip='yes'", "$offerrcmdmp3saszip='no'")
                text = text.replace("$offersheetsaszip='yes'", "$offersheetsaszip='no'")
                config.write_text(text)
                (app / 'abo/zip').mkdir(parents=True, exist_ok=True)
                (app / 'userdatei/userdatei.txt').write_text(
                    'Member,member@example.invalid,31.12.2099,vgigs=1,varchiv=1,vlibrary=1,vsaxes=1,\n'
                    'Admin,admin@example.invalid,31.12.2099,vgigs=1,varchiv=1,vlibrary=1,vsaxes=1,mgigs=1,musers=1,\n')
                tune = app / 'library/Layout_Tune'
                tune.mkdir(exist_ok=True)
                (tune / 'Layout_Tune_SXS.pdf').write_bytes(b'%PDF-1.4\nlayout fixture\n%%EOF')
                name = '2026-11-01 Layout 83.txt'
                for module in ['gigreps', 'gigrepsarchiv']:
                    for existing in (app / module).iterdir():
                        if existing.is_file() and not existing.name.startswith('.'):
                            existing.unlink()
                    for i in range(100):
                        (app / module / ('2026-11-01 Layout ' + str(i).zfill(2) + '.txt')).write_text(
                            'Gig info ' + module + '\n' + SEP + '\nSet 1:\nLayout_Tune\nPause:\nLayout_Tune')
                    path = app / module / name
                    snapshots.append((path, path.read_bytes()))
                (app / 'abo/zip/2026-11-01 Layout 83.pdf').write_bytes(b'%PDF-1.4\nattachment\n%%EOF')
                cases.append(dict(id=case_id, label=label, side=side, width=width, name=name))
    with socket.socket() as sock:
        sock.bind(('127.0.0.1', 0))
        port = sock.getsockname()[1]
    log = open(temp / 'server.log', 'w+')
    proc = subprocess.Popen([PHP, '-d', 'short_open_tag=1', '-d', 'opcache.enable=0',
                             '-S', f'127.0.0.1:{port}', '-t', str(temp)], stdout=log, stderr=log)
    base = f'http://127.0.0.1:{port}'
    try:
        for _ in range(100):
            try:
                requests.get(base + '/' + cases[0]['id'] + '/nadin/login.php', timeout=2)
                break
            except requests.ConnectionError:
                time.sleep(.04)
        else:
            raise AssertionError('PHP server did not start')
        for case in cases:
            case['url'] = base + '/' + case['id'] + '/nadin'
            case['accounts'] = {}
            for role in ['member', 'admin']:
                session = requests.Session()
                response = session.get(case['url'] + '/login.php')
                form = BeautifulSoup(response.text, 'html.parser').find('form')
                data = {e['name']: e.get('value', '') for e in form.select('input[type=hidden][name]')}
                email = role + '@example.invalid'
                data.update(u=email, p=hashlib.sha1((email + 'layout-fixture').encode()).hexdigest()[7:13])
                response = session.post(case['url'] + '/login2.php', data=data, allow_redirects=False)
                ok(response.status_code in [302, 303], 'initial-password login: ' + case['id'] + '/' + role)
                case['accounts'][role] = session.cookies.get_dict()
                for module in ['gigreps', 'gigrepsarchiv']:
                    page = session.get(case['url'] + '/', params={'m': module, 'c': case['name']})
                    doc = BeautifulSoup(page.text, 'html.parser')
                    ok(page.status_code == 200 and 'Gig info ' + module in page.text,
                       'correct source: ' + case['id'] + '/' + role + '/' + module)
                    if case['label'] == 'release':
                        nav = doc.find(id='nadin-gig-list')
                        ok(nav is not None and len(nav.select('a[href]')) == 101,
                           'complete gig navigation: ' + case['id'] + '/' + module)
                        ok('setTimeout("arch()"' not in page.text and 'function arch()' not in page.text,
                           'no recurring layout timer: ' + case['id'] + '/' + module)
        fixture = temp / 'cases.json'
        fixture.write_text(json.dumps(cases))
        subprocess.run(['node', str(ROOT / 'tests/gigrep-layout-browser.cjs'), str(fixture)], check=True)
        for path, original in snapshots:
            ok(path.read_bytes() == original, 'viewing does not rewrite GigRep: ' + str(path.relative_to(temp)))
        log.flush()
        log.seek(0)
        errors = log.read()
        ok('Fatal error' not in errors and 'Parse error' not in errors, 'no PHP fatal or parse errors')
        print(f'PASS: {checks} classic GigRep HTTP/source checks')
    finally:
        proc.terminate()
        proc.wait(timeout=5)
        log.close()
