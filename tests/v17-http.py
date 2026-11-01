"""V17 task responses and actual data preservation on an isolated installation."""
import hashlib, json, os, shutil, socket, subprocess, tempfile, time
from pathlib import Path
import requests
from bs4 import BeautifulSoup

ROOT=Path(__file__).resolve().parents[1];PHP=os.environ.get('NADIN_PHP','php');checks=0
def ok(value,label):
    global checks
    assert value,label
    checks+=1
with tempfile.TemporaryDirectory(prefix='nadin-v17-http-') as temp:
    temp=Path(temp);app=temp/'nadin';shutil.copytree(ROOT,app,ignore=shutil.ignore_patterns('.git','vendor'))
    config=app/'config.inc.php';config.write_text(config.read_text().replace("$salt='???'","$salt='v17-fixture'"))
    rights='vgigs,vsaxes,vbones,vtrumpets,vrhythm,vother,vscore,vreclist,varchiv,vlibrary,upaudio,uppdf,mgigs,musers'.split(',')
    (app/'userdatei/userdatei.txt').write_text('Admin,admin@example.invalid,31.12.2099,'+','.join(r+'=1' for r in rights)+'\nMember,member@example.invalid,31.12.2099,vgigs=1,vsaxes=1,\n')
    tune=app/'library/Task_Test';tune.mkdir();(tune/'infos.txt').write_text('Original\n* - * - * - * * * - * - * - *\nhttps://example.invalid')
    (tune/'First_SXS.pdf').write_bytes(b'%PDF-first');(tune/'Second_SXS.pdf').write_bytes(b'%PDF-second');(tune/'Original.mp3').write_bytes(b'MP3-test')
    (app/'gigreps/2026-11-07 Task gig.txt').write_text('Info\n* - * - * - * * * - * - * - *\nTask_Test')
    # A page view must never destroy an existing file named x in a writable data folder.
    (app/'library/x').write_bytes(b'keep-this-file')
    with socket.socket() as sock:sock.bind(('127.0.0.1',0));port=sock.getsockname()[1]
    log=open(temp/'server.log','w+');process=subprocess.Popen([PHP,'-S',f'127.0.0.1:{port}','-t',str(temp)],stdout=log,stderr=log);base=f'http://127.0.0.1:{port}/nadin'
    try:
        for _ in range(100):
            try:
                if requests.get(base+'/login.php').status_code==200:break
            except requests.ConnectionError:pass
            time.sleep(.04)
        def login(email):
            session=requests.Session();page=session.get(base+'/login.php');doc=BeautifulSoup(page.text,'html.parser');form=doc.find('form');data={i['name']:i.get('value','') for i in form.select('input[type=hidden][name]')};data.update(u=email,p=hashlib.sha1((email+'v17-fixture').encode()).hexdigest()[7:13]);response=session.post(base+'/login2.php',data=data,allow_redirects=False);ok(response.status_code==303,'real login '+email);return session
        admin=login('admin@example.invalid');member=login('member@example.invalid')
        token=admin.get(base+'/nadinmodule/media-token.php').json()['csrf'];ok(bool(token),'authenticated media token')
        def post(route,data=None,files=None,csrf=token):
            fields={'csrf':csrf,'_nadin_async':'1'};fields.update(data or {});return admin.post(base+'/nadinmodule/'+route,data=fields,files=files,headers={'Accept':'application/json'})
        ok(member.get(base+'/nadinmodule/media-token.php').status_code==403,'view-only member cannot obtain an editor token')
        ok(requests.get(base+'/nadinmodule/media-token.php').status_code==401,'anonymous token request stays JSON and requires login')
        ok(admin.post(base+'/nadinmodule/media-token.php').status_code==405,'token endpoint is read-only')
        page=admin.get(base+'/index.php?m=library');ok(page.status_code==200 and (app/'library/x').read_bytes()==b'keep-this-file','viewing a page preserves pre-existing x file')
        for route in ['upload.php?tune=Task_Test','uploadpdf.php?tune=Task_Test','uploadaudio.php?tune=Task_Test','uploadatt.php?gig=2026-11-07%20Task%20gig','new.php','renamefolder.php?tune=Task_Test','deletefolder.php?tune=Task_Test','p.php']:
            response=admin.get(base+'/nadinmodule/'+route);doc=BeautifulSoup(response.text,'html.parser');ok(response.status_code==200 and doc.select_one('script[src="../nadinmodule/task.js"]') and doc.select_one('link[href="../nadin.svg"]'),'shared task shell '+route)
        original=(tune/'First_SXS.pdf').read_bytes()
        response=post('PDFrenamefile.php',{'p':'Task_Test','f':'First_SXS.pdf','n':'Second_SXS.pdf'});ok(response.status_code==409 and response.json()['ok'] is False,'rename collision has structured failure')
        ok((tune/'First_SXS.pdf').read_bytes()==original and (tune/'Second_SXS.pdf').read_bytes()==b'%PDF-second','failed rename preserves both files')
        response=post('PDFrenamefile.php',{'p':'Task_Test','f':'First_SXS.pdf','n':'Renamed_SXS.pdf'});ok(response.status_code==200 and response.json()['ok'] and (tune/'Renamed_SXS.pdf').read_bytes()==original,'rename success returns a confirmed result and exact bytes')
        response=post('PDFdeletefile.php',{'p':'Task_Test','f':'Renamed_SXS.pdf'});ok(response.status_code==200 and response.json()['ok'] and not (tune/'Renamed_SXS.pdf').exists(),'delete moves file out of current tune')
        ok(any(p.read_bytes()==original for p in (app/'filesdeleted').iterdir() if p.is_file()),'removed file bytes remain recoverable')
        response=post('PDFdeletefile.php',{'p':'Task_Test','f':'Second_SXS.pdf'},csrf='bad');ok(response.status_code==403 and (tune/'Second_SXS.pdf').is_file(),'structured errors retain CSRF protection')
        response=post('PDFsendfile2.php',{'name':'Task_Test'},files=[('userfile[]',('New_SXS.pdf',b'%PDF-new','application/pdf')),('userfile[]',('Bad.txt',b'bad','text/plain'))]);ok(response.status_code==400 and not (tune/'New_SXS.pdf').exists(),'batch type validation happens before any file is replaced')
        response=post('PDFsendfile2.php',{'name':'Task_Test'},files=[('userfile[]',('New_SXS.pdf',b'%PDF-new','application/pdf'))]);ok(response.status_code==200 and response.json()['ok'] and (tune/'New_SXS.pdf').read_bytes()==b'%PDF-new','explicit file upload stores exact bytes')
        response=post('PDFsendfile2.php',{'name':'Task_Test'},files=[('userfile[]',('New_SXS.pdf',b'%PDF-replacement','application/pdf'))]);ok(response.status_code==200 and (tune/'New_SXS.pdf').read_bytes()==b'%PDF-replacement','same-name replacement succeeds')
        ok(any(p.name.startswith('zzDEL!') and p.read_bytes()==b'%PDF-new' for p in tune.iterdir() if p.is_file()),'same-name replacement retains previous bytes')
        response=post('AUDsendfile2.php',{'name':'Task_Test'},files={'userfile':('Practice.MP3',b'MP3-practice','audio/mpeg')});ok(response.status_code==200 and response.json()['ok'],'MP3 extension is accepted irrespective of case')
        response=post('ATTsendfile2.php?gig=2026-11-07%20Task%20gig',files={'userfile':('setlist.pdf',b'%PDF-attachment','application/pdf')});ok(response.status_code==200 and (app/'abo/zip/2026-11-07 Task gig.pdf').read_bytes()==b'%PDF-attachment','gig attachment upload keeps its established destination')
        notes=admin.get(base+'/nadinmodule/upload.php?tune=Task_Test');form=BeautifulSoup(notes.text,'html.parser').find('form');data={i['name']:i.get('value','') for i in form.select('input[type=hidden][name]')};data.update(infos='First saved notes',links='https://example.invalid/new')
        response=post('uploadsave.php',data);ok(response.status_code==200 and response.json()['revision']==hashlib.sha256((tune/'infos.txt').read_bytes()).hexdigest(),'notes save returns the exact new revision')
        old_revision=data['revision'];data['revision']=response.json()['revision'];data['infos']='Second saved notes';response=post('uploadsave.php',data);ok(response.status_code==200 and 'Second saved notes' in (tune/'infos.txt').read_text(),'a second notes save can use the returned revision')
        data['revision']=old_revision;data['infos']='Stale overwrite';response=post('uploadsave.php',data);ok(response.status_code==409 and 'Second saved notes' in (tune/'infos.txt').read_text(),'stale notes cannot overwrite a newer save')
        response=post('new2.php',{'tune':'New Task Tune'});ok(response.status_code==200 and response.json()['next_url'].startswith('uploadpdf.php?') and (app/'library/New_Task_Tune').is_dir(),'creating a tune leads to its score editor instead of closing the window')
        response=post('new2.php',{'tune':'New Task Tune'});ok(response.status_code==409 and response.json()['ok'] is False,'duplicate tune remains a recoverable form error')
        response=admin.post(base+'/nadinmodule/new2.php',data={'csrf':token,'tune':'Native fallback tune'});ok(response.status_code==200 and 'text/html' in response.headers['Content-Type'] and 'Add scores and files' in response.text,'native form fallback provides a useful continuation')
        log.flush();log.seek(0);server_log=log.read();ok(not any(x in server_log for x in ['PHP Warning:','PHP Fatal error:','PHP Deprecated:']),'task operations produce no PHP warnings or errors')
        print(f'PASS: {checks} V17 HTTP task checks')
    finally:process.terminate();process.wait(timeout=5);log.close()
