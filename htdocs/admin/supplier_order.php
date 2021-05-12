<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur     <eldy@users.sourceforge.net>
<<<<<<< HEAD
 * Copyright (C) 2005-2011 Regis Houssin           <regis.houssin@capnetworks.com>
=======
 * Copyright (C) 2005-2011 Regis Houssin           <regis.houssin@inodbox.com>
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
 * Copyright (C) 2004      Sebastien Di Cintio     <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier          <benoit.mortier@opensides.be>
 * Copyright (C) 2010-2013 Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2011-2018 Philippe Grand          <philippe.grand@atoo-net.com>
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

/**
 *  \file       htdocs/admin/supplier_order.php
 *  \ingroup    fournisseur
 *  \brief      Page d'administration-configuration du module Fournisseur
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/fourn.lib.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';

// Load translation files required by the page
$langs->loadLangs(array("admin", "other", "orders"));

if (!$user->admin)
accessforbidden();

$type=GETPOST('type', 'alpha');
$value=GETPOST('value', 'alpha');
<<<<<<< HEAD
$label = GETPOST('label','alpha');
$action=GETPOST('action', 'alpha');
$scandir = GETPOST('scan_dir','alpha');
=======
$label = GETPOST('label', 'alpha');
$action=GETPOST('action', 'alpha');
$scandir = GETPOST('scan_dir', 'alpha');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

$specimenthirdparty=new Societe($db);
$specimenthirdparty->initAsSpecimen();


/*
 * Actions
 */

if ($action == 'updateMask')
{
<<<<<<< HEAD
    $maskconstorder=GETPOST('maskconstorder','alpha');
    $maskvalue=GETPOST('maskorder','alpha');

    if ($maskconstorder)  $res = dolibarr_set_const($db,$maskconstorder,$maskvalue,'chaine',0,'',$conf->entity);
=======
    $maskconstorder=GETPOST('maskconstorder', 'alpha');
    $maskvalue=GETPOST('maskorder', 'alpha');

    if ($maskconstorder)  $res = dolibarr_set_const($db, $maskconstorder, $maskvalue, 'chaine', 0, '', $conf->entity);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

    if (! $res > 0) $error++;

	if (! $error)
	{
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	}
	else
	{
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
}

<<<<<<< HEAD
else if ($action == 'specimen')  // For orders
{
    $modele=GETPOST('module','alpha');
=======
elseif ($action == 'specimen')  // For orders
{
    $modele=GETPOST('module', 'alpha');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

    $commande = new CommandeFournisseur($db);
    $commande->initAsSpecimen();
    $commande->thirdparty=$specimenthirdparty;

    // Search template files
    $file=''; $classname=''; $filefound=0;
<<<<<<< HEAD
    $dirmodels=array_merge(array('/'),(array) $conf->modules_parts['models']);
    foreach($dirmodels as $reldir)
    {
    	$file=dol_buildpath($reldir."core/modules/supplier_order/pdf/pdf_".$modele.".modules.php",0);
=======
    $dirmodels=array_merge(array('/'), (array) $conf->modules_parts['models']);
    foreach($dirmodels as $reldir)
    {
    	$file=dol_buildpath($reldir."core/modules/supplier_order/pdf/pdf_".$modele.".modules.php", 0);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    	if (file_exists($file))
    	{
    		$filefound=1;
    		$classname = "pdf_".$modele;
    		break;
    	}
    }

    if ($filefound)
    {
    	require_once $file;

<<<<<<< HEAD
    	$module = new $classname($db,$commande);

    	if ($module->write_file($commande,$langs) > 0)
=======
    	$module = new $classname($db, $commande);

    	if ($module->write_file($commande, $langs) > 0)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    	{
    		header("Location: ".DOL_URL_ROOT."/document.php?modulepart=commande_fournisseur&file=SPECIMEN.pdf");
    		return;
    	}
    	else
    	{
    		setEventMessages($module->error, $module->errors, 'errors');
    		dol_syslog($module->error, LOG_ERR);
    	}
    }
    else
    {
    	setEventMessages($langs->trans("ErrorModuleNotFound"), null, 'errors');
    	dol_syslog($langs->trans("ErrorModuleNotFound"), LOG_ERR);
    }
}

// Activate a model
<<<<<<< HEAD
else if ($action == 'set')
=======
elseif ($action == 'set')
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
{
	$ret = addDocumentModel($value, $type, $label, $scandir);
}

<<<<<<< HEAD
else if ($action == 'del')
=======
elseif ($action == 'del')
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
{
	$ret = delDocumentModel($value, $type);
	if ($ret > 0)
	{
<<<<<<< HEAD
        if ($conf->global->COMMANDE_SUPPLIER_ADDON_PDF == "$value") dolibarr_del_const($db, 'COMMANDE_SUPPLIER_ADDON_PDF',$conf->entity);
=======
        if ($conf->global->COMMANDE_SUPPLIER_ADDON_PDF == "$value") dolibarr_del_const($db, 'COMMANDE_SUPPLIER_ADDON_PDF', $conf->entity);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	}
}

// Set default model
<<<<<<< HEAD
else if ($action == 'setdoc')
{
	if (dolibarr_set_const($db, "COMMANDE_SUPPLIER_ADDON_PDF",$value,'chaine',0,'',$conf->entity))
=======
elseif ($action == 'setdoc')
{
	if (dolibarr_set_const($db, "COMMANDE_SUPPLIER_ADDON_PDF", $value, 'chaine', 0, '', $conf->entity))
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		// La constante qui a ete lue en avant du nouveau set
		// on passe donc par une variable pour avoir un affichage coherent
		$conf->global->COMMANDE_SUPPLIER_ADDON_PDF = $value;
	}

	// On active le modele
	$ret = delDocumentModel($value, $type);
	if ($ret > 0)
	{
		$ret = addDocumentModel($value, $type, $label, $scandir);
	}
}

<<<<<<< HEAD
else if ($action == 'setmod')
=======
elseif ($action == 'setmod')
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
{
    // TODO Verifier si module numerotation choisi peut etre active
    // par appel methode canBeActivated

<<<<<<< HEAD
    dolibarr_set_const($db, "COMMANDE_SUPPLIER_ADDON_NUMBER",$value,'chaine',0,'',$conf->entity);
}

else if ($action == 'addcat')
{
    $fourn = new Fournisseur($db);
    $fourn->CreateCategory($user,$_POST["cat"]);
}

else if ($action == 'set_SUPPLIER_ORDER_OTHER')
{
    $freetext = GETPOST('SUPPLIER_ORDER_FREE_TEXT','none');	// No alpha here, we want exact string
	$doubleapproval = GETPOST('SUPPLIER_ORDER_3_STEPS_TO_BE_APPROVED','alpha');
	$doubleapproval = price2num($doubleapproval );

    $res1 = dolibarr_set_const($db, "SUPPLIER_ORDER_FREE_TEXT",$freetext,'chaine',0,'',$conf->entity);
    $res2 = dolibarr_set_const($db, "SUPPLIER_ORDER_3_STEPS_TO_BE_APPROVED",$doubleapproval,'chaine',0,'',$conf->entity);
=======
    dolibarr_set_const($db, "COMMANDE_SUPPLIER_ADDON_NUMBER", $value, 'chaine', 0, '', $conf->entity);
}

elseif ($action == 'addcat')
{
    $fourn = new Fournisseur($db);
    $fourn->CreateCategory($user, $_POST["cat"]);
}

elseif ($action == 'set_SUPPLIER_ORDER_OTHER')
{
    $freetext = GETPOST('SUPPLIER_ORDER_FREE_TEXT', 'none');	// No alpha here, we want exact string
	$doubleapproval = GETPOST('SUPPLIER_ORDER_3_STEPS_TO_BE_APPROVED', 'alpha');
	$doubleapproval = price2num($doubleapproval);

    $res1 = dolibarr_set_const($db, "SUPPLIER_ORDER_FREE_TEXT", $freetext, 'chaine', 0, '', $conf->entity);
    $res2 = dolibarr_set_const($db, "SUPPLIER_ORDER_3_STEPS_TO_BE_APPROVED", $doubleapproval, 'chaine', 0, '', $conf->entity);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

    // TODO We add/delete permission here until permission can have a condition on a global var
    include_once DOL_DOCUMENT_ROOT.'/core/modules/modFournisseur.class.php';
    $newmodule=new modFournisseur($db);
<<<<<<< HEAD
    
=======

>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    if ($conf->global->SUPPLIER_ORDER_3_STEPS_TO_BE_APPROVED)
    {
    	// clear default rights array
    	$newmodule->rights=array();
    	// add new right
    	$r=0;
    	$newmodule->rights[$r][0] = 1190;
    	$newmodule->rights[$r][1] = $langs->trans("Permission1190");
    	$newmodule->rights[$r][2] = 'w';
    	$newmodule->rights[$r][3] = 0;
    	$newmodule->rights[$r][4] = 'commande';
    	$newmodule->rights[$r][5] = 'approve2';
<<<<<<< HEAD
    	
=======

>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    	// Insert
    	$newmodule->insert_permissions(1);
    }
    else
    {
    	// Remove all rights with Permission1190
    	$newmodule->delete_permissions();
<<<<<<< HEAD
    	
=======

>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    	// Add all right without Permission1190
    	$newmodule->insert_permissions(1);
    }
}

// Activate ask for payment bank
<<<<<<< HEAD
else if ($action == 'set_BANK_ASK_PAYMENT_BANK_DURING_SUPPLIER_ORDER')
{
    $res = dolibarr_set_const($db, "BANK_ASK_PAYMENT_BANK_DURING_SUPPLIER_ORDER",$value,'chaine',0,'',$conf->entity);
=======
elseif ($action == 'set_BANK_ASK_PAYMENT_BANK_DURING_SUPPLIER_ORDER')
{
    $res = dolibarr_set_const($db, "BANK_ASK_PAYMENT_BANK_DURING_SUPPLIER_ORDER", $value, 'chaine', 0, '', $conf->entity);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

    if (! $res > 0) $error++;

    if (! $error)
    {
        setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
    }
    else
    {
        setEventMessages($langs->trans("Error"), null, 'errors');
    }
}


/*
 * View
 */

$form=new Form($db);

<<<<<<< HEAD
$dirmodels=array_merge(array('/'),(array) $conf->modules_parts['models']);

llxHeader("","");

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("SuppliersSetup"),$linkback,'title_setup');
=======
$dirmodels=array_merge(array('/'), (array) $conf->modules_parts['models']);

llxHeader("", "");

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("SuppliersSetup"), $linkback, 'title_setup');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

print "<br>";

$head = supplierorder_admin_prepare_head();

dol_fiche_head($head, 'order', $langs->trans("Suppliers"), -1, 'company');


// Supplier order numbering module

<<<<<<< HEAD
print load_fiche_titre($langs->trans("OrdersNumberingModules"),'','');
=======
print load_fiche_titre($langs->trans("OrdersNumberingModules"), '', '');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td width="100">'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td>'.$langs->trans("Example").'</td>';
print '<td align="center" width="60">'.$langs->trans("Status").'</td>';
print '<td align="center" width="16">'.$langs->trans("ShortInfo").'</td>';
print "</tr>\n";

clearstatcache();

foreach ($dirmodels as $reldir)
{
	$dir = dol_buildpath($reldir."core/modules/supplier_order/");

    if (is_dir($dir))
    {
        $handle = opendir($dir);
        if (is_resource($handle))
        {

            while (($file = readdir($handle))!==false)
            {
                if (substr($file, 0, 25) == 'mod_commande_fournisseur_' && substr($file, dol_strlen($file)-3, 3) == 'php')
                {
                    $file = substr($file, 0, dol_strlen($file)-4);

                    require_once $dir.$file.'.php';

                    $module = new $file;

                    if ($module->isEnabled())
                    {
                        // Show modules according to features level
                        if ($module->version == 'development'  && $conf->global->MAIN_FEATURES_LEVEL < 2) continue;
                        if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) continue;


                        print '<tr class="oddeven"><td>'.$module->nom."</td><td>\n";
                        print $module->info();
                        print '</td>';

                        // Show example of numbering module
                        print '<td class="nowrap">';
                        $tmp=$module->getExample();
<<<<<<< HEAD
                        if (preg_match('/^Error/',$tmp)) {
=======
                        if (preg_match('/^Error/', $tmp)) {
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
                            $langs->load("errors"); print '<div class="error">'.$langs->trans($tmp).'</div>';
                        }
                        elseif ($tmp=='NotConfigured') print $langs->trans($tmp);
                        else print $tmp;
                        print '</td>'."\n";

                        print '<td align="center">';
                        if ($conf->global->COMMANDE_SUPPLIER_ADDON_NUMBER == "$file")
                        {
<<<<<<< HEAD
                            print img_picto($langs->trans("Activated"),'switch_on');
                        }
                        else
                        {
                            print '<a href="'.$_SERVER["PHP_SELF"].'?action=setmod&amp;value='.$file.'" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"),'switch_off').'</a>';
=======
                            print img_picto($langs->trans("Activated"), 'switch_on');
                        }
                        else
                        {
                            print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=setmod&amp;value='.$file.'" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
                        }
                        print '</td>';

                        $commande=new CommandeFournisseur($db);
                        $commande->initAsSpecimen();

                        // Info
                        $htmltooltip='';
                        $htmltooltip.=''.$langs->trans("Version").': <b>'.$module->getVersion().'</b><br>';
<<<<<<< HEAD
                        $nextval=$module->getNextValue($mysoc,$commande);
                        if ("$nextval" != $langs->trans("NotAvailable")) {  // Keep " on nextval
                            $htmltooltip.=''.$langs->trans("NextValue").': ';
                            if ($nextval) {
                                if (preg_match('/^Error/',$nextval) || $nextval=='NotConfigured')
=======
                        $nextval=$module->getNextValue($mysoc, $commande);
                        if ("$nextval" != $langs->trans("NotAvailable")) {  // Keep " on nextval
                            $htmltooltip.=''.$langs->trans("NextValue").': ';
                            if ($nextval) {
                                if (preg_match('/^Error/', $nextval) || $nextval=='NotConfigured')
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
                                    $nextval = $langs->trans($nextval);
                                $htmltooltip.=$nextval.'<br>';
                            } else {
                                $htmltooltip.=$langs->trans($module->error).'<br>';
                            }
                        }

                        print '<td align="center">';
<<<<<<< HEAD
                        print $form->textwithpicto('',$htmltooltip,1,0);
=======
                        print $form->textwithpicto('', $htmltooltip, 1, 0);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
                        print '</td>';

                        print '</tr>';
                    }
                }
            }
            closedir($handle);
        }
    }
}

print '</table><br>';


/*
 *  Documents models for supplier orders
 */

<<<<<<< HEAD
print load_fiche_titre($langs->trans("OrdersModelModule"),'','');
=======
print load_fiche_titre($langs->trans("OrdersModelModule"), '', '');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

// Defini tableau def de modele
$def = array();

$sql = "SELECT nom";
$sql.= " FROM ".MAIN_DB_PREFIX."document_model";
$sql.= " WHERE type = 'order_supplier'";
$sql.= " AND entity = ".$conf->entity;

$resql=$db->query($sql);
if ($resql)
{
    $i = 0;
    $num_rows=$db->num_rows($resql);
    while ($i < $num_rows)
    {
        $array = $db->fetch_array($resql);
        array_push($def, $array[0]);
        $i++;
    }
}
else
{
    dol_print_error($db);
}

print '<table class="noborder" width="100%">'."\n";
print '<tr class="liste_titre">'."\n";
print '<td width="100">'.$langs->trans("Name").'</td>'."\n";
print '<td>'.$langs->trans("Description").'</td>'."\n";
print '<td align="center" width="60">'.$langs->trans("Status").'</td>'."\n";
print '<td align="center" width="60">'.$langs->trans("Default").'</td>'."\n";
print '<td align="center" width="40">'.$langs->trans("ShortInfo").'</td>';
print '<td align="center" width="40">'.$langs->trans("Preview").'</td>';
print '</tr>'."\n";

clearstatcache();

foreach ($dirmodels as $reldir)
{
	$dir = dol_buildpath($reldir."core/modules/supplier_order/pdf/");

    if (is_dir($dir))
    {
        $handle=opendir($dir);
        if (is_resource($handle))
        {
            while (($file = readdir($handle))!==false)
            {
<<<<<<< HEAD
                if (preg_match('/\.modules\.php$/i',$file) && preg_match('/^(pdf_|doc_)/',$file))
=======
                if (preg_match('/\.modules\.php$/i', $file) && preg_match('/^(pdf_|doc_)/', $file))
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
                {
                    $name = substr($file, 4, dol_strlen($file) -16);
                    $classname = substr($file, 0, dol_strlen($file) -12);

	                require_once $dir.'/'.$file;
	                $module = new $classname($db, new CommandeFournisseur($db));


                    print "<tr class=\"oddeven\">\n";
                    print "<td>";
	                print (empty($module->name)?$name:$module->name);
	                print "</td>\n";
                    print "<td>\n";
                    require_once $dir.$file;
<<<<<<< HEAD
                    $module = new $classname($db,$specimenthirdparty);
		    if (method_exists($module,'info')) print $module->info($langs);
=======
                    $module = new $classname($db, $specimenthirdparty);
		    if (method_exists($module, 'info')) print $module->info($langs);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	            else print $module->description;
                    print "</td>\n";

                    // Active
                    if (in_array($name, $def))
                    {
                        print '<td align="center">'."\n";
                        if ($conf->global->COMMANDE_SUPPLIER_ADDON_PDF != "$name")
                        {
                            print '<a href="'.$_SERVER["PHP_SELF"].'?action=del&amp;value='.$name.'&amp;scan_dir='.$module->scandir.'&amp;label='.urlencode($module->name).'&amp;type=order_supplier">';
<<<<<<< HEAD
                            print img_picto($langs->trans("Enabled"),'switch_on');
=======
                            print img_picto($langs->trans("Enabled"), 'switch_on');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
                            print '</a>';
                        }
                        else
                        {
<<<<<<< HEAD
                            print img_picto($langs->trans("Enabled"),'switch_on');
=======
                            print img_picto($langs->trans("Enabled"), 'switch_on');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
                        }
                        print "</td>";
                    }
                    else
                    {
                        print '<td align="center">'."\n";
<<<<<<< HEAD
                        print '<a href="'.$_SERVER["PHP_SELF"].'?action=set&amp;value='.$name.'&amp;scan_dir='.$module->scandir.'&amp;label='.urlencode($module->name).'&amp;type=order_supplier">'.img_picto($langs->trans("Disabled"),'switch_off').'</a>';
=======
                        print '<a href="'.$_SERVER["PHP_SELF"].'?action=set&amp;value='.$name.'&amp;scan_dir='.$module->scandir.'&amp;label='.urlencode($module->name).'&amp;type=order_supplier">'.img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
                        print "</td>";
                    }

                    // Default
                    print '<td align="center">';
                    if ($conf->global->COMMANDE_SUPPLIER_ADDON_PDF == "$name")
                    {
<<<<<<< HEAD
                        print img_picto($langs->trans("Default"),'on');
                    }
                    else
                    {
                        print '<a href="'.$_SERVER["PHP_SELF"].'?action=setdoc&amp;value='.$name.'&amp;scan_dir='.$module->scandir.'&amp;label='.urlencode($module->name).'&amp;type=order_supplier"" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"),'off').'</a>';
=======
                        print img_picto($langs->trans("Default"), 'on');
                    }
                    else
                    {
                        print '<a href="'.$_SERVER["PHP_SELF"].'?action=setdoc&amp;value='.$name.'&amp;scan_dir='.$module->scandir.'&amp;label='.urlencode($module->name).'&amp;type=order_supplier"" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"), 'off').'</a>';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
                    }
                    print '</td>';

                    // Info
                    $htmltooltip =    ''.$langs->trans("Name").': '.$module->name;
                    $htmltooltip.='<br>'.$langs->trans("Type").': '.($module->type?$module->type:$langs->trans("Unknown"));
                    $htmltooltip.='<br>'.$langs->trans("Width").'/'.$langs->trans("Height").': '.$module->page_largeur.'/'.$module->page_hauteur;
                    $htmltooltip.='<br><br><u>'.$langs->trans("FeaturesSupported").':</u>';
<<<<<<< HEAD
                    $htmltooltip.='<br>'.$langs->trans("Logo").': '.yn($module->option_logo,1,1);
                    $htmltooltip.='<br>'.$langs->trans("PaymentMode").': '.yn($module->option_modereg,1,1);
                    $htmltooltip.='<br>'.$langs->trans("PaymentConditions").': '.yn($module->option_condreg,1,1);
                    print '<td align="center">';
                    print $form->textwithpicto('',$htmltooltip,1,0);
                    print '</td>';
                    print '<td align="center">';
                    print '<a href="'.$_SERVER["PHP_SELF"].'?action=specimen&amp;module='.$name.'">'.img_object($langs->trans("Preview"),'order').'</a>';
=======
                    $htmltooltip.='<br>'.$langs->trans("Logo").': '.yn($module->option_logo, 1, 1);
                    $htmltooltip.='<br>'.$langs->trans("PaymentMode").': '.yn($module->option_modereg, 1, 1);
                    $htmltooltip.='<br>'.$langs->trans("PaymentConditions").': '.yn($module->option_condreg, 1, 1);
                    print '<td align="center">';
                    print $form->textwithpicto('', $htmltooltip, 1, 0);
                    print '</td>';
                    print '<td align="center">';
                    print '<a href="'.$_SERVER["PHP_SELF"].'?action=specimen&amp;module='.$name.'">'.img_object($langs->trans("Preview"), 'order').'</a>';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
                    print '</td>';

                    print "</tr>\n";
                }
            }

            closedir($handle);
        }
    }
}

print '</table><br>';

/*
 * Other options
 */

print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_SUPPLIER_ORDER_OTHER">';

<<<<<<< HEAD
print load_fiche_titre($langs->trans("OtherOptions"),'','');
=======
print load_fiche_titre($langs->trans("OtherOptions"), '', '');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameter").'</td>';
print '<td align="center" width="60">'.$langs->trans("Value").'</td>';
print '<td width="80">&nbsp;</td>';
print "</tr>\n";
$var=false;

//if ($conf->global->MAIN_FEATURES_LEVEL > 0)
//{
	print '<tr class="oddeven"><td>';
	print $form->textwithpicto($langs->trans("UseDoubleApproval"), $langs->trans("Use3StepsApproval"), 1, 'help').'<br>';
	print $langs->trans("IfSetToYesDontForgetPermission");
	print '</td><td>';
	print '<input type="text" size="6" name="SUPPLIER_ORDER_3_STEPS_TO_BE_APPROVED" value="'.$conf->global->SUPPLIER_ORDER_3_STEPS_TO_BE_APPROVED.'">';
<<<<<<< HEAD
	print '</td><td align="right">';
=======
	print '</td><td class="right">';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
	print "</td></tr>\n";

//}

// Ask for payment bank during supplier order
/* Kept as hidden for the moment
if ($conf->banque->enabled)
{

    print '<tr class="oddeven"><td>';
    print $langs->trans("BANK_ASK_PAYMENT_BANK_DURING_SUPPLIER_ORDER").'</td><td>&nbsp</td><td align="center">';
    if (! empty($conf->use_javascript_ajax))
    {
        print ajax_constantonoff('BANK_ASK_PAYMENT_BANK_DURING_SUPPLIER_ORDER');
    }
    else
    {
        if (empty($conf->global->BANK_ASK_PAYMENT_BANK_DURING_ORDER))
        {
            print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_BANK_ASK_PAYMENT_BANK_DURING_SUPPLIER_ORDER&amp;value=1">'.img_picto($langs->trans("Disabled"),'switch_off').'</a>';
        }
        else
        {
            print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_BANK_ASK_PAYMENT_BANK_DURING_SUPPLIER_ORDER&amp;value=0">'.img_picto($langs->trans("Enabled"),'switch_on').'</a>';
        }
    }
    print '</td></tr>';
}
else
{

    print '<tr class="oddeven"><td>';
    print $langs->trans("BANK_ASK_PAYMENT_BANK_DURING_SUPPLIER_ORDER").'</td><td>&nbsp;</td><td align="center">'.$langs->trans('NotAvailable').'</td></tr>';
}
*/

$substitutionarray=pdf_getSubstitutionArray($langs, null, null, 2);
$substitutionarray['__(AnyTranslationKey)__']=$langs->trans("Translation");
$htmltext = '<i>'.$langs->trans("AvailableVariables").':<br>';
foreach($substitutionarray as $key => $val)	$htmltext.=$key.'<br>';
$htmltext.='</i>';

print '<tr class="oddeven"><td colspan="2">';
print $form->textwithpicto($langs->trans("FreeLegalTextOnOrders"), $langs->trans("AddCRIfTooLong").'<br><br>'.$htmltext, 1, 'help', '', 0, 2, 'freetexttooltip').'<br>';
$variablename='SUPPLIER_ORDER_FREE_TEXT';
if (empty($conf->global->PDF_ALLOW_HTML_FOR_FREE_TEXT))
{
    print '<textarea name="'.$variablename.'" class="flat" cols="120">'.$conf->global->$variablename.'</textarea>';
}
else
{
    include_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
<<<<<<< HEAD
    $doleditor=new DolEditor($variablename, $conf->global->$variablename,'',80,'dolibarr_notes');
    print $doleditor->Create();
}
print '</td><td align="right">';
=======
    $doleditor=new DolEditor($variablename, $conf->global->$variablename, '', 80, 'dolibarr_notes');
    print $doleditor->Create();
}
print '</td><td class="right">';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print "</td></tr>\n";

print '</table><br>';

print '</form>';



/*
 * Notifications
 */

<<<<<<< HEAD
print load_fiche_titre($langs->trans("Notifications"),'','');
=======
print load_fiche_titre($langs->trans("Notifications"), '', '');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameter").'</td>';
print '<td align="center" width="60"></td>';
print '<td width="80">&nbsp;</td>';
print "</tr>\n";

print '<tr class="oddeven"><td colspan="2">';
print $langs->trans("YouMayFindNotificationsFeaturesIntoModuleNotification").'<br>';
<<<<<<< HEAD
print '</td><td align="right">';
=======
print '</td><td class="right">';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
print "</td></tr>\n";

print '</table>';

<<<<<<< HEAD

llxFooter();

=======
// End of page
llxFooter();
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
$db->close();
