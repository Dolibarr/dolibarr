<?php
/* Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 *
 */

/*!
  \file       htdocs/comm/index.php
  \ingroup    commercial
  \brief      Page acceuil de la zone mailing
  \version    $Revision$
*/
 
require("./pre.inc.php");

if ($user->societe_id > 0)
{
  accessforbidden();
}
	  
$langs->load("commercial");
$langs->load("orders");

llxHeader('','Mailing');

/*
 *
 */

print_titre("Espace mailing");

print '<table border="0" width="100%">';

print '<tr><td valign="top" width="30%">';

/*
 * Dernières actions commerciales effectuées
 *
 */

$sql = "SELECT count(*), client";
$sql .= " FROM ".MAIN_DB_PREFIX."societe";
$sql .= " WHERE client in (1,2)";
$sql .= " GROUP BY client";



if ( $db->query($sql) ) 
{
  $num = $db->num_rows();

  print '<table class="noborder" width="100%">';
  print '<tr class="liste_titre"><td colspan="4">Statistiques</td></tr>';
  $var = true;
  $i = 0;

  while ($i < $num ) 
    {
      $row = $db->fetch_row();
 
      $st[$row[1]] = $row[0];

      $i++;
    }
  
  print "<tr $bc[$var]>";
  print '<td>Clients</td><td align="center">'.$st[1]."</td></tr>";
  print '<td>Prospects</td><td align="center">'.$st[2]."</td></tr>";

  print "</table><br>";

  $db->free();
} 
else
{
  dolibarr_print_error($db);
}

/*
 *
 *
 */

/*
 *
 *
 */
print '</td><td valign="top" width="70%">';




/*
 * 
 *
 */

$sql = "SELECT m.rowid, m.titre, m.nbemail";
$sql .= " FROM ".MAIN_DB_PREFIX."mailing as m";
$sql .= " LIMIT 10";
if ( $db->query($sql) ) 
{
  $num = $db->num_rows();
  if ($num > 0)
    { 
      print '<table class="noborder" width="100%">';
      print '<tr class="liste_titre"><td colspan="4">10 derniers mailing</td></tr>';
      $var = true;
      $i = 0;
      
      while ($i < $num ) 
	{
	  $obj = $db->fetch_object();
	  $var=!$var;
	  
	  print "<tr $bc[$var]>";
	  print '<td><a href="fiche.php?id='.$obj->rowid.'">'.$obj->titre.'</a></td>';
	  print '<td>'.$obj->nbemail.'</td>';

	  $i++;
	}

      print "</table><br>";
    }
  $db->free();
} 
else
{
  dolibarr_print_error($db);
}



print '</td></tr>';
print '</table>';

$db->close();


?>
<span class="titrepage">SPAM : L'état du droit en France</span> : <a class="extern" href="http://www.cnil.fr/index.php?id=1272">http://www.cnil.fr/index.php?id=1272</a>

<br><br>
Une adresse de messagerie électronique est une donnée nominative, soit directement lorsque le nom de l'internaute figure dans le libellé de l'adresse soit indirectement dans la mesure où toute adresse électronique peut être associée à une personne physique. Dès lors, toute opération de prospection par courrier électronique est soumise à la législation de protection des données.

<p>1. La loi&nbsp; pour la confiance dans l'économie numérique</p>

L'utilisation d'adresses de courriers électroniques dans les opérations de prospection commerciale est subordonnée au recueil du consentement préalable des personnes concernées.
<P class="bodytext">Le dispositif juridique applicable a été introduit par l'article 22 de la <A HREF="http://www.legifrance.gouv.fr/WAspad/UnTexteDeJorf?numjo=ECOX0200175L" target="legifrance">loi du 21 juin 2004</a>&nbsp; pour la confiance dans l'économie numérique. 
</P>
<P class="bodytext">Les dispositions applicables sont définies par les articles <A HREF="http://www.legifrance.gouv.fr/WAspad/UnArticleDeCode?commun=CPOSTE&art=l34-5" target="legifrance">L. 34-5</a> du code des postes et des télécommunications et <A HREF="http://www.legifrance.gouv.fr/WAspad/UnArticleDeCode?commun=CCONSO&art=L121-20-5" target="legifrance">L. 121-20-5</a> du code de la consommation. L'application du principe du consentement préalable en droit français résulte de la transposition de l'article 13 de la Directive européenne du 12 juillet 2002 « Vie privée et communications électroniques ». 
</P>
Il est interdit d'utiliser l'adresse de courrier électronique d'une personne physique à des fins de prospection commerciale sans avoir préalablement obtenu son consentement.

<P class="bodytext">L'expression de ce consentement doit être libre, spécifique et informée. En conséquence, son recueil ne doit pas être dilué dans une acceptation des conditions générales ou couplé à une demande de bons de réduction.&nbsp; La CNIL recommande à cet égard <B>qu'il soit recueilli par le biais d'une case à cocher </B>et rappelle qu'une case pré-cochée est contraire à l'esprit de la loi. 
</P>
La loi a prévu une dérogation au principe du consentement préalable en maintenant un régime de droit d'opposition : 
<P class="bodytext">il s'agit de l'hypothèse dans laquelle la prospection concerne des « produits ou services analogues » à ceux déjà fournis par la même personne physique ou morale qui aura recueilli les coordonnées électroniques de l'intéressé. <BR>Par exemple, une entreprise qui a vendu un livre pourra solliciter cet acheteur pour l'acquisition d'un disque, à la condition toutefois que la personne démarchée ait été expressément informée, lors de la collecte de son adresse de courrier électronique, de l'utilisation de celle-ci à des fins commerciales et qu'elle ait été mise en mesure de s'y opposer de manière simple. 
</P>
<P class="bodytext">Dans tous les cas de figure, <B>chaque message électronique envoyé doit prévoir des modalités de désinscription</B> et préciser l'identité de la personne pour le compte de laquelle le message a été envoyé. 
</P>
Enfin, la loi pour la confiance dans l'économie numérique a aménagé une période transitoire d'une durée de 6 mois à compter de sa publication, à savoir le 22 juin 2004. 

<P class="bodytext">Ainsi, les entreprises peuvent jusqu'au 22 décembre 2004 adresser, à partir de fichiers constitués dans le respect des dispositions de la loi Informatique et libertés du 6 janvier 1978, un courrier électronique afin de recueillir le consentement des personnes. L'absence de réponse de celles-ci dans la période des 6 mois équivaudra à un refus d'être démarché.&nbsp; </P>
<P class="bodytext">Indépendamment des règles spécifiques prévues dans le code des postes et des télécommunications et dans celui de la consommation, les opérations de prospection par courrier électronique, quelque soit leur nature (commerciale, caritative, politique, religieuse ou associative par exemple), sont soumises au respect de la législation relative à la protection des données personnelles, à savoir la <a href="http://www.cnil.fr/index.php?id=301" target="cnil">loi Informatique et Libertés du 6 janvier 1978</a>. 
</P>
<?PHP
 

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
