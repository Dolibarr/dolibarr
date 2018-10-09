<?php

/* Copyright (C) 2018      Nicolas ZABOURI      <info@inovea-conseil.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file    gdpr/admin/setup.php
 * \ingroup gdpr
 * \brief   gdpr setup page.
 */
if (!defined('NOLOGIN'))
    define("NOLOGIN", 1);   // This means this output page does not require to be logged.
if (!defined('NOCSRFCHECK'))
    define('NOCSRFCHECK', '1');  // Do not check anti CSRF attack test
if (!defined('NOREQUIREMENU'))
    define('NOREQUIREMENU', '1');
// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"]))
    $res = @include($_SERVER["CONTEXT_DOCUMENT_ROOT"] . "/main.inc.php");
// Try main.inc.php into web root detected using web root caluclated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1;
$j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
    $i--;
    $j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)) . "/main.inc.php"))
    $res = @include(substr($tmp, 0, ($i + 1)) . "/main.inc.php");
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php"))
    $res = @include(dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php");
// Try main.inc.php using relative path
if (!$res && file_exists("../../main.inc.php"))
    $res = @include("../../main.inc.php");
if (!$res && file_exists("../../../main.inc.php"))
    $res = @include("../../../main.inc.php");
if (!$res)
    die("Include of main fails");

global $langs, $user, $db, $conf;

dol_include_once('/contact/class/contact.class.php');
dol_include_once('/societe/class/societe.class.php');
dol_include_once('/adherents/class/adherent.class.php');
dol_include_once('/user/class/user.class.php');

dol_include_once('/gdpr/class/gdpr.class.php');

$idc = GETPOST('c');
$ids = GETPOST('s');
$ida = GETPOST('a');
$action = GETPOST('action');
$lang = GETPOST('l');
$code = GETPOST('key');
$acc = "RGPDACCEPT_" . $lang;
$ref = "RGPDREFUSE_" . $lang;
$langs->load('gdpr@gdpr',0,0,$lang);

if (empty($action) || (empty($idc) && empty($ids) && empty($ida))) {
    return 0;
} elseif (!empty($idc)) {
    $contact = new Contact($db);
    $contact->fetch($idc);
    $check = md5($contact->email);
    if ($check != $code) {
        $return = $langs->trans('ErrorEmailRGPD');
    } elseif ($action == 1) {
        $contact->array_options['options_gdpr_consentement'] = 1;
        $contact->array_options['options_gdpr_opposition_traitement'] = 0;
        $contact->array_options['options_gdpr_opposition_prospection'] = 0;
        $contact->array_options['options_gdpr_date'] = date('Y-m-d', time());

        $return = $conf->global->$acc;
    } elseif ($action == 2) {
        $contact->no_email = 1;
        $contact->array_options['options_gdpr_consentement'] = 0;
        $contact->array_options['options_gdpr_opposition_traitement'] = 1;
        $contact->array_options['options_gdpr_opposition_prospection'] = 1;
        $contact->array_options['options_gdpr_date'] = date('Y-m-d', time());

        $return = $conf->global->$ref;
    }
    $contact->update($idc);
} elseif (!empty($ids)) {
    $societe = new Societe($db);
    $societe->fetch($ids);
    $check = md5($societe->email);
    if ($check != $code) {
        $return = $langs->trans('ErrorEmailRGPD');
    } elseif ($action == 1) {
        $societe->array_options['options_gdpr_consentement'] = 1;
        $societe->array_options['options_gdpr_opposition_traitement'] = 0;
        $societe->array_options['options_gdpr_opposition_prospection'] = 0;
        $societe->array_options['options_gdpr_date'] = date('Y-m-d', time());
        $return = $conf->global->$acc;
    } elseif ($action == 2) {
        $societe->array_options['options_gdpr_consentement'] = 0;
        $societe->array_options['options_gdpr_opposition_traitement'] = 1;
        $societe->array_options['options_gdpr_opposition_prospection'] = 1;
        $societe->array_options['options_gdpr_date'] = date('Y-m-d', time());

        $return = $conf->global->$ref;
    }
    $societe->update($ids);
} elseif (!empty($ida)) {
    $adherent = new Adherent($db);
    $adherent->fetch($ida);
    $check = md5($adherent->email);
    if ($check != $code) {
        $return = $langs->trans('ErrorEmailRGPD');
    } elseif ($action == 1) {
        $adherent->array_options['options_gdpr_consentement'] = 1;
        $adherent->array_options['options_gdpr_opposition_traitement'] = 0;
        $adherent->array_options['options_gdpr_opposition_prospection'] = 0;
        //$adherent->array_options['options_gdpr_date'] = date('Y-m-d', time());
        $return = $conf->global->$acc;
    } elseif ($action == 2) {
        $adherent->array_options['options_gdpr_consentement'] = 0;
        $adherent->array_options['options_gdpr_opposition_traitement'] = 1;
        $adherent->array_options['options_gdpr_opposition_prospection'] = 1;
        //$adherent->array_options['options_gdpr_date'] = date('Y-m-d', time());

        $return = $conf->global->$ref;
    }
    $newuser = new User($db);
    $adherent->update($newuser);
}

header("Content-type: text/html; charset=" . $conf->file->character_set_client);

print '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">';
print "\n";
print "<html>\n";
print "<head>\n";
print '<meta name="robots" content="noindex,nofollow">' . "\n";
print '<meta name="keywords" content="dolibarr">' . "\n";
print '<meta name="description" content="Dolibarr RGPD">' . "\n";
print "<title>" . $langs->trans("RGPDReturn") . "</title>\n";
print '<link rel="stylesheet" type="text/css" href="' . DOL_URL_ROOT . $conf->css . '?lang=' . $lang . '">' . "\n";
print '<style type="text/css">';
print '.CTableRow1      { margin: 1px; padding: 3px; font: 12px verdana,arial; background: #e6E6eE; color: #000000; -moz-border-radius-topleft:6px; -moz-border-radius-topright:6px; -moz-border-radius-bottomleft:6px; -moz-border-radius-bottomright:6px;}';
print '.CTableRow2      { margin: 1px; padding: 3px; font: 12px verdana,arial; background: #FFFFFF; color: #000000; -moz-border-radius-topleft:6px; -moz-border-radius-topright:6px; -moz-border-radius-bottomleft:6px; -moz-border-radius-bottomright:6px;}';
print '</style>';

print "</head>\n";
print '<body style="margin: 10% 40%">' . "\n";
print '<table class="CTableRow1" ><tr><td style="text_align:center;">';
print $return . "<br>\n";
print '</td></tr></table>';
print "</body>\n";
print "</html>\n";
$db->close();
?>