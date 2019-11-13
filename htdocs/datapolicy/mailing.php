<?php
/* Copyright (C) 2018       Nicolas ZABOURI         <info@inovea-conseil.com>
 * Copyright (C) 2019       Frédéric France         <frederic.france@netlogic.fr>
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    htdocs/datapolicy/mailing.php
 * \ingroup datapolicy
 * \brief   datapolicy mailing page.
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/datapolicy/class/datapolicy.class.php';

$idcontact = GETPOST('idc');

if (!empty($idcontact)) {
    $contact = new Contact($db);
    $contact->fetch($idcontact);
    DataPolicy::sendMailDataPolicyContact($contact);
} else {
    $contacts = new DataPolicy($db);
    $contacts->getAllContactNotInformed();
    $contacts->getAllCompaniesNotInformed();
    $contacts->getAllAdherentsNotInformed();
    echo $langs->trans('AllAgreementSend');
}
