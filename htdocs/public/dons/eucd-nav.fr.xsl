<?xml version="1.0" encoding="ISO-8859-1"?>
<!DOCTYPE xsl:stylesheet [<!ENTITY nbsp "&#160;">]>
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform">


  <xsl:template match="/html[@lang='fr']/body/div[@class='main']/form/table[@id='formulaire']">
    <table cellpadding="4" cellspacing="0">
      <xsl:apply-templates select="@*|node()"/>
    </table>
    <p>
      La FSF France s'engage à n'utliser vos informations personnelles
      qu'exclusivement pour le traitement de votre don.
    </p>


    <p>Nous vous adresserons ensuite un <a
	href="http://france.fsfeurope.org/donations/formulaire.fr.html">formulaire</a>
	vous permettant de bénéficier d'une déduction d'impôts. Selon
	l'<a
	href="http://www.legifrance.gouv.fr/citoyen/unarticledecode.ow?code=CGIMPOT0.rcv&amp;art=200">article
	200 du CGI</a>, <i>Ouvrent droit à une réduction d'impôt sur
	le revenu égale à 50 % de leur montant les sommes prises dans
	la limite de 6 % du revenu imposable qui correspondent à des
	dons et versements, y compris l'abandon exprès de revenus ou
	produits, effectués par les contribuables domiciliés en
	France.</i> Voir aussi, concernant les entreprises, l'<a
	href="http://www.legifrance.gouv.fr/WAspad/VisuArticleCode?commun=CGIMPO&amp;code=&amp;h0=CGIMPO00.rcv&amp;h1=1&amp;h3=39">article
	238 bis du CGI</a>.</p>

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

  <xsl:template match="/html[@lang='fr']/body/div[@class='main']/form/table/tr[@id='cp']">
    <tr>
      <td class="titre">
	Code Postal
      </td>
      <td class="valeur">
	<xsl:apply-templates select="@*|node()"/>
      </td>
    </tr>
  </xsl:template>


  <xsl:template match="/html[@lang='fr']/body/div[@class='main']/form/table/tr[@id='ville']">
    <tr>
      <td class="titre">
	Ville
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
	  et prénoms soient affichés dans la liste des donateurs ?
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
      </td>      
    </tr>
  </xsl:template> 
<!--
Local Variables: ***
mode: xml ***
End: ***
-->
</xsl:stylesheet>
