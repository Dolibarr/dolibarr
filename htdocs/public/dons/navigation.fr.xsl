<?xml version="1.0" encoding="ISO-8859-1"?>
<!DOCTYPE xsl:stylesheet [<!ENTITY nbsp "&#160;">]>
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

  <xsl:template match="/html[@lang='fr']/body/div">
    <!-- $Id$ -->
    <!-- $Source$ -->
    <!-- Top menu line -->
    <table border="1" cellpadding="5" cellspacing="10" class="main">
      <tr>
	<td colspan="2">
	  <span><img src="http://eucd.info/eucd.info-logo-50x64.png" align="middle" /> <a href="http://eucd.info/">EUCD.INFO</a> - Au <a href="http://eucd.info/donations.fr.php">secours</a> de la copie privée<br/>For UK citizens: <a href="http://www.eucd.org/">www.eucd.org</a> is addressing the same problem your country.</span>
	</td>
      </tr>
      
      <tr>
	<td valign="top">
	  <a href="http://eucd.info/index.fr.php">Accueil</a><br />
	  <a href="http://eucd.info/documents/documents.fr.php">Dossier</a><br />
	  <a href="http://eucd.info/revue.fr.php">Presse</a><br />
	  <br/>
	  <a href="http://eucd.info/donations.fr.php">Aider</a><br />
	  <a href="http://eucd.info/transparence.fr.php">Transparence</a><br />
	  <a href="http://wiki.ael.be/index.php/EUCD-Status">Situation</a><br />
	  <a href="http://mail.gnu.org/mailman/listinfo/fsfe-france">Discuter</a><br />
	  <a href="http://eucd.info/who.fr.php">Qui</a><br />
	  <a href="http://eucd.info/images.fr.php">Images</a><br />
	  <br />
	  <br />
	  <a href="http://dons.eucd.info/">Dons</a><br />	  
	  <script language="php">
	    if (file_exists ("therm.php"))
	    {
	    include("therm.php");
	    }
	  </script>

	</td>
	
	<td valign="top">
	  
	  <xsl:apply-templates select="@*|node()"/>

	</td>
      </tr>
      
    </table>
    <address><a href="mailto:contact@eucd.info">Contact:</a> EUCD.INFO c/o FSF France 8, rue de valois, 75001 Paris - Tel: 01 42 76 05 49 - Mail: <a href="mailto:contact@eucd.info">contact@eucd.info</a> - Web: <a href="http://eucd.info/">http://eucd.info/</a></address>
  </xsl:template> 

<!--
Local Variables: ***
mode: xml ***
End: ***
-->
</xsl:stylesheet>
