<?php
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo <jlb@j1b.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *      \file       htdocs/adherents/cotisations.php
 *      \ingroup    adherent
 *		\brief      Page de consultation et insertion d'une cotisation
 *		\version    $Id$
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/adherents/adherent.class.php");
require_once(DOL_DOCUMENT_ROOT."/adherents/cotisation.class.php");
require_once(DOL_DOCUMENT_ROOT."/compta/bank/account.class.php");

$langs->load("members");

$sortorder=$_GET["sortorder"];
$sortfield=$_GET["sortfield"];
$page=$_GET["page"];
$filter=$_GET["filter"];
$statut=isset($_GET["statut"])?$_GET["statut"]:1;

if (! $sortorder) {  $sortorder="DESC"; }
if (! $sortfield) {  $sortfield="c.dateadh"; }
if ($page == -1) { $page = 0 ; }
$offset = $conf->liste_limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

$msg='';
$date_select=isset($_GET["date_select"])?$_GET["date_select"]:$_POST["date_select"];

// Desactivation fonctions insertions en banque apres coup
// Cette fonction me semble pas utile. Si on a fait des adhesions alors que module banque
// pas actif c'est qu'on voulait pas d'insertion en banque.
// si on active apres coup, on va pas modifier toutes les adhesions pour avoir une ecriture
// en banque mais on va mettre le solde banque direct a la valeur apres toutes les adh�sions.
$allowinsertbankafter=0;

if (! $user->rights->adherent->cotisation->lire)
	 accessforbidden();


/*
*	Actions
*/

// Insertion de la cotisation dans le compte banquaire
if ($allowinsertbankafter && $_POST["action"] == '2bank' && $_POST["rowid"] !='')
{
    if (defined("ADHERENT_BANK_USE") && $conf->global->ADHERENT_BANK_USE)
    {
		if (! $_POST["accountid"])
		{
			$msg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->trans("BankAccount")).'</div>';
		}
		if (! $_POST["paymenttypeid"])
		{
			$msg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->trans("OperationType")).'</div>';
		}

		// Cr�er un tiers + facture et enregistrer son paiement ? -> Non requis avec module compta expert
		// Eventuellement offrir option a la creation adhesion

		if (! $msg)
		{
			$db->begin();

	        $dateop=time();

			$cotisation=new Cotisation($db);
			$result=$cotisation->fetch($_POST["rowid"]);
			$adherent=new Adherent($db);
			$result=$adherent->fetch($cotisation->fk_adherent);

			if ($result > 0)
	        {
				$amount=$cotisation->amount;

				$acct=new Account($db);
				$acct->fetch($_POST["accountid"]);
				$insertid=$acct->addline($dateop, $_POST["paymenttypeid"], $_POST["label"], $amount, $_POST["num_chq"],ADHERENT_BANK_CATEGORIE,$user);
				if ($insertid < 0)
				{
					dol_print_error($db,$acct->error);
				}
				else
				{
        			$inserturlid=$acct->add_url_line($insertid, $adherent->rowid, DOL_URL_ROOT.'/adherents/fiche.php?rowid=', $adherent->getFullname(), 'member');

					// Met a jour la table cotisation
					$sql="UPDATE ".MAIN_DB_PREFIX."cotisation";
					$sql.=" SET fk_bank=".$insertid.",";
					$sql.=" note='".addslashes($_POST["label"])."'";
					$sql.=" WHERE rowid=".$_POST["rowid"];
					dol_syslog("cotisations sql=".$sql);
					$result = $db->query($sql);
					if ($result)
					{
						//Header("Location: cotisations.php");
						$db->commit();
					}
					else
					{
						$db->rollback();
						dol_print_error($db);
					}
				}
	        }
	        else
	        {
				$db->rollback();
	            dol_print_error($db,$cotisation->error);
	        }
		}
    }
}


/*
 * View
 */

llxHeader('',$langs->trans("ListOfSubscriptions"),'EN:Module_Foundations|FR:Module_Adh&eacute;rents|ES:M&oacute;dulo_Miembros');

if ($msg)	print $msg.'<br>';

// Liste des cotisations
$sql = "SELECT d.rowid, d.login, d.prenom, d.nom, d.societe,";
$sql.= " c.rowid as crowid, c.cotisation,";
$sql.= " ".$db->pdate("c.dateadh")." as dateadh,";
$sql.= " ".$db->pdate("c.datef")." as datef,";
$sql.= " c.fk_bank as bank, c.note,";
$sql.= " b.fk_account";
$sql.= " FROM ".MAIN_DB_PREFIX."adherent as d, ".MAIN_DB_PREFIX."cotisation as c";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."bank as b ON c.fk_bank=b.rowid";
$sql.= " WHERE d.rowid = c.fk_adherent";
if (isset($date_select) && $date_select != '')
{
  $sql.= " AND dateadh LIKE '$date_select%'";
}
$sql.= " ORDER BY $sortfield $sortorder";
$sql.= $db->plimit($conf->liste_limit+1, $offset);

$result = $db->query($sql);
if ($result)
{
    $num = $db->num_rows($result);
    $i = 0;

    $title=$langs->trans("ListOfSubscriptions");
    if (! empty($date_select)) $title.=' ('.$langs->trans("Year").' '.$date_select.')';
    $param.="&amp;statut=$statut&amp;date_select=$date_select";
	print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder,'',$num);


    print '<table class="noborder" width="100%">';

    print '<tr class="liste_titre">';
    print_liste_field_titre($langs->trans("Ref"),"cotisations.php","c.rowid",$param,"","",$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Name"),"cotisations.php","d.nom",$param,"","",$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Login"),"cotisations.php","d.login",$param,"","",$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Label"),"cotisations.php","c.note",$param,"",'align="left"',$sortfield,$sortorder);
    if ($conf->banque->enabled && $conf->global->ADHERENT_BANK_USE)
    {
        print_liste_field_titre($langs->trans("Bank"),"cotisations.php","b.fk_account",$pram,"","",$sortfield,$sortorder);
    }
    print_liste_field_titre($langs->trans("Date"),"cotisations.php","c.dateadh",$param,"",'align="center"',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("DateEnd"),"cotisations.php","c.datef",$param,"",'align="center"',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Amount"),"cotisations.php","c.cotisation",$param,"",'align="right"',$sortfield,$sortorder);
    print "</tr>\n";

	// Static objects
    $cotisation=new Cotisation($db);
    $adherent=new Adherent($db);
    $accountstatic=new Account($db);

	$var=true;
    $total=0;
    while ($i < $num && $i < $conf->liste_limit)
    {
        $objp = $db->fetch_object($result);
        $total+=$objp->cotisation;

        $cotisation->ref=$objp->crowid;
        $cotisation->id=$objp->crowid;

        $adherent->ref=trim($objp->prenom.' '.$objp->nom);
        $adherent->id=$objp->rowid;
        $adherent->login=$objp->login;

        $var=!$var;

        if ($allowinsertbankafter && ! $objp->fk_account && $conf->banque->enabled && $conf->global->ADHERENT_BANK_USE && $objp->cotisation)
		{
			print "<form method=\"post\" action=\"cotisations.php\">";
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		}
        print "<tr $bc[$var]>";

		// Ref
		print '<td>'.$cotisation->getNomUrl(1).'</td>';

		// Nom
		print '<td>'.$adherent->getNomUrl(1).'</td>';

		// Login
		print '<td>'.$adherent->login.'</td>';

		// Libelle
		print '<td>';
        if ($allowinsertbankafter && $user->rights->banque->modifier && ! $objp->fk_account && $conf->banque->enabled && $conf->global->ADHERENT_BANK_USE && $objp->cotisation)
		{
			print "<input name=\"label\" type=\"text\" class=\"flat\" size=\"30\" value=\"".$langs->trans("Subscriptions").' '.dol_print_date($objp->dateadh,"%Y")."\" >\n";
	                //	print "<td><input name=\"debit\" type=\"text\" size=8></td>";
	                //	print "<td><input name=\"credit\" type=\"text\" size=8></td>";
			print '<input type="submit" class="button" value="'.$langs->trans("Save").'">';
		}
		else
		{
			print dol_trunc($objp->note,32);
		}
		print '</td>';

		// Banque
        if ($conf->banque->enabled && $conf->global->ADHERENT_BANK_USE)
        {
            if ($objp->fk_account)
            {
                $accountstatic->id=$objp->fk_account;
                $accountstatic->fetch($objp->fk_account);
                //$accountstatic->label=$objp->label;
                print '<td>'.$accountstatic->getNomUrl(1).'</td>';
            }
            else
            {
				print "<td>";
                if ($allowinsertbankafter && $objp->cotisation)
				{
	                print '<input type="hidden" name="action" value="2bank">';
	                print '<input type="hidden" name="rowid" value="'.$objp->crowid.'">';
	                $html = new Form($db);
	                $html->select_comptes('','accountid',0,'',1);
					print '<br>';
	                $html->select_types_paiements('','paymenttypeid');
	                print '<input name="num_chq" type="text" class="flat" size="5">';
				}
				else
				{
					print '&nbsp;';
				}
				print "</td>\n";
            }
        }

		// Date start
		print '<td align="center">'.dol_print_date($objp->dateadh,'day')."</td>\n";

		// Date end
		print '<td align="center">'.dol_print_date($objp->datef,'day')."</td>\n";

		// Price
		print '<td align="right">'.price($objp->cotisation).'</td>';

        print "</tr>";
        if ($allowinsertbankafter && ! $objp->fk_account && $conf->banque->enabled && $conf->global->ADHERENT_BANK_USE && $objp->cotisation)
		{
			print "</form>\n";
		}
		$i++;
    }

    // Total
    $var=!$var;
    print '<tr class="liste_total">';
    print "<td>".$langs->trans("Total")."</td>\n";
    print "<td align=\"right\">&nbsp;</td>\n";
    print "<td align=\"right\">&nbsp;</td>\n";
    print "<td align=\"right\">&nbsp;</td>\n";
    if ($conf->banque->enabled && $conf->global->ADHERENT_BANK_USE)
    {
    	print '<td>&nbsp;</td>';
    }
   	print '<td>&nbsp;</td>';
   	print '<td>&nbsp;</td>';
   	print "<td align=\"right\">".price($total)."</td>\n";
    print "</tr>\n";

    print "</table>";
    print "<br>\n";


}
else
{
  dol_print_error($db);
}


$db->close();

llxFooter('$Date$ - $Revision$');
?>
