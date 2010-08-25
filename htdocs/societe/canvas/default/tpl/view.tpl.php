<?php
/* Copyright (C) 2010 Regis Houssin <regis@dolibarr.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 *
 * $Id$
 */
?>

<!-- BEGIN PHP TEMPLATE -->

<?php if ($mesg) { ?>
<div class="error"><?php echo $mesg; ?></div>
<?php } ?>

<form name="formsoc" method="POST">
<input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>">
<table class="border" width="100%">

<tr>
	<td width="20%"><?php echo $langs->trans('Name'); ?></td>
	<td colspan="3"><?php echo $form->showrefnav($soc,'socid','',($user->societe_id?0:1),'rowid','nom'); ?></td>
</tr>

<tr>
	<td><?php echo $langs->trans('Prefix'); ?></td>
	<td colspan="3"><?php echo $soc->prefix_comm; ?></td>
</tr>

<?php if ($soc->client) { ?>
<tr>
	<td><?php echo $langs->trans('CustomerCode'); ?></td>
	<td colspan="3"><?php echo $soc->code_client; ?>
	<?php if ($soc->check_codeclient() <> 0) { ?>
	<font class="error">(<?php echo $langs->trans("WrongCustomerCode"); ?>)</font>
	<?php } ?>
	</td>
</tr>
<?php } ?>

<?php if ($soc->fournisseur) { ?>
<tr>
	<td><?php echo $langs->trans('SupplierCode'); ?></td>
	<td colspan="3"><?php echo $soc->code_fournisseur; ?>
	<?php if ($soc->check_codefournisseur() <> 0) { ?>
	<font class="error">(<?php echo $langs->trans("WrongSupplierCode"); ?>)</font>
	<?php } ?>
	</td>
</tr>
<?php } ?>

<?php if ($conf->global->MAIN_MODULE_BARCODE) { ?>
<tr>
	<td><?php echo $langs->trans('Gencod'); ?></td>
	<td colspan="3"><?php echo $soc->gencod; ?></td>
</tr>
<?php } ?>

<tr>
	<td valign="top"><?php echo $langs->trans('Address'); ?></td>
	<td colspan="3"><?php echo nl2br($soc->address); ?></td>
</tr>

<tr>
	<td width="25%"><?php echo $langs->trans('Zip'); ?></td>
	<td width="25%"><?php echo $soc->cp; ?></td>
	<td width="25%"><?php echo $langs->trans('Town'); ?></td>
	<td width="25%"><?php echo $soc->ville; ?></td>
</tr>

<tr>
	<td><?php echo $langs->trans("Country"); ?></td>
	<td colspan="3" nowrap="nowrap">
	<?php
	$img=picto_from_langcode($soc->pays_code);
	if ($soc->isInEEC()) echo $form->textwithpicto(($img?$img.' ':'').$soc->pays,$langs->trans("CountryIsInEEC"),1,0);
	else echo ($img?$img.' ':'').$soc->pays;
	?>
	</td>
</tr>

<tr>
	<td><?php echo $langs->trans('State'); ?></td>
	<td colspan="3"><?php echo $soc->departement; ?></td>
</tr>

<tr>
	<td><?php echo $langs->trans('Phone'); ?></td>
	<td><?php echo dol_print_phone($soc->tel,$soc->pays_code,0,$soc->id,'AC_TEL'); ?></td>
	<td><?php echo $langs->trans('Fax'); ?></td>
	<td><?php echo dol_print_phone($soc->fax,$soc->pays_code,0,$soc->id,'AC_FAX'); ?></td>
</tr>

<tr>
	<td><?php echo $langs->trans('EMail'); ?></td>
	<td><?php echo dol_print_email($soc->email,0,$soc->id,'AC_EMAIL'); ?></td>
	<td><?php echo $langs->trans('Web'); ?></td>
	<td><?php echo dol_print_url($soc->url); ?></td>
</tr>

<?php $profid=$langs->transcountry('ProfId1',$soc->pays_code); ?>
<?php if ($profid!='-')	{ ?>
<tr>
	<td><?php echo $profid; ?></td>
	<td><?php echo $soc->siren; ?>
	<?php if ($soc->siren) {
			if ($soc->id_prof_check(1,$soc) > 0) print ' &nbsp; '.$soc->id_prof_url(1,$soc);
			else print ' <font class="error">('.$langs->trans("ErrorWrongValue").')</font>';
	} ?>
	</td>
<?php } else { ?>
<tr>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
<?php } ?>

<?php $profid=$langs->transcountry('ProfId2',$soc->pays_code); ?>
<?php if ($profid!='-')	{ ?>
	<td><?php echo $profid; ?></td>
	<td><?php echo $soc->siret; ?>
	<?php if ($soc->siret) {
			if ($soc->id_prof_check(2,$soc) > 0) print ' &nbsp; '.$soc->id_prof_url(2,$soc);
			else print ' <font class="error">('.$langs->trans("ErrorWrongValue").')</font>';
	} ?>
	</td>
</tr>
<?php } else { ?>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
</tr>
<?php } ?>

<?php $profid=$langs->transcountry('ProfId3',$soc->pays_code); ?>
<?php if ($profid!='-')	{ ?>
<tr>
	<td><?php echo $profid; ?></td>
	<td><?php echo $soc->ape; ?>
	<?php if ($soc->ape) {
			if ($soc->id_prof_check(3,$soc) > 0) print ' &nbsp; '.$soc->id_prof_url(3,$soc);
			else print ' <font class="error">('.$langs->trans("ErrorWrongValue").')</font>';
	} ?>
	</td>
<?php } else { ?>
<tr>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
<?php } ?>

<?php $profid=$langs->transcountry('ProfId4',$soc->pays_code); ?>
<?php if ($profid!='-') { ?>
	<td><?php echo $profid; ?></td>
	<td><?php echo $soc->idprof4; ?>
	<?php if ($soc->idprof4) {
			if ($soc->id_prof_check(4,$soc) > 0) print ' &nbsp; '.$soc->id_prof_url(4,$soc);
			else print ' <font class="error">('.$langs->trans("ErrorWrongValue").')</font>';
	} ?>
	</td>
</tr>
<?php } else { ?>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
</tr>
<?php } ?>

<tr>
	<td><?php echo $langs->trans('VATIsUsed'); ?></td>
	<td><?php echo yn($soc->tva_assuj); ?></td>

<?php if ($conf->use_javascript_ajax) { ?>
<script language="JavaScript" type="text/javascript">
function CheckVAT(a) {
	newpopup('<?php echo DOL_URL_ROOT; ?>/societe/checkvat/checkVatPopup.php?vatNumber='+a,'<?php echo dol_escape_js($langs->trans("VATIntraCheckableOnEUSite")); ?>',500,260);
}
</script>
<?php } ?>

	<td nowrap="nowrap"><?php echo $langs->trans('VATIntra'); ?></td>
	<td>
	<?php if ($soc->tva_intra) {
		$s='';
		$s.=$soc->tva_intra;
		$s.='<input type="hidden" name="tva_intra" size="12" maxlength="20" value="'.$soc->tva_intra.'">';
		$s.=' &nbsp; ';
		if ($conf->use_javascript_ajax)	{
			$s.='<a href="#" onclick="javascript: CheckVAT(document.formsoc.tva_intra.value);">'.$langs->trans("VATIntraCheck").'</a>';
			echo $form->textwithpicto($s,$langs->trans("VATIntraCheckDesc",$langs->trans("VATIntraCheck")),1);
		} else {
			echo $s.'<a href="'.$langs->transcountry("VATIntraCheckURL",$soc->id_pays).'" target="_blank">'.img_picto($langs->trans("VATIntraCheckableOnEUSite"),'help').'</a>';
		}
	} else {
		echo '&nbsp;';
	} ?>
	</td>
</tr>

<?php if($mysoc->pays_code=='ES') {
		if($mysoc->localtax1_assuj=="1" && $mysoc->localtax2_assuj=="1") { ?>
<tr>
	<td><?php echo $langs->trans("LocalTax1IsUsedES"); ?></td>
	<td><?php echo yn($soc->localtax1_assuj); ?></td>
	<td><?php echo $langs->trans("LocalTax2IsUsedES"); ?></td>
	<td><?php echo yn($soc->localtax2_assuj); ?></td>
</tr>
		<?php }	elseif($mysoc->localtax1_assuj=="1") { ?>
<tr>
	<td><?php echo $langs->trans("LocalTax1IsUsedES"); ?></td>
	<td colspan="3"><?php echo yn($soc->localtax1_assuj); ?></td>
<tr>
		<?php }	elseif($mysoc->localtax2_assuj=="1") { ?>
<tr>
	<td><?php echo $langs->trans("LocalTax2IsUsedES"); ?></td>
	<td colspan="3"><?php echo yn($soc->localtax2_assuj); ?></td>
<tr>
<?php } } ?>

<tr>
	<td><?php echo $langs->trans('Capital'); ?></td>
	<td colspan="3">
	<?php
	if ($soc->capital) echo $soc->capital.' '.$langs->trans("Currency".$conf->monnaie);
	else echo '&nbsp;';
	?>
	</td>
</tr>

<tr>
	<td><?php echo $langs->trans('JuridicalStatus'); ?></td>
	<td colspan="3"><?php echo $soc->forme_juridique; ?></td>
</tr>

<?php
$arr = $formcompany->typent_array(1);
$soc->typent= $arr[$soc->typent_code];
?>
<tr>
	<td><?php echo $langs->trans("Type"); ?></td>
	<td><?php echo $soc->typent; ?></td>
	<td><?php echo $langs->trans("Staff"); ?></td>
	<td><?php echo $soc->effectif; ?></td>
</tr>

<?php
if ($conf->global->MAIN_MULTILANGS)
{
	require_once(DOL_DOCUMENT_ROOT."/lib/functions2.lib.php");
	echo '<tr><td>'.$langs->trans("DefaultLang").'</td><td colspan="3">';
	//$s=picto_from_langcode($soc->default_lang);
	//print ($s?$s.' ':'');
	$langs->load("languages");
	$labellang = ($soc->default_lang?$langs->trans('Language_'.$soc->default_lang):'');
	echo $labellang;
	echo '</td></tr>';
}
?>

<tr>
	<td>
	<table width="100%" class="nobordernopadding">
		<tr>
			<td><?php echo $langs->trans('RIB'); ?></td>
			<td align="right">
			<?php if ($user->rights->societe->creer) { ?>
			<a href="<?php echo DOL_URL_ROOT.'/societe/rib.php?socid='.$soc->id; ?>"><?php echo img_edit(); ?></a>
			<?php } else { ?>
			&nbsp;
			<?php } ?>
			</td>
		</tr>
	</table>
	</td>
	<td colspan="3"><?php echo $soc->display_rib(); ?></td>
</tr>

<tr>
	<td>
	<table width="100%" class="nobordernopadding">
		<tr>
			<td><?php echo $langs->trans('ParentCompany'); ?></td>
			<td align="right">
			<?php if ($user->rights->societe->creer) { ?>
			<a href="<?php echo DOL_URL_ROOT.'/societe/lien.php?socid='.$soc->id; ?>"><?php echo img_edit(); ?></a>
			<?php } else { ?>
			&nbsp;
			<?php } ?>
			</td>
		</tr>
	</table>
	</td>
	<td colspan="3">
	<?php if ($soc->parent) {
		$socm = new Societe($db);
		$socm->fetch($soc->parent);
		echo $socm->getNomUrl(1).' '.($socm->code_client?"(".$socm->code_client.")":"");
		echo $socm->ville?' - '.$socm->ville:'';
	} else {
		echo $langs->trans("NoParentCompany");
	} ?>
	</td>
</tr>

<tr>
	<td>
	<table width="100%" class="nobordernopadding">
		<tr>
			<td><?php echo $langs->trans('SalesRepresentatives'); ?></td>
			<td align="right">
			<?php if ($user->rights->societe->creer) { ?>
			<a href="<?php echo DOL_URL_ROOT.'/societe/commerciaux.php?socid='.$soc->id; ?>"><?php echo img_edit(); ?></a>
			<?php } else { ?>
			&nbsp;
			<?php } ?>
			</td>
		</tr>
	</table>
	</td>
	<td colspan="3">
	<?php
	$sql = "SELECT count(sc.rowid) as nb";
	$sql.= " FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql.= " WHERE sc.fk_soc =".$soc->id;

	$resql = $db->query($sql);
	if ($resql)	{
		$num = $db->num_rows($resql);
		$obj = $db->fetch_object($resql);
		echo $obj->nb?($obj->nb):$langs->trans("NoSalesRepresentativeAffected");
	} else {
		dol_print_error($db);
	}
	?>
	</td>
</tr>

<?php
if ($conf->adherent->enabled) {
	$langs->load("members");
	echo '<tr><td width="25%" valign="top">'.$langs->trans("LinkedToDolibarrMember").'</td>';
	echo '<td colspan="3">';
	$adh=new Adherent($db);
	$result=$adh->fetch('','',$soc->id);
	if ($result > 0) {
		$adh->ref=$adh->getFullName($langs);
		echo $adh->getNomUrl(1);
	} else {
		echo $langs->trans("UserNotLinkedToMember");
	}
	echo '</td>';
	echo "</tr>\n";
}
?>

</table>
</form>

</div>

<!-- END PHP TEMPLATE -->