<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
 */

/**
 *     \file       htdocs/compta/ventilation/card.php
 *     \ingroup    compta
 *     \brief      Page fiche ventilation
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';

$langs->load("bills");

$mesg = '';

if (!$user->rights->compta->ventilation->creer) accessforbidden();


/*
 * Actions
 */

if ($_POST["action"] == 'ventil' && $user->rights->compta->ventilation->creer)
{
  $sql = " UPDATE ".MAIN_DB_PREFIX."facturedet";
  $sql .= " SET fk_code_ventilation = ".$_POST["codeventil"];
  $sql .= " WHERE rowid = ".$_GET["id"];

  $db->query($sql);
}

llxHeader("","","Fiche ventilation");

if ($cancel == $langs->trans("Cancel"))
{
  $action = '';
}
/*
 *
 *
 */

$sql = "SELECT rowid, numero, intitule";
$sql .= " FROM ".MAIN_DB_PREFIX."compta_compte_generaux";
$sql .= " ORDER BY numero ASC";

$result = $db->query($sql);
if ($result)
{
  $num = $db->num_rows($result);
  $i = 0;

  while ($i < $num)
    {
      $row = $db->fetch_row($result);
      $cgs[$row[0]] = $row[1] . ' ' . $row[2];
      $i++;
    }
}

/*
 * Cr�ation
 *
 */
$form = new Form($db);
$facture_static=new Facture($db);

if($_GET["id"])
{
    $sql = "SELECT f.facnumber, f.rowid as facid, l.fk_product, l.description, l.price,";
    $sql .= " l.qty, l.rowid, l.tva_tx, l.remise_percent, l.subprice,";
    $sql .= " l.date_start as date_start, l.date_end as date_end,";
    $sql .= " l.fk_code_ventilation ";
    $sql .= " FROM ".MAIN_DB_PREFIX."facturedet as l";
    $sql .= " , ".MAIN_DB_PREFIX."facture as f";
    $sql .= " WHERE f.rowid = l.fk_facture AND f.fk_statut = 1 AND l.rowid = ".$_GET["id"];

    $result = $db->query($sql);
    if ($result)
    {
        $num_lignes = $db->num_rows($result);
        $i = 0;

        if ($num_lignes)
        {

            $objp = $db->fetch_object($result);


            if($objp->fk_code_ventilation == 0)
            {
                print '<form action="card.php?id='.$_GET["id"].'" method="post">'."\n";
                print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
                print '<input type="hidden" name="action" value="ventil">';
            }


            print load_fiche_titre("Ventilation");

            print '<table class="border" width="100%">';

			// Ref facture
            print '<tr><td>'.$langs->trans("Invoice").'</td>';
			$facture_static->ref=$objp->facnumber;
			$facture_static->id=$objp->facid;
			print '<td>'.$facture_static->getNomUrl(1).'</td>';
            print '</tr>';

            print '<tr><td width="20%">Ligne</td>';
            print '<td>'.nl2br($objp->description).'</td></tr>';
            print '<tr><td width="20%">Ventiler dans le compte :</td><td>';

            if($objp->fk_code_ventilation == 0)
            {
                print $form->selectarray("codeventil",$cgs, $objp->fk_code_ventilation);
            }
            else
            {
                print $cgs[$objp->fk_code_ventilation];
            }

            print '</td></tr>';

            if($objp->fk_code_ventilation == 0)
            {
                print '<tr><td>&nbsp;</td><td><input type="submit" class="button" value="'.$langs->trans("Ventiler").'"></td></tr>';
            }
            print '</table>';
            print '</form>';
        }
        else
        {
            print "Error";
        }
    }
    else
    {
        print "Error";
    }
}
else
{
    print "Error ID incorrect";
}

llxFooter();
$db->close();
