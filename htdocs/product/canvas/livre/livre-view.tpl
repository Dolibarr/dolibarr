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
 *}

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
 <td>Poids</td>
 <td colspan="3">{$prod_weight}g</td>
</tr>
</table>

<br>

<table class="border" width="100%">
<tr>
 <td width="15%">Prix au feuillet</td>
 <td width="35%">{$prod_pxfeuil} HT</td>

 <td width="15%"><a href="fiche.php?id={$livre_couverture_id}">Prix couverture</a></td>
 <td width="35%">{$prod_pxcouv} HT</td>
</tr>
<tr>
 <td width="15%">Prix de revient</td>
 <td width="35%">{$prod_pxrevient} HT</td>

 <td width="15%">Prix reliure</td>
 <td width="35%">{$prod_pxreliure} HT</td>
</tr>
<tr>
 <td>Prix de vente</td>
 <td>{$prod_pxvente} TTC</td>
 <td width="15%">Taux TVA</td>
 <td width="35%">{$prod_tva_tx} %</td>
</tr>
</table>

<br>

<table class="border" width="100%">

<tr>
 <td width="15%">Stock disponible</td>
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

<tr>
 <td>Emplacement Stock</td>
 <td colspan="3">{$prod_stock_loc}</td>
</tr>

 <tr>
  <td>Statut</td>
  <td colspan="3">{$prod_statut}</td>
 </tr>

</table>
<br>
<table class="border" width="100%">
 <tr>
  <td>Auteur / Editeur</td>
  <td>{$livre_auteur}</td>
  <td>Saisi par</td>
  <td>
    {$livre_contrat_user_fullname}
  </td>
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

</table>

<br />

<table class="border" width="100%">

 <tr>
  <td width="15%" valign="top">Description</td>
  <td width="85%">{$prod_description}</td>
 </tr>

 <tr>
  <td width="15%" valign="top">Note (non visible sur les factures, propals...)</td>
  <td width="85%">{$prod_note}</td>
 </tr>

</table>

<!-- END SMARTY TEMPLATE -->