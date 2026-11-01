<script>
function findPos(obj) {
    var curtop = 0;
    if (obj.offsetParent) {
        do {
            curtop += obj.offsetTop;
        } while (obj = obj.offsetParent);
    return (curtop-200);
    }
}

<?php $scrollTarget=$_COOKIE['scrlt'] ?? '';
if (is_string($scrollTarget) && $scrollTarget!=='' && strlen($scrollTarget)<=256) { ?>
(function(){
 var target=document.getElementById(<?php echo json_encode($scrollTarget,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_INVALID_UTF8_SUBSTITUTE); ?>);
 if(target){setTimeout(function(){if(!window.NadinTask||!NadinTask.restored)window.scroll(0,findPos(target));},777);target.style.background='yellow';}
})();
<?php } ?>

function checkIE() 
// Returns the version of Internet Explorer or a -1
// (indicating the use of another browser).
{
  var rv = -1; // Return value assumes failure.
  if (navigator.appName == 'Microsoft Internet Explorer')
  {
    var ua = navigator.userAgent;
    var re  = new RegExp("MSIE ([0-9]{1,}[\.0-9]{0,})");
    if (re.exec(ua) != null)
      rv = parseFloat( RegExp.$1 );
  }
  return rv;
}

function fresh() {
if(document.getElementById('changes')&&document.getElementById('changes').value==='1'){var status=document.getElementById('admin-status');if(status)status.textContent='Library changed — keep this draft open; save or copy it before reloading.';return;}
if(window.NadinTask&&NadinTask.rememberView)NadinTask.rememberView();
location.reload();
}

function openerhilite(that) {
if (document.getElementById(that)) document.getElementById(that).style.background='yellow';
oldthat=that;
setTimeout("if (document.getElementById(oldthat)) document.getElementById(oldthat).style.background='#e0ffd6'",770);
}
</script>
<span id="loadmarker"></span>
