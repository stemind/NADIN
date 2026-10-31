"""Main behaviour contract, exercised through real HTTP on disposable installations.

NADIN_APP_ROOT selects a branch checkout; no production files or outgoing mail.
"""
import csv, hashlib, io, os, re, shutil, socket, subprocess, tempfile, time
from pathlib import Path
from urllib.parse import urljoin, parse_qs, urlparse
import requests
from bs4 import BeautifulSoup

ROOT=Path(os.environ.get('NADIN_APP_ROOT',Path(__file__).resolve().parents[1])).resolve()
PHP=os.environ.get('NADIN_PHP','php'); checks=0
EXPECTED_VERSION=os.environ.get('NADIN_EXPECTED_VERSION', 'V9')
SEP='* - * - * - * * * - * - * - *'
def ok(value,label):
    global checks
    assert value,label
    checks+=1
def soup(response): return BeautifulSoup(response.text,'html.parser')

with tempfile.TemporaryDirectory(prefix='nadin-main-parity-') as temp:
    temp=Path(temp); app=temp/'band/nadin'
    shutil.copytree(ROOT,app,ignore=shutil.ignore_patterns('.git','vendor'))
    config=app/'config.inc.php'
    cfg=config.read_text().replace("$salt='???'","$salt='main-parity'")
    # Existing installation-specific markers/labels remain authoritative.
    cfg=cfg.replace("$saxes='SXS'","$saxes='ZZSX'").replace("$parts='PRTS'","$parts='ZZPT'")
    cfg=cfg.replace("$humansaxes='sx'","$humansaxes='Sax fixture'").replace("$humanparts='Parts'","$humanparts='Parts fixture'")
    cfg=cfg.replace("$rendergiglistontheleft='yes'","$rendergiglistontheleft='no'")
    config.write_text(cfg)
    rights='vgigs,vsaxes,vbones,vtrumpets,vrhythm,vother,vscore,vreclist,varchiv,vlibrary,upaudio,uppdf,mgigs,musers'.split(',')
    users='Admin,admin@example.invalid,31.12.2099,'+','.join(r+'=1' for r in rights)+',\n'
    users+='Member,member@example.invalid,31.12.2099,vgigs=1,vsaxes=1,vlibrary=1,vreclist=1,\n'
    users+='Expired,expired@example.invalid,1.1.2000,vgigs=1,vsaxes=1,\n'
    (app/'userdatei/userdatei.txt').write_text(users)
    entries=['Set 1 / Pause:','Parity_A','Zwischenstück:','Parity_B','Parity_A']
    snapshots={}
    for tune,part in [('Parity_A','ZZSX'),('Parity_B','ZZPT')]:
        d=app/'library'/tune; d.mkdir()
        (d/(tune+'_'+part+'.pdf')).write_bytes(b'%PDF-1.4\n'+tune.encode()+b'\n%%EOF')
        (d/'infos.txt').write_text('Tune info '+tune+'\n'+SEP+'\nMusic: https://example.invalid/music?x=1&y=2')
        (d/(tune+'_RCMD.(01.23).mp3')).write_bytes(b'MP3 fixture')
        (d/('XYZ_2026-10-31_'+tune+'.(01.23).mp3')).write_bytes(b'Recording fixture')
        (d/(tune+'_ZZSX_HDDN.pdf')).write_bytes(b'%PDF-hidden')
    for module in ['gigreps','gigrepsarchiv']:
        (app/module/'2026-11-01 Parity.txt').write_text('Gig info '+module+'\n'+SEP+'\n'+'\n'.join(entries))
    (app/'abo/zip').mkdir(exist_ok=True)
    (app/'abo/zip/2026-11-01 Parity.pdf').write_bytes(b'%PDF-gig-attachment')
    for relative in ['config.inc.php','userdatei/userdatei.txt','gigreps/2026-11-01 Parity.txt','gigrepsarchiv/2026-11-01 Parity.txt']:
        snapshots[relative]=(app/relative).read_bytes()
    with socket.socket() as sock: sock.bind(('127.0.0.1',0));port=sock.getsockname()[1]
    log=open(temp/'server.log','w+')
    proc=subprocess.Popen([PHP,'-d','short_open_tag=1','-d','opcache.enable=0','-S',f'127.0.0.1:{port}','-t',str(temp)],stdout=log,stderr=log)
    base=f'http://127.0.0.1:{port}/band/nadin/'
    try:
        for _ in range(100):
            try:
                if requests.get(base+'login.php').status_code==200:break
            except requests.ConnectionError:pass
            time.sleep(.04)
        def login(email):
            session=requests.Session();login_doc=soup(session.get(base+'login.php'))
            labels=re.findall(r'\bNADIN\s+(V?\d+(?:\.\d+)*)\b',login_doc.get_text(' ',strip=True))
            ok(EXPECTED_VERSION in labels and all(label==EXPECTED_VERSION for label in labels),'all login version labels agree: '+repr(labels))
            form=login_doc.find('form')
            data={x['name']:x.get('value','') for x in form.select('input[type=hidden][name]')}
            data.update(u=email,p=hashlib.sha1((email+'main-parity').encode()).hexdigest()[7:13])
            response=session.post(base+'login2.php',data=data,allow_redirects=False)
            return session,response
        admin,response=login('admin@example.invalid');ok(response.status_code in [302,303],'existing initial-password admin login')
        member,response=login('member@example.invalid');ok(response.status_code in [302,303],'existing initial-password musician login')
        expired,response=login('expired@example.invalid')
        ok('Parity_A' not in expired.get(base+'index.php?m=gigreps&c=2026-11-01%20Parity.txt').text,'expired member has no music access')
        for session,label in [(admin,'admin'),(member,'member')]:
            page=session.get(base+'index.php',params={'m':'gigreps','c':'2026-11-01 Parity.txt'});doc=soup(page)
            ok(page.status_code==200 and 'Parity_A' in page.text and 'Gig info gigreps' in page.text,'current GigRep '+label)
            rows=doc.select('.nadin-tune-row') or doc.select('table tr[onmouseover]')
            titles=[r.find('h5').get_text() for r in rows if r.find('h5')]
            ok(titles==entries,'literal headings, tune order and repetitions '+label)
            links=[a['href'] for a in doc.select('a[href*="get/file.php"]')]
            pdf=next(u for u in links if 'ZZSX' in u and 'HDDN' not in u)
            ok(session.get(urljoin(base,pdf)).content.startswith(b'%PDF-1.4'),'custom section marker download '+label)
            parts=next(u for u in links if 'ZZPT' in u)
            ok(session.get(urljoin(base,parts)).content.startswith(b'%PDF-1.4'),'Parts download '+label)
            ok(doc.find('a',href='https://example.invalid/music?x=1&y=2') is not None,'tune links '+label)
            attachment=next(a['href'] for a in doc.select('a[href*="get/setlist.php"]'))
            ok(session.get(urljoin(base,attachment)).content==b'%PDF-gig-attachment','gig PDF attachment '+label)
            for kind in ['print','xls']:
                result=session.get(base+'nadinmodule/gigreps.'+kind+'.php',params={'m':'gigreps','c':'2026-11-01 Parity.txt'})
                values=([c.get_text() for c in soup(result).select('td.print')] if kind=='print' else [field for row in csv.reader(io.StringIO(result.text),delimiter=';') for field in row if field in entries])
                ok(result.status_code==200 and values==entries,'Print/XLS retains headings/order/repetitions '+kind+'/'+label+' '+repr(values))
            lib=session.get(base+'index.php?m=library');libdoc=soup(lib)
            ok(lib.status_code==200 and all(libdoc.find('a',href=lambda x:x and 'library.'+kind+'.php' in x) for kind in ['print','xls']),'Library Print/XLS '+label)
            library_export=libdoc.find('a',href=lambda x:x and 'forscore.php' in x)
            ok(bool(library_export)==(app/'get/forscore.php').is_file(),'Library forScore capability remains reachable '+label)
            if library_export:
                exported=session.get(urljoin(base,library_export['href']))
                ok(exported.status_code==200 and 'reviewed_profile' in exported.text,'Library forScore export opens '+label)
            rec=session.get(base+'index.php?m=reclist');recdoc=soup(rec)
            recurl=next(a['href'] for a in recdoc.select('a[href*="get/file.php"]') if 'Parity_A' in a['href'])
            ok(session.get(urljoin(base,recurl)).content==b'Recording fixture','recordings download '+label)
        for module in ['gigreps','gigrepsarchiv']:
            for kind in ['print','xls']:
                r=admin.get(base+'nadinmodule/gigreps.'+kind+'.php',params={'m':module,'c':'2026-11-01 Parity.txt'})
                ok(r.status_code==200 and 'Parity_A' in r.text,'admin export current/archive '+module+'/'+kind)
        archive=admin.get(base+'index.php?m=gigrepsarchiv&c=2026-11-01%20Parity.txt')
        ok(archive.status_code==200 and 'Gig info gigrepsarchiv' in archive.text,'archive uses archive source with same filename')
        for relative,original in snapshots.items():ok((app/relative).read_bytes()==original,'read flows preserve source bytes '+relative)
        for route in ['index.php?m=library','index.php?m=reclist','index.php?m=gigreps&c=2026-11-01%20Parity.txt']:
            result=requests.get(base+route)
            ok('Parity_A' not in result.text,'anonymous music access denied '+route)
        log.flush();log.seek(0);errors=log.read()
        ok('Fatal error' not in errors and 'Parse error' not in errors,'no PHP fatal/parse errors')
        print(f'PASS: {checks} Main concept/HTTP parity checks ({ROOT.name})')
    finally:proc.terminate();proc.wait(timeout=5);log.close()
