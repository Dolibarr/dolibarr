<!-- SMARTY  -->
<table width="100%" border="0" class="notopnoleftnoright">
<tr>
	<td class="notopnoleftnoright" valign="middle">
    	<div class="titre">Liste des Livres</div>
	</td>
</tr>
</table>

<form action="liste.php?canvas=livre" method="post" name="formulaire">

<table class="liste" width="100%">
 <tr class="liste_titre">
  <td class="liste_titre">Case
  </td>

  <td class="liste_titre" >Référence
  <a href="liste.php?sortfield=p.ref&amp;sortorder=asc&amp;begin=&amp;envente=&amp;canvas=livre&amp;fourn_id=&amp;snom=&amp;sref=">
  <img src="{$url_root}/theme/{$theme}/img/1downarrow.png" border="0" alt="A-Z" title="A-Z">
  </a>
  <a href="liste.php?sortfield=p.ref&amp;sortorder=desc&amp;begin=&amp;envente=&amp;canvas=livre&amp;fourn_id=&amp;snom=&amp;sref=">
  <img src="{$url_root}/theme/{$theme}/img/1uparrow.png" border="0" alt="Z-A" title="Z-A">
  </a>
  </td>

  <td class="liste_titre">Titre
  <a href="liste.php?sortfield=p.label&amp;sortorder=asc&amp;canvas=livre&amp;fourn_id=&amp;snom=&amp;sref=">
  <img src="{$url_root}/theme/{$theme}/img/1downarrow.png" border="0" alt="A-Z" title="A-Z">
  </a>
  <a href="liste.php?sortfield=p.ref&amp;sortorder=desc&amp;begin=&amp;envente=&amp;canvas=livre&amp;fourn_id=&amp;snom=&amp;sref=">
  <img src="{$url_root}/theme/{$theme}/img/1uparrow.png" border="0" alt="Z-A" title="Z-A">
  </a>
  </td>

  <td class="liste_titre" align="center">Casier</td>
  <td class="liste_titre" align="center">Entrepot</td>
  <td class="liste_titre" align="center">Ventes</td>
  <td class="liste_titre">Stock</td>
  <td class="liste_titre" align="center">Pages</td>
  <td class="liste_titre" align="right">Prix</td>
  <td class="liste_titre" align="right">Valorisation
  </td>
  <td class="liste_titre" align="right">Action
  </td>

</tr>

<tr class="liste_titre">
 <td class="liste_titre">&nbsp;</td>
 <td class="liste_titre"><input class="flat" type="text" name="sref" value=""></td>
 <td class="liste_titre"><input class="flat" type="text" name="snom" value=""></td>
 <td class="liste_titre">&nbsp;</td>
 <td class="liste_titre">&nbsp;</td>
 <td class="liste_titre">&nbsp;</td>
 <td class="liste_titre">&nbsp;</td>
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
      <td>$datas</td>
      <td><a href="fiche.php?id={$datas[mysec].id}">{$datas[mysec].ref}</a></td>
      <td>{$datas[mysec].titre}</td>
      <td align="center">{$datas[mysec].casier}</td>
      <td align="center">{$datas[mysec].entrepot}</td>
      <td>{$datas[mysec].ventes}</td>
      <td>{$datas[mysec].stock}</td>
      <td align="center">{$datas[mysec].pages}</td>
      <td align="right">{$datas[mysec].prix}</td>
      <td align="right">{$datas[mysec].valo}</td>
   </tr>
{/strip}
{/section}

</table>
</form>
<!-- END SMARTY -->