<?php
/* Copyright (C) 2018	Andreu Bisquerra	<jove@bisquerra.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

//if (! defined('NOREQUIREUSER'))	define('NOREQUIREUSER','1');	// Not disabled cause need to load personalized language
//if (! defined('NOREQUIREDB'))		define('NOREQUIREDB','1');		// Not disabled cause need to load personalized language
//if (! defined('NOREQUIRESOC'))		define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN'))		define('NOREQUIRETRAN','1');
if (! defined('NOCSRFCHECK'))		define('NOCSRFCHECK','1');
if (! defined('NOTOKENRENEWAL'))	define('NOTOKENRENEWAL','1');
if (! defined('NOREQUIREMENU'))		define('NOREQUIREMENU','1');
if (! defined('NOREQUIREHTML'))		define('NOREQUIREHTML','1');
if (! defined('NOREQUIREAJAX'))		define('NOREQUIREAJAX','1');

require '../main.inc.php';	// Load $user and permissions

$langs->loadLangs(array("bills", "cashdesk"));

$place = GETPOST('place','int');


/*
 * View
 */

top_htmlhead($head, $title, $disablejs, $disablehead, $arrayofjs, $arrayofcss);

?>
<script>
function Save(){
	$.get( "invoice.php", { action: "freezone", place: "<?php echo $place;?>", desc:$('#desc').val(), number:$('#price').val()} );
	parent.$.colorbox.close();
}
</script>
</head>
<body>
<br>
<center>
<input type="text" id="desc" name="desc" style="width:40%;font-size: 200%;" placeholder="<?php echo $langs->trans('Description');?>">
<input type="text" id="price" name="price" style="width:15%;font-size: 200%;" placeholder="<?php echo $langs->trans('Price');?>">
<input type="hidden" name="place" value="<?php echo $place;?>">
<input type="button" style="width:15%;font-size: 200%;" value="OK" onclick="Save();">
</center>

</body>
</html>