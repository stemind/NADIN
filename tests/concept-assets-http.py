"""File roles must agree with the configured PDF/audio and infos.txt contract."""
import csv, hashlib, io, os, shutil, socket, subprocess, tempfile, time
from pathlib import Path
from urllib.parse import parse_qs, urlsplit
import requests
from bs4 import BeautifulSoup

ROOT=Path(os.environ.get('NADIN_APP_ROOT',Path(__file__).resolve().parents[1])).resolve()
PHP=os.environ.get('NADIN_PHP','php'); checks=0;failures=[]
SEP='* - * - * - * * * - * - * - *'
def ok(value,label):
    global checks
    checks+=1
    if not value:failures.append(label);print('FAIL:',label,flush=True)
def soup(response):return BeautifulSoup(response.text,'html.parser')

with tempfile.TemporaryDirectory(prefix='nadin-file-concepts-') as temporary:
    temp=Path(temporary);app=temp/'nadin';shutil.copytree(ROOT,app,ignore=shutil.ignore_patterns('.git','vendor'))
    config=app/'config.inc.php';config.write_text(config.read_text().replace("$salt='???'","$salt='asset-concepts'"))
    rights='vgigs,vsaxes,vbones,vtrumpets,vrhythm,vother,vscore,vreclist,varchiv,vlibrary,upaudio,uppdf,mgigs,musers'.split(',')
    (app/'userdatei/userdatei.txt').write_text('Admin,admin@example.invalid,31.12.2099,'+','.join(r+'=1' for r in rights)+',\n')
    assets={
      'Concept_Types':{'Tune_SXS.pdf':b'%PDF-normal','Working_SXS.mid':b'MIDI','Tune_RCMD.(01.23).mp3':b'RECORDING','Guide_RCMD.pdf':b'%PDF-guide'},
      'Concept_Prefix':{'SXS_Prefix.pdf':b'%PDF-prefix'},
      'Concept_Text':{'Tune_SXS.pdf':b'%PDF-text','infos.txt':('ACTUAL TUNE NOTES\n'+SEP+'\nhttps://example.invalid/real').encode(),'Guide.txt':b'SEPARATE DOWNLOADABLE GUIDE'},
      'Concept_Recovery':{'Tune_SXS.pdf':b'%PDF-recovery','.private_SXS.pdf':b'PRIVATE','zzDEL!removed_SXS.pdf':b'RECOVERY'},
      'Concept_Legacy':{'Tune_SXS.pdf':b'%PDF-legacy','legacy-notes.txt':('LEGACY TUNE NOTES\n'+SEP+'\nhttps://example.invalid/legacy').encode()},
    }
    for tune,files in assets.items():
        folder=app/'library'/tune;folder.mkdir()
        for name,data in files.items():(folder/name).write_bytes(data)
    for mode in ['gigreps','gigrepsarchiv']:(app/mode/'2099-01-01 Asset concepts.txt').write_text('Asset roles\n'+SEP+'\n'+'\n'.join(assets))
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
        data={x['name']:x.get('value','') for x in form.select('input[type=hidden][name]')}
        data.update(u='admin@example.invalid',p=hashlib.sha1(b'admin@example.invalidasset-concepts').hexdigest()[7:13])
        ok(session.post(base+'login2.php',data=data,allow_redirects=False).status_code in [302,303],'fixture login')
        for mode in ['gigreps','gigrepsarchiv']:
            page=session.get(base+'index.php',params={'m':mode,'c':'2099-01-01 Asset concepts.txt'});doc=soup(page)
            links=[(parse_qs(urlsplit(a['href']).query).get('c',[''])[0],a.get_text(' ',strip=True)) for a in doc.select('a[href*="get/file.php"]')]
            ok(('Concept_Types/Tune_SXS.pdf','sx') in links,'ordinary PDF remains the section link '+mode)
            ok(('Concept_Types/Working_SXS.mid','Working_SXS.mid') in links,'section-marked MIDI remains an ordinary extra file '+mode)
            ok(('Concept_Types/Guide_RCMD.pdf','Guide_RCMD.pdf') in links,'RCMD-marked PDF remains an ordinary extra file '+mode)
            ok(any(path=='Concept_Types/Tune_RCMD.(01.23).mp3' and 'Recording' in title for path,title in links),'recommended recording is MP3 '+mode)
            ok(('Concept_Prefix/SXS_Prefix.pdf','sx') in links,'configured substring can begin the PDF filename '+mode)
            ok(('Concept_Text/Guide.txt','Guide.txt') in links,'non-metadata text file remains downloadable '+mode)
            ok('ACTUAL TUNE NOTES' in doc.get_text(),'infos.txt remains the authoritative tune information '+mode)
            ok('LEGACY TUNE NOTES' in doc.get_text(),'historical text metadata remains readable without infos.txt '+mode)
            ok(('Concept_Legacy/legacy-notes.txt','legacy-notes.txt') in links,'legacy text metadata also retains a download link '+mode)
            ok(not any('private_SXS' in path or 'zzDEL!' in path for path,title in links),'private/recovery files never occupy section slots '+mode)
            for kind in ['print','xls']:
                response=session.get(base+'nadinmodule/gigreps.'+kind+'.php',params={'m':mode,'c':'2099-01-01 Asset concepts.txt'})
                if kind=='print':
                    titles=[x.get_text() for x in soup(response).select('td.print')]
                    ok(titles==list(assets),'Print retains exact tune order '+mode)
                else:
                    records=list(csv.reader(io.StringIO(response.content.decode('utf-8-sig')),delimiter=';'))
                    prefix=next((r for r in records if 'Concept_Prefix' in r),[])
                    text=next((r for r in records if 'Concept_Text' in r),[])
                    ok('sx' in prefix,'XLS recognises the same prefix section PDF '+mode)
                    ok(len(text)>2 and text[2].strip(' |')=='ACTUAL TUNE NOTES','XLS uses infos.txt rather than another text file '+mode)
        for tune,files in assets.items():
            for name,original in files.items():ok((app/'library'/tune/name).read_bytes()==original,'reading preserves '+tune+'/'+name)
        log.flush();log.seek(0);errors=log.read();ok('Fatal error' not in errors and 'Parse error' not in errors,'no PHP fatal/parse errors')
        print(('FAIL' if failures else 'PASS')+f': {checks} asset-role HTTP checks ({ROOT.name})',flush=True)
        if failures:raise AssertionError('; '.join(failures))
    finally:server.terminate();server.wait(timeout=5);log.close()
