<!-- SMARTY  -->

<form action="liste.php" method="post" name="formulaire">
<input type="hidden" name="sortfield" value="p.ref">
<input type="hidden" name="sortorder" value="ASC">
<input type="hidden" name="type" value="0">

<table class="liste" width="100%">
 <tr class="liste_titre">
  <td class="liste_titre_sel">Case
  <a href="liste.php?sortfield=p.ref&amp;sortorder=asc&amp;begin=&amp;envente=&amp;type=0&amp;fourn_id=&amp;snom=&amp;sref=">
  <img src="/theme/eldy/img/1downarrow.png" border="0" alt="A-Z" title="A-Z">
  </a>
  <a href="liste.php?sortfield=p.ref&amp;sortorder=desc&amp;begin=&amp;envente=&amp;type=0&amp;fourn_id=&amp;snom=&amp;sref=">
  <img src="/theme/eldy/img/1uparrow.png" border="0" alt="Z-A" title="Z-A">
  </a>
  </td>

  <td class="liste_titre_sel" >Référence
  <a href="liste.php?sortfield=p.ref&amp;sortorder=asc&amp;begin=&amp;envente=&amp;type=0&amp;fourn_id=&amp;snom=&amp;sref=">
  <img src="/theme/eldy/img/1downarrow.png" border="0" alt="A-Z" title="A-Z">
  </a>
  <a href="liste.php?sortfield=p.ref&amp;sortorder=desc&amp;begin=&amp;envente=&amp;type=0&amp;fourn_id=&amp;snom=&amp;sref=">
  <img src="/theme/eldy/img/1uparrow.png" border="0" alt="Z-A" title="Z-A">
  </a>
  </td>


  <td class="liste_titre_sel">N3
  <a href="liste.php?sortfield=p.ref&amp;sortorder=asc&amp;begin=&amp;envente=&amp;type=0&amp;fourn_id=&amp;snom=&amp;sref=">
  <img src="/theme/eldy/img/1downarrow.png" border="0" alt="A-Z" title="A-Z">
  </a>
  <a href="liste.php?sortfield=p.ref&amp;sortorder=desc&amp;begin=&amp;envente=&amp;type=0&amp;fourn_id=&amp;snom=&amp;sref=">
  <img src="/theme/eldy/img/1uparrow.png" border="0" alt="Z-A" title="Z-A">
  </a>
  </td>

  <td class="liste_titre_sel">Casier
  <a href="liste.php?sortfield=p.ref&amp;sortorder=asc&amp;begin=&amp;envente=&amp;type=0&amp;fourn_id=&amp;snom=&amp;sref=">
  <img src="/theme/eldy/img/1downarrow.png" border="0" alt="A-Z" title="A-Z">
  </a>
  <a href="liste.php?sortfield=p.ref&amp;sortorder=desc&amp;begin=&amp;envente=&amp;type=0&amp;fourn_id=&amp;snom=&amp;sref=">
  <img src="/theme/eldy/img/1uparrow.png" border="0" alt="Z-A" title="Z-A">
  </a>
  </td>

  <td class="liste_titre_sel">Entrepôt
  </td>

  <td class="liste_titre_sel">Ventes
  </td>

  <td class="liste_titre_sel">Stock
  </td>

  <td class="liste_titre_sel">Pages
  </td>

  <td class="liste_titre_sel">Prix
  </td>

  <td class="liste_titre_sel">Valorisation
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
 <td class="liste_titre" align="right"><input type="image" class="liste_titre" name="button_search" src="/theme/eldy/img/search.png" alt="Rechercher"><input type="image" class="liste_titre" name="button_removefilter" src="/theme/eldy/img/searchclear.png" alt="Supprimer filtre"></td>
</tr>

{section name=mysec loop=$datas}
{strip}
   <tr class="{cycle values="pair,impair"}">
      <td>{$datas[mysec].case}</td>
      <td><a href="fiche.php?id={$datas[mysec].id}">{$datas[mysec].ref}</a></td>
      <td>{$datas[mysec].titre}</td>
      <td>{$datas[mysec].casier}</td>
      <td>{$datas[mysec].entrepot}</td>
      <td>{$datas[mysec].vente}</td>
      <td>{$datas[mysec].stock}</td>
      <td>{$datas[mysec].pages}</td>
      <td>{$datas[mysec].prix}</td>
      <td>{$datas[mysec].valo}</td>
   </tr>
{/strip}
{/section}

</table>
</form>
<!-- END SMARTY -->