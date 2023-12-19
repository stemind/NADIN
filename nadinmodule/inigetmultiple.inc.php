<?php
function let_to_num($v){ //This function transforms the php.ini notation for numbers (like '2M') to an integer (2*1024*1024 in this case)
    $l = substr($v, -1);
    $ret = substr($v, 0, -1);
    switch(strtoupper($l)){
    case 'P':
        $ret *= 1024;
    case 'T':
        $ret *= 1024;
    case 'G':
        $ret *= 1024;
    case 'M':
        $ret *= 1024;
    case 'K':
        $ret *= 1024;
        break;
    }
    return $ret;
}
$max_upload_size = min(let_to_num(ini_get('post_max_size')), let_to_num(ini_get('upload_max_filesize')));
$post_max_size = let_to_num(ini_get('post_max_size'));
?>
Multiple files at once possible: Max. <?php echo (ini_get('max_file_uploads')-1) ?> files with a total of <?php echo floor($post_max_size/1024/1024).' MB. Max. size per file: '.floor($max_upload_size/1024/1024).' MB. Too little? Use FTP or change php.ini.';
?>