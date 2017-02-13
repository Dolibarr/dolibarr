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
 * Output javascript for interactions code of ecm module
 */
?>

<!-- BEGIN PHP TEMPLATE ecm/tpl/enablefiletreeajax.tpl.php -->
<!-- Doc of fileTree plugin at http://www.abeautifulsite.net/blog/2008/03/jquery-file-tree/ -->

<script type="text/javascript">

<?php
$openeddir='/';
?>

$(document).ready(function() {

	$('#filetree').fileTree({
		root: '<?php print dol_escape_js($openeddir); ?>',
		// Ajax called if we click to expand a dir (not a file). Parameter of dir is provided as a POST parameter.
		script: '<?php echo DOL_URL_ROOT.'/core/ajax/ajaxdirtree.php?modulepart=ecm&openeddir='.urlencode($openeddir); ?>',
		folderEvent: 'click',	// 'dblclick'
		multiFolder: false  },
		// Called if we click on a file (not a dir)
		function(file) {
			$("#mesg").hide();
			loadandshowpreview(file,0);
		},
		// Called if we click on a dir (not a file)
		function(elem) {
			id=elem.attr('id').substr(12);	// We get id that is 'fmdirlia_id_xxx' (id we want is xxx)
			jQuery("#formuserfile_section_dir").val(elem.attr('rel'));
   			jQuery("#formuserfile_section_id").val(id);
			jQuery('#formuserfile').show();
		}
	);

	$('#refreshbutton').click( function() {
		console.log("Click on refreshbutton");
		$.pleaseBePatient("<?php echo $langs->trans('PleaseBePatient'); ?>");
		$.get( "<?php echo DOL_URL_ROOT . '/ecm/ajax/ecmdatabase.php'; ?>", {
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
	//console.log(section);

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

</script>
<!-- END PHP TEMPLATE ecm/tpl/builddatabase.tpl.php -->
