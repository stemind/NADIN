<?php
/** A notes revision identifies both its tune directory and its current bytes. */
function nadin_media_notes_revision($path) {
    clearstatcache(true);
    $directory=@stat(dirname($path));
    $identity=$directory ? $directory['dev'].':'.$directory['ino'] : 'missing';
    $contents=is_file($path) ? @hash_file('sha256',$path) : hash('sha256','');
    if ($contents===false) throw new RuntimeException('The tune notes could not be read. Reload the editor before saving.');
    return hash('sha256',$identity."\0".$contents);
}
