<?php
/* Copyright (C) 2026		Frederic France			<frederic.france@free.fr>
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
 *  \file       htdocs/don/admin/donation_acroform.php
 *  \ingroup    donations
 *  \brief      Page to tell what goes into each field of a PDF form used as a receipt
 */
require '../../main.inc.php';
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var Translate $langs
 * @var User $user
 */
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/donation.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/don/class/don.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/dons/pdf_acroform_donation.modules.php';

// Load translation files required by the page
$langs->loadLangs(array('admin', 'donations', 'other'));

if (!$user->admin) {
	accessforbidden();
}

$action = GETPOST('action', 'aZ09');
$template = dol_sanitizeFileName(GETPOST('template', 'alpha'));
$view = GETPOST('view', 'aZ09');
$page = GETPOSTINT('page');

$model = new pdf_acroform_donation($db);

// The directory the templates are read from and written to
$templatedir = '';
foreach (explode(',', preg_replace('/[\r\n]+/', ',', getDolGlobalString($model->scandir))) as $dir) {
	$dir = trim($dir);
	if ($dir === '') {
		continue;
	}
	$templatedir = DOL_DATA_ROOT.'/'.preg_replace('/DOL_DATA_ROOT\/*/', '', $dir);
	break;
}

if ($template === '') {
	$template = dol_sanitizeFileName(getDolGlobalString('DON_ACROFORM_TEMPLATE'));
}
$templatepath = ($templatedir !== '' && $template !== '') ? $templatedir.'/'.$template : '';
if ($templatepath !== '' && !file_exists($templatepath)) {
	$templatepath = '';
}

// The donation the preview is computed on: the last one, a specimen when there is none
$sampledon = new Don($db);
$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."don";
$sql .= " WHERE entity IN (".getEntity('donation').")";
$sql .= " ORDER BY rowid DESC";
$sql .= $db->plimit(1);
$resql = $db->query($sql);
if ($resql && ($obj = $db->fetch_object($resql))) {
	$sampledon->fetch((int) $obj->rowid);
} else {
	$sampledon->initAsSpecimen();
}


/**
 * Return the picture of one page of a PDF form, making it when it is not there yet
 *
 * @param	string	$pdf		Path of the PDF form
 * @param	int		$pagenumber	Page to draw, counted from 1
 * @return	string				Path of the picture, empty when it could not be made
 */
function acroformPagePicture($pdf, $pagenumber)
{
	if (!class_exists('Imagick')) {
		return '';
	}

	$dir = dirname($pdf).'/preview';
	$picture = $dir.'/'.basename($pdf).'-p'.((int) $pagenumber).'.png';

	// The picture is made again when the form was replaced by another revision
	if (dol_is_file($picture) && filemtime($picture) >= filemtime($pdf)) {
		return $picture;
	}

	dol_mkdir($dir);
	if (dol_convert_file($pdf, 'png', $picture, (string) ($pagenumber - 1)) <= 0) {
		return '';
	}

	return dol_is_file($picture) ? $picture : '';
}

/**
 * Return the url that serves a file of the directory of the templates
 *
 * @param	string	$path	Path of the file
 * @return	string			Url, empty when the file is not under the templates of Dolibarr
 */
function acroformFileUrl($path)
{
	$root = DOL_DATA_ROOT.'/doctemplates/';
	if (strpos($path, $root) !== 0) {
		return '';
	}

	return DOL_URL_ROOT.'/document.php?modulepart=doctemplates&file='.urlencode(substr($path, strlen($root)));
}


/*
 * Actions
 */

if ($action == 'settemplate' && $user->admin) {	// Test on permission already done
	dolibarr_set_const($db, 'DON_ACROFORM_TEMPLATE', $template, 'chaine', 0, '', $conf->entity);
	setEventMessages($langs->trans("RecordModifiedSuccessfully"), null, 'mesgs');
	header("Location: ".$_SERVER["PHP_SELF"].'?template='.urlencode($template));
	exit;
}

if ($action == 'savemap' && $user->admin) {	// Test on permission already done
	if ($templatepath === '') {
		setEventMessages($langs->trans("ErrorAcroFormTemplateNotFound", $template), null, 'errors');
	} else {
		$postedvalues = GETPOST('fieldvalue', 'array');
		$postedtransforms = GETPOST('fieldtransform', 'array');
		$postedtokens = GETPOST('fieldtoken', 'array');

		$fields = array();
		foreach ($model->readFields($templatepath) as $name => $info) {
			if ($info['type'] == 'Button') {
				$token = isset($postedtokens[$name]) ? (string) $postedtokens[$name] : '';
				if ($token !== '') {
					$fields[$name] = array('token' => $token);
				}
				continue;
			}
			$value = isset($postedvalues[$name]) ? dol_string_nohtmltag((string) $postedvalues[$name]) : '';
			if (trim($value) === '') {
				continue;
			}
			$fields[$name] = array(
				'value' => $value,
				'transform' => isset($postedtransforms[$name]) ? (string) $postedtransforms[$name] : '',
			);
		}

		$map = array(
			'version' => 1,
			'source' => array('name' => $template, 'sha256' => hash_file('sha256', $templatepath)),
			'fields' => $fields,
		);

		if (file_put_contents($templatepath.'.map.json', json_encode($map, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) === false) {
			setEventMessages($langs->trans("ErrorFailToCreateFile", basename($templatepath).'.map.json'), null, 'errors');
		} else {
			dolChmod($templatepath.'.map.json');
			setEventMessages($langs->trans("RecordModifiedSuccessfully"), null, 'mesgs');
		}
	}
	$action = '';
}

if ($action == 'testreceipt' && $user->admin) {	// Test on permission already done
	$result = $model->write_file($sampledon, $langs);
	if ($result > 0) {
		setEventMessages($langs->trans("AcroFormReceiptProduced", basename($model->result['fullpath'])), null, 'mesgs');
	} else {
		setEventMessages($model->error, $model->errors, 'errors');
	}
	$action = '';
}

if (GETPOST('sendit', 'alpha') && $user->admin) {	// Test on permission already done
	if ($templatedir === '') {
		setEventMessages($langs->trans("ErrorNoAcroFormDirectory"), null, 'errors');
	} else {
		dol_mkdir($templatedir);
		dol_add_file_process($templatedir, 0, 1, 'userfile', '', null, '', 0, null);
	}
}


/*
 * View
 */

$form = new Form($db);

llxHeader('', $langs->trans("DonationsSetup"), '', '', 0, 0, '', '', '', 'mod-donation page-admin');

print load_fiche_titre($langs->trans("DonationsSetup"), '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>', 'title_setup');

print dol_get_fiche_head(donation_admin_prepare_head(), 'general', $langs->trans("Donations"), -1, 'payment');

print '<span class="opacitymedium">'.$langs->trans("AcroFormSetupDescription").'</span><br><br>';

// ---- the forms available --------------------------------------------------
print load_fiche_titre($langs->trans("AcroFormTemplates"), '', '');

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("File").'</td>';
print '<td class="center">'.$langs->trans("Fields").'</td>';
print '<td class="center">'.$langs->trans("AcroFormFieldsSetUp").'</td>';
print '<td class="center">'.$langs->trans("AcroFormUsedForReceipts").'</td>';
print '</tr>';

$found = 0;
$files = ($templatedir !== '') ? dol_dir_list($templatedir, 'files', 0, '\.pdf$') : array();
foreach ($files as $file) {
	$found++;
	$path = $file['fullname'];
	$fields = $model->readFields($path);
	$map = $model->readMapFile($path);
	$configured = ($map === null) ? 0 : count($map['fields']);

	print '<tr class="oddeven">';
	print '<td><a href="'.$_SERVER["PHP_SELF"].'?template='.urlencode($file['name']).'">'.dol_escape_htmltag($file['name']).'</a></td>';
	print '<td class="center">'.count($fields).'</td>';
	print '<td class="center">'.($configured > 0 ? $configured : '<span class="opacitymedium">'.$langs->trans("None").'</span>').'</td>';
	print '<td class="center">';
	if (getDolGlobalString('DON_ACROFORM_TEMPLATE') == $file['name']) {
		print img_picto($langs->trans("Enabled"), 'switch_on');
	} else {
		print '<a href="'.$_SERVER["PHP_SELF"].'?action=settemplate&token='.newToken().'&template='.urlencode($file['name']).'">'.img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
	}
	print '</td>';
	print '</tr>';
}
if ($found == 0) {
	print '<tr class="oddeven"><td colspan="4"><span class="opacitymedium">'.$langs->trans("NoPDFFormYet", $templatedir).'</span></td></tr>';
}
print '</table>';
print '</div>';

// Upload of a new form
print '<form name="uploadform" action="'.$_SERVER["PHP_SELF"].'" method="POST" enctype="multipart/form-data">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="template" value="'.dol_escape_htmltag($template).'">';
print '<div class="margintopnly"><input type="file" name="userfile[]"> ';
print '<input type="submit" class="button small" name="sendit" value="'.dol_escape_htmltag($langs->trans("Upload")).'">';
print '</div>';
print '</form>';

print '<br>';

// ---- the fields of the chosen form ----------------------------------------
if ($templatepath !== '') {
	$fields = $model->readFields($templatepath);
	$map = $model->readMapFile($templatepath);
	$known = ($map === null) ? array() : $map['fields'];

	$substitutions = $model->getSubstitutionArray($sampledon, $langs, $conf->currency);

	$transforms = array(
		'' => $langs->trans("AcroFormTransformNone"),
		'amountinwords' => $langs->trans("AcroFormTransformAmountInWords"),
		'streetnumber' => $langs->trans("AcroFormTransformStreetNumber"),
		'streetname' => $langs->trans("AcroFormTransformStreetName"),
		'upper' => $langs->trans("AcroFormTransformUpper"),
		'digitsonly' => $langs->trans("AcroFormTransformDigitsOnly"),
	);

	// Show the fields in the order they are read on the form, not in the order the PDF
	// happens to store them: the page first, then from the top of the page to its bottom
	/**
	 * @param	array<string,mixed>	$a	A field of the form, as readFields() returns it
	 * @param	array<string,mixed>	$b	Another field of the form
	 * @return	int						-1, 0 or 1, the order the two fields are read in
	 */
	$inreadingorder = static function (array $a, array $b): int {
		$pagea = empty($a['widgets'][0]['page']) ? 9999 : $a['widgets'][0]['page'];
		$pageb = empty($b['widgets'][0]['page']) ? 9999 : $b['widgets'][0]['page'];
		if ($pagea != $pageb) {
			return $pagea < $pageb ? -1 : 1;
		}
		// The corners of a field are counted from the bottom of the page
		$topa = empty($a['widgets'][0]['rect'][3]) ? 0 : $a['widgets'][0]['rect'][3];
		$topb = empty($b['widgets'][0]['rect'][3]) ? 0 : $b['widgets'][0]['rect'][3];
		if ($topa != $topb) {
			return $topa > $topb ? -1 : 1;
		}
		$lefta = empty($a['widgets'][0]['rect'][0]) ? 0 : $a['widgets'][0]['rect'][0];
		$leftb = empty($b['widgets'][0]['rect'][0]) ? 0 : $b['widgets'][0]['rect'][0];
		return $lefta < $leftb ? -1 : ($lefta > $leftb ? 1 : 0);
	};
	uasort($fields, $inreadingorder);

	$todo = 0;
	foreach ($fields as $name => $info) {
		if (!isset($known[$name])) {
			$todo++;
		}
	}

	// The fields are shown on the form itself when a picture of it can be made
	$pages = array();
	foreach ($fields as $name => $info) {
		foreach ($info['widgets'] as $widget) {
			if (!empty($widget['page'])) {
				$pages[$widget['page']] = 1;
			}
		}
	}
	ksort($pages);
	$pages = array_keys($pages);

	$picture = '';
	$pictureurl = '';
	if ($view != 'list' && count($pages)) {
		$page = in_array($page, $pages) ? $page : $pages[0];
		$picture = acroformPagePicture($templatepath, $page);
		$pictureurl = ($picture === '') ? '' : acroformFileUrl($picture);
	}
	$showdocument = ($pictureurl !== '');

	print load_fiche_titre($langs->trans("AcroFormFieldsOf", $template), '', '');
	print '<span class="opacitymedium">'.$langs->trans("AcroFormFieldsCount", count($fields), $todo).'</span><br><br>';

	// Which of the two views is shown
	print '<div class="marginbottomonly">';
	if ($showdocument) {
		print '<span class="butActionRefused">'.$langs->trans("AcroFormDocumentView").'</span> ';
		print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?template='.urlencode($template).'&view=list">'.$langs->trans("AcroFormListView").'</a>';
	} elseif ($view != 'list' && class_exists('Imagick')) {
		print '<span class="opacitymedium">'.$langs->trans("AcroFormNoPicture").'</span>';
	} elseif ($view != 'list') {
		print '<span class="opacitymedium">'.$langs->trans("AcroFormNeedsImagick").'</span>';
	} else {
		print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?template='.urlencode($template).'&view=document">'.$langs->trans("AcroFormDocumentView").'</a> ';
		print '<span class="butActionRefused">'.$langs->trans("AcroFormListView").'</span>';
	}
	print '</div>';

	// The values a receipt can show, with what they give on the donation of the preview
	print '<div class="marginbottomonly">';
	$keyshelp = array();
	foreach ($substitutions as $key => $value) {
		$keyshelp[] = '<b>'.dol_escape_htmltag($key).'</b>: '.dol_escape_htmltag((string) $value);
	}
	print $form->textwithpicto($langs->trans("AcroFormAvailableKeys"), implode('<br>', $keyshelp), 1, 'help', '', 0, 3, 'acroformkeys');
	print '</div>';

	print '<form name="mapform" action="'.$_SERVER["PHP_SELF"].'" method="POST">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="savemap">';
	print '<input type="hidden" name="template" value="'.dol_escape_htmltag($template).'">';

	if ($showdocument) {
		$size = getimagesize($picture);
		// The picture is drawn at 72 pixels per inch, which is the unit the corners of a
		// field are given in, so one pixel of the picture is one unit of the form
		$width = ($size === false) ? 0 : (float) $size[0];
		$height = ($size === false) ? 0 : (float) $size[1];

		print '<div class="fichecenter"><div class="fichehalfleft">';

		if (count($pages) > 1) {
			print '<div class="marginbottomonly">';
			foreach ($pages as $one) {
				if ($one == $page) {
					print '<span class="butActionRefused">'.$langs->trans("Page").' '.$one.'</span> ';
				} else {
					print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?template='.urlencode($template).'&view=document&page='.$one.'">'.$langs->trans("Page").' '.$one.'</a> ';
				}
			}
			print '</div>';
		}

		print '<div style="position: relative; max-width: '.((int) $width).'px; border: 1px solid #ccc;">';
		print '<img src="'.$pictureurl.'" alt="'.dol_escape_htmltag($langs->trans("AcroFormPageOfForm", $page)).'" style="display: block; width: 100%;">';

		foreach ($fields as $name => $info) {
			$set = isset($known[$name]);
			foreach ($info['widgets'] as $number => $widget) {
				if (empty($widget['page']) || $widget['page'] != $page || empty($widget['rect']) || $width <= 0 || $height <= 0) {
					continue;
				}
				$rect = $widget['rect'];
				// The corners are counted from the bottom left of the page
				$left = 100 * min($rect[0], $rect[2]) / $width;
				$right = 100 * max($rect[0], $rect[2]) / $width;
				$top = 100 * ($height - max($rect[1], $rect[3])) / $height;
				$bottom = 100 * ($height - min($rect[1], $rect[3])) / $height;

				print '<button type="button" class="acroformbox'.($set ? ' acroformboxset' : '').'" data-field="'.dol_escape_htmltag($name).'"';
				print ' title="'.dol_escape_htmltag($name).'" aria-label="'.dol_escape_htmltag($langs->trans("AcroFormField").' '.$name).'"';
				print ' style="position: absolute; left: '.round($left, 3).'%; top: '.round($top, 3).'%;';
				print ' width: '.round($right - $left, 3).'%; height: '.round($bottom - $top, 3).'%;';
				print ' min-width: 8px; min-height: 8px; padding: 0; margin: 0; cursor: pointer;';
				print ' background: '.($set ? 'rgba(45, 105, 165, 0.20)' : 'rgba(180, 125, 30, 0.14)').';';
				print ' border: 1px '.($set ? 'solid #2d69a5' : 'dashed #b47d1e').';"></button>';
			}
		}

		print '</div>';
		print '<div class="opacitymedium small margintoponly">'.$langs->trans("AcroFormClickAField").'</div>';
		print '</div><div class="fichehalfright">';

		// One block per field, the one of the field clicked on the form is shown
		print '<div id="acroformpanel" class="marginleftonly">';
		print '<div class="acroformpanelempty opacitymedium">'.$langs->trans("AcroFormClickAField").'</div>';

		foreach ($fields as $name => $info) {
			$isbutton = ($info['type'] == 'Button');
			$value = isset($known[$name]['value']) ? (string) $known[$name]['value'] : '';
			$transform = isset($known[$name]['transform']) ? (string) $known[$name]['transform'] : '';
			$token = isset($known[$name]['token']) ? (string) $known[$name]['token'] : '';

			print '<div class="acroformblock" id="acroformblock-'.dol_escape_htmltag($name).'" style="display: none;">';
			print '<div class="tagtable centpercent">';
			print '<div><b class="wordbreak">'.dol_escape_htmltag($name).'</b>';
			print ' <span class="opacitymedium small">'.dol_escape_htmltag($info['type']);
			if (!empty($info['maxlen'])) {
				print ' &middot; '.$langs->trans("AcroFormMaxLength", $info['maxlen']);
			}
			print '</span></div>';

			if ($isbutton) {
				$choices = array('' => $langs->trans("AcroFormDoNotTick"));
				foreach ($info['options'] as $option) {
					if ($option !== 'Off') {
						$choices[$option] = $option;
					}
				}
				print '<div class="margintoponly">'.$form->selectarray('fieldtoken['.$name.']', $choices, $token, 0, 0, 0, '', 0, 0, 0, '', 'minwidth200').'</div>';
			} else {
				print '<div class="margintoponly"><input type="text" class="minwidth300 maxwidth300onsmartphone" name="fieldvalue['.$name.']" value="'.dol_escape_htmltag($value).'"></div>';
				print '<div class="margintoponly">'.$form->selectarray('fieldtransform['.$name.']', $transforms, $transform, 0, 0, 0, '', 0, 0, 0, '', 'minwidth150').'</div>';
				$preview = ($value === '') ? '' : $model->applyTransform(make_substitutions($value, $substitutions, $langs), $transform, $langs, $conf->currency);
				print '<div class="margintoponly opacitymedium small">'.$langs->trans("AcroFormPreview").'</div>';
				print '<div>'.($preview === '' ? '<span class="opacitymedium">'.$langs->trans("AcroFormWillStayEmpty").'</span>' : dol_escape_htmltag($preview)).'</div>';
			}

			print '</div>';
			print '</div>';
		}

		print '</div>';
		print '</div></div>';

		print '<script type="text/javascript">
		jQuery(document).ready(function () {
			jQuery(".acroformbox").on("click", function () {
				var field = jQuery(this).attr("data-field");
				jQuery(".acroformpanelempty").hide();
				jQuery(".acroformblock").hide();
				jQuery("#acroformblock-" + field.replace(/([^a-zA-Z0-9_-])/g, "\\\\$1")).show();
				jQuery(".acroformbox").css("outline", "");
				jQuery(this).css("outline", "2px solid #2d69a5");
			});
		});
		</script>';
	}

	print '<div class="div-table-responsive-no-min"'.($showdocument ? ' style="display: none;"' : '').'>';
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("AcroFormField").'</td>';
	print '<td class="center">'.$langs->trans("Page").'</td>';
	print '<td>'.$langs->trans("AcroFormContent").'</td>';
	print '<td>'.$langs->trans("AcroFormTransform").'</td>';
	print '<td>'.$langs->trans("AcroFormPreview").'</td>';
	print '</tr>';

	foreach ($fields as $name => $info) {
		$isbutton = ($info['type'] == 'Button');
		$page = (!empty($info['widgets']) && !empty($info['widgets'][0]['page'])) ? $info['widgets'][0]['page'] : 0;
		$value = isset($known[$name]['value']) ? (string) $known[$name]['value'] : '';
		$transform = isset($known[$name]['transform']) ? (string) $known[$name]['transform'] : '';
		$token = isset($known[$name]['token']) ? (string) $known[$name]['token'] : '';

		print '<tr class="oddeven'.(isset($known[$name]) ? '' : ' warning').'">';

		print '<td><span class="nowraponall"><b>'.dol_escape_htmltag($name).'</b></span>';
		print '<br><span class="opacitymedium small">'.dol_escape_htmltag($info['type']);
		if (!empty($info['maxlen'])) {
			print ' &middot; '.$langs->trans("AcroFormMaxLength", $info['maxlen']);
		}
		print '</span></td>';

		print '<td class="center">'.($page > 0 ? $page : '').'</td>';

		if ($isbutton) {
			$choices = array('' => $langs->trans("AcroFormDoNotTick"));
			foreach ($info['options'] as $option) {
				if ($option === 'Off') {
					continue;
				}
				$choices[$option] = $option;
			}
			print '<td colspan="2">'.$form->selectarray('fieldtoken['.$name.']', $choices, $token, 0, 0, 0, '', 0, 0, 0, '', 'minwidth200').'</td>';
			print '<td>'.($token !== '' ? $langs->trans("AcroFormWillBeTicked") : '<span class="opacitymedium">'.$langs->trans("AcroFormWillStayEmpty").'</span>').'</td>';
		} else {
			print '<td><input type="text" class="minwidth300" name="fieldvalue['.$name.']" value="'.dol_escape_htmltag($value).'"></td>';
			print '<td>'.$form->selectarray('fieldtransform['.$name.']', $transforms, $transform, 0, 0, 0, '', 0, 0, 0, '', 'minwidth150').'</td>';

			$preview = '';
			if ($value !== '') {
				$preview = $model->applyTransform(make_substitutions($value, $substitutions, $langs), $transform, $langs, $conf->currency);
			}
			print '<td>';
			if ($preview === '') {
				print '<span class="opacitymedium">'.$langs->trans("AcroFormWillStayEmpty").'</span>';
			} else {
				$toolong = (!empty($info['maxlen']) && dol_strlen($preview) > $info['maxlen']);
				print '<span class="'.($toolong ? 'error' : '').'">'.dol_escape_htmltag($preview).'</span>';
				if ($toolong) {
					print ' '.img_warning($langs->trans("AcroFormValueWouldBeTruncated", $info['maxlen']));
				}
			}
			print '</td>';
		}

		print '</tr>';
	}

	print '</table>';
	print '</div>';

	print '<div class="center margintoponly">';
	print '<input type="submit" class="button" value="'.dol_escape_htmltag($langs->trans("Save")).'">';
	print ' &nbsp; ';
	print '<a class="button button-cancel" href="'.$_SERVER["PHP_SELF"].'?action=testreceipt&token='.newToken().'&template='.urlencode($template).'">'.$langs->trans("AcroFormTestOnDonation", $sampledon->ref).'</a>';
	print '</div>';

	print '</form>';
}

print dol_get_fiche_end();

llxFooter();
$db->close();
