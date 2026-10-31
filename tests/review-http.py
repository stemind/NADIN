"""Final V16 review: real HTTP regressions, synthetic accounts and disposable data."""
import base64, hashlib, json, os, re, shutil, socket, subprocess, tempfile, time
from pathlib import Path
import requests
from bs4 import BeautifulSoup

ROOT = Path(__file__).resolve().parents[1]
PHP = os.environ.get('NADIN_PHP', 'php')
checks, failures = 0, []

def ok(value, label):
    global checks
    checks += 1
    if not value:
        failures.append(label)
        print('FAIL:', label, flush=True)

with tempfile.TemporaryDirectory(prefix='nadin-review-') as temp:
    temp = Path(temp)
    app = temp / 'nadin'
    shutil.copytree(ROOT, app, ignore=shutil.ignore_patterns('.git'))
    (app / 'config.inc.php').write_text((app / 'config.inc.php').read_text().replace("$salt='???'", "$salt='review-fixture'"))
    rights = 'vgigs,vsaxes,vbones,vtrumpets,vrhythm,vother,vscore,vreclist,varchiv,vlibrary,upaudio,uppdf,mgigs,musers'.split(',')
    (app / 'userdatei/userdatei.txt').write_text('Admin,admin@example.invalid,31.12.2099,' + ','.join(r + '=1' for r in rights) + '\n')
    separator = '* - * - * - * * * - * - * - *'
    tune = app / 'library' / 'Review[A]'
    tune.mkdir()
    (tune / 'Review_SXS.pdf').write_bytes(b'%PDF-review')
    (tune / '.hidden_TPS.pdf').write_bytes(b'%PDF-hidden')
    (tune / 'zzDEL!_TBS.pdf').write_bytes(b'%PDF-deleted')
    (tune / 'Review_RTM.pdf').write_bytes(b'')
    (tune / 'Review_SCRE.php').write_bytes(b'not a score')
    (tune / 'Review_SCRE.pdf').symlink_to(tune / 'Review_SXS.pdf')
    links = ['https://example.invalid/plain?a=1&b=2', 'http://example.invalid/http',
             'Label: https://example.invalid/labelled?next=https://example.invalid/other',
             'mailto:band@example.invalid', 'Contact: mailto:other@example.invalid',
             'javascript:alert(1)', 'Unsafe: javascript:alert(2)',
             'data:text/html,<script>alert(3)</script>']
    (tune / 'infos.txt').write_text('Review notes\n' + separator + '\n' + '\n'.join(links))
    (app / 'gigreps/Review.txt').write_text('Review\n' + separator + '\nReview[A]')
    # Legacy single-byte filenames must survive a UTF-8 JSON editor round trip.
    legacy_tune = b'M\xfcller'
    legacy_dir = os.fsencode(app / 'library') + b'/' + legacy_tune
    os.mkdir(legacy_dir)
    with open(legacy_dir + b'/Tune_SXS.pdf', 'wb') as f:
        f.write(b'%PDF-legacy')
    legacy_gig = os.fsencode(app / 'gigreps') + b'/2026-11-01 Z\xfcrich.txt'
    original_tunes = legacy_tune + b'\nMissing_\xe9\nReview[A]\n' + legacy_tune
    with open(legacy_gig, 'wb') as f:
        f.write(b'Legacy info\n' + separator.encode() + b'\n' + original_tunes)
    (app / 'abo/zip').mkdir(exist_ok=True)
    legacy_pdf = os.fsencode(app / 'abo/zip') + b'/2026-11-01 Z\xfcrich.pdf'
    with open(legacy_pdf, 'wb') as f:
        f.write(b'%PDF-legacy-attachment')
    with socket.socket() as sock:
        sock.bind(('127.0.0.1', 0))
        port = sock.getsockname()[1]
    log = open(temp / 'server.log', 'w+')
    server = subprocess.Popen([PHP, '-S', f'127.0.0.1:{port}', '-t', str(temp)], stdout=log, stderr=log)
    base = f'http://127.0.0.1:{port}/nadin'
    try:
        for _ in range(100):
            try:
                if requests.get(base + '/login.php').status_code == 200:
                    break
            except requests.ConnectionError:
                pass
            time.sleep(.04)
        s = requests.Session()
        def login():
            r = s.get(base + '/login.php')
            form = BeautifulSoup(r.text, 'html.parser').find('form')
            data = {x['name']: x.get('value', '') for x in form.select('input[type=hidden]')}
            data.update(u='admin@example.invalid', p=hashlib.sha1(b'admin@example.invalidreview-fixture').hexdigest()[7:13])
            return s.post(base + '/login2.php', data=data, allow_redirects=False)
        ok(login().status_code == 303, 'local login')
        r = s.get(base + '/index.php?m=gigreps&c=Review.txt')
        soup = BeautifulSoup(r.text, 'html.parser')
        for url in (links[0], links[1], links[2].split(': ', 1)[1], links[3], links[4].split(': ', 1)[1]):
            ok(soup.find('a', href=url) is not None, 'tune link retained: ' + url)
        ok(not soup.select('.nadin-detail-row a[href^="javascript:"],.nadin-detail-row a[href^="data:"]'), 'unsafe tune links remain blocked')
        api_url = base + '/nadinmodule/admin-api.php'
        def api(data, post=False):
            data = dict(data, area='gigs')
            if post:
                data['csrf'] = s.get(api_url, params={'area': 'gigs', 'action': 'token'}).json()['csrf']
                return s.post(api_url, json=data)
            return s.get(api_url, params=data)
        listing = api({}).json()
        item = next(x for x in listing['catalog'] if x['title'] == 'Review[A]')
        ok(item['files'] == ['sx'], 'catalog lists real readable media in bracket-named directory only')
        item = next(x for x in listing['catalog'] if x['title'] == 'Müller')
        ok(item['id'] == 'Müller', 'legacy tune ID survives JSON')
        row = next(x for x in listing['gigs'] if 'Zürich' in x['title'])
        ok(row['id'] == '2026-11-01 Zürich.txt', 'legacy gig ID survives JSON')
        response = api({'action': 'gig', 'id': row['id']})
        ok(response.status_code == 200, 'legacy gig opens from catalog')
        if response.status_code == 200:
            gig = response.json()['gig']
            ok(gig['tunes'] == ['Müller', 'Missing_é', 'Review[A]', 'Müller'], 'legacy setlist renders readable text and repetitions')
            for field, expected in [('view_url', b'get/file.php'), ('attachment_url', b'%PDF-legacy-attachment')]:
                target = gig.get(field, '')
                result = s.get(base + '/' + target) if target else None
                ok(result is not None and result.status_code == 200 and expected in result.content, 'byte-correct legacy ' + field)
            result = api(dict(gig, action='save', infos='Edited info'), True)
            ok(result.status_code == 200, 'legacy gig saves through JSON editor')
            with open(legacy_gig, 'rb') as f:
                ok(f.read().split(separator.encode() + b'\n', 1)[1] == original_tunes, 'saving infos preserves exact legacy setlist bytes, missing tunes and repetitions')
            if result.status_code == 200:
                gig = result.json()['gig']
                revisions = [json.loads(p.read_text().split('\n', 1)[1]) for p in (app / 'userdatei/revisions').glob('*.php')]
                ok(any(base64.b64decode(x['path_bytes']).endswith(b'Z\xfcrich.txt') for x in revisions), 'backup retains original single-byte filename')
                token = api({'action': 'token'}).json()['csrf']
                attachment = s.post(api_url, data={'area': 'gigs', 'action': 'attachment', 'id': gig['id'], 'revision': gig['revision'], 'csrf': token}, files={'pdf': ('replacement.pdf', b'%PDF-replaced-legacy', 'application/pdf')})
                with open(legacy_pdf, 'rb') as f:
                    ok(attachment.status_code == 200 and f.read() == b'%PDF-replaced-legacy', 'replace attachment on a legacy gig without changing filename')
                if attachment.status_code == 200:
                    gig = attachment.json()['gig']
                copied = api(dict(gig, action='copy', date='2026-11-02', title='Legacy copy'), True)
                ok(copied.status_code == 200 and copied.json()['gig']['attachment'], 'copy legacy gig and attachment')
                archived = api(dict(gig, action='archive'), True)
                ok(archived.status_code == 200, 'archive legacy gig')
                if archived.status_code == 200:
                    restored = api(dict(archived.json()['gig'], action='restore'), True)
                    ok(restored.status_code == 200 and os.path.isfile(legacy_gig), 'restore legacy gig under its original byte name')
        added = api({'action': 'save', 'id': '', 'date': '2026-11-03', 'title': 'Legacy catalog selection', 'infos': '', 'tunes': [item['id']]}, True)
        ok(added.status_code == 200 and (app / 'gigreps/2026-11-03 Legacy catalog selection.txt').read_bytes().endswith(legacy_tune), 'new gig resolves legacy tune selected from catalog')
        # Two byte names can display identically. Never pick one silently or modify either.
        (app / 'library/Müller').mkdir()
        if added.status_code == 200:
            data = added.json()['gig']
            before = (app / 'gigreps/2026-11-03 Legacy catalog selection.txt').read_bytes()
            rejected = api(dict(data, action='save', infos='Must not be saved'), True)
            ok(rejected.status_code == 409 and (app / 'gigreps/2026-11-03 Legacy catalog selection.txt').read_bytes() == before, 'ambiguous legacy tune is rejected without rewriting setlist')
        (app / 'gigreps/2026-11-01 Zürich.txt').write_text('UTF-8 collision')
        rejected = api({'action': 'gig', 'id': '2026-11-01 Zürich.txt'})
        ok(rejected.status_code == 409, 'ambiguous legacy gig is rejected before editing')
        # A collision must be checked after duration removal and filename normalisation.
        (tune / 'source.mp3').write_bytes(b'SOURCE')
        (tune / 'target.mp3').write_bytes(b'TARGET')
        page = s.get(base + '/nadinmodule/AUDfiles.php', params={'name': 'Review[A]'})
        token = json.loads(re.search(r'var token=("[^"\n]+")', page.text).group(1))
        result = s.post(base + '/nadinmodule/AUDrenamefile.php', data={'p': 'Review[A]', 'f': 'source.mp3', 'n': 'target(00.23).mp3', 'csrf': token})
        ok(result.status_code == 409, 'audio rename reports normalised name collision')
        ok((tune / 'source.mp3').exists() and (tune / 'target.mp3').read_bytes() == b'TARGET', 'audio rename cannot overwrite another recording')
        if not (tune / 'source.mp3').exists():
            (tune / 'source.mp3').write_bytes(b'SOURCE')
        result = s.post(base + '/nadinmodule/AUDrenamefile.php', data={'p': 'Review[A]', 'f': 'source.mp3', 'n': 'renamed(00.23).MP3', 'csrf': token})
        ok(result.status_code == 200 and (tune / 'renamed.MP3').is_file(), 'uppercase MP3 rename remains usable')
        result = s.post(base + '/nadinmodule/AUDrenamefile.php', data={'p': 'Review[A]', 'f': 'renamed.MP3', 'n': 'bad.php', 'csrf': token})
        ok(result.status_code == 400 and (tune / 'renamed.MP3').is_file(), 'audio rename cannot create executable extension')
        (tune / 'alias.mp3').symlink_to(tune / 'renamed.MP3')
        result = s.post(base + '/nadinmodule/AUDrenamefile.php', data={'p': 'Review[A]', 'f': 'alias.mp3', 'n': 'moved.mp3', 'csrf': token})
        ok(result.status_code == 404 and (tune / 'alias.mp3').is_symlink(), 'audio rename rejects symlink source')
        # Optional federation failure must leave a usable local login.
        cfg = app / 'userdatei/federation.php'
        for contents in ['<?php return false;', '<?php throw new RuntimeException("fixture");', '<?php invalid syntax !']:
            cfg.write_text(contents)
            ok(login().status_code == 303, 'local login independent of broken optional federation configuration')
        cfg.unlink()
        log.flush()
        log.seek(0)
        errors = log.read()
        ok('Fatal error' not in errors and 'Parse error' not in errors, 'review flows contain no PHP fatal or parse errors')
        if failures:
            raise AssertionError(f'{len(failures)} of {checks} final-review checks failed: ' + '; '.join(failures))
        print(f'PASS: {checks} final-review HTTP checks')
    finally:
        server.terminate()
        server.wait(timeout=5)
        log.close()
