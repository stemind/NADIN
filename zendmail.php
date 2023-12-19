<?php 
if($usesmtp=='yes') $transport = new Zend_Mail_Transport_Smtp($smtpservername, $mysmtpconfigdata);
$mail = new Zend_Mail();
$mail->setFrom($zendfrom, $zendname);
$mail->addTo($zendto);
$mail->setSubject($zendsubject);
//$mail->addHeader('Reply-to', '<'.$zendfrom.'>');
$mail->setReplyTo($zendfrom);

if (strpos($zendmessage,'&#')>0) $mail->setBodyHtml(nl2br(str_replace('<','&lt;',str_replace('>','&gt;',$zendmessage))));
else $mail->setBodyText($zendmessage);

if($usesmtp=='yes') $mail->send($transport);
else $mail->send();
?>