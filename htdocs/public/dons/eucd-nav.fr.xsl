<?xml version="1.0" encoding="ISO-8859-1"?>
<!DOCTYPE xsl:stylesheet [<!ENTITY nbsp "&#160;">]>
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

  <xsl:template match="/html[@lang='fr']/body/div">

    <!-- Top menu line -->
    <table border="1" cellpadding="5" cellspacing="10" class="main">
      <tr>
	<td colspan="2">
	  <span><a href="http://eucd.info/">EUCD.INFO</a> - Au <a href="donations.fr.php">secours</a> de la copie privée</span>
	</td>
      </tr>
      
      <tr>
	<td valign="top">
	  <a href="http://eucd.info/index.fr.php">Accueil</a><br />
	  <a href="http://eucd.info/revue.fr.php">Presse</a><br />
	  <a href="http://eucd.info/donations.fr.php">Aider</a><br />
	  <a href="http://eucd.info/transparence.fr.php">Transparence</a><br />

	  <br/>
	  <a href="http://eucd.info/eucd.fr.php">Analyse</a><br />
	  <a href="http://wiki.ael.be/index.php/EUCD-Status">Situation</a><br />
	  <a href="http://mail.gnu.org/mailman/listinfo/fsfe-france">Discuter</a><br />
	  <a href="http://eucd.info/who.fr.php">Qui</a><br />
	  <br />
	  <br />
	  <a href="donations.fr.php">Dons</a><br />	  
	  <script language="php">
	    //require("/var/www/www.eucd.info/htdocs/thermometer.php");
	    //print moneyMeter($totaal_ontvangen+$post_donaties+$post_sponsoring, $totaal_pending, $post_intent);
	  </script>

	</td>
	
	<td>	  
	  <xsl:apply-templates select="@*|node()"/>
	</td>
      </tr>
    </table>
    <address><a href="mailto:contact@eucd.info">Contact:</a> EUCD.INFO c/o FSF France 8, rue de valois, 75001 Paris - Tel: 01 42 76 05 49 - Mail: <a href="mailto:contact@eucd.info">contact@eucd.info</a> - Web: <a href="http://eucd.info/">http://eucd.info/</a></address>
  </xsl:template> 

  <xsl:template match="/html[@lang='fr']/body/div[@class='main']/form/table[@id='formulaire']">
    <table cellpadding="4" cellspacing="0">
      <xsl:apply-templates select="@*|node()"/>
    </table>
    <p>
      La FSF France s'engage à n'utliser vos informations personnelles
      qu'exclusivement pour le traitement de votre don.
    </p>
  </xsl:template> 

  <xsl:template match="/html[@lang='fr']/body/div[@class='main']/form/table/tr[@id='projet']">
    <input type="hidden" name="projetid" value="1" />
  </xsl:template> 

  <xsl:template match="/html[@lang='fr']/body/div[@class='main']/form/table/tr[@id='date']">
    <tr>
      <td class="titre">
	Date
      </td>
      <td class="valeur">
	<xsl:apply-templates select="@*|node()"/>
      </td>
    </tr>
  </xsl:template> 

  <xsl:template match="/html[@lang='fr']/body/div[@class='main']/form/table/tr[@id='nom']">
    <tr>
      <td class="titre">
	Nom Prénom
      </td>
      <td class="valeur">
	<xsl:apply-templates select="@*|node()"/>
      </td>
    </tr>
  </xsl:template>
  
  <xsl:template match="/html[@lang='fr']/body/div[@class='main']/form/table/tr[@id='adresse']">
    <tr>
      <td class="titre">
	Adresse
      </td>
      <td class="valeur">
	<xsl:apply-templates select="@*|node()"/>
      </td>
    </tr>
  </xsl:template>

  <xsl:template match="/html[@lang='fr']/body/div[@class='main']/form/table/tr[@id='ville']">
    <tr>
      <td class="titre">
	CP Ville
      </td>
      <td class="valeur">
	<xsl:apply-templates select="@*|node()"/>
      </td>
    </tr>
  </xsl:template>

  <xsl:template match="/html[@lang='fr']/body/div[@class='main']/form/table/tr[@id='email']">
    <tr>
      <td class="titre">
	Email
      </td>
      <td class="valeur">
	<xsl:apply-templates select="@*|node()"/>
      </td>
    </tr>
  </xsl:template>

  <xsl:template match="/html[@lang='fr']/body/div[@class='main']/form/table/tr[@id='montant']">
    <tr>
      <td class="titre">
	Montant
      </td>
      <td class="valeur">
	<xsl:apply-templates select="@*|node()"/> euros
      </td>
    </tr>
  </xsl:template>

  <xsl:template match="/html[@lang='fr']/body/div[@class='main']/form/table/tr[@id='public']">
    <tr>
      <td valign="top" class="titre">Don public</td>
      <td class="valeur">
	<xsl:apply-templates select="@*|node()"/>

	<div class="commentaire">
	  Acceptez-vous que votre don soit public et que vos noms
	  et prénoms soient affichés dans la liste des donateurs.
	</div>	
      </td>
    </tr>    
  </xsl:template> 

  <xsl:template match="/html[@lang='fr']/body/div[@class='main']/form/table/tr[@id='commentaire']">
    <tr>
      <td valign="top" class="titre">
	Commentaire
      </td>

      <td valign="top" class="valeur">
	<xsl:apply-templates select="@*|node()"/>

	<div class="commentaire">
	  Commentaire libre
	</div>
      </td>      
    </tr>
  </xsl:template> 
<!--
Local Variables: ***
mode: xml ***
End: ***
-->
</xsl:stylesheet>
