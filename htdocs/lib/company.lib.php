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
 */

/**
 *	\file       htdocs/lib/company.lib.php
 *	\brief      Ensemble de fonctions de base pour le module societe
 *	\ingroup    societe
 *	\version    $Id$
 */

/**
 * Enter description here...
 *
 * @param unknown_type $objsoc
 * @return unknown
 */
function societe_prepare_head($objsoc)
{
	global $langs, $conf, $user;
	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/soc.php?socid='.$objsoc->id;
	$head[$h][1] = $langs->trans("Card");
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
		$head[$h][0] = DOL_URL_ROOT.'/comm/prospect/fiche.php?socid='.$objsoc->id;
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

	//show categorie tab
	if ($conf->categorie->enabled)
	{
		$head[$h][0] = DOL_URL_ROOT.'/categories/categorie.php?socid='.$objsoc->id;
		$head[$h][1] = $langs->trans('Categories');
		$head[$h][2] = 'category';
		$h++;
	}
	if ($user->societe_id == 0)
	{
		$head[$h][0] = DOL_URL_ROOT.'/societe/socnote.php?socid='.$objsoc->id;
		$head[$h][1] = $langs->trans("Note");
		$head[$h][2] = 'note';
		$h++;
	}
	if ($user->societe_id == 0)
	{
		$head[$h][0] = DOL_URL_ROOT.'/societe/document.php?socid='.$objsoc->id;
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

	return $head;
}


/**
 * Enter description here...
 *
 * @param unknown_type $objsoc
 * @return unknown
 */
function societe_prepare_head2($objsoc)
{
	global $langs, $conf, $user;
	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/soc.php?socid='.$objsoc->id;
	$head[$h][1] = $langs->trans("Card");
	$head[$h][2] = 'company';
	$h++;

	$head[$h][0] = DOL_URL_ROOT .'/societe/rib.php?socid='.$objsoc->id;
	$head[$h][1] = $langs->trans("BankAccount")." $account->number";
	$head[$h][2] = 'rib';
	$h++;

	$head[$h][0] = 'lien.php?socid='.$objsoc->id;
	$head[$h][1] = $langs->trans("Links");
	$head[$h][2] = 'links';
	$h++;

	$head[$h][0] = 'commerciaux.php?socid='.$objsoc->id;
	$head[$h][1] = $langs->trans("SalesRepresentative");
	$head[$h][2] = 'salesrepresentative';
	$h++;

	return $head;
}


/**
 *    \brief      Retourne le nom traduit ou code+nom d'un pays
 *    \param      id          id du pays
 *    \param      withcode    1=affiche code + nom
 *    \return     string      Nom traduit du pays
 */
function getCountryLabel($id,$withcode=0)
{
	global $db,$langs;

	$sql = "SELECT rowid, code, libelle FROM ".MAIN_DB_PREFIX."c_pays";
	$sql.= " WHERE rowid=".$id;

	dolibarr_syslog("Company.lib::getCountryLabel sql=".$sql);
	$resql=$db->query($sql);
	if ($resql)
	{
		$obj = $db->fetch_object($resql);
		if ($obj)
		{
			$label=$obj->code && $langs->trans("Country".$obj->code)!="Country".$obj->code?$langs->trans("Country".$obj->code):($obj->libelle!='-'?$obj->libelle:'');
			if ($withcode) return $label==$obj->code?"$obj->code":"$obj->code - $label";
			else return $label;
		}
		else
		{
			return $langs->trans("NotDefined");
		}
		$db->free($resql);
	}
}


/**
 *    \brief      Retourne le nom traduit de la forme juridique
 *    \param      code        Code de la forme juridique
 *    \return     string      Nom traduit du pays
 */
function getFormeJuridiqueLabel($code)
{
	global $db,$langs;

	if (! $code) return '';

	$sql = "SELECT libelle FROM ".MAIN_DB_PREFIX."c_forme_juridique";
	$sql.= " WHERE code='$code'";

	dolibarr_syslog("Company.lib::getFormeJuridiqueLabel sql=".$sql);
	$resql=$db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);

		if ($num)
		{
			$obj = $db->fetch_object($resql);
			$label=($obj->libelle!='-' ? $obj->libelle : '');
			return $label;
		}
		else
		{
			return $langs->trans("NotDefined");
		}

	}
}


/**
 * 		\brief		Show html area for list of contacts
 *		\param		conf		Object conf
 * 		\param		lang		Object lang
 * 		\param		db			Database handler
 * 		\param		objsoc		Third party object
 */
function show_contacts($conf,$langs,$db,$objsoc)
{
	global $user;
	global $bc;

	$contactstatic = new Contact($db);

	if ($conf->clicktodial->enabled)
	{
		$user->fetch_clicktodial(); // lecture des infos de clicktodial
	}

	print_titre($langs->trans("ContactsForCompany"));
	print '<table class="noborder" width="100%">';

	print '<tr class="liste_titre"><td>'.$langs->trans("Name").'</td>';
	print '<td>'.$langs->trans("Poste").'</td><td>'.$langs->trans("Tel").'</td>';
	print '<td>'.$langs->trans("Fax").'</td><td>'.$langs->trans("EMail").'</td>';
	print "<td>&nbsp;</td>";
	if ($conf->agenda->enabled && $user->rights->agenda->myactions->create)
	{
		print '<td>&nbsp;</td>';
	}
	print "</tr>";

	$sql = "SELECT p.rowid, p.name, p.firstname, p.poste, p.phone, p.fax, p.email, p.note ";
	$sql .= " FROM ".MAIN_DB_PREFIX."socpeople as p";
	$sql .= " WHERE p.fk_soc = ".$objsoc->id;
	$sql .= " ORDER by p.datec";

	$result = $db->query($sql);
	$i = 0;
	$num = $db->num_rows($result);
	$var=true;

	if ($num)
	{
		while ($i < $num)
		{
			$obj = $db->fetch_object($result);
			$var = !$var;

			print "<tr ".$bc[$var].">";

			print '<td>';
			$contactstatic->id = $obj->rowid;
			$contactstatic->name = $obj->name;
			$contactstatic->firstname = $obj->firstname;
			print $contactstatic->getNomUrl(1);
			print '</td>';

			print '<td>'.$obj->poste.'</td>';

			// Lien click to dial
			print '<td>';
			print dol_print_phone($obj->phone,$obj->pays_code,$obj->rowid,$objsoc->id,'AC_TEL');
			print '</td>';
			print '<td>';
			print dol_print_phone($obj->fax,$obj->pays_code,$obj->rowid,$objsoc->id,'AC_FAX');
			print '</td>';
			print '<td>';
			print dol_print_email($obj->email,$obj->rowid,$objsoc->id,'AC_EMAIL');
			print '</td>';

			print '<td align="center">';
			print '<a href="'.DOL_URL_ROOT.'/contact/fiche.php?action=edit&amp;id='.$obj->rowid.'">';
			print img_edit();
			print '</a></td>';

			if ($conf->agenda->enabled && $user->rights->agenda->myactions->create)
			{
				print '<td align="center"><a href="'.DOL_URL_ROOT.'/comm/action/fiche.php?action=create&backtopage=1&actioncode=AC_RDV&contactid='.$obj->rowid.'&socid='.$objsoc->id.'">';
				print img_object($langs->trans("Rendez-Vous"),"action");
				print '</a></td>';
			}

			print "</tr>\n";
			$i++;
		}
	}
	else
	{
		//print "<tr ".$bc[$var].">";
		//print '<td>'.$langs->trans("NoContactsYetDefined").'</td>';
		//print "</tr>\n";
	}
	print "</table>\n";

	print "<br>\n";
}


/**
 *    	\brief      Show html area with actions to do
 * 		\param		conf		Object conf
 * 		\param		langs		Object langs
 * 		\param		db			Object db
 * 		\param		objsoc		Object third party
 * 		\param		objcon		Object contact
 */
function show_actions_todo($conf,$langs,$db,$objsoc,$objcon='')
{
	global $bc;

	if ($conf->agenda->enabled)
	{
		require_once(DOL_DOCUMENT_ROOT."/actioncomm.class.php");
		$actionstatic=new ActionComm($db);
		$userstatic=new User($db);
		$contactstatic = new Contact($db);

		if (is_object($objcon) && $objcon->id) print_titre($langs->trans("TasksHistoryForThisContact"));
		else print_titre($langs->trans("ActionsOnCompany"));

		print '<table width="100%" class="noborder">';
		print '<tr class="liste_titre">';
		print '<td colspan="7"><a href="'.DOL_URL_ROOT.'/comm/action/listactions.php?socid='.$objsoc->id.'&amp;status=todo">'.$langs->trans("ActionsToDoShort").'</a></td><td align="right">&nbsp;</td>';
		print '</tr>';

		$sql = "SELECT a.id, a.label,";
		$sql.= " ".$db->pdate("a.datep")." as dp,";
		$sql.= " ".$db->pdate("a.datea")." as da,";
		$sql.= " a.percent,";
		$sql.= " a.propalrowid, a.fk_user_author, a.fk_contact,";
		$sql.= " c.code as acode, c.libelle,";
		$sql.= " u.login, u.rowid,";
		$sql.= " sp.name, sp.firstname";
		$sql.= " FROM ".MAIN_DB_PREFIX."c_actioncomm as c, ".MAIN_DB_PREFIX."user as u, ".MAIN_DB_PREFIX."actioncomm as a";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."socpeople as sp ON a.fk_contact = sp.rowid";
		$sql.= " WHERE u.rowid = a.fk_user_author";
		if ($objsoc->id) $sql.= " AND a.fk_soc = ".$objsoc->id;
		if (is_object($objcon) && $objcon->id) $sql.= " AND a.fk_contact = ".$objcon->id;
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
					print "<tr ".$bc[$var].">";

					print '<td width="100" align="left">'.dolibarr_print_date($obj->dp,'dayhour')."</td>\n";

					// Picto warning
					print '<td width="16">';
					if ($obj->dp && date("U",$obj->dp) < time()) print ' '.img_warning("Late");
					else print '&nbsp;';
					print '</td>';

					if ($obj->propalrowid)
					{
						print '<td width="140" ><a href="propal.php?propalid='.$obj->propalrowid.'">'.img_object($langs->trans("ShowAction"),"task");
						$transcode=$langs->trans("Action".$obj->acode);
						$libelle=($transcode!="Action".$obj->acode?$transcode:$obj->libelle);
						print $libelle;
						print '</a></td>';
					}
					else
					{
						$actionstatic->type_code=$obj->acode;
						$actionstatic->libelle=$obj->libelle;
						$actionstatic->id=$obj->id;
						print '<td width="140">'.$actionstatic->getNomUrl(1,16).'</td>';
					}

					print '<td colspan="2">'.$obj->label.'</td>';

					// Contact pour cette action
					if (! $objcon->id && $obj->fk_contact > 0)
					{
						$contactstatic->name=$obj->name;
						$contactstatic->firstname=$obj->firstname;
						$contactstatic->id=$obj->fk_contact;
						print '<td width="120">'.$contactstatic->getNomUrl(1,'',10).'</td>';
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
		print "</table>\n";

		print "<br>\n";
	}
}

/**
 *    	\brief      Show html area with actions done
 * 		\param		conf		Object conf
 * 		\param		langs		Object langs
 * 		\param		db			Object db
 * 		\param		objsoc		Object third party
 * 		\param		objcon		Object contact
 */
function show_actions_done($conf,$langs,$db,$objsoc,$objcon='')
{
	global $bc;

	$histo=array();
	$numaction = 0 ;

	if ($conf->agenda->enabled)
	{
		// Recherche histo sur actioncomm
		$sql = "SELECT a.id, a.label,";
		$sql.= " ".$db->pdate("a.datep")." as dp,";
		$sql.= " ".$db->pdate("a.datep2")." as dp2,";
		$sql.= " a.note, a.percent,";
		$sql.= " a.propalrowid as pid, a.fk_commande as oid, a.fk_facture as fid,";
		$sql.= " a.fk_user_author, a.fk_contact,";
		$sql.= " c.code as acode, c.libelle,";
		$sql.= " u.login, u.rowid,";
		$sql.= " sp.name, sp.firstname";
		$sql.= " FROM ".MAIN_DB_PREFIX."c_actioncomm as c, ".MAIN_DB_PREFIX."user as u, ".MAIN_DB_PREFIX."actioncomm as a";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."socpeople as sp ON a.fk_contact = sp.rowid";
		$sql.= " WHERE u.rowid = a.fk_user_author";
		if ($objsoc->id) $sql.= " AND a.fk_soc = ".$objsoc->id;
		if (is_object($objcon) && $objcon->id) $sql.= " AND a.fk_contact = ".$objcon->id;
		$sql.= " AND c.id=a.fk_action";
		$sql.= " AND a.percent = 100";
		$sql.= " ORDER BY a.datea DESC, a.id DESC";

		dolibarr_syslog("company.lib::show_actions_done sql=".$sql, LOG_DEBUG);
		$resql=$db->query($sql);
		if ($resql)
		{
			$i = 0 ;
			$num = $db->num_rows($resql);
			$var=true;
			while ($i < $num)
			{
				$obj = $db->fetch_object($resql);
				$histo[$numaction]=array('type'=>'action','id'=>$obj->id,'date'=>$obj->dp2,'note'=>$obj->label,'percent'=>$obj->percent,
				'acode'=>$obj->acode,'libelle'=>$obj->libelle,
				'userid'=>$obj->user_id,'login'=>$obj->login,
				'contact_id'=>$obj->fk_contact,'name'=>$obj->name,'firstname'=>$obj->firstname,
				'pid'=>$obj->pid,'oid'=>$obj->oid,'fid'=>$obj->fid);
				$numaction++;
				$i++;
			}
		}
		else
		{
			dolibarr_print_error($db);
		}
	}

	if ($conf->mailing->enabled && $objcon->email)
	{
		$langs->load("mails");

		// Recherche histo sur mailing
		$sql = "SELECT m.rowid as id, ".$db->pdate("mc.date_envoi")." as da, m.titre as note, '100' as percentage,";
		$sql.= " 'AC_EMAILING' as acode,";
		$sql.= " u.rowid as user_id, u.login";	// User that valid action
		$sql.= " FROM ".MAIN_DB_PREFIX."mailing as m, ".MAIN_DB_PREFIX."mailing_cibles as mc, ".MAIN_DB_PREFIX."user as u";
		$sql.= " WHERE mc.email = '".addslashes($objcon->email)."'";	// Search is done on email.
		$sql.= " AND mc.statut = 1";
		$sql.= " AND u.rowid = m.fk_user_valid";
		$sql.= " AND mc.fk_mailing=m.rowid";
		$sql.= " ORDER BY mc.date_envoi DESC, m.rowid DESC";

		dolibarr_syslog("company.lib::show_actions_done sql=".$sql, LOG_DEBUG);
		$resql=$db->query($sql);
		if ($resql)
		{
			$i = 0 ;
			$num = $db->num_rows($resql);
			$var=true;
			while ($i < $num)
			{
				$obj = $db->fetch_object($resql);
				$histo[$numaction]=array('type'=>'mailing','id'=>$obj->id,'date'=>$obj->da,'note'=>$obj->note,'percent'=>$obj->percentage,
				'acode'=>$obj->acode,'libelle'=>$obj->libelle,
				'userid'=>$obj->user_id,'login'=>$obj->login,
				'contact_id'=>$obj->contact_id);
				$numaction++;
				$i++;
			}
		}
		else
		{
			dolibarr_print_error($db);
		}
	}


	if ($conf->agenda->enabled || ($conf->mailing->enabled && $objcon->email))
	{
		require_once(DOL_DOCUMENT_ROOT."/actioncomm.class.php");
		require_once(DOL_DOCUMENT_ROOT."/propal.class.php");
		require_once(DOL_DOCUMENT_ROOT."/commande/commande.class.php");
		require_once(DOL_DOCUMENT_ROOT."/facture.class.php");
		$actionstatic=new ActionComm($db);
		$userstatic=new User($db);
		$contactstatic = new Contact($db);
		$propalstatic=new Propal($db);
		$orderstatic=new Commande($db);
		$facturestatic=new Facture($db);

		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print '<td colspan="8"><a href="'.DOL_URL_ROOT.'/comm/action/listactions.php?socid='.$objsoc->id.'&amp;status=done">'.$langs->trans("ActionsDoneShort").'</a></td>';
		print '</tr>';

		foreach ($histo as $key=>$value)
		{
			$var=!$var;
			print "<tr ".$bc[$var].">";

			// Champ date
			print '<td width="100" align="left">'.dolibarr_print_date($histo[$key]['date'],'dayhour')."</td>\n";

			// Picto
			print '<td width="16">&nbsp;</td>';

			// Action
			print '<td width="140">';
			if ($histo[$key]['type']=='action')
			{
				$actionstatic->type_code=$histo[$key]['acode'];
				$actionstatic->libelle=$histo[$key]['libelle'];
				$actionstatic->id=$histo[$key]['id'];
				print $actionstatic->getNomUrl(1,16);
			}
			if ($histo[$key]['type']=='mailing')
			{
				print '<a href="'.DOL_URL_ROOT.'/comm/mailing/fiche.php?id='.$histo[$key]['id'].'">'.img_object($langs->trans("ShowEMailing"),"email").' ';
				$transcode=$langs->trans("Action".$histo[$key]['acode']);
				$libelle=($transcode!="Action".$histo[$key]['acode']?$transcode:'Send mass mailing');
				print dolibarr_trunc($libelle,30);
			}
			print '</td>';

			// Note
			print '<td>'.dolibarr_trunc($histo[$key]['note'], 30).'</td>';

			// Objet lie
			print '<td>';
			if ($histo[$key]['pid'] && $conf->propal->enabled)
			{
				print '<a href="'.DOL_URL_ROOT.'/comm/propal.php?propalid='.$histo[$key]['pid'].'">'.img_object($langs->trans("ShowPropal"),"propal");
				print $langs->trans("Propal");
				print '</a>';
			}
			elseif ($histo[$key]['oid'] && $conf->commande->enabled)
			{
				$orderstatic->ref=$langs->trans("Order");
				$orderstatic->id=$histo[$key]['oid'];
				print $orderstatic->getNomUrl(1);
			}
			elseif ($histo[$key]['fid'] && $conf->facture->enabled)
			{
				$facturestatic->ref=$langs->trans("Invoice");
				$facturestatic->id=$histo[$key]['fid'];
				$facturestatic->type=$histo[$key]['ftype'];
				print $facturestatic->getNomUrl(1,'compta');
			}
			else print '&nbsp;';
			print '</td>';

			// Contact pour cette action
			if (! $objcon->id && $histo[$key]['contact_id'] > 0)
			{
				$contactstatic->name=$histo[$key]['name'];
				$contactstatic->firstname=$histo[$key]['firstname'];
				$contactstatic->id=$histo[$key]['contact_id'];
				print '<td width="120">'.$contactstatic->getNomUrl(1,'',10).'</td>';
			}
			else
			{
				print '<td>&nbsp;</td>';
			}

			// Auteur
			print '<td nowrap="nowrap" width="80">';
			$userstatic->id=$histo[$key]['userid'];
			$userstatic->login=$histo[$key]['login'];
			print $userstatic->getLoginUrl(1);
			print '</td>';

			// Statut
			print '<td nowrap="nowrap" width="20">'.$actionstatic->LibStatut($histo[$key]['percent'],3).'</td>';

			print "</tr>\n";
			$i++;
		}

		$db->free($result);
	}

	print "</table>\n";
	print "<br>\n";
}

?>
