"""NADIN 7.7 forScore integration using a disposable 7.6-based installation."""
import base64
import hashlib
import os
import json
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
DEFAULT_CHARSET = os.environ.get('NADIN_DEFAULT_CHARSET', 'UTF-8')
EXPECTED_VERSION = '7.7'
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
    rep('Long required.txt', ['Heading ' + str(i) + ':' for i in range(80)] + ['Ambiguous_Heading_Tune'])
    escaped_heading = '<img src=x onerror=alert(1)>'
    rep('Escaped.txt', [escaped_heading])
    rep('Müller – Probe.txt', [heading, 'Heading_A'])
    latin_heading = 'zum weiter Üben: Müller – Grüsse €'
    latin_rep = rep('Latin headings.txt', [])
    latin_rep.write_bytes(('Fixture\n' + SEPARATOR + '\n').encode('ascii')
                          + latin_heading.encode('cp1252') + b'\nHeading_A')
    with socket.socket() as sock:
        sock.bind(('127.0.0.1', 0))
        port = sock.getsockname()[1]
    log = open(temp / 'server.log', 'w+')
    process = subprocess.Popen([PHP, '-d', 'default_charset=' + DEFAULT_CHARSET,
                                '-S', f'127.0.0.1:{port}', '-t', str(temp)],
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
        login_page = session.get(base + '/login.php')
        ok('NADIN ' + EXPECTED_VERSION in BeautifulSoup(login_page.text, 'html.parser').get_text(' ', strip=True),
           'login page renders the current branch/release version')
        data = fields(login_page)
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
            ok(response.headers.get('Content-Type', '').lower() == 'text/html; charset=utf-8',
               'HTTP charset is UTF-8 regardless of server default: ' + name)
            return response

        def download(response, **overrides):
            data = fields(response)
            data.update(action='download', **overrides)
            return session.post(endpoint, data=data)

        response = preview('Headings.txt')
        version_page = BeautifulSoup(response.text, 'html.parser')
        ok(version_page.title.get_text() in ('forScore – NADIN', 'forScore - NADIN'),
           'export window title has a simple task name without a version')
        ok(any(h.get_text(' ', strip=True) == 'forScore' for h in version_page.find_all(['h1', 'h2'])),
           'export heading renders the current branch/release version')
        ok('Heading — included automatically as a text placeholder.' in response.text and 'forScore’s Backup panel' in response.text,
           'browser decoding preserves punctuation in static interface text')
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
        ok(result.headers.get('Content-Type', '').lower() == 'text/html; charset=utf-8',
           'validation errors retain the explicit UTF-8 response charset')

        response = preview('Müller – Probe.txt')
        ok('Müller – Probe — sx' in response.text,
           'UTF-8 filename accents and dash render correctly through the HTTP charset')
        response = preview('Latin headings.txt')
        soup = BeautifulSoup(response.text, 'html.parser')
        ok(soup.select_one('.nadin-forscore-heading strong').get_text() == latin_heading,
           'legacy Windows-1252 headings are converted once and decoded as UTF-8')
        result = download(response, mode='new', initial='1')
        ok(result.status_code == 200
           and result.headers.get('Content-Type', '').lower() == 'application/octet-stream',
           'HTML charset does not replace the binary download content type')
        ok(ET.fromstring(result.content)[0].get('title') == latin_heading,
           'downloaded XML retains exact Unicode heading text')

        # The two existing screens gain only a link in the Print export block.
        for module, name in (('gigreps', 'Headings.txt'), ('gigrepsarchiv', 'Headings.txt')):
            screen = session.get(base + '/', params={'m': module, 'c': name})
            soup = BeautifulSoup(screen.text, 'html.parser')
            link = soup.find('a', href=lambda href: href and href.startswith('get/forscore.php?'))
            ok(link is not None, 'forScore link is present in ' + module)
            print_link = link.find_previous('a')
            ok(print_link.get_text() == 'print↓' and print_link.parent is link.parent,
               'forScore sits immediately below Print in ' + module)
            ok(link.get('style') == print_link.get('style') and link.get('target') == '_blank',
               'forScore retains Print styling and target in ' + module)


        # Current UI: one explicit import choice; no manual review dependency.
        workflow_blank = preview('Headings.txt')
        workflow_soup = BeautifulSoup(workflow_blank.text, 'html.parser')
        buttons = workflow_soup.select('button[name="action"][value="download"]')
        ok(len(buttons) == 2 and [b.get('data-download') for b in buttons] == ['top', 'bottom'],
           'top and bottom download actions share the authenticated form')
        action_css = workflow_soup.select_one('head link[href*="forscore-actions.css"]')
        ok(action_css is not None and session.get(base + '/nadinmodule/forscore-actions.css').status_code == 200,
           'scoped download styling is included in the head and actually served')
        ok(workflow_soup.find('select', attrs={'name': 'workflow', 'required': True}) is not None,
           'import intent is one required selection')
        ok(workflow_soup.find('select', attrs={'name': 'mode'}) is None
           and workflow_soup.find('input', attrs={'name': 'initial'}) is None,
           'scattered mode/initial controls are replaced by one import choice')
        result = download(workflow_blank)
        ok(result.status_code == 400 and 'Choose an import option' in result.text,
           'no default silently imports every PDF')
        first_data = fields(workflow_blank)
        first_data.update(action='review', workflow='first', profile='sx')
        workflow_first = session.post(endpoint, data=first_data)
        ok(workflow_first.status_code == 200 and 'No CSV is needed.' in workflow_first.text,
           'first import does not request an inventory')
        result = download(workflow_first)
        first_xml = ET.fromstring(result.content)
        ok(result.status_code == 200 and len([x for x in first_xml if 'data' in x.attrib]) == 2,
           'first-import choice embeds the required unique PDFs without another checkbox')
        fresh_csv = 'Filename\n' + '\n'.join(x.get('path') for x in first_xml if 'data' in x.attrib) + '\n'
        update_data = fields(workflow_first)
        update_data.update(action='review', workflow='update')
        workflow_update = session.post(endpoint, data=update_data,
                                       files={'csv': ('current-notes.csv', fresh_csv, 'text/csv')})
        ok(workflow_update.status_code == 200 and 'current-notes.csv' in workflow_update.text,
           'inventory upload remains visibly identified after automatic refresh')
        result = download(workflow_update)
        ok(result.status_code == 200 and not any('data' in x.attrib for x in ET.fromstring(result.content)),
           'following import reuses existing PDFs and annotations')
        legacy_data = fields(workflow_update)
        legacy_data.update(mode='new', initial='1')
        result = session.post(endpoint, data=dict(legacy_data, action='download'))
        ok(result.status_code == 200 and not any('data' in x.attrib for x in ET.fromstring(result.content)),
           'cached old forms do not discard a provided inventory when initial was also checked')
        reference_data = fields(workflow_update)
        reference_data.update(action='review', workflow='references')
        reference_preview = session.post(endpoint, data=reference_data)
        result = download(reference_preview)
        ok(result.status_code == 200 and not any('data' in x.attrib for x in ET.fromstring(result.content)),
           'setlist-only choice includes no PDF data')
        missing_inventory = fields(workflow_blank)
        missing_inventory.update(action='download', workflow='update')
        result = session.post(endpoint, data=missing_inventory)
        ok(result.status_code == 400 and 'Upload a current forScore library CSV' in result.text,
           'following import cannot silently embed duplicates without a current inventory')
        # Initial-import confirmation, section changes and an actual changed PDF.
        response = preview('Headings.txt')
        result = download(response, mode='new')
        ok(result.status_code == 400 and 'first NADIN import' in result.text,
           'embedding without a current inventory requires initial-import confirmation')
        data = fields(response)
        data.update(action='download', profile='tb', reviewed_profile='tb')
        result = session.post(endpoint, data=data)
        ok(result.status_code == 400 and 'section is not available' in result.text,
           'a forged section never grants access')
        response = preview('Headings.txt')
        (app / 'library/Heading_A/Heading_A_SXS.pdf').write_bytes(b'%PDF-1.4\nrevised\n%%EOF')
        result = download(response, mode='new', initial='1')
        ok(result.status_code == 400 and 'changed since the preview' in result.text,
           'a changed PDF invalidates an earlier preview')
        for params, message in (({'m': 'gigreps', 'c': '../Headings.txt'}, 'Invalid GigRep'),
                                ({'m': 'userdatei', 'c': 'userdatei.txt'}, 'Invalid source')):
            result = session.get(endpoint, params=params)
            ok(result.status_code == 400 and message in result.text,
               'invalid source cannot read another file: ' + params['c'])
        result = requests.get(endpoint, params={'m': 'gigreps', 'c': 'Headings.txt'})
        ok('reviewed_profile' not in result.text and 'data="' not in result.text,
           'an unauthenticated request receives no export or preview')

        if os.environ.get('NADIN_BROWSER_TESTS') == '1':
            env = dict(os.environ, NADIN_TEST_URL=base,
                       NADIN_TEST_COOKIES=json.dumps(session.cookies.get_dict()))
            subprocess.run(['node', 'tests/forscore-browser.cjs'], cwd=ROOT, env=env, check=True)
        library_page = BeautifulSoup(session.get(base + '/', params={'m': 'library', 'c': ''}).text, 'html.parser')
        ok(library_page.find('a', href=lambda href: href and 'forscore.php' in href) is not None,
           'Library retains its forScore entry point')
        ok(library_page.find('a', href=lambda href: href and 'library.print.php' in href) is not None
           and library_page.find('a', href=lambda href: href and 'library.xls.php' in href) is not None,
           'Library retains Print and XLS links')
        # Section preferences are applied by the server before the first render.
        response = preview('Headings.txt')
        preference = next(c for c in session.cookies if c.name.startswith('nadin_forscore_section_'))
        ok(preference.path == '/band/nadin/' and preference.value == 'sx'
           and 'HttpOnly' in response.headers.get('Set-Cookie', '')
           and 'SameSite=Lax' in response.headers.get('Set-Cookie', ''),
           'section preference is persistent, HTTP-only and scoped to this installation')
        change = fields(response)
        change.update(action='review', profile='parts')
        changed = session.post(endpoint, data=change)
        remembered = session.get(endpoint, params={'m': 'gigreps', 'c': 'Headings.txt'})
        ok(changed.status_code == 200 and fields(remembered)['profile'] == 'parts',
           'a changed section is remembered when reopening another export')
        explicit = preview('Headings.txt')
        ok(fields(explicit)['profile'] == 'sx', 'explicit section overrides the remembered section')
        preference = next(c for c in session.cookies if c.name.startswith('nadin_forscore_section_'))
        preference.value = 'tb'
        fallback = session.get(endpoint, params={'m': 'gigreps', 'c': 'Headings.txt'})
        ok(fallback.status_code == 200 and fields(fallback)['profile'] == 'sx',
           'an unavailable remembered section is ignored without granting rights')
        favicon = BeautifulSoup(fallback.text, 'html.parser').find('link', rel='icon')
        ok(favicon is not None and favicon['href'] == '../favicon.ico'
           and session.get(base + '/favicon.ico').content == (app / 'favicon.ico').read_bytes(),
           'standalone export explicitly loads this NADIN installation favicon')

        def counts(response):
            soup = BeautifulSoup(response.text, 'html.parser')
            return {x['data-count']: int(x.get_text()) for x in soup.select('[data-count]')}

        ok(counts(workflow_first) == dict(entries=6, headings=3, included=2, reused=0,
                                        references=0, missing=0, unresolved=0),
           'first-import summary counts ordered repeats/headings but embeds each PDF once')
        ok(counts(workflow_update) == dict(entries=6, headings=3, included=0, reused=2,
                                         references=0, missing=0, unresolved=0),
           'update summary identifies the PDFs reused from the current inventory')
        ok(not BeautifulSoup(workflow_first.text, 'html.parser').select('select[data-pdf-choice]')
           and len(BeautifulSoup(workflow_first.text, 'html.parser').select('input[data-pdf-choice]')) == 2,
           'unambiguous PDFs are automatic values rather than unnecessary selection menus')
        reference_data = fields(workflow_blank)
        reference_data.update(action='review', workflow='references')
        workflow_references = session.post(endpoint, data=reference_data)
        ok(counts(workflow_references)['references'] == 2
           and counts(workflow_references)['included'] == 0,
           'setlist-only summary distinguishes references from embedded PDF data')
        ambiguous_data = fields(preview('Ambiguous.txt'))
        ambiguous_data.update(action='review', workflow='first')
        workflow_ambiguous = session.post(endpoint, data=ambiguous_data)
        ok(counts(workflow_ambiguous)['unresolved'] == 1,
           'summary reports a genuinely ambiguous tune before a PDF is selected')
        missing_data = fields(preview('Missing.txt'))
        missing_data.update(action='review', workflow='first')
        workflow_missing = session.post(endpoint, data=missing_data)
        ok(counts(workflow_missing)['missing'] == 4 and counts(workflow_missing)['headings'] == 1,
           'summary distinguishes actual missing notes from automatic text headings')
        # Reusing the same browser for another account must not reuse the first account's section.
        remember_parts = fields(preview('Headings.txt'))
        remember_parts.update(action='review', profile='parts')
        session.post(endpoint, data=remember_parts)
        members = app / 'userdatei/userdatei.txt'
        members.write_bytes(members.read_bytes() + b'Other,other@example.invalid,31.12.2099,vgigs=1,vsaxes=1,varchiv=1,vlibrary=1,\n')
        def login_as(address):
            page = session.get(base + '/login.php')
            data = fields(page)
            data.update(u=address, p=hashlib.sha1((address + 'forscore-fixture').encode()).hexdigest()[7:13])
            result = session.post(base + '/login2.php', data=data, allow_redirects=False)
            ok(result.status_code in (302, 303), 'switch fixture account: ' + address)
        login_as('other@example.invalid')
        other = session.get(endpoint, params={'m': 'gigreps', 'c': 'Headings.txt'})
        ok(fields(other)['profile'] == 'sx', 'another account in the same browser has its own section preference')
        login_as('fixture@example.invalid')
        same = session.get(endpoint, params={'m': 'gigreps', 'c': 'Headings.txt'})
        ok(fields(same)['profile'] == 'parts', 'returning to the first account restores its own section')
        if os.environ.get('NADIN_WORKFLOW_DOM') == '1':
            dom_input = temp / 'workflow-new-dom.json'
            dom_input.write_text(json.dumps({'blank': workflow_blank.text, 'first': workflow_first.text,
                                            'update': workflow_update.text, 'references': workflow_references.text,
                                            'ambiguous': workflow_ambiguous.text, 'missing': workflow_missing.text}))
            subprocess.run(['node', 'tests/forscore-workflow-dom.cjs', str(dom_input)], cwd=ROOT,
                           env=dict(os.environ, NADIN_FORSCORE_JS=str(ROOT / 'nadinmodule/forscore.js')),
                           check=True)

        if os.environ.get('NADIN_WORKFLOW_BROWSER') == '1':
            subprocess.run(['node', 'tests/forscore-workflow-browser.cjs'], cwd=ROOT,
                           env=dict(os.environ, NADIN_TEST_URL=base,
                                    NADIN_TEST_COOKIES=json.dumps(session.cookies.get_dict())), check=True)

        log.flush()
        log.seek(0)
        errors = log.read()
        ok('Fatal error' not in errors and 'Parse error' not in errors,
           'no PHP fatal or parse errors')
        print(f'PASS: {checks} NADIN 7.7 forScore HTTP checks (server default: {DEFAULT_CHARSET})')
    finally:
        process.terminate()
        process.wait(timeout=5)
        log.close()
