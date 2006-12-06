<!-- BEGIN SMARTY TEMPLATE -->
<table class="border" width="100%">
<tr>
 <td width="15%">Référence</td>
 <td width="35%" style="font-weight: bold;">{$prod_ref}</td>
 <td width="50%" align="right">
  {$fiche_cursor_prev}{$fiche_cursor_next}
 </td>
</tr>

<tr>
 <td width="15%">Titre du livre</td>
 <td width="85%" colspan="2">{$prod_label}</td>
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

<table class="border" width="100%">
 <tr>
  <td width="50%" valign="top">Description</td>
  <td width="50%" valign="top">Photo</td>
 </tr>

 <tr>
  <td width="50%" valign="top">{$prod_description|nl2br}</td>
  <td rowspan="3" valign="top"></td>
 </tr>

 <tr>
  <td width="50%" valign="top">Note (non visible sur les factures, propals...)
 </tr>
 <tr>
  <td width="50%" valign="top">{$prod_note|nl2br}</td>
 </tr>
</table>


</table>

<!-- END SMARTY TEMPLATE -->