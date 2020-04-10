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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/takepos/freezone.php
 *	\ingroup    takepos
 *	\brief      Popup to enter a free line
 */

//if (! defined('NOREQUIREUSER'))	define('NOREQUIREUSER','1');	// Not disabled cause need to load personalized language
//if (! defined('NOREQUIREDB'))		define('NOREQUIREDB','1');		// Not disabled cause need to load personalized language
//if (! defined('NOREQUIRESOC'))		define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN'))		define('NOREQUIRETRAN','1');
if (!defined('NOCSRFCHECK'))		define('NOCSRFCHECK', '1');
if (!defined('NOTOKENRENEWAL'))	define('NOTOKENRENEWAL', '1');
if (!defined('NOREQUIREMENU'))		define('NOREQUIREMENU', '1');
if (!defined('NOREQUIREHTML'))		define('NOREQUIREHTML', '1');
if (!defined('NOREQUIREAJAX'))		define('NOREQUIREAJAX', '1');

require '../main.inc.php'; // Load $user and permissions
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

global $mysoc;

$langs->loadLangs(array("bills", "cashdesk"));

$place = (GETPOST('place', 'aZ09') ? GETPOST('place', 'aZ09') : '0'); // $place is id of table for Bar or Restaurant

$idline = GETPOST('idline', 'int');
$action = GETPOST('action', 'alpha');

if (empty($user->rights->takepos->run)) {
	accessforbidden();
}

// get invoice
$invoice = new Facture($db);
if ($place > 0) {
	$invoice->fetch($place);
} else {
	$invoice->fetch('', '(PROV-POS'.$_SESSION['takeposterminal'].'-'.$place.')');
}

// get default vat rate
$constforcompanyid = 'CASHDESK_ID_THIRDPARTY'.$_SESSION['takeposterminal'];
$soc = new Societe($db);
if ($invoice->socid > 0) $soc->fetch($invoice->socid);
else $soc->fetch($conf->global->$constforcompanyid);
$vatRateDefault = get_default_tva($mysoc, $soc);

/*
 * View
 */

$arrayofcss = array('/takepos/css/pos.css.php');
$arrayofjs = array();

top_htmlhead($head, '', 0, 0, $arrayofjs, $arrayofcss);
?>
<script>
	var vatRate = '<?php echo dol_escape_js($vatRateDefault); ?>';

	/**
	 * Apply new VAT rate
	 *
	 * @param   {string}    id          VAT id
	 * @param   {string}    rate        VAT rate
	 */
	function ApplyVATRate(id, rate) {
		console.log("Save selected VAT Rate into vatRate variable with value "+rate);
		vatRate = rate;
		jQuery('button.vat_rate').removeClass('selected');
		jQuery('#vat_rate_'+id).addClass('selected');
	}

	/**
	 * Save (validate)
	 */
	function Save() {
		console.log("We click so we call page invoice.php with place=<?php echo $place; ?> tva_tx="+vatRate);
		$.get( "invoice.php", { action: "<?php echo $action; ?>", place: "<?php echo $place; ?>", desc:$('#desc').val(), number:$('#number').val(), tva_tx: vatRate} );
		parent.$.colorbox.close();
	}

	$( document ).ready(function() {
		$('#desc').focus()
	});
</script>
</head>
<body>
<br>
<center>
<input type="text" id="desc" name="desc" class="takepospay" style="width:40%;" placeholder="<?php echo $langs->trans('Description'); ?>">
<?php
if ($action == "freezone") echo '<input type="text" id="number" name="number" class="takepospay" style="width:15%;" placeholder="'.$langs->trans('Price').'">';
if ($action == "addnote") echo '<input type="hidden" id="number" name="number" value="'.$idline.'">';
?>
<input type="hidden" name="place" class="takepospay" value="<?php echo $place; ?>">
<input type="button" class="button takepospay clearboth" value="OK" onclick="Save();">
<?php
if ($action == 'freezone') {
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';

	$form = new Form($db);
	$num = $form->load_cache_vatrates("'".$mysoc->country_code."'");
	if ($num > 0) {
		print '<br><br>';
		print $langs->trans('VAT').' : ';
		foreach ($form->cache_vatrates as $rate) {
			print '<button type="button" class="button item_value vat_rate'.($rate['txtva'] == $vatRateDefault ? ' selected' : '').'" id="vat_rate_'.$rate['rowid'].'" onclick="ApplyVATRate(\''.$rate['rowid'].'\', \''.$rate['txtva'].'\');">'.$rate['txtva'].' %</button>';
		}
	}
}
?>
</center>

</body>
</html>
