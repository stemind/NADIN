<?php
   for ($i=0;$i<count($tunes);$i++) {
 
      $tunes[$i]=trim($tunes[$i]);

      $path='library/'.$tunes[$i];
      
      if ($handle = @opendir($path))  { 
         while (false !== ($file = readdir($handle)))  { 


if ($tunes[$i]!=mkascii($tunes[$i])) { 
rename('library/'.$tunes[$i],'library/'.mkascii($tunes[$i]));  
echo '<script>location.replace(location.href)</script>';
}


if ($file!=mkascii($file)) {
rename('library/'.$tunes[$i].'/'.$file,'library/'.$tunes[$i].'/'.mkascii($file));
echo '<script>location.replace(location.href)</script>';
}    

}
} 
}
?>