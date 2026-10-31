<?php
/** Read historical user rows consistently in authentication and administration. */
function nadinMemberLine($line) {
    $stamp=hash('sha256',implode(',',array_map('trim',explode(',',strtolower($line)))));
    $line=preg_replace('/^\xEF\xBB\xBF/','',$line);
    $fields=array_map(function($field){return trim((string)$field);},str_getcsv($line,',','"',''));
    if (count($fields)<3) return null;
    // Older lists also contain unquoted commas in names. Locate the account
    // by its single email field; never guess between two possible identities.
    $emails=array();
    foreach ($fields as $i=>$field) {
        if ($i===0) continue; // A display name may itself be an email address.
        $value=strtolower(str_replace(array(' ',"\t"),'',$field));
        if (filter_var($value,FILTER_VALIDATE_EMAIL)) $emails[$i]=$value;
    }
    if (count($emails)!==1) return null;
    $emailIndex=array_key_first($emails);
    if ($emailIndex<1 || !array_key_exists($emailIndex+1,$fields)) return null;
    $email=$emails[$emailIndex];
    $expiry=str_replace(array(' ',"\t"),'',$fields[$emailIndex+1]); $date=null;
    if (preg_match('/^([0-9]{1,2})\.([0-9]{1,2})\.([0-9]{4})$/D',$expiry,$m)) {
        if (checkdate((int)$m[2],(int)$m[1],(int)$m[3])) $date=DateTimeImmutable::createFromFormat('!j.n.Y',((int)$m[1]).'.'.((int)$m[2]).'.'.$m[3]);
    } elseif (preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/D',$expiry,$m)) {
        if (checkdate((int)$m[2],(int)$m[3],(int)$m[1])) $date=DateTimeImmutable::createFromFormat('!Y-m-d',$expiry);
    }
    $rights=array();
    foreach (array_slice($fields,$emailIndex+2) as $right) if (preg_match('/^([a-z]+)\s*=\s*1$/i',$right,$m)) $rights[strtolower($m[1])]=true;
    // Keep the existing stamp for recognised rows, preserving their current sessions.
    return array('email'=>$email,'name'=>implode(', ',array_slice($fields,0,$emailIndex)),'date'=>$date,'rights'=>$rights,'stamp'=>$stamp);
}

function nadinMemberNameField($name) {
    return strpbrk($name,',"')===false ? $name : '"'.str_replace('"','""',$name).'"';
}

function nadinMemberNeedsReview($line) {
    $line=trim(preg_replace('/^\xEF\xBB\xBF/','',$line));
    if ($line==='' || preg_match('~^(?:#|//|;)~',$line)) return false;
    return strpos($line,',')!==false && preg_match('/(?:^|,)\s*[a-z]+\s*=\s*[01]\s*(?:,|$)/i',$line)===1;
}
