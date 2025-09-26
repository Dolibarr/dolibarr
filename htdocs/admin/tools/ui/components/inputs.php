<?php
/*
 * Copyright (C) 2024 Anthony Damhet <a.damhet@progiseize.fr>
 *
 * This program and files/directory inner it is free software: you can
 * redistribute it and/or modify it under the terms of the
 * GNU Affero General Public License (AGPL) as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AGPL for more details.
 *
 * You should have received a copy of the GNU AGPL
 * along with this program. If not, see <https://www.gnu.org/licenses/agpl-3.0.html>.
 */

// Load Dolibarr environment
require '../../../../main.inc.php';

/**
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Protection if external user
if ($user->socid > 0) : accessforbidden();
endif;

// Includes
dol_include_once('admin/tools/ui/class/documentation.class.php');
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';

// Load documentation translations
$langs->load('uxdocumentation');

$action = GETPOST('action', 'alpha');

//
$documentation = new Documentation($db);
$morejs = [
	'/includes/ace/src/ace.js',
	'/includes/ace/src/ext-statusbar.js',
	'/includes/ace/src/ext-language_tools.js',
];
// Output html head + body - Param is Title
$documentation->docHeader('Inputs', $morejs);

// Set view for menu and breadcrumb
// Menu must be set in constructor of documentation class
$documentation->view = array('Components','Inputs');

// Output sidebar
$documentation->showSidebar(); ?>

<div class="doc-wrapper">

	<?php $documentation->showBreadCrumb(); ?>

	<div class="doc-content-wrapper">

		<h1 class="documentation-title"><?php echo $langs->trans('DocInputsTitle'); ?></h1>
		<p class="documentation-text"><?php echo $langs->trans('DocInputsMainDescription'); ?></p>

		<!-- Summary -->
		<?php $documentation->showSummary(); ?>

		<!-- Basic usage -->
		<div class="documentation-section" id="setinputssection-basicusage">
			<h2 class="documentation-title"><?php echo $langs->trans('DocBasicUsage'); ?></h2>
			<!-- Classic Input -->
			<p class="documentation-text"><?php echo $langs->trans('DocClassicInputsDescription'); ?></p>
			<div class="documentation-example">
				<td>Available Input</td>
				<td><input id="label" name="label" class="minwidth200" maxlength="255" value=""></td>
				<br><br>
				<td>Disabled Input</td>
				<td><input id="label" name="label" class="minwidth200" maxlength="255" value="" disabled></td>
			</div>
			<?php
			$lines = array(
				'Available Input',
				'<input id="label" name="label" class="minwidth200" maxlength="255" value="">',
				'',
				'Disabled Input',
				'<input id="label" name="label" class="minwidth200" maxlength="255" value="" disabled>',
			);
			echo $documentation->showCode($lines); ?>

			<!-- Checkbox input -->
			<p class="documentation-text"><?php echo $langs->trans('DocCheckboxInputsDescription'); ?></p>
			<div class="documentation-example">
				<span class="spannature paddinglarge marginrightonly nonature-back valignmiddle"><label for="prospectinput" class="valignmiddle"><span class="valignmiddle">Prospect</span><input id="prospectinput" class="flat checkforselect marginleftonly valignmiddle" type="checkbox" name="prospect" value="1" checked></label></span>
				<span  class="spannature paddinglarge marginrightonly nonature-back valignmiddle"><label for="customerinput" class="valignmiddle"><span class="valignmiddle">Customer</span><input id="customerinput" class="flat checkforselect marginleftonly valignmiddle" type="checkbox" name="customer" value="1" checked></label></span>
				<span class="spannature paddinglarge marginrightonly nonature-back valignmiddle"><label for="supplierinput" class="valignmiddle"><span class="valignmiddle">Supplier</span><input id="supplierinput" class="flat checkforselect marginleftonly valignmiddle" type="checkbox" name="supplier" value="1" checked></label></span>
			</div>
			<?php
			$lines = array(
				'<span class="spannature paddinglarge marginrightonly nonature-back valignmiddle"><label for="prospectinput" class="valignmiddle"><span class="valignmiddle">Prospect</span><input id="prospectinput" class="flat checkforselect marginleftonly valignmiddle" type="checkbox" name="prospect" value="1" checked></label></span>',
				'<span class="spannature paddinglarge marginrightonly nonature-back valignmiddle"><label for="customerinput" class="valignmiddle"><span class="valignmiddle">Customer</span><input id="customerinput" class="flat checkforselect marginleftonly valignmiddle" type="checkbox" name="customer" value="1" checked></label></span>',
				'<span class="spannature paddinglarge marginrightonly nonature-back valignmiddle"><label for="supplierinput" class="valignmiddle"><span class="valignmiddle">Supplier</span><input id="supplierinput" class="flat checkforselect marginleftonly valignmiddle" type="checkbox" name="supplier" value="1" checked></label></span>',
			);
			echo $documentation->showCode($lines); ?>

			<!-- Radio input -->
			<p class="documentation-text"><?php echo $langs->trans('DocRadioInputsDescription'); ?></p>
			<div class="documentation-example">
				<input type="radio" id="idforradioinput1" name="radioinput" value="value1"><label for="idforradioinput1" class="marginrightonly"> Radio Input 1</label>
				<input type="radio" id="idforradioinput2" name="radioinput" value="value2"><label for="idforradioinput2" class="marginrightonly"> Radio Input 2</label>
			</div>
			<?php
			$lines = array(
				'<input type="radio" id="idforradioinput1" name="radioinput" value="value1"><label for="idforradioinput1" class="marginrightonly"> Radio Input 1</label>',
				'<input type="radio" id="idforradioinput2" name="radioinput" value="value2"><label for="idforradioinput2" class="marginrightonly"> Radio Input 2</label>'
			);
			echo $documentation->showCode($lines); ?>
		</div>

		<!-- Helper functions -->
		<div class="documentation-section" id="setinputssection-helperfunctions">
			<h2 class="documentation-title"><?php echo $langs->trans('DocHelperFunctionsInputUsage'); ?></h2>
			<p class="documentation-text"><?php echo $langs->trans('DocSelectInputsDescription'); ?></p>
			<div class="documentation-example">
				<td>Select with empty value</td>
				<?php
				$values = ['1' => 'value 1', '2' => 'value 2', '3' => 'value 3'];
				$form = new Form($db);
				print $form->selectarray('htmlnameselectwithemptyvalue', $values, 'idselectwithemptyvalue', 1, 0, 0, '', 0, 0, 0, '', 'minwidth200');
				?>
				<br><br>
				<td>Select within empty value</td>
				<?php
				$values = ['1' => 'value 1', '2' => 'value 2', '3' => 'value 3'];
				$form = new Form($db);
				print $form->selectarray('htmlnameselectwithinemptyvalue', $values, 'idnameselectwithinemptyvalue', 0, 0, 0, '', 0, 0, 0, '', 'minwidth200');
				?>
			</div>
			<?php
			$lines = array(
				'<?php',
				'',
				'/**',
				' * Function selectarray',
				' *',
				' * @param string 				$htmlname           Name of html select area. Try to start name with "multi" or "search_multi" if this is a multiselect,',
				' * @param array            	$array              Array like array(key => value) or array(key=>array(\'label\'=>..., \'data-...\'=>..., \'disabled\'=>..., \'css\'=>...)),',
				' * @param string|string[]|int 	$id                 Preselected key or array of preselected keys for multiselect. Use \'ifone\' to autoselect record if there is only one record.,',
				' * @param int<0,1>|string 		$show_empty         0 no empty value allowed, 1 or string to add an empty value into list (If 1: key is -1 and value is \'\' or "&nbsp;", If \'Placeholder string\': key is -1 and value is the string), <0 to add an empty value with key that is this value.,',
				' * @param int<0,1>				$key_in_label       1 to show key into label with format "[key] value",',
				' * @param int<0,1>				$value_as_key       1 to use value as key,',
				' * @param string 				$moreparam          Add more parameters onto the select tag. For example "style=\"width: 95%\"" to avoid select2 component to go over parent container,',
				' * @param int<0,1>				$translate          1=Translate and encode value,',
				' * @param int 					$maxlen             Length maximum for labels,',
				' * @param int<0,1>				$disabled           Html select box is disabled,',
				' * @param string 				$sort               \'ASC\' or \'DESC\' = Sort on label, \'\' or \'NONE\' or \'POS\' = Do not sort, we keep original order,',
				' * @param string 				$morecss            Add more class to css styles,',
				' * @param int 					$addjscombo         Add js combo,',
				' * @param string 				$moreparamonempty   Add more param on the empty option line. Not used if show_empty not set,',
				' * @param int 					$disablebademail    1=Check if a not valid email, 2=Check string \'---\', and if found into value, disable and colorize entry,',
				' * @param int 					$nohtmlescape       No html escaping (not recommended, use \'data-html\' if you need to use label with HTML content).,',
				' * @return string                                  HTML select string.,',
				' */',
				'',

				'// Select with empty value',
				'print $form->selectarray(\'htmlnameselectwithemptyvalue\', $values, \'idselectwithemptyvalue\', 1, 0, 0, \'\', 0, 0, 0, \'\', \'minwidth200\');',
				'',
				'// Select within empty value',
				'print $form->selectarray(\'htmlnameselectwithinemptyvalue\', $values, \'idnameselectwithinemptyvalue\', 0,0, 0, \'\', 0, 0, 0, \'\', \'minwidth200\');',

			);
			echo $documentation->showCode($lines, 'php'); ?>

			<!-- Multiselect input -->
			<p class="documentation-text"><?php echo $langs->trans('DocMultiSelectInputsDescription'); ?></p>
			<div class="documentation-example">
				<td>Multiselect</td>
				<?php
				$values = ['1' => 'value 1', '2' => 'value 2', '3' => 'value 3'];
				$form = new Form($db);
				print $form->multiselectarray('categories', $values, GETPOST('categories', 'array'), 0, 0, 'minwidth200', 0, 0);
				?>
			</div>
			<?php
			$lines = array(
				'<?php',
				'',
				'/**',
				' * Show a multiselect form from an array. WARNING: Use this only for short lists.',
				' *',
				' * @param 	string 		$htmlname 		Name of select',
				' * @param 	array<string,string|array{id:string,label:string,color:string,picto:string,labelhtml:string}>	$array 			Array(key=>value) or Array(key=>array(\'id\'=>key, \'label\'=>value, \'color\'=> , \'picto\'=> , \'labelhtml\'=> ))',
				' * @param 	string[]	$selected 		Array of keys preselected',
				' * @param 	int<0,1>	$key_in_label 	1 to show key like in "[key] value"',
				' * @param 	int<0,1>	$value_as_key 	1 to use value as key',
				' * @param 	string 		$morecss 		Add more css style',
				' * @param 	int<0,1> 	$translate 		Translate and encode value',
				' * @param 	int|string 	$width 			Force width of select box. May be used only when using jquery couch. Example: 250, \'95%\'',
				' * @param 	string 		$moreattrib 	Add more options on select component. Example: \'disabled\'',
				' * @param 	string 		$elemtype 		Type of element we show (\'category\', ...). Will execute a formatting function on it. To use in readonly mode if js component support HTML formatting.',
				' * @param 	string 		$placeholder 	String to use as placeholder',
				' * @param 	int<-1,1> 	$addjscombo 	Add js combo',
				' * @return 	string                      HTML multiselect string',
				' * @see selectarray(), selectArrayAjax(), selectArrayFilter()',
				' */',
				'',
				'// Multiselect',
				'print $form->multiselectarray(\'categories\', $values, GETPOST(\'categories\', \'array\'), 0, 0, \'minwidth200\', 0, 0);'
			);
			echo $documentation->showCode($lines, 'php'); ?>

			<!-- Date input -->
			<p class="documentation-text"><?php echo $langs->trans('DocDateSelectInputsDescription'); ?></p>
			<div class="documentation-example">
				<td>Date Select</td>
				<?php
				$values = ['1' => 'value 1', '2' => 'value 2', '3' => 'value 3'];
				$form = new Form($db);
				print $form->selectDate();
				?>
				<br><br>
				<td>Date Select with hours</td>
				<?php
				$values = ['1' => 'value 1', '2' => 'value 2', '3' => 'value 3'];
				$form = new Form($db);
				print $form->selectDate('', 're2', 1, 1, 1);
				?>
			</div>
			<?php
			$lines = array(
				'<?php',
				'/**',
				' *  Show a HTML widget to input a date or combo list for day, month, years and optionally hours and minutes.,',
				' *  Fields are preselected with :,',
				' *              - set_time date (must be a local PHP server timestamp or string date with format \'YYYY-MM-DD\' or \'YYYY-MM-DD HH:MM\'),',
				' *              - local date in user area, if set_time is \'\' (so if set_time is \'\', output may differs when done from two different location),',
				' *              - Empty (fields empty), if set_time is -1 (in this case, parameter empty must also have value 1),',
				' *',
				' * @param integer|string 		$set_time 		Pre-selected date (must be a local PHP server timestamp), -1 to keep date not preselected, \'\' to use current date with 00:00 hour (Parameter \'empty\' must be 0 or 2).,',
				' * @param string 				$prefix 		Prefix for fields name,',
				' * @param int 					$h 				1 or 2=Show also hours (2=hours on a new line), -1 has same effect but hour and minutes are prefilled with 23:59 if date is empty, 3 or 4 (4=hours on a new line)=Show hour always empty,',
				' * @param int 					$m 				1=Show also minutes, -1 has same effect but hour and minutes are prefilled with 23:59 if date is empty, 3 show minutes always empty,',
				' * @param int 					$empty 			0=Fields required, 1=Empty inputs are allowed, 2=Empty inputs are allowed for hours only,',
				' * @param string 				$form_name 		Not used,',
				' * @param int<0,1> 				$d 				1=Show days, month, years,',
				' * @param int<0,2>				$addnowlink 	Add a link "Now", 1 with server time, 2 with local computer time,',
				' * @param int<0,1> 				$disabled 		Disable input fields,',
				' * @param int|string			$fullday 		When a checkbox with id #fullday is checked, hours are set with 00:00 (if value if \'fulldaystart\') or 23:59 (if value is \'fulldayend\'),',
				' * @param string 				$addplusone 	Add a link "+1 hour". Value must be name of another selectDate field.,',
				' * @param int|string|array<string,mixed>      $adddateof 		Add a link "Date of ..." using the following date. Must be array(array(\'adddateof\' => ..., \'labeladddateof\' => ...)),',
				' * @param string 				$openinghours 	Specify hour start and hour end for the select ex 8,20,',
				' * @param int 					$stepminutes 	Specify step for minutes between 1 and 30,',
				' * @param string 				$labeladddateof Label to use for the $adddateof parameter. Deprecated. Used only when $adddateof is not an array.,',
				' * @param string 				$placeholder 	Placeholder,',
				' * @param \'auto\'|\'gmt\'|\'tzserver\'|\'tzuserrel\'	$gm 	\'auto\' (for backward compatibility, avoid this), \'gmt\' or \'tzserver\' or \'tzuserrel\',',
				' * @param string				$calendarpicto 	URL of the icon/image used to display the calendar,',
				' * @return string               	         	Html for selectDate,',
				' * @see    form_date(), select_month(), select_year(), select_dayofweek(),',
				' */',
				'',
				'// Date Select',
				'print $form->selectDate();',
				'',
				'// Date Select with hours',
				'print $form->selectDate(\'\', \'re2\', 1, 1, 1);'
			);
			echo $documentation->showCode($lines, 'php'); ?>


			<!-- Editor input -->
			<p class="documentation-text"><?php echo $langs->trans('DocEditorInputsDescription'); ?></p>
			<div class="documentation-example">
				<?php
				$doleditor = new DolEditor('desc', GETPOST('desc', 'restricthtml'), '', 160, 'dolibarr_details', '', false, true, getDolGlobalString('FCKEDITOR_ENABLE_DETAILS'), ROWS_4, '90%');
				$doleditor->Create();
				?>
			</div>
			<?php
			$lines = array(
				'<?php',
				'/**',
				' * Create an object to build an HTML area to edit a large string content',
				' *',
				' *  @param 	string				$htmlname		        		HTML name of WYSIWYG field',
				' *  @param 	string				$content		        		Content of WYSIWYG field',
				' *  @param	int|string			$width							Width in pixel of edit area (auto by default)',
				' *  @param 	int					$height			       		 	Height in pixel of edit area (200px by default)',
				' *  @param 	string				$toolbarname	       		 	Name of bar set to use (\'Full\', \'dolibarr_notes[_encoded]\', \'dolibarr_details[_encoded]\'=the less featured, \'dolibarr_mailings[_encoded]\', \'dolibarr_readonly\')',
				' *  @param  string				$toolbarlocation       			Deprecated. Not used',
				' *  @param  bool				$toolbarstartexpanded  			Bar is visible or not at start',
				' *  @param	bool|int			$uselocalbrowser				Enabled to add links to local object with local browser. If false, only external images can be added in content.',
				' *  @param  bool|int|string		$okforextendededitor    		1 or True=Allow usage of extended editor tool if qualified (like ckeditor). If \'textarea\', force use of simple textarea. If \'ace\', force use of Ace.',
				' *                          	                        		Warning: If you use \'ace\', don\'t forget to also include ace.js in page header. Also, the button "save" must have class="buttonforacesave"',
				' *  @param  int					$rows                   		Size of rows for textarea tool',
				' *  @param  string				$cols                   		Size of cols for textarea tool (textarea number of cols \'70\' or percent \'x%\')',
				' *  @param	int<0,1>			$readonly						0=Read/Edit, 1=Read only',
				' *  @param	array{x?:string,y?:string,find?:string}	$poscursor	Array for initial cursor position array(\'x\'=>x, \'y\'=>y).',
				' *                      	                       				array(\'find\'=> \'word\')  can be used to go to line were the word has been found',
				' */',
				'',
				'$doleditor = new DolEditor(\'desc\', GETPOST(\'desc\', \'restricthtml\'), \'\', 160, \'dolibarr_details\', \'\', false, true, getDolGlobalString(\'FCKEDITOR_ENABLE_DETAILS\'), ROWS_4, \'90%\');',
				'print $form->multiselectarray(\'categories\', $values, GETPOST(\'categories\', \'array\'), 0, 0, \'minwidth200\', 0, 0);'
			);
			echo $documentation->showCode($lines, 'php'); ?>
		</div>

		<!-- Search Filter Tool Input -->
		<div class="documentation-section" id="setinputssection-getSearchFilterToolInput">
			<h2 class="documentation-title"><?php echo $langs->trans('DocSearchInputUsage'); ?></h2>
			<p class="documentation-text"><?php echo $langs->trans('DocSearchInputUsageDescription'); ?></p>
			<div class="documentation-example">
				<?php
				$containerCssSelector = '#demo-search-filter-tool-container-01';
				print $form->getSearchFilterToolInput(
					$containerCssSelector. ' .search-item',
					'',
					'',
					['attr' => [
							'data-counter-target' => $containerCssSelector. ' .counter',
							'data-no-item-target' => $containerCssSelector. ' .search-tool-no-results',
						],
					]
				);
				?>

				<div id="demo-search-filter-tool-container-01">
					<p>Counter : <strong class="counter">12</strong></p>
					<ul>
						<li class="search-item">France</li>
						<li class="search-item">Italy</li>
						<li class="search-item">Germany</li>
						<li class="search-item">Spain</li>
						<li class="search-item">Canada</li>
						<li class="search-item">Brazil</li>
						<li class="search-item">Argentina</li>
						<li class="search-item">Japan</li>
						<li class="search-item">Australia</li>
						<li class="search-item">India</li>
						<li class="search-item">Egypt</li>
						<li class="search-item">South Africa</li>
					</ul>
					<div class="search-tool-no-results hidden-search-result" ><?php print $langs->trans('NoResults') ?></div>
				</div>

			</div>
			<?php
			$lines = array(
				'<div class="search-tool-container">',
				'	<input ',
				'	 type="search"',
				'	 name=""',
				'	 autofocus="" <?php // To use only if search is in top of page ?> ',
				'	 value=""',
				'	 class="search-tool-input"  <?php // optional for js filter you can use custom class ?> ',
				'	 placeholder="Search"',
				'	 autocomplete="off"',
				'	 data-search-tool-target="#demo-filter .search-item" <?php // required for js filter ?> ',
				'	 data-counter-target="#demo-filter .counter" <?php // optional for js filter ?> ',
				'	 data-no-item-target="#demo-filter .search-tool-no-results" <?php // optional for js filter  ?> ',
				'	>',
				'</div>',
				'<div id="demo-filter">',
				'	<p>Counter : <strong class="counter">4</strong></p>',
				'	<ul>',
				'		<li class="search-item">France</li>',
				'		<li class="search-item">Italy</li>',
				'		<li class="search-item">Germany</li>',
				'		<li class="search-item">Spain</li>',
				'	</ul>',
				'	<div class="search-tool-no-results hidden-search-result" >No results</div>',
				'</div>',

			);
			echo $documentation->showCode($lines, 'php');

			$lines = array(
				'<?php',
				'print $form->getSearchFilterToolInput(',
				'	\'#demo-filter .search-item\',',
				'	\'search-tools-input\',',
				'	[\'attr\' => [',
				'			\'data-no-item-target\' => \'#demo-filter .search-tool-no-results\', ',
				'			\'data-counter-target\' => \'#demo-filter .counter\', ',
				'		],',
				'	]',
				'); ',
				'?>',
				'',
				'<div id="demo-filter">',
				'	<p>Counter : <strong class="counter">4</strong></p>',
				'	<ul>',
				'		<li class="search-item">France</li>',
				'		<li class="search-item">Italy</li>',
				'		<li class="search-item">Germany</li>',
				'		<li class="search-item">Spain</li>',
				'	</ul>',
				'	<div class="search-tool-no-results hidden-search-result" >No results</div>',
				'</div>',

			);
			echo $documentation->showCode($lines, 'php');

			?>

		</div>
	</div>

</div>

<?php
// Output close body + html
$documentation->docFooter();

?>
