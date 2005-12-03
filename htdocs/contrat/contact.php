<?php
/* Copyright (C) 2005 Patrick Rouillon     <patrick@rouillon.net>
 * Copyright (C) 2005 Destailleur Laurent  <eldy@users.sourceforge.net>
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
	if ($filter) $sql .= " WHERE $filter";
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
					print '<option value="'.$obj->idp.'" selected="true">'.dolibarr_trunc($obj->nom,24).'</option>';
					$firstCompany = $obj->idp;
				} else
				{
					print '<option value="'.$obj->idp.'">'.dolibarr_trunc($obj->nom,24).'</option>';
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
function select_type_contact($contrat, $defValue, $htmlname = 'type', $source)
{
	$lesTypes = $contrat->liste_type_contact($source);
	print '<select class="flat" name="'.$htmlname.'">';
	foreach($lesTypes as $key=>$value)
	{
		print '<option value="'.$key.'">'.$value.'</option>';
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

    if ($result > 0 && $_POST["id"] > 0)
    {
  		$result = $contrat->add_contact($_POST["contactid"], $_POST["type"], $_POST["source"]);
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
// modification d'un contact. On enregistre le type
if ($_POST["action"] == 'updateligne' && $user->rights->contrat->creer)
{
	$contrat = new Contrat($db);
	if ($contrat->fetch($_GET["id"]))
	{
		$contact = $contrat->detail_contact($_POST["elrowid"]);
		$type = $_POST["type"];
		$statut = $contact->statut;

		$result = $contrat->update_contact($_POST["elrowid"], $statut, $type);
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
		$id_type_contact = $contact->fk_c_type_contact;
		$statut = ($contact->statut == 4) ? 5 : 4;

		$result = $contrat->update_contact($_GET["ligne"], $statut, $id_type_contact);
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

// Efface un contact
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
		print '<tr><td width="25%">'.$langs->trans("Ref").'</td><td colspan="3">';
		print $contrat->ref;
		print "</td></tr>";

		// Customer
		print "<tr><td>".$langs->trans("Customer")."</td>";
		print '<td colspan="3">';
		print '<b><a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$contrat->societe->id.'">'.$contrat->societe->nom.'</a></b></td></tr>';

		print "</table>";


		print '</div>';


		/*
		 * Lignes de contacts
		 */
		echo '<br><table class="noborder" width="100%">';

		/*
		 * Ajouter une ligne de contact
		 * Non affiché en mode modification de ligne
		 */
		if ($_GET["action"] != 'editline' && $user->rights->contrat->creer)
		{
			print '<tr class="liste_titre">';
			print '<td>'.$langs->trans("Source").'</td>';
			print '<td>'.$langs->trans("Company").'</td>';
			print '<td>'.$langs->trans("Contacts").'</td>';
			print '<td>'.$langs->trans("ContactType").'</td>';
			print '<td colspan="3">&nbsp;</td>';
			print "</tr>\n";

			$var = false;

			print '<form action="contact.php?id='.$id.'" method="post">';
			print '<input type="hidden" name="action" value="addcontact">';
			print '<input type="hidden" name="source" value="internal">';
			print '<input type="hidden" name="id" value="'.$id.'">';

            // Ligne ajout pour contact interne
			print "<tr $bc[$var]>";
			
			print '<td>';
			print $langs->trans("Internal");
            print '</td>';			
			
			print '<td colspan="1">';
			print $conf->global->MAIN_INFO_SOCIETE_NOM;
			print '</td>';

			print '<td colspan="1">';
			$html->select_users($user->id,'contactid');
			print '</td>';
			print '<td>';
			select_type_contact($contrat, '', 'type','internal');
			print '</td>';
			print '<td align="right" colspan="3" ><input type="submit" class="button" value="'.$langs->trans("Add").'"></td>';
			print '</tr>';

            print '</form>';

			print '<form action="contact.php?id='.$id.'" method="post">';
			print '<input type="hidden" name="action" value="addcontact">';
			print '<input type="hidden" name="source" value="external">';
			print '<input type="hidden" name="id" value="'.$id.'">';

            // Ligne ajout pour contact externe
			$var=!$var;
			print "<tr $bc[$var]>";
			
			print '<td>';
			print $langs->trans("External");
            print '</td>';			
			
			print '<td colspan="1">';
			$selectedCompany = isset($_GET["newcompany"])?$_GET["newcompany"]:$contrat->societe->id;
			$selectedCompany = select_societes_for_newconcat($contrat, $selectedCompany, $htmlname = 'newcompany');
			print '</td>';

			print '<td colspan="1">';
			$html->select_contacts($selectedCompany, $selected = '', $htmlname = 'contactid');
			print '</td>';
			print '<td>';
			select_type_contact($contrat, '', 'type','external');
			print '</td>';
			print '<td align="right" colspan="3" ><input type="submit" class="button" value="'.$langs->trans("Add").'"></td>';
			print '</tr>';
			
			print "</form>";

		}

        print '<tr><td colspan="6">&nbsp;</td></tr>';
        
		// Liste des contacts liés
		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans("Source").'</td>';
		print '<td>'.$langs->trans("Company").'</td>';
		print '<td>'.$langs->trans("Contacts").'</td>';
		print '<td>'.$langs->trans("ContactType").'</td>';
		print '<td align="center">'.$langs->trans("Status").'</td>';
		print '<td colspan="2">&nbsp;</td>';
		print "</tr>\n";

		$societe = new Societe($db);
    	$var = true;

		foreach(array('internal','external') as $source)
		{
    		$tab = $contrat->liste_contact(-1,$source);
            $num=sizeof($tab);

			$i = 0;
			while ($i < $num)
			{
				$var = !$var;

				print '<tr '.$bc[$var].' valign="top">';

                // Source
				print '<td align="left">';
				if ($tab[$i]['source']=='internal') print $langs->trans("Internal");
				if ($tab[$i]['source']=='external') print $langs->trans("External");
                print '</td>';
                
				// Societe
				print '<td align="left">';
				if ($tab[$i]['socid'] > 0)
				{
					print '<a href="'.DOL_URL_ROOT.'/soc.php?idp='.$tab[$i]['socid'].'">';
					print img_object($langs->trans("ShowCompany"),"company").' '.$societe->get_nom($tab[$i]['socid']);
					print '</a>';
                }
				if ($tab[$i]['socid'] < 0)
				{
                    print $conf->global->MAIN_INFO_SOCIETE_NOM;
                }
				if (! $tab[$i]['socid'])
                {
                    print '&nbsp;';   
                }
				print '</td>';

				// Contact
				print '<td>';
				if ($tab[$i]['source']=='internal')
				{
					print '<a href="'.DOL_URL_ROOT.'/user/fiche.php?id='.$tab[$i]['id'].'">';
					print img_object($langs->trans("ShowUser"),"user").' '.$tab[$i]['nom'].'</a>';
                }
				if ($tab[$i]['source']=='external')
				{
					print '<a href="'.DOL_URL_ROOT.'/contact/fiche.php?id='.$tab[$i]['id'].'">';
					print img_object($langs->trans("ShowContact"),"contact").' '.$tab[$i]['nom'].'</a>';
                }
				print '</td>';

				// Type de contact
				print '<td>'.$tab[$i]['libelle'].'</td>';

				// Statut
				print '<td align="center">';
				// Activation desativation du contact
				if ($contrat->statut >= 0)
					print '<a href="'.DOL_URL_ROOT.'/contrat/contact.php?id='.$contrat->id.'&amp;action=swapstatut&amp;ligne='.$tab[$i]['rowid'].'">';
				print img_statut($tab[$i]['status']);
				if ($contrat->statut >= 0)
					print '</a>';
				print '</td>';

				// Icon update et delete (statut contrat 0=brouillon,1=validé,2=fermé)
				print '<td align="center" nowrap>';
				if ($contrat->statut == 0 && $user->rights->contrat->creer)
				{
					print '&nbsp;';
					print '<a href="contact.php?id='.$contrat->id.'&amp;action=deleteline&amp;lineid='.$tab[$i]['rowid'].'">';
					print img_delete();
					print '</a>';
				}
				print '</td>';

				print "</tr>\n";

				$i ++;
			}
			$db->free($result);
		}
		print "</table>";


	}
	else
	{
		// Contrat non trouvé
		print "Contrat inexistant ou accés refusé";
	}
}

$db->close();

llxFooter('$Date$');
?>
