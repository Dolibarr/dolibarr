<?php
/* Copyright (C) 2010-2012 	Regis Houssin       <regis.houssin@inodbox.com>
 * Copyright (C) 2010-2025 	Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2024      	Frédéric France    	<frederic.france@free.fr>
 * Copyright (C) 2025		MDW					<mdeweerd@users.noreply.github.com>
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
 */

/**
 * Javascript code to activate the drag and drop on lines
 * You can use this if you want to be able to drag and drop rows of a HTML table.
 * You must add id="tablelines" on table level tag
 * $object and $object->id must be defined
 * $object->fk_element or $fk_element must be defined
 * you must have ($nboflines or count($object->lines) or count($taskarray) > 0)
 * you must have $table_element_line = 'tablename' or $object->table_element_line with line to move
 */

/**
 * @var Conf $conf
 * @var CommonObject $object
 *
 * @var ?string $filepath
 * @var ?string $fk_element
 * @var ?int 	$nboflines
 * @var ?string $tagidfortablednd
 * @var	?string	$table_element_line
 * @var ?Task[]	$tasksarray
 * @var ?string	$urltorefreshaftermove
 */
// Protection to avoid direct call of template
if (empty($object) || !is_object($object)) {
	print "Error, template page ".basename(__FILE__)." can't be called with no object defined.";
	exit;
}
'
@phan-var-force ?string $fk_element
@phan-var-force ?Task[] $tasksarray
';

?>
<!-- BEGIN PHP TEMPLATE AJAXROW.TPL.PHP - Script to enable drag and drop on lines of a table -->
<?php

$redirectURL = empty($urltorefreshaftermove) ? ($_SERVER['PHP_SELF'].'?'.dol_escape_js($_SERVER['QUERY_STRING'])) : $urltorefreshaftermove;
// remove some parameters from URL
$redirectURL = preg_replace('/(&|\?)action=[^&#]*/', '', $redirectURL);
$redirectURL = preg_replace('/(&|\?)page_y=[^&#]*/', '', $redirectURL);

$nboflines = (isset($object->lines) ? count($object->lines) : (isset($tasksarray) ? count($tasksarray) : (empty($nboflines) ? 0 : $nboflines)));

$jsConf = [
	'object' => [
		'id' => $object->id,
		'fkElement' => empty($object->fk_element) ? $fk_element : $object->fk_element,
		'tableElementLine' =>  (empty($table_element_line) ? $object->table_element_line : $table_element_line),
		'nbOfLines' => $nboflines,
	],
	'forceReloadPage' => getDolGlobalInt('MAIN_FORCE_RELOAD_PAGE'),
	'tagIdForTableDND' => (empty($tagidfortablednd) ? 'tablelines' : $tagidfortablednd),
	'filePath' => (empty($filepath) ? '' : $filepath),
	'activeAjaxReorder' => GETPOST('action', 'aZ09') != 'editline' && $nboflines > 1 && $conf->browser->layout != 'phone',
	'gripImg' => DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/grip.png',
	'token' => currentToken(), // We use old 'token' and not 'newtoken' for Ajax call because the ajax page has the NOTOKENRENEWAL constant set.
	'DOL_URL_ROOT' => DOL_URL_ROOT,
	'redirectURL' => $redirectURL
];

?>
<script nonce="<?php print getNonce(); ?>">
Dolibarr.on('Ready', function() {
	/**
	 * @typedef {Object} JsConfElementObject
	 * @property {number} id
	 * @property {number|string|null} fkElement
	 * @property {string} tableElementLine
	 * @property {number} nbOfLines
	 */

	/**
	 * @typedef {Object} JsConf
	 * @property {JsConfElementObject} object
	 * @property {number} forceReloadPage
	 * @property {string} tagIdForTableDND
	 * @property {string} filePath
	 * @property {string} gripImg
	 * @property {string} token
	 * @property {string} DOL_URL_ROOT
	 * @property {string} redirectURL
	 */

	/** @type {JsConf} */
	const jsConf = <?php print json_encode($jsConf); ?>;

	const applyCssAndDisplay = function() {
		$(".imgupforline").hide();
		$(".imgdownforline").hide();
		$(".lineupdown").removeAttr('href');

		if(jsConf.activeAjaxReorder) {
			$(".tdlineupdown").css("background-image",`url(${jsConf.gripImg})`);
			$(".tdlineupdown").css("background-repeat","no-repeat");
			$(".tdlineupdown").css("background-position","center center");
		}
	}


	let tableDnDInstance = null;

	function initTableDnD() {
		if (!jsConf.activeAjaxReorder) return;

		Dolibarr.log("Prepare tableDnd for #" + jsConf.tagIdForTableDND);
		// TODO : replace old tableDnD with modern SortableJS
		tableDnDInstance = $("#" + jsConf.tagIdForTableDND).tableDnD({
			onDrop: function (table, row) {
				var page_y = jQuery(document).scrollTop();
				var reloadpage = jsConf.forceReloadPage;
				Dolibarr.log("tableDND onDrop");
				Dolibarr.log(decodeURI($("#" + jsConf.tagIdForTableDND).tableDnDSerialize()));
				$(`#${jsConf.tagIdForTableDND} tr[data-element=extrafield]`).attr('id', '');	// Set extrafields id to empty value in order to ignore them in tableDnDSerialize function
				$(`#${jsConf.tagIdForTableDND} tr[data-ignoreidfordnd=1]`).attr('id', '');	// Set id to empty value in order to ignore them in tableDnDSerialize function
				var roworder = cleanSerialize(decodeURI($(`#${jsConf.tagIdForTableDND}`).tableDnDSerialize()));
				var table_element_line = jsConf.object.tableElementLine;
				var fk_element = jsConf.object.fkElement;
				var element_id = jsConf.object.id;
				var filepath = decodeURIComponent(jsConf.filePath);

				let postData = {
					roworder: roworder,
					table_element_line: table_element_line,
					fk_element: fk_element,
					element_id: element_id,
					filepath: filepath,
					token: jsConf.token
				};

				$.post(`${jsConf.DOL_URL_ROOT}/core/ajax/row.php`,
					postData,
					function () {
						Dolibarr.log("tableDND end of ajax call, reloadpage = " + reloadpage);

						Dolibarr.executeHook('documentRowMoved', postData)

						if (reloadpage == 1) {
							location.href = jsConf.redirectURL + '&page_y=' + page_y;
						}
					}
				);
			},
			onDragClass: "dragClass",
			dragHandle: "td.tdlineupdown"
		});

		$(".tdlineupdown").on("mouseenter", function () {
			$(this).addClass("showDragHandle");
		}).on("mouseleave", function () {
			$(this).removeClass("showDragHandle");
		});
	}

	applyCssAndDisplay();
	initTableDnD();

	Dolibarr.on('reloadDocumentLine', /** @param {{lineId:number, lineElement:string}} data */  function (data) {
		Dolibarr.log('triggered by hook reloadDocumentLine : TEMPLATE AJAXROW.TPL.PHP')
		applyCssAndDisplay();
		initTableDnD();
	});

});
</script>
<!-- END PHP TEMPLATE AJAXROW.TPL.PHP -->
