"""End-to-end TLS OIDC code/PKCE and SAML POST against isolated, locally signed providers.
Requires requests, beautifulsoup4 and cryptography. Never uses a real provider or sends mail.
"""
import base64, datetime, hashlib, http.client, http.server, ipaddress, json, os, shutil, socket, ssl, subprocess, tempfile, threading, time, urllib.parse, zlib
from pathlib import Path
import requests
from bs4 import BeautifulSoup
from cryptography import x509
from cryptography.hazmat.primitives import hashes, serialization
from cryptography.hazmat.primitives.asymmetric import rsa, padding
from cryptography.x509.oid import NameOID
from xml.etree import ElementTree as ET
ROOT=Path(__file__).resolve().parents[1]; PHP=os.environ.get('NADIN_PHP','php'); checks=0

def ok(v,label):
 global checks
 assert v,label
 checks+=1

def b64(b):return base64.urlsafe_b64encode(b).decode().rstrip('=')
def freeport():
 with socket.socket() as s:s.bind(('127.0.0.1',0));return s.getsockname()[1]
def hidden(r,selector='form'):
 form=BeautifulSoup(r.text,'html.parser').select_one(selector)
 return {x['name']:x.get('value','') for x in form.select('input[type=hidden][name]')}

with tempfile.TemporaryDirectory(prefix='nadin-federation-http-') as temp:
 temp=Path(temp);web=temp/'web';app=web/'nadin';shutil.copytree(ROOT,app,ignore=shutil.ignore_patterns('.git'))
 key=rsa.generate_private_key(public_exponent=65537,key_size=2048);pem=key.private_bytes(serialization.Encoding.PEM,serialization.PrivateFormat.PKCS8,serialization.NoEncryption()).decode()
 name=x509.Name([x509.NameAttribute(NameOID.COMMON_NAME,'NADIN isolated test')]);now=datetime.datetime.now(datetime.timezone.utc)
 cert=x509.CertificateBuilder().subject_name(name).issuer_name(name).public_key(key.public_key()).serial_number(x509.random_serial_number()).not_valid_before(now-datetime.timedelta(minutes=5)).not_valid_after(now+datetime.timedelta(days=1)).add_extension(x509.SubjectAlternativeName([x509.IPAddress(ipaddress.ip_address('127.0.0.1'))]),False).add_extension(x509.BasicConstraints(ca=True,path_length=None),True).sign(key,hashes.SHA256()).public_bytes(serialization.Encoding.PEM).decode()
 (temp/'key.pem').write_text(pem);(temp/'cert.pem').write_text(cert)
 tlsport=freeport();phpport=freeport();origin=f'https://127.0.0.1:{tlsport}';base=origin+'/nadin';issuer=origin+'/idp';codes={};mode='valid';calls=[]
 public=key.public_key().public_numbers();jwk={'kty':'RSA','kid':'test','alg':'RS256','use':'sig','n':b64(public.n.to_bytes(256,'big')),'e':b64(public.e.to_bytes(3,'big'))}
 cfg={'base_url':base,'oidc':{'enabled':True,'label':'Organisation OIDC','issuer':issuer,'client_id':'nadin','client_secret':'test-only-secret','ca_bundle':str(temp/'cert.pem'),'identities':{'subject-1':'member@example.invalid'}},'saml':{'enabled':True,'label':'Organisation SAML','entity_id':base+'/saml.php?metadata','idp_entity_id':issuer+'/saml','idp_sso_url':origin+'/idp/sso','idp_cert':cert,'sp_cert':cert,'sp_private_key':pem,'identities':{'subject-1':'member@example.invalid'}}}
 def savecfg():
  encoded=base64.b64encode(json.dumps(cfg).encode()).decode();(app/'userdatei/federation.php').write_text("<?php if(!defined('NADIN_FEDERATION_CONFIG')){http_response_code(404);exit;}return json_decode(base64_decode('"+encoded+"'),true);")
 savecfg();(app/'config.inc.php').write_text((app/'config.inc.php').read_text().replace("$salt='???'","$salt='fixture'"))
 users='Member,member@example.invalid,31.12.2099,vgigs=1,vsaxes=1,\n';(app/'userdatei/userdatei.txt').write_text(users)
 (temp/'router.php').write_text("<?php $_SERVER['HTTPS']='on';return false;")
 class Handler(http.server.BaseHTTPRequestHandler):
  def log_message(self,*args):pass
  def reply(self,data,status=200,headers=()):
   data=data if isinstance(data,bytes) else json.dumps(data).encode();self.send_response(status)
   for k,v in headers:self.send_header(k,v)
   self.send_header('Content-Length',str(len(data)));self.end_headers();self.wfile.write(data)
  def do_GET(self):self.route()
  def do_POST(self):self.route()
  def route(self):
   body=self.rfile.read(int(self.headers.get('Content-Length','0')));url=urllib.parse.urlsplit(self.path);calls.append((self.command,url.path))
   if url.path=='/idp/.well-known/openid-configuration':
    d={'issuer':issuer,'authorization_endpoint':origin+'/idp/auth','token_endpoint':origin+'/idp/token','jwks_uri':origin+'/idp/keys','response_types_supported':['code'],'code_challenge_methods_supported':['S256'],'id_token_signing_alg_values_supported':['RS256']}
    if mode=='discovery-mixup':d['issuer']='https://evil.example'
    return self.reply(d)
   if url.path=='/idp/keys':return self.reply({'keys':[jwk]})
   if url.path=='/idp/auth':
    q={k:v[0] for k,v in urllib.parse.parse_qs(url.query).items()};code=os.urandom(12).hex();codes[code]=q
    return self.reply(b'',302,[('Location',q['redirect_uri']+'?'+urllib.parse.urlencode({'code':code,'state':q['state'],'iss':issuer}))])
   if url.path=='/idp/token':
    q={k:v[0] for k,v in urllib.parse.parse_qs(body.decode()).items()};flow=codes.pop(q.get('code'),None)
    if not flow or self.headers.get('Authorization')!='Basic '+base64.b64encode(b'nadin:test-only-secret').decode() or b64(hashlib.sha256(q.get('code_verifier','').encode()).digest())!=flow['code_challenge'] or q.get('redirect_uri')!=base+'/oidc.php':return self.reply({'error':'invalid_grant'},400)
    claims={'iss':issuer,'aud':'nadin','sub':'subject-1','nonce':flow['nonce'],'iat':int(time.time()),'exp':int(time.time())+300}
    if mode=='wrong-nonce':claims['nonce']='wrong'
    if mode=='unknown-user':claims['sub']='unmapped'
    if mode=='admin-claims':claims.update(email='admin@example.invalid',roles=['musers'],groups=['Administrators'])
    head=b64(json.dumps({'alg':'RS256','kid':'test'}).encode());payload=b64(json.dumps(claims).encode());signed=(head+'.'+payload).encode();jwt=signed.decode()+'.'+b64(key.sign(signed,padding.PKCS1v15(),hashes.SHA256()))
    return self.reply({'id_token':jwt,'access_token':'never-stored','token_type':'Bearer'})
   if url.path.startswith('/idp/'):return self.reply(b'',404)
   conn=http.client.HTTPConnection('127.0.0.1',phpport,timeout=20);headers={k:v for k,v in self.headers.items() if k.lower() not in ('connection','content-length')};conn.request(self.command,self.path,body=body,headers=headers);r=conn.getresponse();data=r.read();self.reply(data,r.status,[(k,v) for k,v in r.getheaders() if k.lower() not in ('connection','content-length','transfer-encoding')]);conn.close()
 log=open(temp/'php.log','w+');process=subprocess.Popen([PHP,'-S',f'127.0.0.1:{phpport}','-t',str(web),str(temp/'router.php')],stdout=log,stderr=log)
 server=http.server.ThreadingHTTPServer(('127.0.0.1',tlsport),Handler);context=ssl.SSLContext(ssl.PROTOCOL_TLS_SERVER);context.load_cert_chain(str(temp/'cert.pem'),str(temp/'key.pem'));server.socket=context.wrap_socket(server.socket,server_side=True);thread=threading.Thread(target=server.serve_forever,daemon=True);thread.start()
 def session():
  s=requests.Session();s.trust_env=False;s.verify=str(temp/'cert.pem');return s
 try:
  for _ in range(100):
   try:
    if requests.get(f'http://127.0.0.1:{phpport}/nadin/login.php').status_code==200:break
   except requests.ConnectionError:pass
   time.sleep(.03)
  s=session();r=s.get(base+'/login.php');ok(len(BeautifulSoup(r.text,'html.parser').select('.nadin-federation-login'))==2,'two optional compact provider buttons')
  def start(s,provider='oidc'):
   r=s.get(base+'/login.php');d=hidden(r,'.nadin-federation-login');d['provider']=provider;return s.post(base+'/federation.php',data=d,allow_redirects=False)
  def oidc(s):
   r=start(s);ok(r.status_code==303,'OIDC start redirects');q=urllib.parse.parse_qs(urllib.parse.urlsplit(r.headers['Location']).query);ok(q['scope']==['openid'] and q['response_type']==['code'] and q['code_challenge_method']==['S256'],'code + minimum scope + PKCE S256');callback=s.get(r.headers['Location'],allow_redirects=False).headers['Location'];return callback,s.get(callback,allow_redirects=False)
  r=s.post(base+'/federation.php',data={'provider':'oidc'},allow_redirects=False);ok(r.status_code==403,'start requires CSRF')
  callback,r=oidc(s);ok(r.status_code==303 and r.headers['Location']=='index.php','OIDC completes over verified TLS');ok('member@example.invalid' in s.get(base+'/index.php').text,'external subject maps to existing member');ok(s.get(base+'/nadinmodule/admin-api.php?area=people').status_code==403,'external member has no admin rights')
  cookie=next(c for c in s.cookies if c.name.startswith('nadin_session_'));ok(cookie.secure and cookie.path=='/nadin/' and cookie.get_nonstandard_attr('SameSite')=='Lax','scoped secure opaque local session')
  ok(s.get(callback).status_code==400,'OIDC callback replay fails');r=s.get(base+'/logout.php',allow_redirects=False);ok('organisation_logout' in r.headers['Location'],'local logout explains separate IdP session');ok(s.get(base+'/password.php',allow_redirects=False).status_code==303,'logout revokes local session')
  for bad in ('wrong-nonce','unknown-user'):
   mode=bad;t=session();_,r=oidc(t);ok(r.status_code==400 and not any(c.name.startswith('nadin_session_') for c in t.cookies),'reject '+bad)
  mode='admin-claims';_,r=oidc(s);ok(r.status_code==303 and s.get(base+'/nadinmodule/admin-api.php?area=people').status_code==403,'IdP email/role claims cannot grant rights');mode='valid'
  cfg['oidc']['enabled']=False;savecfg();ok(s.get(base+'/password.php',allow_redirects=False).status_code==303,'disabling provider invalidates existing session');cfg['oidc']['enabled']=True;savecfg()
  a=session();r=start(a);callback=a.get(r.headers['Location'],allow_redirects=False).headers['Location'];ok(session().get(callback).status_code==400,'wrong browser cannot finish callback');ok(a.get(callback,allow_redirects=False).status_code==303,'legitimate browser can still finish')
  mode='discovery-mixup';ok(start(session()).status_code==503,'discovery issuer mismatch rejected');mode='valid'
  b=session();r=start(b,'saml');ok(r.status_code==303,'SAML start redirects');q=urllib.parse.parse_qs(urllib.parse.urlsplit(r.headers['Location']).query);ok('Signature' in q and q['SigAlg']==['http://www.w3.org/2001/04/xmldsig-more#rsa-sha256'],'SAML AuthnRequest signed with SHA256');request=ET.fromstring(zlib.decompress(base64.b64decode(q['SAMLRequest'][0]),-15));ok(request.attrib['AssertionConsumerServiceURL']==base+'/saml.php','fixed SAML callback')
  samlcookie=next(c for c in b.cookies if c.name.startswith('nadin_saml_'));ok(samlcookie.secure and samlcookie.get_nonstandard_attr('SameSite')=='None' and samlcookie.has_nonstandard_attr('HttpOnly'),'dedicated Secure SameSite=None browser binding')
  unsigned=urllib.parse.urlencode({'SAMLRequest':q['SAMLRequest'][0],'RelayState':q['RelayState'][0],'SigAlg':q['SigAlg'][0]},quote_via=urllib.parse.quote);key.public_key().verify(base64.b64decode(q['Signature'][0]),unsigned.encode(),padding.PKCS1v15(),hashes.SHA256());ok(True,'AuthnRequest redirect signature verified cryptographically')
  payload={'issuer':cfg['saml']['idp_entity_id'],'audience':cfg['saml']['entity_id'],'acs':base+'/saml.php','subject':'subject-1','request':request.attrib['ID'],'key':pem,'cert':cert};response=subprocess.run([PHP,str(app/'tests/saml-response.php')],input=json.dumps(payload),text=True,capture_output=True,check=True).stdout;post={'RelayState':q['RelayState'][0],'SAMLResponse':response}
  ok(session().post(base+'/saml.php',data=post).status_code==400,'SAML missing browser binding rejected');r=b.post(base+'/saml.php',data=post,allow_redirects=False);ok(r.status_code==303 and 'member@example.invalid' in b.get(base+'/index.php').text,'signed SAML POST creates scoped member session');ok(b.post(base+'/saml.php',data=post).status_code==400,'SAML assertion/callback replay rejected')
  r=b.get(base+'/saml.php?metadata');ok(r.status_code==200 and '<md:EntityDescriptor' in r.text and 'PRIVATE KEY' not in r.text and 'member@example.invalid' not in r.text,'public metadata omits mappings and secrets')
  cfg['saml']['identities']={};savecfg();ok(b.get(base+'/password.php',allow_redirects=False).status_code==303,'removing SAML mapping revokes session')
  local=session();r=local.get(base+'/login.php');d=hidden(r,'form[action="login2.php"]');d.update(u='member@example.invalid',p=hashlib.sha1(b'member@example.invalidfixture').hexdigest()[7:13]);r=local.post(base+'/login2.php',data=d,allow_redirects=False);ok(r.status_code==303,'initial-password onboarding remains available')
  (app/'userdatei/federation.php').write_text('<?php return false;');ok('member@example.invalid' in local.get(base+'/index.php').text,'broken optional configuration leaves local session usable');ok(not BeautifulSoup(local.get(base+'/login.php').text,'html.parser').select('.nadin-federation-login'),'broken configuration hides provider buttons')
  store=(app/'userdatei/auth-v11.php').read_text();ok('never-stored' not in store and 'test-only-secret' not in store and 'PRIVATE KEY' not in store,'session store has no provider keys or access tokens')
  log.flush();log.seek(0);errors=log.read();ok('Fatal error' not in errors and 'Parse error' not in errors,'no fatal or parse errors');print(f'PASS: {checks} TLS federation HTTP checks')
 finally:
  server.shutdown();server.server_close();process.terminate();process.wait(timeout=5);log.close()
