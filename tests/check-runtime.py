"""Run native PHP or --simulate-php9 checks; requires Python requests/bs4/cryptography and Node jsdom."""
import argparse, os, shlex, shutil, subprocess, sys, tempfile
from pathlib import Path
ROOT=Path(__file__).resolve().parents[1]
parser=argparse.ArgumentParser(description=__doc__)
parser.add_argument('--php',default=os.environ.get('NADIN_PHP','php'))
parser.add_argument('--simulate-php9',action='store_true')
args=parser.parse_args();php=shutil.which(args.php)
if not php:raise SystemExit('PHP executable not found: '+args.php)
def run(cmd,**kwargs):
 subprocess.run(cmd,cwd=ROOT,check=True,**kwargs)
with tempfile.TemporaryDirectory(prefix='nadin-runtime-') as temp:
 temp=Path(temp);wrapper=temp/'php';options=['-d','opcache.enable=0','-d','opcache.enable_cli=0']
 if args.simulate_php9:
  options+=['-d','auto_prepend_file='+str(ROOT/'tests/runtime-guard.php'),'-d','disable_functions=utf8_encode,utf8_decode,curl_close,imagedestroy,get_magic_quotes_runtime,set_magic_quotes_runtime']
 wrapper.write_text('#!/bin/sh\nexec '+shlex.join([php,*options])+' "$@"\n');wrapper.chmod(0o700)
 env=dict(os.environ,NADIN_PHP=str(wrapper))
 run([str(wrapper),'-v'],env=env)
 sources=[p for p in ROOT.rglob('*.php') if not any(x in p.relative_to(ROOT).parts for x in ['vendor','.git'])]
 for path in sources:run([str(wrapper),'-l',str(path)],env=env,stdout=subprocess.DEVNULL)
 print(f'PASS: {len(sources)} PHP syntax checks',flush=True)
 tests=['encoding.php','encoding-http.py','auth.php','bruteforce.php','auth-http.py','admin-http.py','shared-data-http.py','member-format.php','legacy-users.php','forscore.php','federation.php','federation-http.py','zip.php','v16-http.py','review-http.py','php-compat.php','php-compat-http.py']
 for name in tests:run([str(wrapper) if name.endswith('.php') else sys.executable,'tests/'+name],env=env)
 preview=temp/'preview';run([sys.executable,'tests/render-preview.py',str(preview)],env=env)
 run(['node','tests/admin-dom.cjs',str(preview/'views')],env=env);run(['node','tests/login-dom.cjs'],env=env)
 print('PASS: complete runtime suite ('+('PHP 9 simulation; not a PHP 9 runtime' if args.simulate_php9 else 'native PHP')+')',flush=True)
