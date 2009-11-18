<?php
/* Copyright (C) 2007-2008 Jeremie Ollivier <jeremie.o@laposte.net>
 * Copyright (C) 2008 Laurent Destailleur   <eldy@uers.sourceforge.net>
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

require ('../master.inc.php');
require ('include/environnement.php');
require ('classes/Facturation.class.php');

$obj_facturation = unserialize ($_SESSION['serObjFacturation']);
unset ($_SESSION['serObjFacturation']);


switch ( $_GET['action'] )
{
	default:
		if ( $_POST['hdnSource'] != 'NULL' )
		{
			$sql = "SELECT p.rowid, p.ref, p.price, p.tva_tx";
			if ($conf->stock->enabled && !empty($conf_fkentrepot)) $sql.= ", ps.reel";
			$sql.= " FROM ".MAIN_DB_PREFIX."product as p";
			if ($conf->stock->enabled && !empty($conf_fkentrepot)) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_stock as ps ON p.rowid = ps.fk_product AND ps.fk_entrepot = ".$conf_fkentrepot;
			
			// Recuperation des donnees en fonction de la source (liste deroulante ou champ texte) ...
			if ( $_POST['hdnSource'] == 'LISTE' )
			{
				$sql.= " WHERE p.rowid = ".$_POST['selProduit'];
			}
			else if ( $_POST['hdnSource'] == 'REF' )
			{
				$sql.= " WHERE p.ref = '".$_POST['txtRef']."'";
			}
			
			$result = $db->query($sql);
			
			if ($result)
			{
				// ... et enregistrement dans l'objet
				if ( $db->num_rows ($result) )
				{
					$ret=array();
					$tab = $db->fetch_array($result);
					foreach ( $tab as $key => $value )
					{
						$ret[$key] = $value;
					}
					
					$obj_facturation->id( $ret['rowid'] );
					$obj_facturation->ref( $ret['ref'] );
					$obj_facturation->stock( $ret['reel'] );
					$obj_facturation->prix( $ret['price'] );
					$obj_facturation->tva( $ret['tva_tx'] );
					
					// Definition du filtre pour n'afficher que le produit concerne
					if ( $_POST['hdnSource'] == 'LISTE' )
					{
						$filtre = $ret['ref'];
					}
					else if ( $_POST['hdnSource'] == 'REF' )
					{
						$filtre = $_POST['txtRef'];
					}
					
					$redirection = DOL_URL_ROOT.'/cashdesk/affIndex.php?menu=facturation&filtre='.$filtre;
				}
				else
				{
					$obj_facturation->raz();
					
					if ( $_POST['hdnSource'] == 'REF' )
					{
						$redirection = DOL_URL_ROOT.'/cashdesk/affIndex.php?menu=facturation&filtre='.$_POST['txtRef'];
					}
					else
					{
						$redirection = DOL_URL_ROOT.'/cashdesk/affIndex.php?menu=facturation';
					}
				}
			}
			else
			{
				dol_print_error($db);
			}
		}
		else
		{
			$redirection = DOL_URL_ROOT.'/cashdesk/affIndex.php?menu=facturation';
		}

	break;

	case 'ajout_article':
	$obj_facturation->qte($_POST['txtQte']);
	$obj_facturation->tva($_POST['selTva']);
	$obj_facturation->remise_percent($_POST['txtRemise']);
	$obj_facturation->ajoutArticle();

	$redirection = DOL_URL_ROOT.'/cashdesk/affIndex.php?menu=facturation';
	break;

	case 'suppr_article':
	$obj_facturation->supprArticle($_GET['suppr_id']);

	$redirection = DOL_URL_ROOT.'/cashdesk/affIndex.php?menu=facturation';
	break;

}


$_SESSION['serObjFacturation'] = serialize ($obj_facturation);

header ('Location: '.$redirection);
?>
