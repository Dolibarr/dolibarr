<?php

$js_OpenPopupWindow = "function PopupPostalCode(postalcode,objectville)
{
  var url = 'searchpostalcode.php?cp=' + postalcode + '&targetobject=window.opener.document.formsoc.' + objectville.name;
  //  alert(url);
  var hWnd = window.open(url, \"SearchPostalCodeWindow\", \"width=\" + 300 + \",height=\" + 150 + \",resizable=yes,scrollbars=yes\");
  if((document.window != null) && (!hWnd.opener))
     hWnd.opener = document.window;
}
";

print '<script language="javascript">'."\n";
print $js_OpenPopupWindow;
print "\n</script>\n";

?>
