"""Run actual login, administration, media and download flows with shared directory links."""
import os,subprocess,sys
from pathlib import Path
root=Path(__file__).resolve().parents[1]
subprocess.run([sys.executable,str(root/'tests/admin-http.py')],cwd=root,env=dict(os.environ,NADIN_TEST_SHARED_DATA='1'),check=True)
print('PASS: shared user/library/gig/ZIP directory HTTP scenario')
