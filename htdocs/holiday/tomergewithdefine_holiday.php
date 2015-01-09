<?php
/* Copyright (C) 2007-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2011      Dimitri Mouillard <dmouillard@teclib.com>
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
 *      \file       define_congespayes.php
 *      \ingroup    congespayes
 *      \brief      File that defines the balance of paid leave of users.
 *      \version    $Id: define_congespayes.php,v 1.00 2011/09/15 11:00:00 dmouillard Exp $
 *      \author      dmouillard@teclib.com <Dimitri Mouillard>
 *      \remarks      File that defines the balance of paid leave of users.
 */

require('../main.inc.php');
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/holiday/common.inc.php';

// Protection if external user
if ($user->societe_id > 0) accessforbidden();

// If the user does not have perm to read the page
if(!$user->rights->holiday->define_holiday) accessforbidden();


/*
 * View
 */

llxHeader(array(),$langs->trans('CPTitreMenu'));


print_fiche_titre($langs->trans('MenuConfCP'));

$congespayes = new Holiday($db);
$listUsers = $congespayes->fetchUsers(false, true);

// Si il y a une action de mise à jour
if (isset($_POST['action']) && $_POST['action'] == 'update') {

   $fk_type = $_POST['fk_type'];

   foreach ($_POST['nb_conges'] as $user_id => $compteur) {
      if (!empty($compteur)) {
         $userValue = str_replace(',', '.', $compteur);
         $userValue = number_format($userValue, 2, '.', '');
      } else {
         $userValue = '0.00';
      }
      $congespayes->updateSoldeCP($user_id,$userValue,$fk_type);
   }

   print '<div class="tabBar">';
   print $langs->trans('UpdateConfCPOK');
   print '</div>';

}

$var = true;
$i = 0;

foreach($congespayes->getTypes() as $type)
{
   if($type['affect']==1)
   {
      print '<div class="tabBar" style="float:left;width:300px;margin-right:10px;">';

         print '<h3>'.$type['label'].'</h3>';

         print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">' . "\n";
         print '<input type="hidden" name="action" value="update" />';
         print '<input type="hidden" name="fk_type" value="'.$type['rowid'].'" />';

         print '<table class="noborder" width="100%;">';
            print "<tr class=\"liste_titre\">";
               print '<td width="50%">' . $langs->trans('Employee') . '</td>';
               print '<td width="30%">' . $langs->trans('Counter') . '</td>';
            print '</tr>';

         foreach ($listUsers as $users) {
            $var = !$var;
            print '<tr ' . $bc[$var] . '>';
               print '<td>' . $users['name'] . ' ' . $users['firstname'] . '</td>';
               print '<td>';
               print '<input type="text" value="' .
                              $congespayes->getCPforUser($users['rowid'],$type['rowid']) .
                           '" name="nb_conges[' . $users['rowid'] . ']"
                           size="5" style="text-align: center;"/>';
               print ' jours</td>' . "\n";
            print '</tr>';

            $i++;
         }

         echo "<tr>";
            print '<td colspan="2"><input type="submit" value="'
                           . $langs->trans("UpdateEventOptionCP") .
                           ' ' . $type['label'] .
                           '" name="bouton" class="button" style="margin: 10px;"></td>';
         echo "</tr>";

         print '</table>';
         print '</form>';

      print '</div>';
   }
}

// Fin de page
$db->close();
llxFooter();
