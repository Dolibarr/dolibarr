{* Copyright (C) 2009 Regis Houssin <regis@dolibarr.fr>
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

<table width="100%" border="0" class="notopnoleftnoright" style="margin-bottom: 2px;">
<tr>
	<td class="nobordernopadding" width="40" align="left" valign="middle">
		{$title_picto}
	</td>
	<td class="nobordernopadding" valign="middle">
    	<div class="titre">{$title_text}</div>
	</td>
</tr>
</table>

<form action="liste.php?canvas=default" method="post" name="formulaire">

<table class="liste" width="100%">
 <tr class="liste_titre">

  <td class="liste_titre">Référence
  <a href="liste.php?sortfield=p.ref&amp;sortorder=asc&amp;begin=&amp;envente=&amp;canvas=default&amp;fourn_id=&amp;snom=&amp;sref=">
  <img src="{$url_root}/theme/{$theme}/img/1downarrow.png" border="0" alt="A-Z" title="A-Z">
  </a>
  <a href="liste.php?sortfield=p.ref&amp;sortorder=desc&amp;begin=&amp;envente=&amp;canvas=default&amp;fourn_id=&amp;snom=&amp;sref=">
  <img src="{$url_root}/theme/{$theme}/img/1uparrow.png" border="0" alt="Z-A" title="Z-A">
  </a>
  </td>

  <td class="liste_titre">Libellé
  <a href="liste.php?sortfield=p.label&amp;sortorder=asc&amp;canvas=default&amp;fourn_id=&amp;snom=&amp;sref=">
  <img src="{$url_root}/theme/{$theme}/img/1downarrow.png" border="0" alt="A-Z" title="A-Z">
  </a>
  <a href="liste.php?sortfield=p.ref&amp;sortorder=desc&amp;begin=&amp;envente=&amp;canvas=default&amp;fourn_id=&amp;snom=&amp;sref=">
  <img src="{$url_root}/theme/{$theme}/img/1uparrow.png" border="0" alt="Z-A" title="Z-A">
  </a>
  </td>
  
  <td class="liste_titre">Code barre
  <a href="liste.php?sortfield=p.label&amp;sortorder=asc&amp;canvas=default&amp;fourn_id=&amp;snom=&amp;sref=">
  <img src="{$url_root}/theme/{$theme}/img/1downarrow.png" border="0" alt="A-Z" title="A-Z">
  </a>
  <a href="liste.php?sortfield=p.ref&amp;sortorder=desc&amp;begin=&amp;envente=&amp;canvas=default&amp;fourn_id=&amp;snom=&amp;sref=">
  <img src="{$url_root}/theme/{$theme}/img/1uparrow.png" border="0" alt="Z-A" title="Z-A">
  </a>
  </td>

  <td class="liste_titre" align="center">Date de modification</td>
  <td class="liste_titre" align="right">Prix de vente</td>
  <td class="liste_titre" align="right">Stock</td>
  <td class="liste_titre" align="right">Etat</td>

</tr>

<tr class="liste_titre">
 <td class="liste_titre"><input class="flat" type="text" name="sref" value=""></td>
 <td class="liste_titre"><input class="flat" type="text" name="snom" value=""></td>
 <td class="liste_titre"><input class="flat" type="text" name="sbarcode" value=""></td>
 <td class="liste_titre">&nbsp;</td>
 <td class="liste_titre">&nbsp;</td>
 <td class="liste_titre">&nbsp;</td>
 <td class="liste_titre" align="right">
 	<input type="image" class="liste_titre" name="button_search" src="{$url_root}/theme/{$theme}/img/search.png" alt="Rechercher">
 	<input type="image" class="liste_titre" name="button_removefilter" src="{$url_root}/theme/{$theme}/img/searchclear.png" alt="Supprimer filtre">
 	</td>
</tr>

{section name=mysec loop=$datas}
{strip}
   <tr class="{cycle values="pair,impair"}">
      <td><a href="fiche.php?id={$datas[mysec].id}">{$datas[mysec].ref}</a></td>
      <td>{$datas[mysec].label}</td>
      <td align="center">{$datas[mysec].barcode}</td>
      <td align="center">{$datas[mysec].datem}</td>
      <td align="right">{$datas[mysec].sellingprice}</td>
      <td align="right">{$datas[mysec].stock}</td>
      <td align="right">{$datas[mysec].status}</td>
   </tr>
{/strip}
{/section}

</table>
</form>

<!-- END SMARTY TEMPLATE -->