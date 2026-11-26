"""MP3 transport contracts through a real, authenticated PHP endpoint.

Uses disposable synthetic data and Python's standard library. Select another
branch checkout with NADIN_APP_ROOT and a PHP executable with NADIN_PHP.
"""
import hashlib
import http.cookiejar
import json
import os
from pathlib import Path
import re
import shutil
import signal
import socket
import subprocess
import tempfile
import time
import urllib.error
import urllib.parse
import urllib.request

ROOT = Path(os.environ.get('NADIN_APP_ROOT', Path(__file__).resolve().parents[1])).resolve()
PHP = os.environ.get('NADIN_PHP', 'php')
events = []


def check(value, label):
    events.append(dict(label=label, passed=bool(value)))
    if not value:
        print('FAIL:', label, flush=True)


with tempfile.TemporaryDirectory(prefix='nadin-audio-stream-') as temporary:
    temp = Path(temporary)
    app = temp / 'band/nadin'
    shutil.copytree(ROOT, app, ignore=shutil.ignore_patterns('.git', 'vendor'))
    config = app / 'config.inc.php'
    config.write_bytes(config.read_bytes().replace(b"$salt='???'", b"$salt='audio-transport'"))
    rights = 'vgigs,vsaxes,vbones,vtrumpets,vrhythm,vother,vscore,vreclist,varchiv,vlibrary,upaudio,uppdf,mgigs,musers'.split(',')
    (app / 'userdatei/userdatei.txt').write_text(
        'Editor,editor@example.invalid,31.12.2099,' + ','.join(x + '=1' for x in rights) + ',\n'
        'Member,member@example.invalid,31.12.2099,vgigs=1,vlibrary=1,\n'
        'Expired,expired@example.invalid,01.01.2000,vgigs=1,vlibrary=1,\n')
    tune = app / 'library/Transport_Tune'
    tune.mkdir()
    raw = bytes(range(256)) * 8192 + b'non-aligned MP3 fixture tail'
    (tune / 'Transport_RCMD.(02.10).mp3').write_bytes(raw)
    (tune / 'Upper.MP3').write_bytes(raw[:1234])
    (tune / 'Empty.mp3').write_bytes(b'')
    (tune / 'Private_HDDN.mp3').write_bytes(raw[:1234])
    (tune / 'Private_SXS.mp3').write_bytes(raw[:1234])
    pdf = b'%PDF-1.4\nsynthetic PDF bytes\n%%EOF\n'
    (tune / 'Transport_SXS.pdf').write_bytes(pdf)
    # Exercise output buffering and compression without changing production config.
    prepend = temp / 'prepend.php'
    prepend.write_text('<?php ob_start();')
    with socket.socket() as sock:
        sock.bind(('127.0.0.1', 0))
        port = sock.getsockname()[1]
    log = open(temp / 'server.log', 'w+')
    server = subprocess.Popen(
        [PHP, '-d', 'display_errors=0', '-d', 'error_reporting=32767',
         '-d', 'zlib.output_compression=1', '-d', 'output_buffering=4096',
         '-d', 'auto_prepend_file=' + str(prepend), '-d', 'max_execution_time=1',
         '-S', '127.0.0.1:' + str(port), '-t', str(temp)],
        stdout=log, stderr=log, start_new_session=True,
        env=dict(os.environ, PHP_CLI_SERVER_WORKERS='4'))
    base = 'http://127.0.0.1:' + str(port) + '/band/nadin/'
    jar = http.cookiejar.CookieJar()
    client = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar))

    def request(path, headers=None, method='GET', opener=client, data=None):
        req = urllib.request.Request(base + path, headers=headers or {}, method=method, data=data)
        try:
            response = opener.open(req, timeout=15)
        except urllib.error.HTTPError as error:
            response = error
        with response:
            return response.status, response.headers, response.read()

    def login(email):
        jar.clear()
        _, _, page = request('login.php')
        hidden = dict(re.findall(rb'<input[^>]*name=[\"\']([^\"\']+)[\"\'][^>]*value=[\"\']([^\"\']*)[\"\']', page))
        fields = {key.decode(): value.decode() for key, value in hidden.items()}
        fields.update(u=email, p=hashlib.sha1((email + 'audio-transport').encode()).hexdigest()[7:13])
        return request('login2.php', method='POST', data=urllib.parse.urlencode(fields).encode())

    path = 'get/file.php?' + urllib.parse.urlencode({'c': tune.name + '/Transport_RCMD.(02.10).mp3'})
    try:
        for _ in range(100):
            try:
                request('login.php')
                break
            except (urllib.error.URLError, ConnectionError):
                time.sleep(.05)
        login('editor@example.invalid')
        status, headers, body = request(path)
        check(status == 200 and body == raw, 'full authenticated MP3 is byte-identical')
        check(headers.get('Accept-Ranges') == 'bytes', 'MP3 advertises byte ranges')
        check(headers.get('Content-Type', '').startswith('audio/mpeg'), 'MP3 content type')
        check(int(headers.get('Content-Length', -1)) == len(raw), 'full length is exact')
        check('inline' in headers.get('Content-Disposition', ''), 'MP3 remains inline')
        for range_header, start, end in [
            ('bytes=0-0', 0, 0), ('bytes=0-1023', 0, 1023),
            ('bytes=960000-', 960000, len(raw) - 1),
            ('bytes=1048576-1052672', 1048576, 1052672),
            ('bytes=-257', len(raw) - 257, len(raw) - 1),
            ('bytes=00001-00009', 1, 9),
            ('ByTeS=3-11', 3, 11),
            ('bytes=5-999999999999999999999999999999', 5, len(raw) - 1),
            ('bytes=-999999999999999999999999999999', 0, len(raw) - 1),
        ]:
            status, headers, body = request(path, {'Range': range_header, 'Accept-Encoding': 'gzip'})
            check(status == 206 and body == raw[start:end + 1], 'exact partial bytes: ' + range_header)
            check(headers.get('Content-Range') == f'bytes {start}-{end}/{len(raw)}', 'exact Content-Range: ' + range_header)
            check(int(headers.get('Content-Length', -1)) == end - start + 1, 'exact partial length: ' + range_header)
            check(headers.get('Content-Encoding') in (None, 'identity'), 'partial bytes are uncompressed: ' + range_header)
        for invalid in ['bytes=' + str(len(raw)) + '-', 'bytes=-0', 'bytes=999999999999999999999999999999-']:
            status, headers, body = request(path, {'Range': invalid})
            check(status == 416 and headers.get('Content-Range') == 'bytes */' + str(len(raw)) and body == b'', 'unsatisfiable range: ' + invalid)
        for ignored in ['bytes=100-10', 'bytes=abc-def', 'items=0-1', 'bytes=0-1,10-20', 'bytes=-', 'bytes=1-2trailing', 'bytes=999999999999999999999999999999-999999999999999999999999999998']:
            status, headers, body = request(path, {'Range': ignored})
            check(status == 200 and body == raw and headers.get('Content-Range') is None, 'malformed/unsupported range ignored: ' + ignored)
        for validator in ['"unknown-version"', 'W/"weak-version"', 'Wed, 01 Jan 2020 00:00:00 GMT']:
            status, _, body = request(path, {'Range': 'bytes=100-', 'If-Range': validator})
            check(status == 200 and body == raw, 'unverifiable If-Range uses full representation: ' + validator)
        for extra in [{}, {'Range': 'bytes=100-200'}, {'Range': 'bytes=999999999999-'}]:
            status, headers, body = request(path, extra, method='HEAD')
            check(status == 200 and int(headers.get('Content-Length', -1)) == len(raw) and body == b'' and headers.get('Content-Range') is None, 'HEAD has full metadata and no body: ' + str(extra))
        # End a real socket read early, then resume precisely from received bytes.
        partial = client.open(base + path, timeout=15)
        first = partial.read(98317)
        partial.close()
        status, _, rest = request(path, {'Range': 'bytes=' + str(len(first)) + '-'})
        check(status == 206 and first + rest == raw, 'aborted transfer resumes without repeated or missing bytes')
        status, headers, body = request('get/file.php?' + urllib.parse.urlencode({'c': tune.name + '/Upper.MP3'}), {'Range': 'bytes=1-7'})
        check(status == 206 and body == raw[1:8] and headers.get('Content-Type', '').startswith('audio/mpeg'), 'uppercase MP3 extension streams correctly')
        empty_path = 'get/file.php?' + urllib.parse.urlencode({'c': tune.name + '/Empty.mp3'})
        status, headers, body = request(empty_path)
        check(status == 200 and body == b'' and headers.get('Content-Length') == '0', 'empty MP3 is a zero-length full response')
        status, headers, body = request(empty_path, {'Range': 'bytes=0-'})
        check(status == 416 and body == b'' and headers.get('Content-Range') == 'bytes */0', 'range on empty MP3 is unsatisfiable')
        status, headers, body = request(empty_path, {'Range': 'bytes=-1'})
        check(status == 200 and body == b'' and headers.get('Content-Length') == '0', 'non-zero suffix on empty MP3 falls back to full empty response')
        status, headers, body = request('get/file.php?' + urllib.parse.urlencode({'c': tune.name + '/Transport_SXS.pdf'}))
        check(status == 200 and body == pdf and headers.get('Content-Type', '').startswith('application/pdf'), 'ordinary PDF remains byte-identical')
        outsider = urllib.request.build_opener()
        status, _, body = request(path, {'Range': 'bytes=0-50'}, opener=outsider)
        check(status != 206 and raw[:50] not in body, 'anonymous partial request cannot retrieve MP3 bytes')
        login('member@example.invalid')
        for name in ['Private_HDDN.mp3', 'Private_SXS.mp3']:
            status, _, body = request('get/file.php?' + urllib.parse.urlencode({'c': tune.name + '/' + name}), {'Range': 'bytes=0-50'})
            check(status != 206 and raw[:50] not in body, 'range preserves permission checks: ' + name)
        login('expired@example.invalid')
        status, _, body = request(path, {'Range': 'bytes=0-50'})
        check(status != 206 and raw[:50] not in body, 'expired login cannot retrieve partial MP3')
        check((tune / 'Transport_RCMD.(02.10).mp3').read_bytes() == raw, 'streaming never changes recording bytes')
        if shutil.which('ffmpeg') and shutil.which('ffprobe'):
            login('editor@example.invalid')
            browser_mp3 = tune / 'Browser_RCMD.(02.10).mp3'
            subprocess.run(['ffmpeg', '-hide_banner', '-loglevel', 'error', '-f', 'lavfi', '-i',
                            'sine=frequency=440:duration=130', '-c:a', 'libmp3lame', '-b:a', '128k', str(browser_mp3)], check=True)
            source = 'get/file.php?' + urllib.parse.urlencode({'c': tune.name + '/' + browser_mp3.name})
            status, headers, full_mp3 = request(source)
            check(status == 200 and full_mp3 == browser_mp3.read_bytes(), 'valid 130-second MP3 is delivered byte-identically')
            status, headers, tail_mp3 = request(source, {'Range': 'bytes=960000-'})
            check(status == 206 and tail_mp3 == full_mp3[960000:], 'valid long MP3 resumes beyond the one-minute byte position')
            downloaded = temp / 'downloaded.mp3'
            downloaded.write_bytes(full_mp3[:960000] + tail_mp3)
            duration = float(subprocess.check_output(['ffprobe', '-v', 'error', '-show_entries', 'format=duration', '-of', 'default=noprint_wrappers=1:nokey=1', str(downloaded)]))
            check(abs(duration - 130) < 1, 'independent FFprobe confirms complete resumed MP3 duration')
            (app / 'audio-probe.html').write_text('<!doctype html><title>Disposable audio transport probe</title><audio id="audio" controls preload="metadata" src="' + source + '"></audio>')
            if os.environ.get('NADIN_STREAM_BROWSER') == '1':
                browser = subprocess.run(['node', str(Path(__file__).with_name('audio-stream-browser.cjs'))],
                                         input=json.dumps(dict(base=base, cookies=[dict(name=c.name, value=c.value, domain='127.0.0.1', path=c.path) for c in jar])), text=True)
                check(browser.returncode == 0, 'real Chromium playback, seeking and network interruption')
        log.flush()
        log.seek(0)
        errors = log.read()
        check('Fatal error' not in errors and 'Parse error' not in errors, 'no PHP fatal or parse errors')
    finally:
        os.killpg(server.pid, signal.SIGTERM)
        server.wait(timeout=5)
        log.close()

failed = [event['label'] for event in events if not event['passed']]
result = dict(source=str(ROOT), checks=len(events), failures=failed, events=events)
if os.environ.get('NADIN_STREAM_EVIDENCE'):
    Path(os.environ['NADIN_STREAM_EVIDENCE']).write_text(json.dumps(result, indent=2) + '\n')
print(('FAIL' if failed else 'PASS') + ': ' + str(len(events)) + ' MP3 transport checks', flush=True)
raise SystemExit(bool(failed))
