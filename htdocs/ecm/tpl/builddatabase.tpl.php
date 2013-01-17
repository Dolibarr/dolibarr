<?php
/* Copyright (C) 2012	Regis Houssin	<regis.houssin@capnetworks.com>
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
 *
 */

$openeddir='/';

// TODO: just use ajaxdirtree.php for load database after ajax refresh and not scan directories
// too slow every page loaded !

?>

<!-- BEGIN PHP TEMPLATE FOR JQUERY -->
<script type="text/javascript">
$(document).ready( function() {
	$('#filetree').fileTree({ root: '<?php print dol_escape_js($openeddir); ?>',
			// Called if we click on a file (not a dir)
			script: '<?php echo DOL_URL_ROOT.'/core/ajax/ajaxdirtree.php?modulepart=ecm&openeddir='.urlencode($openeddir); ?>',
			folderEvent: 'click',
			multiFolder: false  },
			// Called if we click on a file (not a dir)
		function(file) {
			$("#mesg").hide();
			loadandshowpreview(file,0);
		}
	);

	$('#refreshbutton').click( function() {
		ecmBuildDatabase();
	});
});

function loadandshowpreview(filedirname,section)
{
	//alert('filedirname='+filedirname);
	$('#ecmfileview').empty();

	var url = '<?php echo dol_buildpath('/core/ajax/ajaxdirpreview.php',1); ?>?action=preview&module=ecm&section='+section+'&file='+urlencode(filedirname);
	$.get(url, function(data) {
		//alert('Load of url '+url+' was performed : '+data);
		pos=data.indexOf("TYPE=directory",0);
		//alert(pos);
		if ((pos > 0) && (pos < 20))
		{
			filediractive=filedirname;    // Save current dirname
			filetypeactive='directory';
		}
		else
		{
			filediractive=filedirname;    // Save current dirname
			filetypeactive='file';
		}
		$('#ecmfileview').append(data);
	});
}

ecmBuildDatabase = function() {
	$.pleaseBePatient("<?php echo $langs->trans('PleaseBePatient'); ?>");
	$.getJSON( "<?php echo DOL_URL_ROOT . '/ecm/ajax/ecmdatabase.php'; ?>", {
		action: "build",
		element: "ecm"
	},
	function(response) {
		$.unblockUI();
		location.href="<?php echo $_SERVER['PHP_SELF']; ?>";
	});
};
</script>
<!-- END PHP TEMPLATE FOR JQUERY -->