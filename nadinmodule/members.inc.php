<?php
/** Read historical user rows consistently in authentication and administration. */
function nadinMemberLine($line) {
    $stamp=hash('sha256',implode(',',array_map('trim',explode(',',strtolower($line)))));
    $line=preg_replace('/^\xEF\xBB\xBF/','',$line);
    $fields=array_map('trim',explode(',',$line));
    if (count($fields)<3) return null;
    $email=strtolower(str_replace(array(' ',"\t"),'',$fields[1]));
    if (!filter_var($email,FILTER_VALIDATE_EMAIL)) return null; // Comments and installation instructions are not accounts.
    $expiry=str_replace(array(' ',"\t"),'',$fields[2]); $date=null;
    if (preg_match('/^([0-9]{1,2})\.([0-9]{1,2})\.([0-9]{4})$/D',$expiry,$m)) {
        if (checkdate((int)$m[2],(int)$m[1],(int)$m[3])) $date=DateTimeImmutable::createFromFormat('!j.n.Y',((int)$m[1]).'.'.((int)$m[2]).'.'.$m[3]);
    } elseif (preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/D',$expiry,$m)) {
        if (checkdate((int)$m[2],(int)$m[3],(int)$m[1])) $date=DateTimeImmutable::createFromFormat('!Y-m-d',$expiry);
    }
    $rights=array();
    foreach (array_slice($fields,3) as $right) if (preg_match('/^([a-z]+)\s*=\s*1$/i',$right,$m)) $rights[strtolower($m[1])]=true;
    // Keep the existing stamp for recognised rows, preserving their current sessions.
    return array('email'=>$email,'name'=>$fields[0],'date'=>$date,'rights'=>$rights,'stamp'=>$stamp);
}
