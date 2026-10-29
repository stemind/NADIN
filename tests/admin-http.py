"""V12 regression tests: isolated filesystem, real HTTP requests, no outgoing mail."""
import hashlib, json, os, shutil, socket, subprocess, tempfile, time, io, zipfile, re
from pathlib import Path
import requests
from bs4 import BeautifulSoup
ROOT=Path(__file__).resolve().parents[1]
PHP=os.environ.get('NADIN_PHP','php'); checks=0

def ok(value,label):
 global checks
 assert value,label
 checks+=1

with tempfile.TemporaryDirectory(prefix='nadin-v12-') as temp:
 app=Path(temp)/'nadin';shutil.copytree(ROOT,app,ignore=shutil.ignore_patterns('.git'))
 (app/'config.inc.php').write_text((app/'config.inc.php').read_text().replace("$salt='???'","$salt='v12-fixture'"))
 rights=['vgigs','vsaxes','vbones','vtrumpets','vrhythm','vother','vscore','vreclist','varchiv','vlibrary','upaudio','uppdf','mgigs','musers']
 users='Admin, admin@example.invalid, 31.12.2099, '+', '.join(r+'=1' for r in rights)+',\nMember, member@example.invalid, 31.12.2099, vgigs=1, vsaxes=1,\n'
 (app/'userdatei/userdatei.txt').write_text(users)
 (app/'userdatei/mail-adapter.php').write_text('<?php function nadin_mail_transport($to,$subject,$body){return true;}')
 with socket.socket() as sock:sock.bind(('127.0.0.1',0));port=sock.getsockname()[1]
 log=open(Path(temp)/'server.log','w+')
 process=subprocess.Popen([PHP,'-S',f'127.0.0.1:{port}','-t',str(Path(temp))],stdout=log,stderr=log)
 base=f'http://127.0.0.1:{port}/nadin'
 try:
  for _ in range(100):
   try:
    if requests.get(base+'/login.php').status_code==200:break
   except requests.ConnectionError:pass
   time.sleep(.05)
  def login(email):
   s=requests.Session();r=s.get(base+'/login.php');soup=BeautifulSoup(r.text,'html.parser');d={e['name']:e.get('value','') for e in soup.select('input[type=hidden][name]')};d.update(u=email,p=hashlib.sha1((email+'v12-fixture').encode()).hexdigest()[7:13]);r=s.post(base+'/login2.php',data=d);ok(r.status_code==200 and email in r.text,'login '+email);return s
  s=login('admin@example.invalid');member=login('member@example.invalid')
  def api(d,session=s,post=False,token=True):
   d=dict(d)
   if post:
    if token:
     r=session.get(base+'/nadinmodule/admin-api.php',params={'area':d['area'],'action':'token'});d['csrf']=r.json().get('csrf','')
    return session.post(base+'/nadinmodule/admin-api.php',json=d)
   return session.get(base+'/nadinmodule/admin-api.php',params=d)
  def gigsave(**kw):return api(dict(area='gigs',action='save',**kw),post=True)
  r=s.get(base+'/index.php?m=managegigs');ok(r.status_code==200 and 'nadin-admin-data' in r.text and 'admin.js' in r.text,'new gig editor renders')
  r=s.get(base+'/index.php?m=users');ok('People &amp;' in r.text or 'People & permissions' in r.text,'people editor renders')
  ok('textarea' not in BeautifulSoup(r.text,'html.parser').find(id='nadin-admin').text,'no raw user-file textarea')
  r=api({'area':'gigs'},member);ok(r.status_code==403,'member cannot read admin catalog')
  r=api({'area':'people'},member);ok(r.status_code==403,'member cannot read user list')
  r=api({'area':'gigs','action':'save','id':'','date':'2026-12-01','title':'No CSRF'},post=True,token=False);ok(r.status_code==403,'write requires csrf')
  r=api({'area':'gigs','action':'save','date':'2026-12-01','title':'GET mutation'});ok(not (app/'gigreps/2026-12-01 GET mutation.txt').exists(),'GET does not mutate')
  r=gigsave(id='',date='2026-02-31',title='Impossible');ok(r.status_code==400,'invalid date rejected')
  r=gigsave(id='',date='2026-12-01',title='../Traversal');ok(r.status_code==400,'traversal name rejected')
  r=gigsave(id='',date='2026-12-01',title='V12 Test',infos='Meet 19:00',tunes=['Anna_Swings_Best','Break','Berta_Has_The_Blues']);ok(r.status_code==200,r.text);g=r.json()['gig'];ok(g['id']=='2026-12-01 V12 Test.txt' and len(g['tunes'])==3,'create gig with heading')
  ok(gigsave(id='',date='2026-12-01',title='V12 Test').status_code==409,'duplicate create rejected')
  old=dict(g);g['tunes'].append('Anna_Swings_Best');r=gigsave(**{k:g[k] for k in ['id','revision','infos','tunes']});ok(r.status_code==200,'repeated tune preserved');g=r.json()['gig']
  r=gigsave(**{k:old[k] for k in ['id','revision','infos','tunes']});ok(r.status_code==409,'stale save rejected')
  ok(gigsave(id='../config.inc.php',revision='',infos='',tunes=[]).status_code==400,'gig traversal rejected')
  ok(gigsave(id=g['id'],revision=g['revision'],infos='',tunes=['../private']).status_code==400,'tune traversal rejected')
  token=api({'area':'gigs','action':'token'}).json()['csrf']
  r=s.post(base+'/nadinmodule/admin-api.php',data={'area':'gigs','action':'attachment','id':g['id'],'revision':g['revision'],'csrf':token},files={'pdf':('notes.pdf',b'%PDF-1.4\nFixture PDF','application/pdf')});ok(r.status_code==200,r.text);g=r.json()['gig'];ok(g['attachment'],'attachment saved')
  r=s.post(base+'/nadinmodule/admin-api.php',data={'area':'gigs','action':'attachment','id':g['id'],'revision':g['revision'],'csrf':token},files={'pdf':('notes.pdf',b'<?php echo 1;','application/pdf')});ok(r.status_code==400,'fake PDF rejected')
  def action(action,g,**kw):return api(dict(area='gigs',action=action,id=g['id'],mode=g['mode'],revision=g['revision'],**kw),post=True)
  r=action('copy',g,date='2026-12-02',title='V12 Copy');ok(r.status_code==200,r.text);copied=r.json()['gig'];ok(copied['tunes']==g['tunes'] and copied['attachment'],'copy preserves setlist and attachment')
  r=action('rename',copied,date='2026-12-03',title='V12 Renamed');ok(r.status_code==200,r.text);renamed=r.json()['gig'];ok(not (app/'gigreps'/copied['id']).exists() and renamed['attachment'],'rename moves attachment')
  r=action('archive',renamed);ok(r.status_code==200,r.text);archived=r.json()['gig'];ok(archived['mode']=='archive' and archived['attachment'],'archive retains attachment')
  ok(gigsave(id='',date='2026-12-03',title='V12 Renamed').status_code==409,'name collision with archive rejected')
  r=action('restore',archived);ok(r.status_code==200,r.text);restored=r.json()['gig'];ok(restored['mode']=='current','restore archive')
  r=action('trash',restored);ok(r.status_code==200,r.text);deleted=r.json()['gig'];ok(deleted['mode']=='trash' and deleted['attachment'],'deleted gig and PDF retained')
  r=action('restore',deleted);ok(r.status_code==200,r.text);restored=r.json()['gig'];ok(restored['id']==renamed['id'] and restored['attachment'],'deleted gig restored under original name')
  # Lazy ZIP generation follows the exact setlist and section rights, with Parts fallback.
  d=app/'library/Fallback_Tune';d.mkdir();(d/'Fallback_Tune_PRTS_.pdf').write_bytes(b'%PDF-parts');(d/'Fallback_Tune_SCRE_.pdf').write_bytes(b'%PDF-score')
  r=gigsave(id=g['id'],revision=g['revision'],infos=g['infos'],tunes=['Anna_Swings_Best','Fallback_Tune']);ok(r.status_code==200,'update setlist for ZIP');g=r.json()['gig']
  zipquery={'c':'zip/allsaxes/'+g['id'][:-4]+'_allsaxes.zip'}
  r=member.get(base+'/get/zip.php',params=zipquery);ok(r.status_code==200 and r.content.startswith(b'PK'),'lazy section ZIP download')
  names=zipfile.ZipFile(io.BytesIO(r.content)).namelist();ok(any('SXS' in n for n in names) and any('PRTS' in n for n in names) and not any('SCRE' in n for n in names),'section ZIP includes Parts fallback, excludes score')
  (d/'Fallback_Tune_PRTS_.pdf').write_bytes(b'%PDF-replaced')
  r=member.get(base+'/get/zip.php',params=zipquery);z=zipfile.ZipFile(io.BytesIO(r.content));ok(z.read('Fallback_Tune/Fallback_Tune_PRTS_.pdf')==b'%PDF-replaced','source replacement refreshes ZIP')
  r=member.get(base+'/get/zip.php',params={'c':'zip/alltrumpets/'+g['id'][:-4]+'_alltrumpets.zip'});ok(r.status_code==403,'section ZIP enforces permission')
  r=action('archive',g);ok(r.status_code==200,'archive test ZIP gig');g=r.json()['gig']
  ok(member.get(base+'/get/zip.php',params=zipquery).status_code==403,'archive ZIP requires archive permission')
  r=action('restore',g);g=r.json()['gig']
  # Person edits preserve untouched lines, password continuity and parallel changes.
  people=api({'area':'people'}).json()['rows'];admin=next(p for p in people if p['email']=='admin@example.invalid');m=next(p for p in people if p['email']=='member@example.invalid')
  baseperson=dict(area='people',action='save',original=m['email'],revision=m['revision'],name='Member New',email=m['email'],expiry=m['expiry'],rights=m['rights'])
  r=api(baseperson,post=True);ok(r.status_code==200,r.text);m2=next(p for p in r.json()['people']['rows'] if p['email']==m['email']);ok(m2['name']=='Member New','person name saved')
  ok(api(baseperson,post=True).status_code==409,'stale person edit rejected')
  ok(s.get(base+'/password.php').status_code==200 and 'admin@example.invalid' in s.get(base+'/password.php').text,'unrelated admin session retained')
  ok(member.get(base+'/password.php',allow_redirects=False).status_code==303,'changed member session revoked')
  p=dict(baseperson,revision=m2['revision'],rights=['uppdf']);ok(api(p,post=True).status_code==400,'contradictory rights rejected')
  p=dict(baseperson,revision=m2['revision'],email='other@example.invalid');ok(api(p,post=True).status_code==400,'email identity cannot be silently changed')
  p=dict(baseperson,revision=m2['revision'],name='Injected, email');ok(api(p,post=True).status_code==400,'CSV injection rejected')
  r=api(dict(area='people',action='delete',original=admin['email'],revision=admin['revision']),post=True);ok(r.status_code==400,'cannot delete own account')
  p=dict(area='people',action='save',original=admin['email'],revision=admin['revision'],name=admin['name'],email=admin['email'],expiry=admin['expiry'],rights=['vgigs']);ok(api(p,post=True).status_code==400,'cannot remove own user management')
  p['rights']=admin['rights'];p['name']='Admin Renamed';r=api(p,post=True);ok(r.status_code==200,r.text);ok(api({'area':'people'}).status_code==200,'own current session survives permitted edit')
  new=dict(area='people',action='save',original='',name='New Sax',email='new@example.invalid',expiry='2026-12-31',rights=['vgigs','vsaxes']);r=api(new,post=True);ok(r.status_code==200,r.text)
  newrow=next(p for p in r.json()['people']['rows'] if p['email']=='new@example.invalid');ok(api(new,post=True).status_code==409,'duplicate email rejected')
  newcomer=login('new@example.invalid');ok(newcomer.get(base+'/index.php').status_code==200,'new person uses unchanged simple onboarding')
  r=api(dict(area='people',action='delete',original=newrow['email'],revision=newrow['revision']),post=True);ok(r.status_code==200,'remove person');ok(newcomer.get(base+'/password.php',allow_redirects=False).status_code==303,'remove revokes sessions')
  # Existing media forms carry tokens; dangerous GET endpoints cannot mutate.
  for path in ['new.php','upload.php?tune=Anna_Swings_Best','PDFsendfile.php?name=Anna_Swings_Best','AUDsendfile.php?name=Anna_Swings_Best','PDFfiles.php?name=Anna_Swings_Best']:
   r=s.get(base+'/nadinmodule/'+path);ok('nadinMutation' in r.text and r.status_code==200,'media CSRF UI '+path)
  for path in ['PDFdeletefile.php','AUDdeletefile.php','PDFrenamefile.php','new2.php','ATTsendfile2.php','uploadsave.php']:
   ok(s.get(base+'/nadinmodule/'+path).status_code==403,'media GET rejected '+path)
  for path in ['trx.php','savegig.php','saveusers.php']:
   ok(s.post(base+'/nadinmodule/'+path).status_code==410,'old editor retired '+path)
  # Real media writes still work with the new guard, without changing their familiar forms.
  def media_token(path):
   response=s.get(base+'/nadinmodule/'+path)
   return json.loads(re.search(r'var token=("[^"\n]+")',response.text).group(1))
  token=media_token('new.php')
  r=s.post(base+'/nadinmodule/new2.php',data={'tune':'V12 Media Test','csrf':token});ok(r.status_code==200 and (app/'library/V12_Media_Test').is_dir(),'create tune through existing form')
  r=s.post(base+'/nadinmodule/PDFsendfile2.php',data={'name':'V12_Media_Test','csrf':token},files={'userfile[]':('V12_Media_Test_SXS_.pdf',b'%PDF-media','application/pdf')});ok((app/'library/V12_Media_Test/V12_Media_Test_SXS_.pdf').exists(),'PDF upload with CSRF')
  r=s.post(base+'/nadinmodule/PDFrenamefile.php',data={'p':'V12_Media_Test','f':'V12_Media_Test_SXS_.pdf','n':'V12_Media_Test_SXS_Renamed.pdf','csrf':token});ok(r.status_code==200 and (app/'library/V12_Media_Test/V12_Media_Test_SXS_Renamed.pdf').exists(),'PDF rename with POST')
  r=s.post(base+'/nadinmodule/PDFrenamefile.php',data={'p':'V12_Media_Test','f':'V12_Media_Test_SXS_Renamed.pdf','n':'code.php','csrf':token});ok(r.status_code==400 and not (app/'library/V12_Media_Test/code.php').exists(),'executable rename rejected')
  r=s.post(base+'/nadinmodule/AUDdeletefile.php',data={'p':'V12_Media_Test','f':'V12_Media_Test_SXS_Renamed.pdf','csrf':token});ok(r.status_code==403,'audio delete cannot remove a PDF')
  revision=hashlib.sha256(b'').hexdigest()
  payload={'foldername':'V12_Media_Test','infos':'Tune notes','links':'https://example.invalid','revision':revision,'csrf':token}
  r=s.post(base+'/nadinmodule/uploadsave.php',data=payload);ok(r.status_code==200 and (app/'library/V12_Media_Test/infos.txt').read_text().startswith('Tune notes'),'tune notes saved')
  r=s.post(base+'/nadinmodule/uploadsave.php',data=payload);ok(r.status_code==409 and 'nadin-media-error' in r.text,'stale tune notes rejected with visible parent error')
  # Renaming a tune updates exact setlist entries, never similar names or free-form info.
  reference=app/'gigreps/2026-12-24 Media reference.txt';reference.write_text('Mention V12_Media_Test unchanged in info\n* - * - * - * * * - * - * - *\nV12_Media_Test\nV12_Media_Test_Extra')
  (app/'library/V12_Media_Test/.nadin-forscore.json').write_text('{"fixture":"stable"}')
  r=s.post(base+'/nadinmodule/renamefolder2.php?tune=V12_Media_Test',data={'tune':'V12 Media Renamed','csrf':token});ok(r.status_code==200 and (app/'library/V12_Media_Renamed').is_dir(),r.text)
  text=reference.read_text();ok('Mention V12_Media_Test unchanged in info' in text and '\nV12_Media_Renamed\nV12_Media_Test_Extra' in text,'tune rename updates exact entries only')
  ok((app/'library/V12_Media_Renamed/.nadin-forscore.json').read_text()=='{"fixture":"stable"}','tune rename preserves forScore registry')
  r=s.post(base+'/nadinmodule/PDFdeletefile.php',data={'p':'V12_Media_Renamed','f':'V12_Media_Test_SXS_Renamed.pdf','csrf':token});ok(r.status_code==200 and not (app/'library/V12_Media_Renamed/V12_Media_Test_SXS_Renamed.pdf').exists(),'PDF moved to deleted files')
  ok(bool(list((app/'filesdeleted').glob('*V12_Media_Test_SXS_Renamed.pdf'))),'deleted PDF retained')
  r=s.post(base+'/nadinmodule/deletefolder2.php?tune=V12_Media_Renamed',data={'tune':'V12_Media_Renamed','csrf':token});ok(r.status_code==409,'in-use empty tune cannot be deleted')
  reference.unlink()
  r=s.post(base+'/nadinmodule/deletefolder2.php?tune=V12_Media_Renamed',data={'tune':'V12_Media_Renamed','csrf':token});ok(r.status_code==200 and not (app/'library/V12_Media_Renamed').exists(),'unused empty tune can be removed')
  ok(bool(list((app/'filesdeleted').glob('*!V12_Media_Renamed'))),'empty tune folder retained for recovery')
  # Exact read permissions, path protection and archive attachment permissions.
  revoked=dict(baseperson,revision=m2['revision'],rights=['vgigs']);ok(api(revoked,post=True).status_code==200,'revoke section permission explicitly')
  member=login('member@example.invalid')
  # A new login must respect the changed section permission.
  file='Anna_Swings_Best/Anna_Swings_Best_SXS_.pdf'
  ok(member.get(base+'/get/file.php',params={'c':file}).status_code==403,'PDF section enforced after rights change')
  ok(s.get(base+'/get/file.php',params={'c':file}).status_code==200,'admin PDF download')
  for route in ['/get/file.php?c=../config.inc.php','/get/file.php?c=Anna_Swings_Best/.nadin-forscore.json','/get/setlist.php?c=../config.inc.php']:
   ok(s.get(base+route).status_code==404,'private/traversal file rejected '+route)
  (app/'library/Anna').mkdir();(app/'library/Anna/Anna_PRTS_.pdf').write_bytes(b'%PDF-prefix')
  ok(member.get(base+'/get/file.php',params={'c':'Anna/Anna_PRTS_.pdf'}).status_code==403,'similar tune prefix grants no access')
  r=action('archive',g);g=r.json()['gig']
  ok(member.get(base+'/get/setlist.php',params={'c':g['id'][:-4]+'.pdf'}).status_code==403,'archive attachment requires archive rights')
  ok(s.get(base+'/get/setlist.php',params={'c':g['id'][:-4]+'.pdf'}).status_code==200,'authorised archive attachment works')
  ok(bool(list((app/'userdatei/revisions').glob('*.php'))),'safety copies created')
  r=s.get(base+'/userdatei/admin-v12-lock.php');ok(r.status_code==404 and not r.content,'lock file guarded')
  for path in ['/index.php?m=gigreps','/index.php?m=library','/get/forscore.php?m=library']:
   r=s.get(base+path);ok(r.status_code==200,'existing view '+path)
  log.flush();log.seek(0);ok('Fatal error' not in log.read(),'no PHP fatal errors')
  print(f'PASS: {checks} V12 HTTP/storage regression checks')
 finally:
  process.terminate();process.wait(timeout=5);log.flush();log.seek(0)
  print('\n'.join(l for l in log.read().splitlines() if 'NADIN admin:' in l or 'Fatal error' in l))
  log.close()
