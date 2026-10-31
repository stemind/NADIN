"""forScore headings: real HTTP forms in disposable NADIN V9+ installations."""
import base64
import hashlib
import os
from pathlib import Path
import shutil
import socket
import subprocess
import tempfile
import time
import xml.etree.ElementTree as ET

import requests
from bs4 import BeautifulSoup

ROOT = Path(__file__).resolve().parents[1]
PHP = os.environ.get('NADIN_PHP', 'php')
SEPARATOR = '* - * - * - * * * - * - * - *'
checks = 0


def ok(value, label):
    global checks
    assert value, label
    checks += 1


def fields(response):
    form = BeautifulSoup(response.text, 'html.parser').find('form')
    assert form is not None, response.text[:1000]
    data = {el['name']: el.get('value', '') for el in form.select('input[type=hidden][name]')}
    for select in form.select('select[name]'):
        option = select.find('option', selected=True) or select.find('option')
        data[select['name']] = option.get('value', '') if option else ''
    return data


with tempfile.TemporaryDirectory(prefix='nadin-forscore-http-') as temp:
    temp = Path(temp)
    app = temp / 'band' / 'nadin'
    shutil.copytree(ROOT, app, ignore=shutil.ignore_patterns('.git'))
    config = app / 'config.inc.php'
    original_config = config.read_text()
    assert "$salt='???'" in original_config
    config.write_text(original_config.replace("$salt='???'", "$salt='forscore-fixture'"))
    (app / 'userdatei/userdatei.txt').write_text(
        'Fixture,fixture@example.invalid,31.12.2099,vgigs=1,vsaxes=1,varchiv=1,vlibrary=1,\n')

    def asset(tune, filename):
        folder = app / 'library' / tune
        folder.mkdir(exist_ok=True)
        (folder / filename).write_bytes(b'%PDF-1.4\n' + filename.encode() + b'\n%%EOF')

    def rep(name, entries, archive=False):
        path = app / ('gigrepsarchiv' if archive else 'gigreps') / name
        path.write_text('Fixture\n' + SEPARATOR + '\n' + '\n'.join(entries))
        return path

    asset('Heading_A', 'Heading_A_SXS.pdf')
    asset('Heading_B', 'Heading_B_PRTS.pdf')
    heading = 'Aus_Gigrep: Grüße & "Zugabe"'
    entries = [heading, 'Heading_A', 'Pause:', 'Heading_B', heading, 'Heading_A']
    source = rep('Headings.txt', entries)
    original_rep = source.read_bytes()
    rep('Headings.txt', entries, archive=True)
    rep('Only headings.txt', [heading, 'Pause:', heading])
    (app / 'library/Empty_Heading_Tune').mkdir()
    asset('Wrong_Section', 'Wrong_Section_TBS.pdf')
    asset('Hidden_Heading_Tune', 'Hidden_Heading_Tune_SXS_HDDN.pdf')
    (app / 'library/Dangling_Heading_Tune').symlink_to(app / 'library/Absent_Source', target_is_directory=True)
    rep('Missing.txt', ['Start:', 'Empty_Heading_Tune', 'Wrong_Section',
                        'Hidden_Heading_Tune', 'Dangling_Heading_Tune'])
    asset('Ambiguous_Heading_Tune', 'Ambiguous_Heading_Tune_One_SXS.pdf')
    asset('Ambiguous_Heading_Tune', 'Ambiguous_Heading_Tune_Two_SXS.pdf')
    rep('Ambiguous.txt', ['Start:', 'Ambiguous_Heading_Tune'])
    rep('Switch.txt', ['Switch_Heading'])
    escaped_heading = '<img src=x onerror=alert(1)>'
    rep('Escaped.txt', [escaped_heading])
    with socket.socket() as sock:
        sock.bind(('127.0.0.1', 0))
        port = sock.getsockname()[1]
    log = open(temp / 'server.log', 'w+')
    process = subprocess.Popen([PHP, '-S', f'127.0.0.1:{port}', '-t', str(temp)],
                               stdout=log, stderr=log)
    base = f'http://127.0.0.1:{port}/band/nadin'
    try:
        for _ in range(100):
            try:
                if requests.get(base + '/login.php', timeout=2).status_code == 200:
                    break
            except requests.ConnectionError:
                pass
            time.sleep(.04)
        else:
            raise AssertionError('PHP server did not start')
        session = requests.Session()
        data = fields(session.get(base + '/login.php'))
        data.update(u='fixture@example.invalid',
                    p=hashlib.sha1(b'fixture@example.invalidforscore-fixture').hexdigest()[7:13])
        login = session.post(base + '/login2.php', data=data, allow_redirects=False)
        ok(login.status_code in (302, 303), 'synthetic login works with legacy and current accounts')
        endpoint = base + '/get/forscore.php'

        def preview(name, archive=False):
            response = session.get(endpoint, params={
                'm': 'gigrepsarchiv' if archive else 'gigreps', 'c': name, 'profile': 'sx'})
            ok(response.status_code == 200 and 'reviewed_profile' in response.text,
               'preview opens: ' + name)
            return response

        def download(response, **overrides):
            data = fields(response)
            data.update(action='download', **overrides)
            return session.post(endpoint, data=data)

        response = preview('Headings.txt')
        soup = BeautifulSoup(response.text, 'html.parser')
        headers = soup.select('.nadin-forscore-heading')
        ok([x.strong.get_text() for x in headers] == [heading, 'Pause:'],
           'preview preserves literal heading text without converting underscores')
        ok(not any(x.select('.nadin-forscore-warning,select') for x in headers),
           'headings have no missing-note warnings or PDF choices')
        ok(soup.find('input', attrs={'name': 'placeholders'}) is None,
           'headings do not require a confirmation checkbox')
        result = download(response, mode='new', initial='1')
        ok(result.status_code == 200 and '.4ss' in result.headers.get('Content-Disposition', ''),
           'embedded download succeeds without heading confirmation')
        xml = ET.fromstring(result.content)
        expected = [('placeholder', heading), ('score', 'Heading A'), ('placeholder', 'Pause:'),
                    ('score', 'Heading B'), ('placeholder', heading), ('score', 'Heading A')]
        ok([(x.tag, x.get('title')) for x in xml] == expected,
           'download preserves heading/tune order and repetitions')
        embedded = [x for x in xml if 'data' in x.attrib]
        ok(len(embedded) == 2, 'each new PDF is embedded once')
        ok(all(base64.b64decode(x.get('data')).startswith(b'%PDF-') for x in embedded),
           'embedded PDF data remains intact')
        result = download(response, mode='references')
        references = ET.fromstring(result.content)
        ok(result.status_code == 200 and not any('data' in x.attrib for x in references)
           and len(references.findall('placeholder')) == 3,
           'reference-only export includes headings without confirmation')
        inventory = 'Filename\n' + '\n'.join(x.get('path') for x in embedded) + '\n'
        data = fields(response)
        data.update(action='review', mode='new')
        reviewed = session.post(endpoint, data=data,
                                files={'csv': ('inventory.csv', inventory, 'text/csv')})
        ok(reviewed.status_code == 200, 'fresh CSV can be reviewed with headings')
        result = download(reviewed, mode='new')
        reused = ET.fromstring(result.content)
        ok(result.status_code == 200 and not any('data' in x.attrib for x in reused),
           'current inventory reuses existing PDFs alongside headings')
        ok(source.read_bytes() == original_rep, 'source Rep remains byte-for-byte unchanged')

        for archive, name in ((False, 'Only headings.txt'), (True, 'Headings.txt')):
            result = download(preview(name, archive), mode='references')
            parsed = ET.fromstring(result.content)
            ok(result.status_code == 200 and len(parsed.findall('placeholder')) == 3,
               'heading-only or archived Rep succeeds without confirmation')

        response = preview('Missing.txt')
        soup = BeautifulSoup(response.text, 'html.parser')
        ok(soup.find('input', attrs={'name': 'placeholders'}) is not None,
           'real missing/hidden/blocked notes retain the confirmation control')
        ok(len(soup.select('.nadin-forscore-heading')) == 1,
           'empty, wrong-section, hidden and symlink sources remain tunes')
        result = download(response, mode='new', initial='1')
        ok(result.status_code == 400 and 'Missing notes require explicit placeholder confirmation.' in result.text,
           'unconfirmed missing notes block the download')
        result = download(response, mode='new', initial='1', placeholders='1')
        ok(result.status_code == 200 and len(ET.fromstring(result.content).findall('placeholder')) == 5,
           'explicitly confirmed missing notes coexist with automatic headings')

        response = preview('Ambiguous.txt')
        result = download(response, mode='new', initial='1', placeholders='1')
        ok(result.status_code == 400 and 'Choose a PDF for every ambiguous tune.' in result.text,
           'placeholder confirmation never bypasses an ambiguous PDF choice')

        response = preview('Switch.txt')
        switch = app / 'library/Switch_Heading'
        switch.mkdir()
        result = download(response, mode='references', placeholders='1')
        ok(result.status_code == 400 and 'changed since the preview' in result.text,
           'heading becoming an empty tune invalidates the reviewed export')
        switch.rmdir()
        stale = download(result, mode='references', placeholders='1')
        ok(stale.status_code == 400 and 'changed since the preview' in stale.text,
           'empty tune becoming a heading also requires a fresh review')

        response = preview('Escaped.txt')
        soup = BeautifulSoup(response.text, 'html.parser')
        header = soup.select_one('.nadin-forscore-heading')
        ok(header.strong.get_text() == escaped_heading and not header.find('img'),
           'heading markup is displayed as text in HTML')
        result = download(response, mode='references')
        ok(result.status_code == 200 and ET.fromstring(result.content)[0].get('title') == escaped_heading,
           'heading XML is escaped without text loss')
        data = fields(response)
        data.update(action='download', csrf='invalid')
        result = session.post(endpoint, data=data)
        ok(result.status_code == 400 and 'session changed' in result.text,
           'heading-only exports retain CSRF protection')
        log.flush()
        log.seek(0)
        errors = log.read()
        ok('Fatal error' not in errors and 'Parse error' not in errors,
           'no PHP fatal or parse errors')
        print(f'PASS: {checks} forScore heading HTTP checks')
    finally:
        process.terminate()
        process.wait(timeout=5)
        log.close()
