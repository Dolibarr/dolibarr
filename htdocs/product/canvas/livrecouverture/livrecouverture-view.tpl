<!-- BEGIN SMARTY TEMPLATE -->
<table class="border" width="100%">
<tr>
 <td width="15%">Réf</td>
 <td width="35%" style="font-weight: bold;">{$livre_ref}</td>
 <td width="50%" align="right">
	&nbsp;
 </td>
</tr>

<tr>
 <td width="15%">Couverture du livre</td>
 <td width="85%" colspan="2"><a href="fiche.php?id={$livre_id}">{$livre_label}</a></td>
</tr>
</table>

<br />

<table class="border" width="100%">

<tr>
 <td width="15%">Stock</td>
 <td width="35%" {$smarty_stock_dispo_class}>{$prod_stock_dispo}</td>
 <td width="15%">Seuil d'alerte</td>
 <td width="35%">{$prod_stock_alert}</td>
</tr>

<tr>
 <td width="15%">Stock réel</td>
 <td width="35%">{$prod_stock_reel}</td>
 <td width="15%">Exemplaires en commande</td>
 <td width="35%">{$prod_stock_in_command}</td>
</tr>

</table>

<br />

<!-- END SMARTY TEMPLATE -->