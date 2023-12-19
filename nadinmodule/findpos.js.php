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

<?php 
if ($_COOKIE['scrlt']!='') echo "
if (document.getElementById('".$_COOKIE['scrlt']."')) {
setTimeout(\"window.scroll(0,findPos(document.getElementById('".$_COOKIE['scrlt']."')))\",777);
document.getElementById('".$_COOKIE['scrlt']."').style.background='yellow';
}
";
?>

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
if (checkIE()>-1) history.go(0);else location.reload();
}

function openerhilite(that) {
if (document.getElementById(that)) document.getElementById(that).style.background='yellow';
oldthat=that;
setTimeout("if (document.getElementById(oldthat)) document.getElementById(oldthat).style.background='#e0ffd6'",770);
}
</script>
<span id="loadmarker"></span>
