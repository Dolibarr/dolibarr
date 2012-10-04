<?php

/** a mettre dans facture.php **/
// Affichage d'autres éléments ajoutables à la facture
if($conf->global->FACTURE_MULTI_ORIGIN) include_once(DOL_DOCUMENT_ROOT.'/compta/facture_multi_origin.php');

if(empty($origin)) return 0;
else if ($origin == 'commande') {

	// Show other origin objects mergeable into same invoice
	print_titre($langs->trans('OtherOrders'));
	
	// Display list of elements
	// 1 - Filters
	$sref			= GETPOST('sref');
	$sref_client	= GETPOST('sref_client');
	$sall			= GETPOST('sall');
	$date_start=dol_mktime(0,0,0,$_REQUEST["date_startmonth"],$_REQUEST["date_startday"],$_REQUEST["date_startyear"]);	// Date for local PHP server
	$date_end=dol_mktime(23,59,59,$_REQUEST["date_endmonth"],$_REQUEST["date_endday"],$_REQUEST["date_endyear"]);
	$date_starty=dol_mktime(0,0,0,$_REQUEST["date_start_delymonth"],$_REQUEST["date_start_delyday"],$_REQUEST["date_start_delyyear"]);	// Date for local PHP server
	$date_endy=dol_mktime(23,59,59,$_REQUEST["date_end_delymonth"],$_REQUEST["date_end_delyday"],$_REQUEST["date_end_delyyear"]);
	
	$sortfield = GETPOST("sortfield",'alpha');
	$sortorder = GETPOST("sortorder",'alpha');
	if (! $sortfield) $sortfield='c.rowid';
	if (! $sortorder) $sortorder='DESC';
	
	$html = new Form($db);
	$htmlother = new FormOther($db);
	$formfile = new FormFile($db);
	$companystatic = new Societe($db);
	
	// 2 - Query for orders
	$sql = 'SELECT s.nom, s.rowid as socid, s.client, c.rowid, c.ref, c.total_ht, c.ref_client,';
	$sql.= ' c.date_valid, c.date_commande, c.date_livraison, c.fk_statut, c.facture as facturee';
	$sql.= ' FROM '.MAIN_DB_PREFIX.'societe as s';
	$sql.= ', '.MAIN_DB_PREFIX.'commande as c';
	if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql.= ' WHERE c.entity = '.$conf->entity;
	$sql.= ' AND c.fk_soc = s.rowid';
	
	// Show orders with status validated, shipping started and delivered (well any order we can bill)
	$sql.= " AND ((c.fk_statut IN (1,2)) OR (c.fk_statut = 3 AND c.facture = 0))";
	
	if ($socid)	$sql.= ' AND s.rowid = '.$socid;
	if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
	if ($sref)
	{
		$sql.= " AND c.ref LIKE '%".$db->escape($sref)."%'";
	}
	if ($sall)
	{
		$sql.= " AND (c.ref LIKE '%".$db->escape($sall)."%' OR c.note LIKE '%".$db->escape($sall)."%')";
	}
	if (!empty($sref_client))
	{
		$sql.= ' AND c.ref_client LIKE \'%'.$db->escape($sref_client).'%\'';
	}
	
	// Date filter
	if ($date_start && $date_end) $sql.= " AND c.date_commande >= '".$db->idate($date_start)."' AND c.date_commande <= '".$db->idate($date_end)."'";
	if ($date_starty && $date_endy) $sql.= " AND c.date_livraison >= '".$db->idate($date_starty)."' AND c.date_livraison <= '".$db->idate($date_endy)."'";
	
	$sql.= ' ORDER BY '.$sortfield.' '.$sortorder;
	$resql = $db->query($sql);
	
	if ($resql)
	{
		if ($socid)
		{
			$soc = new Societe($db);
			$soc->fetch($socid);
		}
		$num = $db->num_rows($resql);
	
		$i = 0;
		$period=$html->select_date($date_start,'date_start',0,0,1,'',1,0,1).' - '.$html->select_date($date_end,'date_end',0,0,1,'',1,0,1);
		$periodely=$html->select_date($date_starty,'date_start_dely',0,0,1,'',1,0,1).' - '.$html->select_date($date_endy,'date_end_dely',0,0,1,'',1,0,1);
	
		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print_liste_field_titre($langs->trans('Ref'),'orderstoinvoice.php','c.ref','','&amp;socid='.$socid,'',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans('RefCustomerOrder'),'orderstoinvoice.php','c.ref_client','','&amp;socid='.$socid,'',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans('OrderDate'),'orderstoinvoice.php','c.date_commande','','&amp;socid='.$socid, 'align="center"',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans('DeliveryDate'),'orderstoinvoice.php','c.date_livraison','','&amp;socid='.$socid, 'align="center"',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans('Status'),'','','','','align="right"');
		print_liste_field_titre($langs->trans('GenerateBill'),'','','','','align="center"');
		print '</tr>';
	
		// Lignes des champs de filtre
		print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
		print '<input type="hidden" name="socid" value="'.$socid.'">';
		print '<input type="hidden" name="action" value="'.$action.'">';
		print '<input type="hidden" name="origin" value="'.$origin.'">';
		print '<input type="hidden" name="originid" value="'.$originid.'">';
		print '<tr class="liste_titre">';
		print '<td class="liste_titre">';
		//REF
		print '<input class="flat" size="10" type="text" name="sref" value="'.$sref.'">';
		print '</td>';
		//print '<td class="liste_titre">';
		print '<td class="liste_titre" align="left">';
		print '<input class="flat" type="text" size="10" name="sref_client" value="'.$sref_client.'">';
	
		//DATE ORDER
		print '<td class="liste_titre" align="center">';
		print $period;
		print '</td>';
	
		//DATE DELIVERY
		print '<td class="liste_titre" align="center">';
		print $periodely;
		print '</td>';
	
		//SEARCH BUTTON
		print '</td><td align="right" class="liste_titre">';
		print '<input type="image" class="liste_titre" name="button_search" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png"  value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
	
		//ALL/NONE
		print '<td class="liste_titre" align="center">';
		if ($conf->use_javascript_ajax) print '<a href="#" id="checkall">'.$langs->trans("All").'</a> / <a href="#" id="checknone">'.$langs->trans("None").'</a>';
		print '</td>';
	
		print '</td></tr>';
		print '</form>';
	
		print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
		print '<input type="hidden" name="socid" value="'.$socid.'">';
		print '<input type="hidden" name="action" value="'.$action.'">';
		print '<input type="hidden" name="origin" value="'.$origin.'">';
		print '<input type="hidden" name="originid[]" value="'.$originid.'">';
		$var=True;
		$generic_commande = new Commande($db);
	
		while ($i < $num)
		{
			$objp = $db->fetch_object($resql);
			$var=!$var;
			print '<tr '.$bc[$var].'>';
			print '<td nowrap="nowrap">';
	
			$generic_commande->id=$objp->rowid;
			$generic_commande->ref=$objp->ref;
	
			print '<table class="nobordernopadding"><tr class="nocellnopadd">';
			print '<td class="nobordernopadding" nowrap="nowrap">';
			print $generic_commande->getNomUrl(1,$objp->fk_statut);
			print '</td>';
	
			print '<td width="20" class="nobordernopadding" nowrap="nowrap">';
			if (($objp->fk_statut > 0) && ($objp->fk_statut < 3) && $db->jdate($objp->date_valid) < ($now - $conf->commande->client->warning_delay)) print img_picto($langs->trans("Late"),"warning");
			print '</td>';
	
			print '<td width="16" align="right" class="nobordernopadding">';
			$filename=dol_sanitizeFileName($objp->ref);
			$filedir=$conf->commande->dir_output . '/' . dol_sanitizeFileName($objp->ref);
			$urlsource=$_SERVER['PHP_SELF'].'?id='.$objp->rowid;
			print $formfile->getDocumentsLink($generic_commande->element, $filename, $filedir);
			print '</td></tr></table>';
			print '</td>';
	
			print '<td>'.$objp->ref_client.'</td>';
	
			// Order date
			print '<td align="center" nowrap>';
			print dol_print_date($db->jdate($objp->date_commande),'day');
			print '</td>';
	
			//Delivery date
			print '<td align="center" nowrap>';
			print dol_print_date($db->jdate($objp->date_livraison),'day');
			print '</td>';
	
			// Statut
			print '<td align="right" nowrap="nowrap">'.$generic_commande->LibStatut($objp->fk_statut,$objp->facturee,5).'</td>';
	
			// Checkbox
			print '<td align="center">';
			print '<input class="flat checkformerge" type="checkbox" name="originid[]" value="'.$objp->rowid.'"'.true.'>';
			print '</td>' ;
	
			print '</tr>';
	
			$total = $total + $objp->price;
			$subtotal = $subtotal + $objp->price;
			$i++;
		}
		print '</table>';
		print '<center><br><input type="checkbox" checked="checked" name="autocloseorders"> '.$langs->trans("CloseProcessedOrdersAutomatically");
		// Button "Create Draft"
    	print '<br><center><input type="submit" class="button" name="bouton" value="'.$langs->trans('CreateDraftWithSelecedOrders').'"></center>';
		print '</form>';
		$db->free($resql);
		?>
		<script type="text/javascript">
			jQuery(document).ready(function() {
			jQuery("#checkall").click(function() {
				jQuery(".checkformerge").attr('checked', true);
			});
			jQuery("#checknone").click(function() {
				jQuery(".checkformerge").attr('checked', false);
			});
		});
		</script>
		<?php
	}
	else
	{
		print dol_print_error($db);
	}
}

?>