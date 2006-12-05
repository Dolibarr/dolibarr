<!-- BEGIN SMARTY TEMPLATE -->

<form id="evolForm" action="fiche.php" method="post">
<input type="hidden" name="action" value="add">
<input type="hidden" name="type" value="0">
<input type="hidden" name="canvas" value="livre">

<table class="border" width="100%">
 <tr>
   <td width="15%">Réf.</td>
   <td colspan="2">
    <input name="ref" size="20" value="{$prod_ref}"
     class="normal" onfocus="this.className='focus';" onblur="this.className='normal';">
  </td>
 </tr>

 <tr>
  <td width="15%">Titre</td>
  <td width="85%" colspan="3">
   <input name="libelle" size="40" value="{$prod_label}"
    class="normal" onfocus="this.className='focus';" onblur="this.className='normal';">
  </td>
 </tr>

 <tr>
  <td width="15%">ISBN</td>
  <td width="35%">
    <input name="isbna" size="2" maxlength="12" value="{$prod_isbna}"
     class="normal" onfocus="this.className='focus';" onblur="this.className='normal';">-
    <input name="isbnb" size="8" maxlength="7" value="{$prod_isbnb}"
     class="normal" onfocus="this.className='focus';" onblur="this.className='normal';">-
    <input name="isbnc" size="13" maxlength="12" class="normal" value="{$prod_isbnc}"
     onfocus="this.className='focus';" onblur="this.className='normal';" >
    {$prod_isbn}
  </td>
  <td width="15%">ISBN-13</td>
  <td width="35%"><i>sera calculé</i>
  </td>
 </tr>

 <tr>
  <td width="15%">EAN</td>
  <td colspan="3" width="85%"><i>sera calculé</i></td>
 </tr>

 <tr>
  <td>Pages</td>
  <td>
    <input name="pages" size="6" maxlength="5" value="{$prod_pages}"
     class="normal" onfocus="this.className='focus';" onblur="this.className='normal';">
  </td>
  <td>Format</td>
  <td>
  <input name="format" size="8" maxlength="7" value="{$prod_format}"
   class="normal" onfocus="this.className='focus';" onblur="this.className='normal';">
</td>
 </tr>
<tr>
 <td>Prix au feuillet</td>
 <td>
  <input name="px_feuillet" type="text" size="7" maxlength="6" value="{$prod_pxfeuil}"
   class="normal" onfocus="this.className='focus';" onblur="this.className='normal';">
 </td>
 <td>Prix couverture</td>
 <td>
  <input name="px_couverture" type="text" size="7" maxlength="6" value="{$prod_pxcouv}"
   class="normal" onfocus="this.className='focus';" onblur="this.className='normal';">
 </td>
</tr>
<tr>
 <td>Prix de revient</td>
 <td colspan="3"><i>sera calculé</i></td>
</tr>

 <tr>
  <td>Prix de vente</td>
  <td>
   <input name="price" type="text" size="7" maxlength="6" value=""
    class="normal" onfocus="this.className='focus';" onblur="this.className='normal';">
  </td>
  <td>Taux TVA</td>
  <td>
   <select class="flat" name="tva_tx">
    <option value="0">0%</option>
    <option value="5.5">5.5%</option>
    <option value="19.6" selected="true">19.6%</option>
   </select>
  </td>
 </tr>
</table>

<br />

<table class="border" width="100%">
 <tr>
  <td width="15%">Seuil stock</td>
  <td width="35%">
   <input name="seuil_stock_alerte" size="4" value=""
     class="normal" onfocus="this.className='focus';" onblur="this.className='normal';">
  </td>
  <td width="15%">Emplacement Stock</td>
  <td width="35%">
   <input name="stock_loc" size="8" value=""
     class="normal" onfocus="this.className='focus';" onblur="this.className='normal';">
  </td>
 </tr>
 <tr>
  <td>Statut</td>
  <td colspan="3">
   <select class="flat" name="statut">
    <option value="1" selected="true">En vente</option>
    <option value="0">Hors vente</option>
   </select>
  </td>
 </tr>
</table>

<br />

<table class="border" width="100%">
<tr>
 <td width="15%">Durée du contrat :</td>
 <td width="35%">
  <input name="contrat_duree" type="text" size="7" maxlength="6" value="{$prod_contrat_duree}"
   class="normal" onfocus="this.className='focus';" onblur="this.className='normal';">
 </td>
 <td width="15%">Date d'application</td>
 <td width="35%">
  <input name="contrat_date_app" type="text" size="7" maxlength="6" value="{$prod_contrat_date_app}"
   class="normal" onfocus="this.className='focus';" onblur="this.className='normal';">
 </td>
</tr>

<tr>
 <td>Taux conclu</td>
 <td>
  <input name="contrat_taux" type="text" size="7" maxlength="6" value="{$prod_contrat_taux}"
   class="normal" onfocus="this.className='focus';" onblur="this.className='normal';">%
 </td>
 <td>Quantité achetée</td>
 <td>
  <input name="contrat_quant" type="text" size="7" maxlength="6" value="{$prod_contrat_quant}"
   class="normal" onfocus="this.className='focus';" onblur="this.className='normal';">
 </td>
</tr>
</table>

<br />

<table class="border" width="100%">
 <tr>
  <td width="15%" valign="top">Description</td>
  <td width="85%" colspan="3">
    <textarea name="desc" rows="6" cols="70"></textarea>
  </td>
 </tr>

 <tr>
  <td width="15%" valign="top">Note (non visible sur les factures, propals...)
  </td>
  <td width="85%" colspan="3">
   <textarea name="note" rows="4" cols="70"></textarea>
  </td>
 </tr>
 <tr>
  <td colspan="4" align="center">
   <input type="submit" class="button" value="Créer">
  </td>
 </tr>

</table>
</form>

<!-- END SMARTY TEMPLATE -->