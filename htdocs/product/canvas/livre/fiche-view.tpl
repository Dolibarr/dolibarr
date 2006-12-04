<!-- BEGIN SMARTY TEMPLATE -->
</table>
<table class="border" width="100%">
<tr>
 <td width="15%">Titre</td>
 <td width="85%" colspan="3">{$prod_label}</td>
</tr>
<tr>
 <td width="15%">ISBN</td>
 <td width="35%">{$prod_isbn}</td>
 <td width="15%">ISBN-13</td>
 <td width="35%">{$prod_isbn13}</td>

</tr>
<tr>
 <td width="15%">EAN</td>
 <td width="35%">{$prod_ean}</td>
 <td>Code barre</td>
 <td>{$prod_ean}</td>
</tr>
<tr>
 <td>Pages</td>
 <td>{$prod_pages}</td>
 <td>Format</td>
 <td>{$prod_format}</td>
</tr>
<tr>
 <td>Prix au feuillet</td>
 <td>{$prod_pxfeuil}</td>

 <td>Prix couverture</td>
 <td>{$prod_pxcouv}</td>
</tr>
<tr>
 <td>Prix de revient</td>
 <td>{$prod_pxrevient}</td>
 <td>Prix de vente</td>
 <td>{$prod_pxvente}</td>
</tr>

</table>
<br>
<table class="border" width="100%">

<tr>
 <td width="15%">Stock</td>
 <td width="35%"><b>{$prod_stock_dispo}</b></td>
 <td width="15%">Seuil d'alerte</td>
 <td width="35%">{$prod_stock_alert}</td>
</tr>

<tr>
 <td width="15%">Stock réel</td>
 <td width="35%">{$prod_stock_reel}</td>
 <td width="15%">Exemplaires en commande</td>
 <td width="35%">{$prod_stock_in_command}</td>
</tr>

<tr>
 <td>Emplacement Stock</td>
 <td colspan="3">{$prod_pages}</td>
</tr>

</table>
<br>
<table class="border" width="100%">


<tr>
 <td width="15%">Durée du contrat : </td>
 <td width="35%">{$prod_contrat_duree}</td>
 <td width="15%">Date d'application</td>
 <td width="35%"></td>
</tr>

<tr>
 <td>Taux conclu</td>
 <td>{$prod_contrat_taux}</td>
 <td>Quantité achetée</td>
 <td>{$prod_contrat_quant}</td>
</tr>

<tr>




<!-- END SMARTY TEMPLATE -->