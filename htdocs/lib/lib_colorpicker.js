StringBuilder = function()
{
 this.arrStr = new Array();
 this.Append = function( inVAL )
 {
  this.arrStr[this.arrStr.length] = inVAL;
 }
 this.toString = function()
 {
  return this.arrStr.join('');
 }
 this.Init = function()
 {
  this.arrStr = null;
  this.arrStr = new Array();
 }
}

var objSB = new StringBuilder();

var arrGray = new Array('0','1','2','3','4','5','6','7','8','9','A','B','C','D','E','F');
var arrSafe = new Array('00','33','66','99','CC','FF');
var arrSys = [['D4D0C8', 'ActiveBorder'],['0A246A', 'ActiveCaption'],['808080', 'AppWorkspace'],['3A6EA5', 'Background'],['D4D0C8', 'ButtonFace'],['FFFFFF', 'ButtonHighlight'],['808080', 'ButtonShadow'],['000000', 'ButtonText'],['FFFFFF', 'CaptionText'],['808080', 'GrayText'],['0A246A', 'Highlight'],['FFFFFF', 'HighlightText'],['D4D0C8', 'InactiveBorder'],['0A246A', 'InactiveCaption'],['D4D0C8', 'InactiveCaptionText'],['FFFFE1', 'InfoBackground'],['000000', 'InfoText'],['D4D0C8', 'Menu'],['000000', 'MenuText'],['D4D0C8', 'Scrollbar'],['404040', 'ThreedDarkShadow'],['D4D0C8', 'ThreedFace'],['FFFFFF', 'ThreedHighlight'],['D4D0C8', 'ThreedLightShadow'],['808080', 'ThreedShadow'],['FFFFFF', 'Window'],['000000', 'WindowFrame'],['000000', 'WindowText']];
var arrName = [['FF0000', 'red'],['FFFF00', 'yellow'],['00FF00', 'lime'],['00FFFF', 'cyan'],['0000FF', 'blue'],['FF00FF', 'magenta'],['FFFFFF', 'white'],['F5F5F5', 'whitesmoke'],['DCDCDC', 'gainsboro'],['D3D3D3', 'lightgrey'],['C0C0C0', 'silver'],['A9A9A9', 'darkgray'],['808080', 'gray'],['696969', 'dimgray'],['000000', 'black'],['2F4F4F', 'darkslategray'],['708090', 'slategray'],['778899', 'lightslategray'],['4682B4', 'steelblue'],['4169E1', 'royalblue'],['6495ED', 'cornflowerblue'],['B0C4DE', 'lightsteelblue'],['7B68EE', 'mediumslateblue'],['6A5ACD', 'slateblue'],['483D8B', 'darkslateblue'],['191970', 'midnightblue'],['000080', 'navy'],['00008B', 'darkblue'],['0000CD', 'mediumblue'],['1E90FF', 'dodgerblue'],['00BFFF', 'deepskyblue'],['87CEFA', 'lightskyblue'],['87CEEB', 'skyblue'],['ADD8E6', 'lightblue'],['B0E0E6', 'powderblue'],['F0FFFF', 'azure'],['E0FFFF', 'lightcyan'],['AFEEEE', 'paleturquoise'],['48D1CC', 'mediumturquoise'],['20B2AA', 'lightseagreen'],['008B8B', 'darkcyan'],['008080', 'teal'],['5F9EA0', 'cadetblue'],['00CED1', 'darkturquoise'],['00FFFF', 'aqua'],['40E0D0', 'turquoise'],['7FFFD4', 'aquamarine'],['66CDAA', 'mediumaquamarine'],['8FBC8F', 'darkseagreen'],['3CB371', 'mediumseagreen'],['2E8B57', 'seagreen'],['006400', 'darkgreen'],['008000', 'green'],['228B22', 'forestgreen'],['32CD32', 'limegreen'],['00FF00', 'lime'],['7FFF00', 'chartreuse'],['7CFC00', 'lawngreen'],['ADFF2F', 'greenyellow'],['98FB98', 'palegreen'],['90EE90', 'lightgreen'],['00FF7F', 'springgreen'],['00FA9A', 'mediumspringgreen'],['556B2F', 'darkolivegreen'],['6B8E23', 'olivedrab'],['808000', 'olive'],['BDB76B', 'darkkhaki'],['B8860B', 'darkgoldenrod'],['DAA520', 'goldenrod'],['FFD700', 'gold'],['F0E68C', 'khaki'],['EEE8AA', 'palegoldenrod'],['FFEBCD', 'blanchedalmond'],['FFE4B5', 'moccasin'],['F5DEB3', 'wheat'],['FFDEAD', 'navajowhite'],['DEB887', 'burlywood'],['D2B48C', 'tan'],['BC8F8F', 'rosybrown'],['A0522D', 'sienna'],['8B4513', 'saddlebrown'],['D2691E', 'chocolate'],['CD853F', 'peru'],['F4A460', 'sandybrown'],['8B0000', 'darkred'],['800000', 'maroon'],['A52A2A', 'brown'],['B22222', 'firebrick'],['CD5C5C', 'indianred'],['F08080', 'lightcoral'],['FA8072', 'salmon'],['E9967A', 'darksalmon'],['FFA07A', 'lightsalmon'],['FF7F50', 'coral'],['FF6347', 'tomato'],['FF8C00', 'darkorange'],['FFA500', 'orange'],['FF4500', 'orangered'],['DC143C', 'crimson'],['FF0000', 'red'],['FF1493', 'deeppink'],['FF00FF', 'fuchsia'],['FF69B4', 'hotpink'],['FFB6C1', 'lightpink'],['FFC0CB', 'pink'],['DB7093', 'palevioletred'],['C71585', 'mediumvioletred'],['800080', 'purple'],['8B008B', 'darkmagenta'],['9370DB', 'mediumpurple'],['8A2BE2', 'blueviolet'],['4B0082', 'indigo'],['9400D3', 'darkviolet'],['9932CC', 'darkorchid'],['BA55D3', 'mediumorchid'],['DA70D6', 'orchid'],['EE82EE', 'violet'],['DDA0DD', 'plum'],['D8BFD8', 'thistle'],['E6E6FA', 'lavender'],['F8F8FF', 'ghostwhite'],['F0F8FF', 'aliceblue'],['F5FFFA', 'mintcream'],['F0FFF0', 'honeydew'],['FAFAD2', 'lightgoldenrodyellow'],['FFFACD', 'lemonchiffon'],['FFF8DC', 'cornsilk'],['FFFFE0', 'lightyellow'],['FFFFF0', 'ivory'],['FFFAF0', 'floralwhite'],['FAF0E6', 'linen'],['FDF5E6', 'oldlace'],['FAEBD7', 'antiquewhite'],['FFE4C4', 'bisque'],['FFDAB9', 'peachpuff'],['FFEFD5', 'papayawhip'],['FFF5EE', 'seashell'],['FFF0F5', 'lavenderblush'],['FFE4E1', 'mistyrose'],['FFFAFA', 'snow']];

var intTdDisp = intTblDisp = 0;
var i = j = k = 0;
var objCurrent = objGray = objSafe = objSys = objName = objLegend = objPreview = objSelected = objPreviewTxt = objSelectedTxt = objGlobal = null;
var strColor = '', strColorTxt = '', strCurrent = '';

fctTblFeed = function()
{
 if (intTdDisp != 16) {
  for (i = intTdDisp; i < 16; i++) {
   objSB.Append('<td class="tdColor"><a class="none" href="#">&nbsp;</a></td>');
   intTblDisp++;
  }
 }
 if (intTblDisp != 256) {
  for (i = intTblDisp; i < 256; i++) {
   if (i % 16 == 0) {objSB.Append('</tr><tr>');}
   objSB.Append('<td class="tdColor"><a class="none" href="#">&nbsp;</a></td>');
  }
 }
}

fctIsInSys = function(strColor)
{
 var strOut = '';
 for (ii = 0; ii < arrSys.length; ii++) {
  if (arrSys[ii][0] == strColor) {strOut = arrSys[ii][1]; break;}
 }
 return strOut;
}

fctIsInName = function(strColor)
{
 var strOut = '';
 for (ii = 0; ii < arrName.length; ii++) {
  if (arrName[ii][0] == strColor) {strOut = arrName[ii][1]; break;}
 }
 return strOut;
}

fctOver = function(strColor, strTxt)
{
 objPreview.style.backgroundColor = strColor;
 objPreviewTxt.innerHTML = strColor + '<br>' + strTxt;
}

fctOut = function()
{
 objPreview.style.backgroundColor = '';
 objPreviewTxt.innerHTML = '';
}

fctSetColor = function(strColor, strTxt)
{
 strCurrent = strColor;
 objSelected.style.backgroundColor = strColor;
 objSelectedTxt.innerHTML = strColor + '<br>' + strTxt;
}

fctSelect = function(strArr, strTxt)
{
 objLegend.innerHTML = '&nbsp;' + strTxt + '&nbsp;';
 objGray.style.display = (strArr == 'Gray') ? 'block' : 'none';
 objSafe.style.display = (strArr == 'Safe') ? 'block' : 'none';
 objSys.style.display = (strArr == 'Sys') ? 'block' : 'none';
 objName.style.display = (strArr == 'Name') ? 'block' : 'none';
}

fctHide = function()
{
 fctReset();
 objGlobal.style.display = 'none';
 objCurrent = null;
}

fctReset = function()
{
 objSelected.style.backgroundColor = '';
 objSelectedTxt.innerHTML = '';
 strCurrent = '';
}

fctOk = function()
{
 objCurrent.value = strCurrent.toUpperCase();
 fctHide();
}

fctShow = function(objForm)
{
 if (objForm) {
  objCurrent = objForm;
  if (objForm.value + '' != '') {
   strColor = objForm.value.replace('#', '');
   strColorTxt = '' + fctIsInName(strColor);
   if (strColorTxt == '') {strColorTxt = '' + fctIsInSys(strColor);}
   fctSetColor('#' + strColor, strColorTxt)
  } else {
   fctReset();
  }
  fctSelect('Name', 'Named');
 }
 if (objCurrent) {
  var w = h = t = l = 0;
  if (self.innerHeight) {
   w = self.innerWidth;
   h = self.innerHeight;
  } else if (document.documentElement && document.documentElement.clientHeight) {
   w = document.documentElement.clientWidth;
   h = document.documentElement.clientHeight;
  } else if (document.body) {
   w = document.body.clientWidth;
   h = document.body.clientHeight;
  }
  if (self.pageYOffset) {
   l = self.pageXOffset;
   t = self.pageYOffset;
  } else if (document.documentElement && document.documentElement.scrollTop) {
   l = document.documentElement.scrollLeft;
   t = document.documentElement.scrollTop;
  } else if (document.body) {
   l = document.body.scrollLeft;
   t = document.body.scrollTop;
  }
  if (objGlobal.style.display != 'block') {objGlobal.style.display = 'block';}
  objGlobal.style.top = parseInt(((h - objGlobal.offsetHeight) / 2) + t, 10) + 'px';
  objGlobal.style.left = parseInt(((w - objGlobal.offsetWidth) / 2) + l, 10) + 'px';
 }
}

fctLoad = function()
{
 var objDiv = document.createElement('DIV');
 objDiv.id = 'objCP';
 objDiv.style.display = 'inline';
 document.body.appendChild(objDiv);
 objDiv.innerHTML = objSB.toString();
 objPreview = document.getElementById('objPreview');
 objSelected = document.getElementById('objSelected');
 objPreviewTxt = document.getElementById('objPreviewTxt');
 objSelectedTxt = document.getElementById('objSelectedTxt');
 objGlobal = document.getElementById('tblGlobal');
 objGray = document.getElementById('tblGray');
 objSafe = document.getElementById('tblSafe');
 objSys = document.getElementById('tblSys');
 objName = document.getElementById('tblName');
 objLegend = document.getElementById('objLegend');
 fctSelect('Name', 'Named');
}

objSB.Append('<table id="tblGlobal" class="tblGlobal" border="0" cellpadding="0" cellspacing="0"><tr><td class="tdContainer"><table border="0" cellpadding="0" cellspacing="0" width="100%"><tr>');
objSB.Append('<td width="24%" align="center"><input type="button" value="Named" class="btnPalette" onClick="fctSelect(\'Name\', \'Named\');"></td>');
objSB.Append('<td width="23%" align="center"><input type="button" value="Safety" class="btnPalette" onClick="fctSelect(\'Safe\', \'Safety\');"></td>');
objSB.Append('<td width="23%" align="center"><input type="button" value="System" class="btnPalette" onClick="fctSelect(\'Sys\', \'System\');"></td>');
objSB.Append('<td width="30%" align="center"><input type="button" value="Grayscale" class="btnPalette" onClick="fctSelect(\'Gray\', \'Grayscale\');"></td>');
objSB.Append('</tr></table></td></tr><tr><td class="tdContainer"><fieldset><legend align="top" id="objLegend"></legend><table id="tblContainer" class="tblContainer" border="0" cellpadding="0" cellspacing="0"><tr><td class="tdContainer">');

objSB.Append('<table id="tblGray" class="tblColor" border="0" cellpadding="0" cellspacing="0"><tr>');
for (i = 0; i < arrGray.length; i++) {
 for (j = 0; j < arrGray.length; j++) {
  strColor = '' + arrGray[i] + arrGray[j] + arrGray[i] + arrGray[j] + arrGray[i] + arrGray[j];
  strColorTxt = '' + fctIsInName(strColor);
  if (strColorTxt == '') {strColorTxt = '' + fctIsInSys(strColor);}
  objSB.Append('<td class="tdColor"><a class="color" href="javascript:fctSetColor(\'#' + strColor + '\', \'' + strColorTxt + '\');" style="background-color:#' + strColor + ';" onMouseOver="fctOver(\'#' + strColor + '\', \'' + strColorTxt + '\');" onMouseOut="fctOut();">&nbsp;</a></td>');
  intTdDisp++;
  intTblDisp++;
 }
 if (i < arrGray.length - 1) {
  objSB.Append('</tr><tr>');
  intTdDisp = 0;
 }
}
fctTblFeed();
objSB.Append('</tr></table>');
intTdDisp = intTblDisp = 0;

objSB.Append('<table id="tblSafe" class="tblColor" border="0" cellpadding="0" cellspacing="0"><tr>');
for (i = 0; i < arrSafe.length; i++) {
 for (j = 0; j < arrSafe.length; j++) {
  for (k = 0; k < arrSafe.length; k++) {
   if (intTblDisp % 16 == 0 && intTdDisp != 0) {
    objSB.Append('</tr><tr>');
    intTdDisp = 0;
   }
   strColor = '' + arrSafe[i] + arrSafe[j] + arrSafe[k];
   strColorTxt = '' + fctIsInName(strColor);
   if (strColorTxt == '') {strColorTxt = '' + fctIsInSys(strColor);}
   objSB.Append('<td class="tdColor"><a class="color" href="javascript:fctSetColor(\'#' + strColor + '\', \'' + strColorTxt + '\');" style="background-color:#' + strColor + ';" onMouseOver="fctOver(\'#' + strColor + '\', \'' + strColorTxt + '\');" onMouseOut="fctOut();">&nbsp;</a></td>');
   intTdDisp++;
   intTblDisp++;
  }
 }
}
fctTblFeed();
objSB.Append('</tr></table>');
intTdDisp = intTblDisp = 0;

objSB.Append('<table id="tblSys" class="tblColor" border="0" cellpadding="0" cellspacing="0"><tr>');
for (i = 0; i < arrSys.length; i++) {
 if (intTblDisp % 16 == 0 && intTdDisp != 0) {
  objSB.Append('</tr><tr>');
  intTdDisp = 0;
 }
 strColor = '' + arrSys[i][0];
 strColorTxt = '' + arrSys[i][1];
 objSB.Append('<td class="tdColor"><a class="color" href="javascript:fctSetColor(\'#' + strColor + '\', \'' + strColorTxt + '\');" style="background-color:#' + strColor + ';" onMouseOver="fctOver(\'#' + strColor + '\', \'' + strColorTxt + '\');" onMouseOut="fctOut();">&nbsp;</a></td>');
 intTdDisp++;
 intTblDisp++;
}
fctTblFeed();
objSB.Append('</tr></table>');
intTdDisp = intTblDisp = 0;

objSB.Append('<table id="tblName" class="tblColor" border="0" cellpadding="0" cellspacing="0"><tr>');
for (i = 0; i < arrName.length; i++) {
 if (intTblDisp % 16 == 0 && intTdDisp != 0) {
  objSB.Append('</tr><tr>');
  intTdDisp = 0;
 }
 strColor = '' + arrName[i][0];
 strColorTxt = '' + arrName[i][1];
 objSB.Append('<td class="tdColor"><a class="color" href="javascript:fctSetColor(\'#' + strColor + '\', \'' + strColorTxt + '\');" style="background-color:#' + strColor + ';" onMouseOver="fctOver(\'#' + strColor + '\', \'' + strColorTxt + '\');" onMouseOut="fctOut();">&nbsp;</a></td>');
 intTdDisp++;
 intTblDisp++;
}
fctTblFeed();
objSB.Append('</tr></table></td></tr></table></fieldset></td></tr><tr><td class="tdContainer">');
objSB.Append('<table border="0" cellpadding="0" cellspacing="0" width="100%">');
objSB.Append('<tr><td class="tdDisplay" id="objPreview">&nbsp;</td><td class="tdDisplay" id="objSelected">&nbsp;</td></tr>');
objSB.Append('<tr><td class="tdDisplayTxt" id="objPreviewTxt" valign="top">&nbsp;</td><td class="tdDisplayTxt" id="objSelectedTxt" valign="top">&nbsp;</td></tr>');
objSB.Append('</table></td></tr><tr><td class="tdContainer">');
objSB.Append('<table border="0" cellpadding="0" cellspacing="0" width="100%"><tr>');
objSB.Append('<td width="33%" align="center"><input type="button" value="Cancel" class="btnColor" onClick="fctHide();"></td>');
objSB.Append('<td width="34%" align="center"><input type="button" value="Reset" class="btnColor" onClick="fctReset();"></td>');
objSB.Append('<td width="33%" align="center"><input type="button" value="Ok" class="btnColor" onClick="fctOk();"></td>');
objSB.Append('</tr></table></td></tr></table>');