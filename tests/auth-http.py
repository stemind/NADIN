"""Exercise account routes against an isolated PHP server; all mail is captured locally."""
import concurrent.futures, hashlib, json, os, shutil, socket, subprocess, tempfile, time
from pathlib import Path
import requests
from bs4 import BeautifulSoup
ROOT=Path(__file__).resolve().parents[1]
PHP=os.environ.get('NADIN_PHP','php')
checks=0

def ok(test,label):
 global checks
 assert test,label
 checks+=1

def fields(response):
 soup=BeautifulSoup(response.text,'html.parser')
 return {el['name']:el.get('value','') for el in soup.select('input[type=hidden][name]')}

with tempfile.TemporaryDirectory(prefix='nadin-http-') as temp:
 web=Path(temp)/'web'; app=web/'band'/'nadin'
 shutil.copytree(ROOT,app,ignore=shutil.ignore_patterns('.git'))
 (app/'config.inc.php').write_text((app/'config.inc.php').read_text().replace("$salt='???'","$salt='test-only-secret'"))
 users='Member, member@example.invalid, 31.12.2099, vgigs=1, vsaxes=1,\nAdmin, admin@example.invalid, 31.12.2099, vgigs=1, musers=1, mgigs=1, vlibrary=1,\n'
 (app/'userdatei/userdatei.txt').write_text(users)
 (app/'userdatei/mail-adapter.php').write_text('<?php function nadin_mail_transport($to,$subject,$body) { file_put_contents(__DIR__."/captured-mail.jsonl",json_encode(array("to"=>$to,"subject"=>$subject,"body"=>$body))."\\n",FILE_APPEND|LOCK_EX); return true; }')
 with socket.socket() as sock:
  sock.bind(('127.0.0.1',0)); port=sock.getsockname()[1]
 log=open(Path(temp)/'server.log','w+')
 process=subprocess.Popen([PHP,'-S',f'127.0.0.1:{port}','-t',str(web)],stdout=log,stderr=log)
 base=f'http://127.0.0.1:{port}/band/nadin'
 try:
  for _ in range(100):
   try:
    if requests.get(base+'/login.php').status_code==200:break
   except requests.ConnectionError:pass
   time.sleep(.05)
  else:raise AssertionError('PHP server did not start')
  def initial(email):return hashlib.sha1((email+'test-only-secret').encode()).hexdigest()[7:13]
  def login(s,email,pw):
   d=fields(s.get(base+'/login.php'));d.update(u=email,p=pw)
   return s.post(base+'/login2.php',data=d,allow_redirects=False)
  def mail():return [json.loads(x) for x in (app/'userdatei/captured-mail.jsonl').read_text().splitlines()]
  s=requests.Session(); r=s.get(base+'/login.php')
  ok(r.status_code==200 and 'csrf' in fields(r),'login form loads in subdirectory')
  ok('no-store' in r.headers.get('Cache-Control',''),'login not cached')
  r=s.post(base+'/login2.php',data={'u':'member@example.invalid','p':initial('member@example.invalid')},allow_redirects=False)
  ok(r.status_code==403 and 'form has expired' in r.text,'login csrf enforced')
  r=login(s,'member@example.invalid',initial('member@example.invalid'))
  ok(r.status_code==303 and r.headers['Location']=='index.php','initial login succeeds')
  cookie=next(c for c in s.cookies if c.name.startswith('nadin_session_'))
  ok(cookie.path=='/band/nadin/' and cookie.has_nonstandard_attr('HttpOnly') and cookie.get_nonstandard_attr('SameSite')=='Lax','scoped HttpOnly SameSite session')
  r=s.get(base+'/index.php');ok(r.status_code==200 and 'member@example.invalid' in r.text and 'password.php' in r.text,'index authenticates new session')
  old=requests.Session();old.cookies.update(s.cookies)
  r=s.get(base+'/password.php');d=fields(r);d.update(current_password=initial('member@example.invalid'),new_password='My personal password 123',confirm_password='My personal password 123')
  bad=dict(d,csrf='bad');r=s.post(base+'/password.php',data=bad);ok('form has expired' in r.text,'change csrf enforced')
  r=s.post(base+'/password.php',data=d);ok('Password changed.' in r.text,'personal password change succeeds')
  r=old.get(base+'/password.php',allow_redirects=False);ok(r.status_code==303 and r.headers['Location']=='/band/nadin/login.php','old session invalidated on next request')
  r=s.get(base+'/password.php');ok('Personal password' in r.text,'new current session retained')
  x=requests.Session();r=login(x,'member@example.invalid',initial('member@example.invalid'));ok(r.status_code==401 and 'Login was not possible' in r.text,'initial password disabled over HTTP')
  r=login(x,'member@example.invalid','My personal password 123');ok(r.headers['Location']=='index.php','personal login succeeds')
  legacy=requests.Session();legacy.cookies.set('u','member@example.invalid');legacy.cookies.set('p',hashlib.sha1(('test-only-secret'+initial('member@example.invalid')).encode()).hexdigest())
  r=legacy.get(base+'/password.php',allow_redirects=False);ok(r.status_code==303,'legacy cookies cannot bypass changed password')
  r=x.get(base+'/bestellen.php');d=fields(r);d['u']='member@example.invalid'
  r=x.post(base+'/bestellen2.php',data=d,allow_redirects=False);ok(r.headers['Location']=='bestellen.php?sent=1','recovery accepted')
  message=mail()[-1];ok(message['to']=='member@example.invalid' and 'one-time recovery code' in message['body'],'recovery sent only to registered address')
  code=message['body'].split('recovery code: ')[1].split('\n')[0]
  ok('My personal password' not in message['body'] and initial('member@example.invalid') not in message['body'],'no personal or retired password mailed')
  r=login(requests.Session(),'member@example.invalid','My personal password 123');ok(r.headers['Location']=='index.php','recovery request preserves password')
  r=x.get(base+'/reset-password.php');d=fields(r);d.update(u='member@example.invalid',code=code,new_password='Changed again password 456',confirm_password='Changed again password 456')
  r=x.post(base+'/reset-password.php',data=d,allow_redirects=False);ok(r.status_code==303 and r.headers['Location']=='login.php?reset=1','recovery code resets password')
  r=x.get(base+'/password.php',allow_redirects=False);ok(r.status_code==303,'reset signs out all sessions without auto-login')
  r=x.get(base+'/reset-password.php');d['csrf']=fields(r)['csrf'];r=x.post(base+'/reset-password.php',data=d);ok('code is invalid or expired' in r.text,'reused recovery code rejected')
  a=requests.Session();r=login(a,'admin@example.invalid',initial('admin@example.invalid'));ok(r.headers['Location']=='index.php','administrator initial login retained')
  r=a.get(base+'/nadinmodule/p.php');ok('Personal password set' in r.text and initial('member@example.invalid') not in r.text,'admin cannot see retired password')
  ok(not (app/'userdatei/.htpasswd').exists() or (app/'userdatei/.htpasswd').stat().st_mtime < time.time()-1,'access details does not generate legacy credentials')
  before=len(mail());r=a.get(base+'/bestellen.php');d=fields(r);d['u']='unknown@example.invalid';r=a.post(base+'/bestellen2.php',data=d,allow_redirects=False)
  ok(r.headers['Location']=='bestellen.php?sent=1' and len(mail())==before,'unknown recovery identical response and no mail')
  r=a.get(base+'/bestellen.php');d=fields(r);d['u']='admin@example.invalid';a.post(base+'/bestellen2.php',data=d)
  ok(initial('admin@example.invalid') in mail()[-1]['body'],'simple onboarding email retained')
  r=a.get(base+'/index.php?m=users');soup=BeautifulSoup(r.text,'html.parser');boot=json.loads(soup.find(id='nadin-admin-data').string);ok(bool(boot['csrf']),'user editor has csrf')
  r=a.post(base+'/nadinmodule/admin-api.php',json={'area':'people','action':'save'});ok(r.status_code==403,'user edits require csrf')
  person=next(p for p in boot['people']['rows'] if p['email']=='member@example.invalid');payload=dict(person,area='people',action='save',original=person['email'],csrf=boot['csrf'],rights=['vgigs']);r=a.post(base+'/nadinmodule/admin-api.php',json=payload);ok(r.status_code==200 and r.json()['email']==person['email'],'user edits work')
  r=a.get(base+'/get/forscore.php?m=library');ok(r.status_code==200 and 'forScore' in r.text,'forScore accepts V11 session')
  for path in ('abo/index.php','abo/us.php','abo/get/file.php?c=anything','nadinmodule/calcbasicauth.inc.php'):
   ok(a.get(base+'/'+path).status_code==410,'retired endpoint '+path)
  (app/'gigreps/fixture.txt').write_text('Fixture');(app/'abo/zip').mkdir(exist_ok=True);(app/'abo/zip/fixture.pdf').write_bytes(b'%PDF-test-gig')
  r=a.get(base+'/get/setlist.php?c=fixture.pdf');ok(r.content==b'%PDF-test-gig','gig PDF attachments preserved')
  (app/'abo/zip/allsaxes').mkdir(exist_ok=True);(app/'abo/zip/allsaxes/fixture.zip').write_bytes(b'PK-test-zip')
  ok(a.get(base+'/get/zip.php?c=zip/allsaxes/fixture.zip').content!=b'PK-test-zip','section rights retained for ZIPs')
  m=requests.Session();login(m,'member@example.invalid','Changed again password 456')
  ok(m.get(base+'/preview.php?gig=x',allow_redirects=False).status_code==403,'ZIP preview requires manager role')
  r=a.get(base+'/logout.php',allow_redirects=False);ok(r.status_code==303,'logout works')
  r=a.get(base+'/password.php',allow_redirects=False);ok(r.status_code==303,'logout removes server session')
  # Direct PHP access to the account store does not reveal data even without Apache .htaccess.
  r=requests.get(base+'/userdatei/auth-v11.php');ok(r.status_code==404 and not r.content,'credential file has executable guard')
  # V13: real HTTP status, Retry-After, persistence and no cookie/header bypass.
  store=app/'userdatei/auth-v11.php';guard='<?php http_response_code(404); exit; ?>\n'
  def db():return json.loads(store.read_text()[len(guard):])
  def putdb(d):store.write_text(guard+json.dumps(d))
  data=db();data['rates']={};putdb(data)
  stranger=requests.Session()
  for i in range(5):
   r=login(stranger,'member@example.invalid','wrong')
   ok(r.status_code==401 and 'Login was not possible' in r.text,'ordinary failure remains generic '+str(i))
  ok(BeautifulSoup(r.text,'html.parser').find(id='u')['value']=='member@example.invalid','failed login preserves email')
  ok(not BeautifulSoup(r.text,'html.parser').find(id='p').get('value'),'password never echoed into form')
  r=login(stranger,'member@example.invalid','wrong')
  ok(r.status_code==429 and 0<int(r.headers['Retry-After'])<=30,'sixth failure returns rate-limit status and real retry time')
  ok('data-retry-after=' in r.text and 'waiting time ends automatically' in r.text,'lock page has countdown and no-JS explanation')
  ok('no-store' in r.headers.get('Cache-Control',''),'lock response never cached')
  before=db()['rates'];fresh=requests.Session();fresh.headers['X-Forwarded-For']='192.0.2.211'
  r=login(fresh,'MEMBER@example.invalid','Changed again password 456')
  ok(r.status_code==429 and db()['rates']==before,'fresh browser and forged forwarded IP cannot bypass or extend lock')
  r=login(fresh,'admin@example.invalid',initial('admin@example.invalid'))
  ok(r.status_code==303 and r.headers['Location']=='index.php','other account at same network remains usable')
  r=fresh.get(base+'/login2.php',allow_redirects=False);ok(r.status_code==303 and r.headers['Location']=='login.php','GET is not a login attempt')
  data=db()
  for rate in data['rates'].values():rate['until']=int(time.time())-1
  putdb(data);r=login(stranger,'member@example.invalid','Changed again password 456')
  ok(r.status_code==303 and r.headers['Location']=='index.php','expired locks release without administrator')
  data=db();data['rates']={};putdb(data)
  for _ in range(6):r=login(stranger,'unknown@example.invalid','wrong')
  ok(r.status_code==429 and 'Retry-After' in r.headers and 'data-retry-after=' in r.text,'unknown email has same lock response')
  r=stranger.post(base+'/login2.php',data={'u':'<script>alert(1)</script>','p':'secret','csrf':'invalid'})
  ok(r.status_code==403 and '<script>alert(1)</script>' not in r.text and 'secret' not in r.text,'invalid form safely escapes email and never echoes password')
  log.flush();log.seek(0);errors=log.read();ok('Fatal error' not in errors and 'Parse error' not in errors,'no PHP fatal/parse errors')
  print(f'PASS: {checks} HTTP account/integration checks')
 finally:
  process.terminate();process.wait(timeout=5);log.close()
