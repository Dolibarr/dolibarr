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