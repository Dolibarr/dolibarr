<?php
/* Copyright (C) 2012	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2018	Laurent Destailleur 	<eldy@users.sourceforge.net>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * Output javascript for interactions code of ecm module
 */

// Protection to avoid direct call of template
if (empty($conf) || !is_object($conf))
{
	print "Error, template enablefiletreeajax.tpl.php can't be called as URL";
	exit;
}

?>

<!-- BEGIN PHP TEMPLATE ecm/tpl/enablefiletreeajax.tpl.php -->
<!-- Doc of fileTree plugin at https://www.abeautifulsite.net/jquery-file-tree -->

<script type="text/javascript">

<?php
if (empty($module)) $module = 'ecm';
$paramwithoutsection = preg_replace('/&?section=(\d+)/', '', $param);

$openeddir = '/'; // The root directory shown
// $preopened		// The dir to have preopened

?>

$(document).ready(function() {

	$('#filetree').fileTree({
		root: '<?php print dol_escape_js($openeddir); ?>',
		// Ajax called if we click to expand a dir (not a file). Parameter 'dir' is provided as a POST parameter by fileTree code to this following URL.
		// We must use token=$_SESSION['token'] and not token=$_SESSION['newtoken'] here because ajaxdirtree has NOTOKENRENEWAL define so there is no rollup of token so we must compare with the one valid on main page
		script: '<?php echo DOL_URL_ROOT.'/core/ajax/ajaxdirtree.php?token='.urlencode($_SESSION['token']).'&modulepart='.urlencode($module).(empty($preopened) ? '' : '&preopened='.urlencode($preopened)).'&openeddir='.urlencode($openeddir).(empty($paramwithoutsection) ? '' : $paramwithoutsection); ?>',
		folderEvent: 'click',	// 'dblclick'
		multiFolder: false  },
		// Called if we click on a file (not a dir)
		function(file) {
			console.log("We click on a file");
			$("#mesg").hide();
			loadandshowpreview(file,0);
		},
		// Called if we click on a dir (not a file)
		function(elem) {
			id=elem.attr('id').substr(12);	// We get id that is 'fmdirlia_id_xxx' (id we want is xxx)
			rel=elem.attr('rel')
			console.log("We click on a dir, we call the ajaxdirtree.php with modulepart=<?php echo $module; ?>, param=<?php echo $paramwithoutsection; ?>");
			console.log("We also save dir name or id into <?php echo $nameforformuserfile ?>_section_... with name section_... id="+id+" rel="+rel);
			jQuery("#<?php echo $nameforformuserfile ?>_section_dir").val(rel);
			jQuery("#<?php echo $nameforformuserfile ?>_section_id").val(id);
			jQuery("#section_dir").val(rel);
			jQuery("#section_id").val(id);
			jQuery("#section").val(id);
			jQuery('#<?php echo $nameforformuserfile ?>').show();
			console.log("We also execute the loadandshowpreview() that is on the onclick of each li defined by ajaxdirtree");
		}
		// The loadanshowpreview is also call by the 'onclick' set on each li return by ajaxdirtree
	);

	$('#refreshbutton').click( function() {
		console.log("Click on refreshbutton");
		$.pleaseBePatient("<?php echo $langs->trans('PleaseBePatient'); ?>");
		$.get( "<?php echo DOL_URL_ROOT.'/ecm/ajax/ecmdatabase.php'; ?>", {
			action: "build",
			element: "ecm"
		},
		function(response) {
			$.unblockUI();
			location.href="<?php echo $_SERVER['PHP_SELF']; ?>";
		});
	});
});

function loadandshowpreview(filedirname,section)
{
	//alert('filedirname='+filedirname);
	//console.log(filedirname);
	//console.log('loadandshowpreview for section='+section);

	$('#ecmfileview').empty();

	var url = '<?php echo dol_buildpath('/core/ajax/ajaxdirpreview.php', 1); ?>?action=preview&module=<?php echo $module; ?>&section='+section+'&file='+urlencode(filedirname)<?php echo (empty($paramwithoutsection) ? '' : "+'".$paramwithoutsection."'"); ?>;
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

</script>
<!-- END PHP TEMPLATE ecm/tpl/enablefiletreeajax.tpl.php -->
