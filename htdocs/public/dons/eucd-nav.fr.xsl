<?xml version="1.0" encoding="ISO-8859-1"?>
<!DOCTYPE xsl:stylesheet [<!ENTITY nbsp "&#160;">]>
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <!-- Copyright (C) 2002 Rodolphe Quiedeville <rodolphe@quiedeville.org> -->
  <!-- $Id$ -->

  <xsl:template match="table[@id='formulaire']">
    <table cellpadding="4" cellspacing="0">
      <xsl:apply-templates select="@*|node()"/>
    </table>
    <p>
      La FSF France s'engage à utiliser vos informations personnelles
      exclusivement pour le traitement de votre don. Vous ne
      receverez aucun email de la la part de la FSF France autre que
      pour la gestion de votre don.
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
      <td class="titre">
	Date
      </td>
    </tr>
  </xsl:template> 

  <xsl:template match="table/tr[@id='nom']">
    <tr>
      <td class="titre">
	Nom 
      </td>
      <td class="valeur">
	<xsl:apply-templates select="@*|node()"/>
      </td>
      <td class="titre">
	Name 
      </td>
    </tr>
  </xsl:template>
  
  <xsl:template match="table/tr[@id='prenom']">
    <tr>
      <td class="titre">
	Prénom
      </td>
      <td class="valeur">
	<xsl:apply-templates select="@*|node()"/>
      </td>
      <td class="titre">
	Firstname 
      </td>
    </tr>
  </xsl:template>

  <xsl:template match="table/tr[@id='societe']">
    <tr>
      <td class="titre">
	Société
      </td>
      <td class="valeur">
	<xsl:apply-templates select="@*|node()"/>
      </td>
      <td class="titre">
	Company 
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
      <td class="titre">
	Address 
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
      <td class="titre">
	Postal code 
      </td>
    </tr>
  </xsl:template>


  <xsl:template match="table/tr[@id='ville']">
    <tr>
      <td class="titre">
	Ville
      </td>
      <td class="valeur">
	<xsl:apply-templates select="@*|node()"/>
      </td>
      <td class="titre">
	Town 
      </td>
    </tr>
  </xsl:template>

  <xsl:template match="table/tr[@id='pays']">
    <tr>
      <td class="titre">
	Pays
      </td>
      <td class="valeur">
	<xsl:apply-templates select="@*|node()"/>
      </td>
      <td class="titre">
	Country 
      </td>
    </tr>
  </xsl:template>

  <xsl:template match="table/tr[@id='email']">
    <tr>
      <td class="titre">
	Email
      </td>
      <td class="valeur">
	<xsl:apply-templates select="@*|node()"/>
      </td>
      <td class="titre">
	Email 
      </td>
    </tr>
  </xsl:template>

  <xsl:template match="table/tr[@id='montant']">
    <tr>
      <td class="titre">
	Montant
      </td>
      <td class="valeur">
	<xsl:apply-templates select="@*|node()"/>,00 euros
      </td>
      <td class="titre">
	Amount 
      </td>
    </tr>
  </xsl:template>

  <xsl:template match="table/tr[@id='limitdate']">
    <tr>
      <td class="titre">
	Echéance
      </td>
      <td class="valeur">
	<xsl:apply-templates select="@*|node()"/>
      </td>
      <td class="titre">
	Expiry
      </td>
    </tr>
  </xsl:template>


  <xsl:template match="/html[@lang='fr']/body/div[@class='main']/form/table/tr[@id='public']">
    <tr>
      <td valign="top" class="titre">
	Don public
      </td>
      <td class="valeur">
	<xsl:apply-templates select="@*|node()"/>
	<div class="commentaire">
	  Acceptez-vous vos noms et prénoms ou le nom de votre société soient affichés dans la liste des <a href="donateurs.php">donateurs</a> ?<br />
	  Do you allow us to list your name, firstaname or company name on the donations list ?
	</div>
      </td>
      <td valign="top" class="titre">
	Don public
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
      <td valign="top" class="titre">
	Comment
      </td>      
    </tr>
  </xsl:template> 
<!--
Local Variables: ***
mode: xml ***
End: ***
-->
</xsl:stylesheet>
