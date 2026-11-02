"""A setlist's ordering, numbering and PDF bytes agree across ordinary exports."""
import csv, hashlib, io, json, os, random, re, shutil, socket, subprocess, tempfile, time, zipfile
from pathlib import Path
from urllib.parse import parse_qs, urljoin, urlsplit
from xml.etree import ElementTree as ET
import requests
from bs4 import BeautifulSoup

ROOT=Path(os.environ.get('NADIN_APP_ROOT',Path(__file__).resolve().parents[1])).resolve()
PHP=os.environ.get('NADIN_PHP','php');checks=0;failures=[];SEP='* - * - * - * * * - * - * - *'
def ok(value,label):
    global checks
    checks+=1
    if not value:failures.append(label);print('FAIL:',label,flush=True)
def soup(response):return BeautifulSoup(response.text,'html.parser')
with tempfile.TemporaryDirectory(prefix='nadin-output-concepts-') as temporary:
    temp=Path(temporary);app=temp/'nadin';shutil.copytree(ROOT,app,ignore=shutil.ignore_patterns('.git','vendor'))
    config=app/'config.inc.php';config.write_text(config.read_text().replace("$salt='???'","$salt='output-concepts'"))
    rights='vgigs,vsaxes,vbones,vtrumpets,vrhythm,vother,vscore,vreclist,varchiv,vlibrary,upaudio,uppdf,mgigs,musers'.split(',')
    (app/'userdatei/userdatei.txt').write_text('Admin,admin@example.invalid,31.12.2099,'+','.join(r+'=1' for r in rights)+',\n')
    profiles=[('SX','SXS','allsaxes'),('TB','TBS','allbones'),('TP','TPS','alltrumpets'),('RH','RTM','allrhythm'),('OT','OTR','allother'),('PT','PRTS','allparts'),('SC','SCRE','allscore')]
    assets={}
    for n,(label,marker,kind) in enumerate(profiles):
        tune='Number_'+label;folder=app/'library'/tune;folder.mkdir();name='Only_'+marker+'.pdf'
        payload=b'%PDF-1.4\n'+random.Random(710+n).randbytes(60000)
        (folder/name).write_bytes(payload);assets[tune]=(name,payload,kind)
    tunes=list(assets);entries=['Set 1 / Music:']+tunes[:3]+['Pause:']+tunes[3:]+[tunes[0]]
    expected=[];number=0
    for title in entries:
        if title in assets:number+=1
        expected.append((title,str(number) if title in assets else ''))
    gig='2099-01-01 Output concepts.txt'
    for mode in ['gigreps','gigrepsarchiv']:(app/mode/gig).write_text('Output agreement\n'+SEP+'\n'+'\n'.join(entries))
    (app/'abo/zip').mkdir(exist_ok=True)
    for kind in ['alllistento']+[row[2] for row in profiles]:(app/'abo/zip'/kind).mkdir(exist_ok=True)
    with socket.socket() as sock:sock.bind(('127.0.0.1',0));port=sock.getsockname()[1]
    log=open(temp/'server.log','w+');server=subprocess.Popen([PHP,'-S',f'127.0.0.1:{port}','-t',str(temp)],stdout=log,stderr=log)
    base=f'http://127.0.0.1:{port}/nadin/'
    try:
        for attempt in range(100):
            try:
                if requests.get(base+'login.php',timeout=2).status_code==200:break
            except requests.ConnectionError:pass
            time.sleep(.04)
        session=requests.Session();form=soup(session.get(base+'login.php')).find('form')
        data={x['name']:x.get('value','') for x in form.select('input[type=hidden][name]')};data.update(u='admin@example.invalid',p=hashlib.sha1(b'admin@example.invalidoutput-concepts').hexdigest()[7:13])
        ok(session.post(base+'login2.php',data=data,allow_redirects=False).status_code in [302,303],'fixture login')
        legacy_zip=not (app/'nadinmodule/zip.inc.php').exists()
        if legacy_zip:session.get(base+'preview.php',params={'gig':gig,'zip':'1'})
        cached={p:p.read_bytes() for p in (app/'abo/zip').rglob('*.zip')} if legacy_zip else {}
        for mode in ['gigreps','gigrepsarchiv']:
            page=session.get(base+'index.php',params={'m':mode,'c':gig});doc=soup(page);actual=[]
            for heading in doc.find_all('h5'):
                title=heading.get_text()
                if title not in entries:continue
                row=heading.find_parent('tr');cells=row.find_all('td',recursive=False);cell=cells[0] if cells else None
                actual.append((title,cell.get_text(strip=True) if cell else ''))
            ok([title for title,n in actual]==entries,'screen retains headings, order and repeated tunes '+mode)
            ok(actual==expected,'screen numbers every PDF-bearing tune, including trumpets alone '+mode+' '+repr(actual))
            printed=session.get(base+'nadinmodule/gigreps.print.php',params={'m':mode,'c':gig});printpairs=[]
            for titlecell in soup(printed).select('td.print'):
                row=titlecell.find_parent('tr');counter=row.find('td',class_='tdcounter')
                printpairs.append((titlecell.get_text(),counter.get_text(strip=True) if counter else ''))
            ok(printpairs==expected,'Print has the same order and numbering '+mode+' '+repr(printpairs))
            sheet=session.get(base+'nadinmodule/gigreps.xls.php',params={'m':mode,'c':gig})
            records=list(csv.reader(io.StringIO(sheet.content.decode('utf-8-sig')),delimiter=';'))
            # The established CSV puts the band/gig label before the first record.
            records=[r[1:] if r and r[0].startswith('XYZ 2099-01-01 Output concepts') else r for r in records]
            pairs=[(r[1],r[0]) for r in records if len(r)>1 and r[1] in entries]
            ok(pairs==expected,'XLS has the same order and numbering '+mode+' '+repr(pairs))
            if (app/'get/forscore.php').exists():
                preview=session.get(base+'get/forscore.php',params={'m':mode,'c':gig,'profile':'tp','workflow':'first'})
                f=soup(preview).find('form');data={x['name']:x.get('value','') for x in f.select('input[type=hidden][name]')}
                for select in f.select('select[name]'):
                    option=select.find('option',selected=True) or select.find('option');data[select['name']]=option.get('value','') if option else ''
                data.update(action='download',placeholders='1')
                export=session.post(base+'get/forscore.php',data=data)
                ok(export.status_code==200 and 'filename' in export.headers.get('Content-Disposition',''),'forScore produces an actual download '+mode)
                try:
                    xml=ET.fromstring(export.content);titles=[child.attrib['title'].replace('_',' ') for child in xml]
                    ok(titles==[title.replace('_',' ') for title in entries],'forScore retains exact sequence and repetition with acknowledged missing notes '+mode)
                except (ET.ParseError,KeyError):ok(False,'forScore download is valid setlist XML '+mode)
        if legacy_zip:
            ok(bool(cached) and all(path.is_file() and path.read_bytes()==data for path,data in cached.items()),'viewing a same-name archived gig preserves current ZIP caches')
            archived='2099-01-02 Archive only.txt';(app/'gigrepsarchiv'/archived).write_text('Archive only\n'+SEP+'\n'+tunes[0])
            obsolete=app/'abo/zip/allsaxes'/(archived[:-4]+'_allsaxes.zip');obsolete.write_bytes(b'OBSOLETE ARCHIVE CACHE')
            session.get(base+'index.php',params={'m':'gigrepsarchiv','c':archived})
            ok(not obsolete.exists(),'legacy cleanup still removes a genuinely archived-only cache')
        # Every section's direct download and ZIP carry the exact original PDF bytes.
        for tune,(name,payload,kind) in assets.items():
            direct=session.get(base+'get/file.php',params={'c':tune+'/'+name})
            ok(direct.status_code==200 and direct.content==payload,'direct download bytes '+tune)
            zipped=session.get(base+'get/zip.php',params={'c':'zip/'+kind+'/'+gig[:-4]+'_'+kind+'.zip','m':'gigreps'})
            ok(zipped.status_code==200 and zipped.content.startswith(b'PK'),'section ZIP download '+tune)
            try:
                with zipfile.ZipFile(io.BytesIO(zipped.content)) as archive:
                    candidates=[f for f in archive.namelist() if Path(f).name==name]
                    ok(len(candidates)==1 and archive.read(candidates[0])==payload,'section ZIP contains exact PDF bytes '+tune)
            except zipfile.BadZipFile:ok(False,'section ZIP is valid '+tune)
            ok((app/'library'/tune/name).read_bytes()==payload,'all exports preserve source PDF bytes '+tune)
        log.flush();log.seek(0);errors=log.read();ok('Fatal error' not in errors and 'Parse error' not in errors,'no PHP fatal/parse errors')
        print(('FAIL' if failures else 'PASS')+f': {checks} output-agreement checks ({ROOT.name})',flush=True)
        if failures:raise AssertionError('; '.join(failures))
    finally:server.terminate();server.wait(timeout=5);log.close()
