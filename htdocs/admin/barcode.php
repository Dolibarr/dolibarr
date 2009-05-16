<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
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
 */

/**
 *	\file       htdocs/admin/barcode.php
 *	\ingroup    barcode
 *	\brief      Page d'administration/configuration du module Code barre
 *	\version    $Id$
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT."/includes/barcode/html.formbarcode.class.php");

$langs->load("admin");

if (!$user->admin)
accessforbidden();

if ($_POST["action"] == 'setcoder')
{
	$sqlp = "UPDATE ".MAIN_DB_PREFIX."c_barcode_type";
	$sqlp.= " SET coder = '" . $_POST["coder"]."'";
	$sqlp.= " WHERE rowid = ". $_POST["code_id"];
	$sqlp.= " AND entity = ".$conf->entity;

	$resql=$db->query($sqlp);
	//print $sqlp;
}
else if ($_POST["action"] == 'setgenbarcodelocation')
{
	dolibarr_set_const($db, "GENBARCODE_LOCATION",$_POST["genbarcodelocation"],'chaine',0,'',$conf->entity);
}
else if ($_POST["action"] == 'setdefaultbarcodetype')
{
	dolibarr_set_const($db, "PRODUIT_DEFAULT_BARCODE_TYPE", $_POST["coder_id"],'chaine',0,'',$conf->entity);
}
else if ($_POST["action"] == 'GENBARCODE_BARCODETYPE_THIRDPARTY')
{
	dolibarr_set_const($db, "GENBARCODE_BARCODETYPE_THIRDPARTY", $_POST["coder_id"],'chaine',0,'',$conf->entity);
}
/*
 else if ($_POST["action"] == 'setproductusebarcode')
 {
 dolibarr_set_const($db, "PRODUIT_USE_BARCODE",$_POST["value"],'chaine',0,'',$conf->entity);
 Header("Location: barcode.php");
 exit;
 }
 */


/*
 * Actions
 */

$html = new Form($db);
$formbarcode = new FormBarCode($db);

llxHeader('',$langs->trans("BarcodeSetup"),'BarcodeConfiguration');

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("BarcodeSetup"),$linkback,'setup');

// Detect bar codes modules
$barcodelist=array();

clearstatcache();


foreach ($conf->file->dol_document_root as $dirroot)
{
	$dir = $dirroot . "/includes/modules/barcode/";

	$handle=@opendir($dir);
	if ($handle)
	{
		while (($file = readdir($handle))!==false)
		{
			if (substr($file, 0, 1) <> '.' && substr($file, 0, 3) <> 'CVS')
			{
				if (is_readable($dir.$file))
				{
					if (eregi('(.*)\.modules\.php$',$file,$reg))
					{
						$filebis=$reg[1];

						// Chargement de la classe de codage
						require_once($dir.$file);
						$classname = "mod".ucfirst($filebis);
						$module = new $classname($db);

						// Show modules according to features level
						if ($module->version == 'development'  && $conf->global->MAIN_FEATURES_LEVEL < 2) continue;
						if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) continue;

						if ($module->isEnabled())
						{
							$barcodelist[$filebis]=$module->info();
						}
					}
				}
			}
		}
	}
}

/*
 *  CHOIX ENCODAGE
 */
$var=true;

print '<br>';
print_titre($langs->trans("BarcodeEncodeModule"));

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td width="200" align="center">'.$langs->trans("Example").'</td>';
print '<td align="center" width="60">'.$langs->trans("CodeBarGenerator").'</td>';
print "</tr>\n";

$sql = "SELECT rowid, code as encoding, libelle, coder, example";
$sql.= " FROM ".MAIN_DB_PREFIX."c_barcode_type";
$sql.= " WHERE entity = ".$conf->entity;

dol_syslog("admin/barcode.php sql=".$sql);
$resql=$db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;
	$var=true;

	while ($i <	$num)
	{
		$obj = $db->fetch_object($resql);

		print '<tr '.$bc[$var].'><td width="100">';
		print $obj->libelle;
		print "</td><td>\n";
		print $langs->trans('BarcodeDesc'.$obj->encoding);
		//print "L'EAN se compose de 8 caracteres, 7 chiffres plus une cle de controle.<br>";
		//print "L'utilisation des symbologies EAN8 impose la souscription et l'abonnement aupres d'organisme tel que GENCOD.<br>";
		//print "Codes numeriques utilises exclusivement a l'identification des produits susceptibles d'etre vendus au grand public.";
		print '</td>';

		// Show example
		print '<td align="center">';
		if ($obj->coder && $obj->coder != -1)
		{
			// Chargement de la classe de codage
			foreach ($conf->dol_document_root as $dirroot)
			{
				$dir=$dirroot . "/includes/modules/barcode/";
				$result=@include_once($dir.$obj->coder.".modules.php");
				if ($result) break;
			}
			if ($result)
			{
				$classname = "mod".ucfirst($obj->coder);
				$module = new $classname($db);

				if ($module->encodingIsSupported($obj->encoding))
				{
					// Build barcode on disk (not used, this is done to make debug easier)
					$result=$module->writeBarCode($obj->example,$obj->encoding,'Y');

					// Generate on the fly and output barcode with generator
					$url=DOL_URL_ROOT.'/viewimage.php?modulepart=barcode&generator='.urlencode($obj->coder).'&code='.urlencode($obj->example).'&encoding='.urlencode($obj->encoding);
					//print $url;
					print '<img src="'.$url.'" title="'.$obj->example.'" border="0">';
				}
				else
				{
					print $langs->trans("FormatNotSupportedByGenerator");
				}
			}
		}
		else
		{
			print $langs->trans("ChooseABarCode");
		}
		print '</td>';

		print '<td align="center">';
		print $formbarcode->setBarcodeEncoder($obj->coder,$barcodelist,$obj->rowid,'form'.$i);
		print "</td></tr>\n";
		$var=!$var;
		$i++;
	}
}
print "</table>\n";

print "<br>";

/*
 * Autres options
 *
 */
print_titre($langs->trans("OtherOptions"));

$var=true;
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameter").'</td>';
print '<td width="60" align="center">'.$langs->trans("Value").'</td>';
print '<td>&nbsp;</td>';
print '</tr>';

// Chemin du binaire genbarcode sous linux
if (!isset($_ENV['windir']) && !file_exists($_ENV['windir']))
{
	$var=!$var;
	print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token_level_1" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="setgenbarcodelocation">';
	print '<tr '.$bc[$var].'>';
	print '<td>'.$langs->trans("GenbarcodeLocation").'</td>';
	print '<td width="60" align="center">';
	print '<input type="text" size="40" name="genbarcodelocation" value="'.$conf->global->GENBARCODE_LOCATION.'">';
	if (! empty($conf->global->GENBARCODE_LOCATION) && ! file_exists($conf->global->GENBARCODE_LOCATION))
	{
		$langs->load("errors");
		print '<br><font class="error">'.$langs->trans("ErrorGenbarCodeNotfound").'</font>';
	}
	print '</td>';
	print '<td width="60" align="center"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
	print '</tr>';
	print '</form>';
}

// Module produits
if ($conf->societe->enabled)
{
	$var=!$var;
	print "<form method=\"post\" action=\"".$_SERVER["PHP_SELF"]."\">";
	print '<input type="hidden" name="token_level_1" value="'.$_SESSION['newtoken'].'">';
	print "<input type=\"hidden\" name=\"action\" value=\"setdefaultbarcodetype\">";
	print "<tr ".$bc[$var].">";
	print '<td>'.$langs->trans("SetDefaultBarcodeTypeProducts").'</td>';
	print '<td width="60" align="right">';
	print $formbarcode->select_barcode_type($conf->global->PRODUIT_DEFAULT_BARCODE_TYPE,"coder_id",1);
	print '</td><td align="right">';
	print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
	print "</td>";
	print '</tr>';
	print '</form>';
}

// Module produits
if ($conf->produit->enabled)
{
	$var=!$var;
	print "<form method=\"post\" action=\"".$_SERVER["PHP_SELF"]."\">";
	print '<input type="hidden" name="token_level_1" value="'.$_SESSION['newtoken'].'">';
	print "<input type=\"hidden\" name=\"action\" value=\"GENBARCODE_BARCODETYPE_THIRDPARTY\">";
	print "<tr ".$bc[$var].">";
	print '<td>'.$langs->trans("SetDefaultBarcodeTypeThirdParties").'</td>';
	print '<td width="60" align="right">';
	print $formbarcode->select_barcode_type($conf->global->GENBARCODE_BARCODETYPE_THIRDPARTY,"coder_id",1);
	print '</td><td align="right">';
	print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
	print "</td>";
	print '</tr>';
	print '</form>';
}

print '</table>';

print "<br>";

$db->close();

llxFooter('$Date$ - $Revision$');
?>