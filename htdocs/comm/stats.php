<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2008 Regis Houssin        <regis@dolibarr.fr>
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
        \file       htdocs/comm/index.php
        \ingroup    commercial
        \brief      Page acceuil de la zone commercial cliente
        \version    $Id$
*/
 
require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/dolgraph.class.php");


/*
 * View
 */

llxHeader('',"Stats");


/**************
 * Paramètrage
 *************/
// Dossier où générer les fichiers
$dir = $conf->commercial->dir_temp . '/' .$user->id ;
create_exdir($dir);

// graphes 
$graphwidth = 380 ;
$graphheight = 160 ;


// Chaine contenant les messages d'erreur
$msq = '' ;

// Date de début du graphe
$date_debut = time() ;
$annees = "" ;
if ($conf->global->SOCIETE_FISCAL_MONTH_START < dolibarr_date("m",time()) ){
	// Si le mois actuel est plus grand, l'année de départ est là même que l'année actuelle
	$date_debut = mktime(0,0,0,$conf->global->SOCIETE_FISCAL_MONTH_START,1,dolibarr_date("Y",time())) ;
	$annees = dolibarr_date("Y",time()) ;
} else {
	// Sinon le début de l'année comptable était l'année d'avant
	$date_debut = mktime(0,0,0,$conf->global->SOCIETE_FISCAL_MONTH_START,1,dolibarr_date("Y",time())-1) ;
	$annees = (dolibarr_date("Y",time())-1).' - '.(dolibarr_date("Y",time())) ;
}

/**********************************************
 * Récupération et génération des Infomations
 **********************************************/
$sql = "SELECT sum(d.qty * d.price) as CAMois, sum( d.qty * (d.price - p.price_min) ) as MRMois, date_format(c.date_valid, '%Y%m') as date, date_format(c.date_valid, '%b') as month";
$sql .= " FROM ".MAIN_DB_PREFIX."commandedet as d, ".MAIN_DB_PREFIX."commande as c, ".MAIN_DB_PREFIX."product as p";
$sql .= " WHERE c.rowid = d.fk_commande and d.fk_product = p.rowid";
$sql .= " AND c.fk_user_author = ".$user->id;
$sql .= " AND c.date_valid > ".$db->idate($date_debut)." AND c.date_valid <= ".$db->idate(mktime());
$sql .= " GROUP BY date_format(c.date_valid,'%Y%m') ASC";

$result = $db->query($sql) ;

// On Récupère tout à la fois
$recapAnneeCA = array() ;
$recapMoisCA = array() ;
$recapAnneeMR = array() ;
$recapMoisMR = array() ;
if($result){
	if($db->num_rows($result)>0){
		while($obj = $db->fetch_object($result)){
			if($obj->date != dolibarr_date("Ym",time())){
				$recapAnneeCA[] = array( $obj->month, $obj->CAMois ) ;
				$recapAnneeMR[] = array( $obj->month, $obj->MRMois ) ;
			}else{
				$recapMoisCA[] = array( $obj->month, $obj->CAMois ) ;
				$recapMoisMR[] = array( $obj->month, $obj->MRMois ) ;
			}
		}
	} else {
		$mesg = 'Aucune enregistrement retourné' ; 
	}
} else {
	$mesg = 'erreur sql : '.$db->error().'<br> requète : '.$db->lastquery().'<br>' ; 
}

$graphfiles=array(
		'recapAnneeCA'           =>array(
			'file' => 'recapAnneeCA.png', 
			'label' => $langs->trans('CAOrder').' - '.$annees,
			'data' => $recapAnneeCA
		),
		'recapAnneeMR'           =>array(
			'file' => 'recapAnneeMR.png', 
			'label' => $langs->trans('MargeOrder').' - '.$annees,
			'data' => $recapAnneeMR
		),
		'recapMoisCA'         =>array(
			'file' => 'recapMoisCA.png', 
			'label' => $langs->trans('CAOrder').' '.$langs->trans('FromTo',dolibarr_date("01/m/Y",time()),dolibarr_date("d/m/Y",time())),
			'data' => $recapMoisCA
		),
		'recapMoisMR'=>array(
			'file' => 'recapMoisMR.png', 
			'label' => $langs->trans('MargeOrder').' '.$langs->trans('FromTo',dolibarr_date("01/m/Y",time()),dolibarr_date("d/m/Y",time())),
			'data' => $recapMoisMR
		)
) ;


/***********************
 * Affichage de graphes
 ***********************/	
$px = new DolGraph();
$msggraph = $px->isGraphKo();
if (! $mesg)
{
	
	foreach($graphfiles as $key => $graph){
			$graph_data = $graph['data'] ;
			
			if (is_array($graph_data))
			{
				$px->SetData($graph_data);
				$px->SetMaxValue($px->GetCeilMaxValue()<0?0:$px->GetCeilMaxValue());
				$px->SetMinValue($px->GetFloorMinValue()>0?0:$px->GetFloorMinValue());
				$px->SetWidth($graphwidth);
				$px->SetHeight($graphheight);
				$px->SetHorizTickIncrement(1);
				$px->SetPrecisionY(0);
				$px->SetShading(3);

				$px->draw($dir."/".$graph['file']);

			}
			else
			{
				dolibarr_print_error($db,'Error for calculating graph on key='.$key.' - '.$product->error);
			}
	}
	
} else {
	$msg.=$msggraph ;
}

/************
 * Affichage
 ************/
// en-tête
if($mesg) print '<div class="error">'.$mesg.'</div>' ;
dolibarr_fiche_head(array(array('stats.php',$langs->trans("Commercial"))), 0, $langs->trans("Stats"));
/*****************************
 * Rappel Infos du Commercial
 *****************************/
print '<table class="border" width="100%">
	<tr>
		<td width="15%">'.$langs->trans("Name").'</td><td colspan="3">'.$user->nom.'</td>
	</tr>
	<tr>
		<td width="15%">'.$langs->trans("Surname").'</td><td colspan="3">'.$user->prenom.'</td>
	</tr>
	<tr>
		<td width="15%">'.$langs->trans("Login").'</td><td colspan="3">'.$user->login.'</td>
	</tr>
</table>' ;

print "</div>" ; // Fin de dolibarr_fiche_head

/***************************
 * Affichage des Graphiques
 ***************************/
//tableaux
foreach($graphfiles as $graph){
	// données
	$url=DOL_URL_ROOT.'/viewimage.php?modulepart=graph_comm&file='.urlencode($user->id .'/'.$graph['file']);
	$generateOn = (file_exists($dir."/".$graph['file']))? $langs->trans("GeneratedOn",dolibarr_print_date(filemtime($dir."/".$graph['file']),"dayhour")) : "" ;
	
	// html
	print '<table class="border" style="float:left;margin:5px;width:48%;min-width:400px;">
	<tr class="liste_titre">
		<td colspan="2">'.$graph['label'].'</td>
	</tr>
	<tr>
		<td colspan="2" align="center" style="padding:5px;">
			<img src="'.$url.'" alt="'.$langs->trans("NoData").'" style="float:left" width="'.$graphwidth.'">
			<table class="border" style="float:right">
				<tr class="liste_titre">
					<td align="center">'.$langs->trans("Month").'</td><td align="center">'.$langs->trans("Currency".$conf->monnaie).'</td>
				</tr>'.array2table($graph['data'],0,'','','align="center"').'
			</table>
		</td>
	</tr>
	<tr>
		<td>'.$generateOn.'</td>
		<td align="center"><a href="stats.php?action=recalcul">'.img_picto($langs->trans("ReCalculate"),'refresh').'</a></td>
	</tr>
</table>' ;
}

/**************
 * Fin de page
 **************/
$db->close();
llxFooter();
?>
