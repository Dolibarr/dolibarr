<?php
/* Copyright (C) 2006-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2006      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2007      Patrick Raguin <patrick.raguin@gmail.com>
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
 * or see http://www.gnu.org/
 *
 * $Id$
 */

/**
   \file       htdocs/lib/company.lib.php
   \brief      Ensemble de fonctions de base pour le module societe
   \ingroup    societe
   \version    $Revision$
   
   Ensemble de fonctions de base de dolibarr sous forme d'include
*/

function societe_prepare_head($objsoc)
{
	global $langs, $conf, $user;
	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/soc.php?socid='.$objsoc->id;
	$head[$h][1] = $langs->trans("Company");
	$head[$h][2] = 'company';
	$h++;

  if ($objsoc->client==1)
  {
    $head[$h][0] = DOL_URL_ROOT.'/comm/fiche.php?socid='.$objsoc->id;
    $head[$h][1] = $langs->trans("Customer");;
    $head[$h][2] = 'customer';
    $h++;
  }
  if ($objsoc->client==2)
  {
    $head[$h][0] = DOL_URL_ROOT.'/comm/prospect/fiche.php?id='.$objsoc->id;
    $head[$h][1] = $langs->trans("Prospect");
    $head[$h][2] = 'prospect';
    $h++;
  }
  if ($objsoc->fournisseur)
  {
    $head[$h][0] = DOL_URL_ROOT.'/fourn/fiche.php?socid='.$objsoc->id;
    $head[$h][1] = $langs->trans("Supplier");
    $head[$h][2] = 'supplier';
    $h++;
  }  
  if ($conf->facture->enabled || $conf->compta->enabled || $conf->comptaexpert->enabled)
  {
    $langs->load("compta");
    $head[$h][0] = DOL_URL_ROOT.'/compta/fiche.php?socid='.$objsoc->id;
    $head[$h][1] = $langs->trans("Accountancy");
    $head[$h][2] = 'compta';
    $h++;
  }
  //affichage onglet cat�gorie
	if ($conf->categorie->enabled)
  {
		$head[$h][0] = DOL_URL_ROOT.'/categories/categorie.php?socid='.$objsoc->id;
		$head[$h][1] = $langs->trans('Categories');
		$head[$h][2] = 'category';
		$h++;   		
  }
  if ($user->societe_id == 0)
  {
    $head[$h][0] = DOL_URL_ROOT.'/socnote.php?socid='.$objsoc->id;
    $head[$h][1] = $langs->trans("Note");
    $head[$h][2] = 'note';
    $h++;
  }
  if ($user->societe_id == 0)
  {
    $head[$h][0] = DOL_URL_ROOT.'/docsoc.php?socid='.$objsoc->id;
    $head[$h][1] = $langs->trans("Documents");
    $head[$h][2] = 'document';
    $h++;
  }
  
  if ($conf->notification->enabled && $user->societe_id == 0)
  {
    $head[$h][0] = DOL_URL_ROOT.'/societe/notify/fiche.php?socid='.$objsoc->id;
    $head[$h][1] = $langs->trans("Notifications");
    $head[$h][2] = 'notify';
    $h++;
  }

  if ($objsoc->fournisseur)
  {
    $head[$h][0] = DOL_URL_ROOT.'/fourn/fiche-stats.php?socid='.$objsoc->id;
    $head[$h][1] = $langs->trans("Statistics");
    $head[$h][2] = 'supplierstat';
    $h++;
  }
  
  if ($user->societe_id == 0)
  {	
    $head[$h][0] = DOL_URL_ROOT.'/societe/info.php?socid='.$objsoc->id;
    $head[$h][1] = $langs->trans("Info");
    $head[$h][2] = 'info';
    $h++;
  }
  
  if ($conf->bookmark->enabled && $user->rights->bookmark->creer)
  {
    $head[$h][0] = DOL_URL_ROOT."/bookmarks/fiche.php?action=add&amp;socid=".$objsoc->id."&amp;urlsource=".$_SERVER["PHP_SELF"]."?socid=".$objsoc->id;
    $head[$h][1] = img_object($langs->trans("BookmarkThisPage"),'bookmark');
    $head[$h][2] = 'image';
    $h++;
  }
  
  return $head;
}

function show_actions_todo($conf,$langs,$db,$objsoc)
{
	global $bc;
	
    if ($conf->agenda->enabled)
    {
		require_once(DOL_DOCUMENT_ROOT."/actioncomm.class.php");
		$actionstatic=new ActionComm($db);
    $userstatic=new User($db);
    $contactstatic = new Contact($db);
    	
		print_titre($langs->trans("ActionsOnCompany"));
		
	    print '<table width="100%" class="noborder">';
	    print '<tr class="liste_titre">';
	    print '<td colspan="11"><a href="'.DOL_URL_ROOT.'/comm/action/index.php?socid='.$objsoc->id.'&amp;status=todo">'.$langs->trans("ActionsToDoShort").'</a></td><td align="right">&nbsp;</td>';
	    print '</tr>';
	
	    $sql = "SELECT a.id, a.label,";
	    $sql.= " ".$db->pdate("a.datep")." as dp,";
	    $sql.= " ".$db->pdate("a.datea")." as da,";
	    $sql.= " a.percent,";
	    $sql.= " c.code as acode, c.libelle, a.propalrowid, a.fk_user_author, a.fk_contact,";
		$sql.= " u.login, u.rowid,";
		$sql.= " sp.name, sp.firstname";
	    $sql.= " FROM ".MAIN_DB_PREFIX."c_actioncomm as c, ".MAIN_DB_PREFIX."user as u, ".MAIN_DB_PREFIX."actioncomm as a";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."socpeople as sp ON a.fk_contact = sp.rowid";
	    $sql.= " WHERE a.fk_soc = ".$objsoc->id;
	    $sql.= " AND u.rowid = a.fk_user_author";
	    $sql.= " AND c.id=a.fk_action AND a.percent < 100";
	    $sql.= " ORDER BY a.datep DESC, a.id DESC";
	
		dolibarr_syslog("company.lib::show_actions_todo sql=".$sql);
	    $result=$db->query($sql);
	    if ($result)
	    {
	        $i = 0 ;
	        $num = $db->num_rows($result);
	        $var=true;
	        
	        if ($num)
	        {
	            while ($i < $num)
	            {
	                $var = !$var;
	
	                $obj = $db->fetch_object($result);
	                print "<tr $bc[$var]>";
	
                    print '<td width="30" align="center">'.strftime("%Y",$obj->dp)."</td>\n";
                    $oldyear = strftime("%Y",$obj->dp);
	
                    print '<td width="30" align="center">' .strftime("%b",$obj->dp)."</td>\n";
                    $oldmonth = strftime("%Y%b",$obj->dp);
	
	                print '<td width="20">'.strftime("%d",$obj->dp)."</td>\n";
	               	print '<td width="30" nowrap="nowrap">'.strftime("%H:%M",$obj->dp).'</td>';
					
					// Picto warning
					print '<td width="16">';
					if (date("U",$obj->dp) < time()) print ' '.img_warning("Late");
					else print '&nbsp;';
					print '</td>';
	
	                // Status/Percent
	                print '<td width="30">&nbsp;</td>';
	
	                if ($obj->propalrowid)
	                {
	                    print '<td><a href="propal.php?propalid='.$obj->propalrowid.'">'.img_object($langs->trans("ShowAction"),"task");
	                    $transcode=$langs->trans("Action".$obj->acode);
	                    $libelle=($transcode!="Action".$obj->acode?$transcode:$obj->libelle);
	                    print $libelle;
	                    print '</a></td>';
	                }
	                else
	                {
			            $actionstatic->code=$obj->acode;
			            $actionstatic->libelle=$obj->libelle;
			            $actionstatic->id=$obj->id;
			            print '<td>'.$actionstatic->getNomUrl(1,16).'</td>';
	                }
	                print '<td colspan="2">'.$obj->label.'</td>';
	
	                // Contact pour cette action
	                if ($obj->fk_contact > 0)
	                {
	                    $contactstatic->name=$obj->name;
	                    $contactstatic->firstname=$obj->firstname;
	                    $contactstatic->id=$obj->fk_contact;
		                print '<td>'.$contactstatic->getNomUrl(1).'</td>';
	                }
	                else
	                {
	                    print '<td>&nbsp;</td>';
	                }
	
	                print '<td width="80" nowrap="nowrap">';
					$userstatic->id=$obj->fk_user_author;
					$userstatic->login=$obj->login;
					print $userstatic->getLoginUrl(1);
					print '</td>';
	
					// Statut
	                print '<td nowrap="nowrap" width="20">'.$actionstatic->LibStatut($obj->percent,3).'</td>';
	
	                print "</tr>\n";
	                $i++;
	            }
			}
			else
			{
				// Aucun action a faire
					
			}
	        $db->free($result);
	    }
	    else
	    {
	        dolibarr_print_error($db);
	    }
	    print "</table>";
	
	    print "<br>";
    }
}

function show_actions_done($conf,$langs,$db,$objsoc)
{
	global $bc;
	
	if ($conf->agenda->enabled)
  {
  	require_once(DOL_DOCUMENT_ROOT."/actioncomm.class.php");
		$actionstatic=new ActionComm($db);
    $userstatic=new User($db);
    $contactstatic = new Contact($db);
    $facturestatic=new Facture($db);
    	
    	print '<table class="noborder" width="100%">';
	    print '<tr class="liste_titre">';
	    print '<td colspan="12"><a href="'.DOL_URL_ROOT.'/comm/action/index.php?socid='.$objsoc->id.'&amp;status=done">'.$langs->trans("ActionsDoneShort").'</a></td>';
	    print '</tr>';
	
	    $sql = "SELECT a.id, a.label,";
	    $sql.= " ".$db->pdate("a.datep")." as dp,";
	    $sql.= " ".$db->pdate("a.datea")." as da,";
	    $sql.= " a.percent,";
	    $sql.= " a.propalrowid, a.fk_facture, a.fk_user_author, a.fk_contact,";
	    $sql.= " c.code as acode, c.libelle,";
	    $sql.= " u.login, u.rowid,";
	    $sql.= " sp.name, sp.firstname";
	    $sql.= " FROM ".MAIN_DB_PREFIX."c_actioncomm as c, ".MAIN_DB_PREFIX."user as u, ".MAIN_DB_PREFIX."actioncomm as a";
	    $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."socpeople as sp ON a.fk_contact = sp.rowid";
	    $sql.= " WHERE a.fk_soc = ".$objsoc->id;
	    $sql.= " AND u.rowid = a.fk_user_author";
	    $sql.= " AND c.id=a.fk_action AND a.percent = 100";
	    $sql.= " ORDER BY a.datea DESC, a.id DESC";
	
		dolibarr_syslog("comm/fiche.php sql=".$sql);
	    $result=$db->query($sql);
	    if ($result)
	    {
	        $i = 0 ;
	        $num = $db->num_rows($result);
	        $oldyear='';
	        $oldmonth='';
	        $var=true;
	
	        while ($i < $num)
	        {
	            $var = !$var;
	
	            $obj = $db->fetch_object($result);
	            print "<tr $bc[$var]>";
	
	            // Champ date
                print '<td width="30" align="center">'.strftime("%Y",$obj->da)."</td>\n";
                $oldyear = strftime("%Y",$obj->da);
                print '<td width="30" align="center">'.strftime("%b",$obj->da)."</td>\n";
                $oldmonth = strftime("%Y%b",$obj->da);
	            print '<td width="20">'.strftime("%d",$obj->da)."</td>\n";
	            print '<td width="30">'.strftime("%H:%M",$obj->da)."</td>\n";
	
				// Picto
	            print '<td width="16">&nbsp;</td>';
	
	            // Espace
	            print '<td width="30">&nbsp;</td>';
	
				// Action
	    		print '<td>';
	            $actionstatic->code=$obj->acode;
	            $actionstatic->libelle=$obj->libelle;
	            $actionstatic->id=$obj->id;
	            print $actionstatic->getNomUrl(1,16);
				print '</td>';
	
	    		// Objet lie
	    		print '<td>';
				if ($obj->propalrowid)
				{
					print '<a href="'.DOL_URL_ROOT.'/comm/propal.php?propalid='.$obj->propalrowid.'">'.img_object($langs->trans("ShowPropal"),"propal");
					print $langs->trans("Propal");
					print '</a>';
				}
				if ($obj->fk_facture)
				{
					$facturestatic->ref=$langs->trans("Invoice");
					$facturestatic->id=$obj->fk_facture;
					$facturestatic->type=$obj->type;
					print $facturestatic->getNomUrl(1,'compta');
				}
				else print '&nbsp;';
	    		print '</td>';
	
				// Libelle
	      print '<td>'.$obj->label.'</td>';
	
	            // Contact pour cette action
	            if ($obj->fk_contact > 0)
	            {
					$contactstatic->name=$obj->name;
					$contactstatic->firstname=$obj->firstname;
					$contactstatic->id=$obj->fk_contact;
	                print '<td>'.$contactstatic->getNomUrl(1).'</td>';
	            }
	            else
	            {
	                print '<td>&nbsp;</td>';
	            }
	
				// Auteur
	            print '<td nowrap="nowrap" width="80">';
				$userstatic->id=$obj->rowid;
				$userstatic->login=$obj->login;
				print $userstatic->getLoginUrl(1);
				print '</td>';
	
				// Statut
	      print '<td nowrap="nowrap" width="20">'.$actionstatic->LibStatut($obj->percent,3).'</td>';
				
	            print "</tr>\n";
	            $i++;
	        }
	
	        $db->free($result);
	    }
	    else
	    {
	        dolibarr_print_error($db);
	    }
	 
	    print "</table><br>";
    }
}
?>
