<!-- BEGIN SMARTY TEMPLATE -->
<table class="border" width="100%">
<tr>
 <td width="15%">Référence</td>
 <td width="35%" style="font-weight: bold;">{$prod_ref}</td>
 <td width="50%" colspan="2" align="right">
  {$fiche_cursor_prev}{$fiche_cursor_next}
 </td>
</tr>

<tr>
 <td width="15%">Titre</td>
 <td width="85%" colspan="3">{$prod_label}</td>
</tr>
</table>

<br/>

<table class="border" width="100%">

<tr>
 <td width="15%">Stock</td>
 <td width="35%" {$smarty_stock_dispo_class}>
   {$prod_stock_dispo}
 </td>
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

<table class="border" width="100%">

<tr>
 <td width="15%">Durée du contrat : </td>
 <td width="35%">{$prod_contrat_duree}</td>
 <td width="15%">Date d'application</td>
 <td width="35%">{$prod_contrat_date_app|date_format:"%e %B %Y"}</td>
</tr>

<tr>
 <td>Taux conclu</td>
 <td>{$prod_contrat_taux}</td>
 <td>Quantité achetée</td>
 <td>{$prod_contrat_quant}</td>
</tr>

</table>

<br />

<table class="border" width="100%">

 <tr>
  <td width="15%" valign="top">Description</td>
  <td width="85%">{$prod_description|nl2br}</td>
 </tr>

 <tr>
  <td valign="top">Note (non visible sur les factures, propals...)</td>
  <td>{$prod_note|nl2br}</td>
 </tr>

</table>
<!-- END SMARTY TEMPLATE -->