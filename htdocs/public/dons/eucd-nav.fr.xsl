<?xml version="1.0" encoding="ISO-8859-1"?>
<!DOCTYPE xsl:stylesheet [<!ENTITY nbsp "&#160;">]>
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

  <xsl:template match="/html[@lang='fr']/body/div">

    <!-- Top menu line -->
    <table border="1" cellpadding="5" cellspacing="10" class="main">
      <tr>
	<td colspan="2">
	  <span>Dons FSF France</span>
	</td>
      </tr>
      
      <tr>
	<td valign="top">
	  <a href="/">Home</a><br />
	  <a href="/tarif.fr.html">Tarifs</a><br />
	  <a href="/lolix.fr.html">Lolix</a><br />
	  <a href="/projets/">Projets</a><br />
	  <a href="/other.fr.html">Autres</a><br />
	  <a href="/standard.fr.html">Standards</a><br />
	  <a href="/contact.fr.html">Contact</a>
	</td>
	
	<td>	
	  <xsl:apply-templates select="@*|node()"/>
	</td>
      </tr>
    </table>

  </xsl:template> 

  <xsl:template match="/html[@lang='fr']/body/div[@class='main']/form/table[@id='formulaire']">
    <table cellpadding="4" cellspacing="0">
      <xsl:apply-templates select="@*|node()"/>
    </table>
    <p>
      La FSF France s'engage à n'utliser vos informations personnelles
      qu'exclusiement pour le traitement de votre don.
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
	<xsl:apply-templates select="@*|node()"/>
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
