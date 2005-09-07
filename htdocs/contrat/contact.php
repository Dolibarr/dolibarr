<?php
/* Copyright (C) 2005 Patrick Rouillon <patrick@rouillon.net>
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
 * $Source$
 */

/**
        \file       htdocs/contrat/contact.php
        \ingroup    contrat
        \brief      Onglet de gestion des contacts des contrats
        \version    $Revision$
*/

require ("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/contrat/contrat.class.php");
require_once(DOL_DOCUMENT_ROOT."/contact.class.php");


$langs->load("contracts");
// $langs->load("orders");
$langs->load("companies");

$user->getrights('contrat');

if (!$user->rights->contrat->lire)
	accessforbidden();


// les methodes locales
/**
  *    \brief      Retourne la liste déroulante des sociétés
  *    \param      selected        Societe présélectionnée
  *    \param      htmlname        Nom champ formulaire
  */
function select_societes_for_newconcat($contrat, $selected = '', $htmlname = 'newcompany')
{
		// On recherche les societes
	$sql = "SELECT s.idp, s.nom FROM";
	$sql .= " ".MAIN_DB_PREFIX."societe as s";
	if ($filter)
		$sql .= " WHERE $filter";
	$sql .= " ORDER BY nom ASC";

	$resql = $contrat->db->query($sql);
	if ($resql)
	{
		$javaScript = "window.location='./contact.php?id=".$contrat->id."&amp;".$htmlname."=' + form.".$htmlname.".options[form.".$htmlname.".selectedIndex].value;";
		print '<select class="flat" name="'.$htmlname.'" onChange="'.$javaScript.'">';
		$num = $contrat->db->num_rows($resql);
		$i = 0;
		if ($num)
		{
			while ($i < $num)
			{
				$obj = $contrat->db->fetch_object($resql);
				if ($i == 0)
					$firstCompany = $obj->idp;
				if ($selected > 0 && $selected == $obj->idp)
				{
					print '<option value="'.$obj->idp.'" selected="true">'.$obj->nom.'</option>';
					$firstCompany = $obj->idp;
				} else
				{
					print '<option value="'.$obj->idp.'">'.$obj->nom.'</option>';
				}
				$i ++;
			}
		}
		print "</select>\n";
		return $firstCompany;
	} else
	{
		dolibarr_print_error($contrat->db);
	}
}

/**
 * 
 */
function select_nature_contact($contrat, $defValue, $htmlname = 'nature')
{
	$lesNatures = $contrat->liste_nature_contact();
	print '<input name="'.$htmlname.'" type="text" size="12" value="'.$defValue.'"> ';
	print '<select size="0" name="choix" onChange="form.'.$htmlname.'.value=this.value;" >';
	for ($i = 0; $i < count($lesNatures); $i ++)
	{
		print '<option>'.$lesNatures[$i].'</option>';
	}
	print "</select>\n";
}

// Sécurité accés client
if ($user->societe_id > 0)
{
	$action = '';
	$socidp = $user->societe_id;
}

/*
 * Ajout d'un nouveau contact
 */

if ($_POST["action"] == 'addcontact' && $user->rights->contrat->creer)
{
	$result = 0;
	$contrat = new Contrat($db);
	$result = $contrat->fetch($_GET["id"]);
	if ($_POST["id"] > 0)
	{
		$result = $contrat->add_contact($_POST["contactid"], $_POST["newnature"]);
	}

	if ($result >= 0)
	{
		Header("Location: contact.php?id=".$contrat->id);
		exit;
	} else
	{
		$mesg = '<div class="error">'.$contrat->error.'</div>';
	}
}
// modification d'un contact. On enregistre la nature
if ($_POST["action"] == 'updateligne' && $user->rights->contrat->creer)
{
	$contrat = new Contrat($db);
	if ($contrat->fetch($_GET["id"]))
	{
		$contact = $contrat->detail_contact($_POST["elrowid"]);
		$nature = $_POST["nature"];
		$statut = $contact->statut;

		$result = $contrat->update_contact($_POST["elrowid"], $statut, $nature);
		if ($result >= 0)
		{
			$db->commit();
		} else
		{
			dolibarr_print_error($db, "result=$result");
			$db->rollback();
		}
	} else
	{
		dolibarr_print_error($db);
	}
}
// bascule du statut d'un contact
if ($_GET["action"] == 'swapstatut' && $user->rights->contrat->creer)
{
	$contrat = new Contrat($db);
	if ($contrat->fetch($_GET["id"]))
	{
		$contact = $contrat->detail_contact($_GET["ligne"]);
		$nature = $contact->nature;
		$statut = ($contact->statut == 4) ? 5 : 4;

		$result = $contrat->update_contact($_GET["ligne"], $statut, $nature);
		if ($result >= 0)
		{
			$db->commit();
		} else
		{
			dolibarr_print_error($db, "result=$result");
			$db->rollback();
		}
	} else
	{
		dolibarr_print_error($db);
	}
}

if ($_GET["action"] == 'deleteline' && $user->rights->contrat->creer)
{
	$contrat = new Contrat($db);
	$contrat->fetch($_GET["id"]);
	$result = $contrat->delete_contact($_GET["lineid"]);

	if ($result >= 0)
	{
		Header("Location: contact.php?id=".$contrat->id);
		exit;
	}
}



llxHeader('', $langs->trans("ContractCard"), "Contrat");

$html = new Form($db);

/* *************************************************************************** */
/*                                                                             */
/* Mode vue et edition                                                         */
/*                                                                             */
/* *************************************************************************** */
$id = $_GET["id"];
if ($id > 0)
{
	$contrat = New Contrat($db);
	if ($contrat->fetch($id) > 0)
	{
		if ($mesg)
			print $mesg;

		$h = 0;
		$head[$h][0] = DOL_URL_ROOT.'/contrat/fiche.php?id='.$contrat->id;
		$head[$h][1] = $langs->trans("ContractCard");
		$h ++;

		$head[$h][0] = DOL_URL_ROOT.'/contrat/contact.php?id='.$contrat->id;
		$head[$h][1] = $langs->trans("ContractContacts");
		$hselected = $h;
		$h ++;

		$head[$h][0] = DOL_URL_ROOT.'/contrat/info.php?id='.$contrat->id;
		$head[$h][1] = $langs->trans("Info");
		$h ++;

		dolibarr_fiche_head($head, $hselected, $langs->trans("Contract").': '.$contrat->id);

		/*
		 *   Contrat
		 */
		print '<table class="border" width="100%">';

		// Reference du contrat
		print '<tr><td>'.$langs->trans("Ref").'</td><td colspan="3">';
		print $contrat->ref;
		print "</td></tr>";

		// Customer
		print "<tr><td>".$langs->trans("Customer")."</td>";
		print '<td colspan="3">';
		print '<b><a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$contrat->societe->id.'">'.$contrat->societe->nom.'</a></b></td></tr>';

		print "</table>";

		/*
		 * Lignes de contacts
		 */
		echo '<br><table class="noborder" width="100%">';

		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans("Societe").'</td>';
		print '<td>'.$langs->trans("Contacts").'</td>';
		print '<td align="center">'.$langs->trans("ContactType").'</td>';
		print '<td align="center">'.$langs->trans("Status").'</td>';
		print '<td colspan="2">&nbsp;</td>';
		print "</tr>\n";

		$tab = $contrat->liste_contact();

		$num = ($tab == null) ? 0 : count($tab);
		if ($tab != null && $num > 0)
		{
			$i = 0;
			$total = 0;

			$var = true;
			$person = new Contact($db);
			$societe = new Societe($db);

			while ($i < $num)
			{
				$objp = $contrat->detail_contact($tab[$i]);

				// detail du contact
				$person->fetch($objp->fk_socpeople);
				$var = !$var; // flip flop lines 

				if ($_GET["action"] != 'editline' || $_GET["rowid"] != $tab[$i])
				{
					print '<tr '.$bc[$var].' valign="top">';

					print '<td>';
					print '<a href="'.DOL_URL_ROOT.'/contact/fiche.php?id='.$objp->fk_socpeople.'">';
					print $person->fullname.'</a>';
					print '</td>';

					print '<td align="left">'.$societe->get_nom($person->socid).'</td>';

					// Description
					print '<td align="center">'.$objp->nature.'</td>';
					// Statut
					print '<td align="center">';
					// Activation descativation du contact
					if ($contrat->statut >= 0)
						print '<a href="'.DOL_URL_ROOT.'/contrat/contact.php?id='.$contrat->id.'&amp;action=swapstatut&amp;ligne='.$tab[$i].'">';
					print img_statut($objp->statut);
					if ($contrat->statut > 0)
						print '</a>';
					print '</td>';

					// Icon update et delete (statut contrat 0=brouillon,1=validé,2=fermé)
					print '<td align="center" nowrap>';
					if ($contrat->statut != 2 && $user->rights->contrat->creer)
					{
						print '<a href="contact.php?id='.$id.'&amp;action=editline&amp;rowid='.$tab[$i].'">';
						print img_edit();
						print '</a>';
					} else
					{
						print '&nbsp;';
					}
					if ($contrat->statut == 0 && $user->rights->contrat->creer)
					{
						print '&nbsp;';
						print '<a href="contact.php?id='.$id.'&amp;action=deleteline&amp;lineid='.$tab[$i].'">';
						print img_delete();
						print '</a>';
					}
					print '</td>';

					print "</tr>\n";

				}
				// mode edition de une ligne ligne (editline)
				// on ne change pas le contact. Seulement la nature
				else
				{

					print "<form name='detailcontact' action=\"contact.php?id=$id\" method=\"post\">";
					print '<input type="hidden" name="action" value="updateligne">';
					print '<input type="hidden" name="elrowid" value="'.$_GET["rowid"].'">';
					// Ligne carac
					print "<tr $bc[$var]>";

					print '<td>';
					print '<a href="'.DOL_URL_ROOT.'/contact/fiche.php?id='.$objp->fk_socpeople.'">';
					print $person->fullname.'</a>';
					print '</td>';
					print '<td align="left">'.$societe->get_nom($person->socid).'</td>';
					// Description
					print '<td align="center">';

					select_nature_contact($contrat, $objp->nature, 'nature');
					print "</td>";
					// Statut
					print '<td align="center">'.img_statut($objp->statut).'</td>';

					// Icon update et delete (statut contrat 0=brouillon,1=validé,2=fermé)
					print '<td align="center" colspan="1"><input type="submit" class="button" value="'.$langs->trans("Save").'"></td>';

					print '</tr>'."\n";
					print "</form>\n";
				}
				$i ++;
			}
			$db->free($result);
		}

		/*
		 * Ajouter une ligne de contact
		 * uniquement sur les contrats en creation.
		 * En pas en mode modification de ligne
		 */
		if ($_GET["action"] != 'editline' && $user->rights->contrat->creer && $contrat->statut == 0)
		{
			print '<tr class="liste_titre">';
			print '<td>'.$langs->trans("Societe").'</td>';
			print '<td>'.$langs->trans("Contacts").'</td>';
			print '<td align="center">'.$langs->trans("ContactType").'</td>';
			print '<td colspan="2">&nbsp;</td>';
			print "</tr>\n";

			$var = false;

			print '<form action="contact.php?id='.$id.'" method="post">';
			print '<input type="hidden" name="action" value="addcontact">';
			print '<input type="hidden" name="id" value="'.$id.'">';

			print "<tr $bc[$var]>";
			print '<td colspan="1">';
			$selectedCompany = $_GET["newcompany"]; // vide pour la premiere recherche
			$selectedCompany = select_societes_for_newconcat($contrat, $selectedCompany, $htmlname = 'newcompany');
			print '</td>';

			print '<td colspan="1">';
			$html->select_contacts($selectedCompany, $selected = '', $htmlname = 'contactid');
			print '</td>';
			print '<td align="center">';
			select_nature_contact($contrat, "", 'newnature');
			print '</td>';
			print '<td align="right" colspan="3" ><input type="submit" class="button" value="'.$langs->trans("Add").'"></td>';
			print '</tr>';

			print "</form>";

		}

		print "</table>";

		print '</div>';

	} else
	{
		// Contrat non trouvé
		print "Contrat inexistant ou accés refusé";
	}
}

$db->close();

llxFooter('$Date$');
?>
