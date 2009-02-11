{* Copyright (C) 2006-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2007 Auguria SARL         <info@auguria.org>
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
 *
 * $Id$
 * $Source$
 *}
<!-- BEGIN SMARTY TEMPLATE -->

<form id="evolForm" action="fiche.php" method="post">
<input type="hidden" name="action" value="update">
<input type="hidden" name="id" value="{$prod_id}">
<input type="hidden" name="canvas" value="{$prod_canvas}">
<input type="hidden" name="price_base_type" value="TTC">

<table width="100%" border="0" class="notopnoleftnoright">
<tr>
	<td class="notopnoleftnoright" valign="middle">
    	<div class="titre">Éditer Livre</div>
	</td>
</tr>
</table>

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
  <td width="35%">978-
    <input name="isbn13" size="13" maxlength="12" class="normal" 
     onfocus="this.className='focus';" onblur="this.className='normal';" value="{$prod_isbn}">
  </td>
 </tr>

 <tr>
  <td width="15%">EAN</td>
  <td width="35%">
    <input class="normal" name="ean" size="16" maxlength="15" value="{$prod_ean}"
     onfocus="this.className='focus';" onblur="this.className='normal';">
  </td>
  <td>Code barre</td>
  <td>{$prod_ean}</td>
 </tr>
 <tr>
  <td>Pages</td>
  <td>
    <input name="pages" size="6" maxlength="5" value="{$prod_pages}"
     class="normal" onfocus="this.className='focus';" onblur="this.className='normal';">
  </td>
  <td>Format</td>
  <td>
   <select class="flat" name="format">
    {html_options values=$livre_available_formats output=$livre_available_formats selected="$prod_format"}
   </select>
  </td>
 </tr>
 <tr>
  <td>Poids</td>
  <td colspan="3">
   <input name="weight" size="5" value="{$prod_weight}"
     class="normal" onfocus="this.className='focus';" onblur="this.className='normal';">g
   <input name="weight_units" type="hidden" value="-3">
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
 <td>{$prod_pxrevient}</td>
 <td>Prix reliure</td>
 <td>
  <input name="px_reliure" type="text" size="7" maxlength="6" value="{$prod_pxreliure}"
   class="normal" onfocus="this.className='focus';" onblur="this.className='normal';">
 </td>
</tr>
</table>

<br />

<table class="border" width="100%">
 <tr>
  <td width="15%">Prix de vente</td>
  <td width="35%">{$prod_pxvente} TTC</td>
  <td width="15%">Taux TVA</td>
  <td width="35%">
   <select class="flat" name="tva_tx">
    {html_options values=$tva_taux_value output=$tva_taux_libelle selected="$prod_tva_tx"}
   </select>
  </td>
 </tr>
</table>

<br />

<table class="border" width="100%">
 <tr>
 <td width="15%">Stock disponible</td>
 <td width="35%"><b>{$prod_stock_dispo}</b></td>
  <td width="15%">Seuil d'alerte stock</td>
  <td width="35%">
   <input name="seuil_stock_alerte" size="4" value="{$prod_seuil_stock_alerte}"
     class="normal" onfocus="this.className='focus';" onblur="this.className='normal';">
  </td>
</tr>

<tr>
  <td width="15%">Emplacement Stock</td>
  <td width="85%" colspan="3">
   <input name="stock_loc" size="8" value="{$prod_stock_loc}"
     class="normal" onfocus="this.className='focus';" onblur="this.className='normal';">
  </td>
 </tr>
 <tr>
  <td>Statut</td>
  <td colspan="3">
   <select class="flat" name="statut">
    {html_options values=$prod_statuts_id output=$prod_statuts_value selected="$prod_statut_id"}
   </select>
  </td>
 </tr>
</table>

<br />

<table class="border" width="100%">

{if $livre_contrat_locked eq '0'}
<tr>
 <td>Auteur / Editeur</td>
 <td>
   <select class="flat" name="auteur">
    {html_options options=$livre_available_auteurs selected=$livre_auteur_id}
   </select>
 </td>
 <td>Saisi par</td><td>{$livre_contrat_user_fullname}</td>
</tr>
<tr>
 <td width="15%">Durée du contrat :</td>
 <td width="35%">
  <input name="contrat_duree" type="text" size="7" maxlength="6" value="{$livre_contrat_duree}"
   class="normal" onfocus="this.className='focus';" onblur="this.className='normal';">
 </td>
 <td width="15%">Date d'application</td>
 <td width="35%">
  {html_select_date field_order='DMY' start_year='-10' time=$livre_contrat_date_app reverse_years=True all_extra='class="flat"'}
 </td>
</tr>
<tr>
 <td>Taux conclu</td>
 <td>
  <input name="contrat_taux" type="text" size="7" maxlength="6" value="{$livre_contrat_taux}"
   class="normal" onfocus="this.className='focus';" onblur="this.className='normal';">%
 </td>
 <td>Quantité achetée</td>
 <td>
  <input name="contrat_quant" type="text" size="7" maxlength="6" value="{$livre_contrat_quant}"
   class="normal" onfocus="this.className='focus';" onblur="this.className='normal';">
 </td>
</tr>
<tr>
 <td>Validation du contrat</td>
 <td>
  <input type="checkbox" name="locked" value="locked" />
 </td>
 <td colspan="2">En cochant la case vous interdisez toute modifications</td>

</tr>

{else}
<tr>
 <td>Auteur / Editeur</td>
 <td>{$livre_auteur}</td>
 <td>Saisi par</td><td>{$livre_contrat_user_fullname}</td>
</tr>
<tr>
 <td width="15%">Durée du contrat : </td>
 <td width="35%">{$livre_contrat_duree}</td>
 <td width="15%">Date d'application</td>
 <td width="35%">{$livre_contrat_date_app|date_format:"%d %B %Y"}</td>
</tr>
<tr>
 <td>Taux conclu</td>
 <td>{$livre_contrat_taux} %</td>
 <td>Quantité achetée</td>
 <td>{$livre_contrat_quant}</td>
</tr>
{/if}

</table>

<br />

<table class="border" width="100%">


 <tr>
  <td valign="top">Description</td>
  <td colspan="3">
    <textarea name="desc" rows="6" cols="70"></textarea>
  </td>
 </tr>

 <tr>
  <td valign="top">Note (non visible sur les factures, propals...)
  </td>
  <td colspan="3">
   <textarea name="note" rows="4" cols="70"></textarea>
  </td>
 </tr>
 <tr>
  <td colspan="4" align="center">
   <input type="submit" class="button" value="Enregistrer">&nbsp;
   <input type="submit" class="button" name="cancel" value="Annuler">
  </td>
 </tr>

</table>
</form>

<!-- END SMARTY TEMPLATE -->