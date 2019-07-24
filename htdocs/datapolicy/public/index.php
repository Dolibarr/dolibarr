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
 * \file    htdocs/datapolicy/admin/setup.php
 * \ingroup datapolicy
 * \brief   datapolicy setup page.
 */

if (!defined('NOLOGIN'))
    define("NOLOGIN", 1);   // This means this output page does not require to be logged.
if (!defined('NOCSRFCHECK'))
    define('NOCSRFCHECK', '1');  // Do not check anti CSRF attack test
if (!defined('NOREQUIREMENU'))
    define('NOREQUIREMENU', '1');

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/datapolicy/class/datapolicy.class.php';

$idc = GETPOST('c', 'int');
$ids = GETPOST('s', 'int');
$ida = GETPOST('a', 'int');
$action = GETPOST('action', 'alpha');
$lang = GETPOST('l', 'alpha');
$code = GETPOST('key', 'alpha');

$acc = "DATAPOLICIESACCEPT_" . $lang;
$ref = "DATAPOLICIESREFUSE_" . $lang;
$langs->load('datapolicy@datapolicy', 0, 0, $lang);

if (empty($action) || (empty($idc) && empty($ids) && empty($ida))) {
    return 0;
} elseif (!empty($idc)) {
    $contact = new Contact($db);
    $contact->fetch($idc);
    $check = md5($contact->email);
    if ($check != $code) {
        $return = $langs->trans('ErrorEmailDATAPOLICIES');
    } elseif ($action == 1) {
        $contact->array_options['options_datapolicy_consentement'] = 1;
        $contact->array_options['options_datapolicy_opposition_traitement'] = 0;
        $contact->array_options['options_datapolicy_opposition_prospection'] = 0;
        $contact->array_options['options_datapolicy_date'] = date('Y-m-d', time());

        $return = $conf->global->$acc;
    } elseif ($action == 2) {
        $contact->no_email = 1;
        $contact->array_options['options_datapolicy_consentement'] = 0;
        $contact->array_options['options_datapolicy_opposition_traitement'] = 1;
        $contact->array_options['options_datapolicy_opposition_prospection'] = 1;
        $contact->array_options['options_datapolicy_date'] = date('Y-m-d', time());

        $return = $conf->global->$ref;
    }
    $contact->update($idc);
} elseif (!empty($ids)) {
    $societe = new Societe($db);
    $societe->fetch($ids);
    $check = md5($societe->email);
    if ($check != $code) {
        $return = $langs->trans('ErrorEmailDATAPOLICIES');
    } elseif ($action == 1) {
        $societe->array_options['options_datapolicy_consentement'] = 1;
        $societe->array_options['options_datapolicy_opposition_traitement'] = 0;
        $societe->array_options['options_datapolicy_opposition_prospection'] = 0;
        $societe->array_options['options_datapolicy_date'] = date('Y-m-d', time());
        $return = $conf->global->$acc;
    } elseif ($action == 2) {
        $societe->array_options['options_datapolicy_consentement'] = 0;
        $societe->array_options['options_datapolicy_opposition_traitement'] = 1;
        $societe->array_options['options_datapolicy_opposition_prospection'] = 1;
        $societe->array_options['options_datapolicy_date'] = date('Y-m-d', time());

        $return = $conf->global->$ref;
    }
    $societe->update($ids);
} elseif (!empty($ida)) {
    $adherent = new Adherent($db);
    $adherent->fetch($ida);
    $check = md5($adherent->email);
    if ($check != $code) {
        $return = $langs->trans('ErrorEmailDATAPOLICIES');
    } elseif ($action == 1) {
        $adherent->array_options['options_datapolicy_consentement'] = 1;
        $adherent->array_options['options_datapolicy_opposition_traitement'] = 0;
        $adherent->array_options['options_datapolicy_opposition_prospection'] = 0;
        //$adherent->array_options['options_datapolicy_date'] = date('Y-m-d', time());
        $return = $conf->global->$acc;
    } elseif ($action == 2) {
        $adherent->array_options['options_datapolicy_consentement'] = 0;
        $adherent->array_options['options_datapolicy_opposition_traitement'] = 1;
        $adherent->array_options['options_datapolicy_opposition_prospection'] = 1;
        //$adherent->array_options['options_datapolicy_date'] = date('Y-m-d', time());

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
print '<meta name="description" content="Dolibarr DATAPOLICIES">' . "\n";
print "<title>" . $langs->trans("DATAPOLICIESReturn") . "</title>\n";
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
