<?PHP
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 *
 * $Id$
 * $Source$
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
 */

require("./pre.inc.php3");

llxHeader();
$db = new Db();

?>


<!-- JAVASCRIPT -->
<script language="Javascript"> type="text/javascript"

var rubriqueLogin=false;

function closeWin()
//pour fermer la popup si l'url de la fenetre principale change
{
	if (rubriqueLogin && !rubriqueLogin.closed) { 
		rubriqueLogin.fermerFenetre();
	}
}
var da=(document.all) ? 1:0;
var pr=(window.print) ? 1:0;
var mac=(navigator.userAgent.indexOf("Mac") != -1);
window.focus();

function printpage()
{
	if(pr)
		window.print()
	else if (da && !mac)
		vbprintpage();
	else
		alert("Erreur !");
	return false;
}


function send(cmd)
{
	document.saisie.submit();
}



function sendAfterValidation(cmd)
{
  document.saisie.submit();
}


function showLogin()
{
var  fieldsCommvoy=""; 
 if(document.saisie.OD_departure !=null)
 		fieldsCommvoy = fieldsCommvoy+ "&OD_departure=" + GetSelectedValue(document.saisie.OD_departure);
 if(document.saisie.OD_arrival !=null)
 		fieldsCommvoy = fieldsCommvoy+ "&OD_arrival=" + GetSelectedValue(document.saisie.OD_arrival);
 if(document.saisie.OD_via !=null)
 		fieldsCommvoy = fieldsCommvoy+ "&OD_via=" + GetSelectedValue(document.saisie.OD_via);
 if(document.saisie.departure_day !=null)
 		fieldsCommvoy = fieldsCommvoy+ "&departure_day=" + document.saisie.departure_day.options[document.saisie.departure_day.selectedIndex].value;
 if(document.saisie.departure_month !=null)
 		fieldsCommvoy = fieldsCommvoy+ "&departure_month=" + document.saisie.departure_month.options[document.saisie.departure_month.selectedIndex].value;
 if(document.saisie.departure_choice !=null)
 		fieldsCommvoy = fieldsCommvoy+ "&departure_choice=" + document.saisie.departure_choice.value;
 if(document.saisie.departure_time_min !=null)
 		fieldsCommvoy = fieldsCommvoy+ "&departure_time_min=" + document.saisie.departure_time_min.options[document.saisie.departure_time_min.selectedIndex].value;
 if(document.saisie.departure_time_max !=null)
 		fieldsCommvoy = fieldsCommvoy+ "&departure_time_max=" + document.saisie.departure_time_max.options[document.saisie.departure_time_max.selectedIndex].value;
 if(document.saisie.return_day !=null)
 		fieldsCommvoy = fieldsCommvoy+ "&return_day=" + document.saisie.return_day.options[document.saisie.return_day.selectedIndex].value;
 if(document.saisie.return_month !=null)
 		fieldsCommvoy = fieldsCommvoy+ "&return_month=" + document.saisie.return_month.options[document.saisie.return_month.selectedIndex].value;
 if(document.saisie.return_choice !=null)
 		fieldsCommvoy = fieldsCommvoy+ "&return_choice=" + document.saisie.return_choice.value;
 if(document.saisie.return_time_min !=null)
 		fieldsCommvoy = fieldsCommvoy+ "&return_time_min=" + document.saisie.return_time_min.options[document.saisie.return_time_min.selectedIndex].value;
 if(document.saisie.return_time_max !=null)
 		fieldsCommvoy = fieldsCommvoy+ "&return_time_max=" + document.saisie.return_time_max.options[document.saisie.return_time_max.selectedIndex].value;
 if(document.saisie.service_COMMVOY1_MEAL !=null){
	radioBt = document.saisie.service_COMMVOY1_MEAL;
var val=''
	for(i=0; i<radioBt.length; i++) { if(radioBt[i].checked) val = radioBt[i].value;}
	fieldsCommvoy = fieldsCommvoy+ "&service_COMMVOY1_MEAL="+ val;}
 if(document.saisie.service_COMMVOY1_PLACEMENT_DAY_TRAIN !=null){
	radioBt = document.saisie.service_COMMVOY1_PLACEMENT_DAY_TRAIN;
var val=''
	for(i=0; i<radioBt.length; i++) { if(radioBt[i].checked) val = radioBt[i].value;}
	fieldsCommvoy = fieldsCommvoy+ "&service_COMMVOY1_PLACEMENT_DAY_TRAIN="+ val;}
 if(document.saisie.service_COMMVOY1_BOOKING !=null){
	radioBt = document.saisie.service_COMMVOY1_BOOKING;
var val=''
	for(i=0; i<radioBt.length; i++) { if(radioBt[i].checked) val = radioBt[i].value;}
	fieldsCommvoy = fieldsCommvoy+ "&service_COMMVOY1_BOOKING="+ val;}
 if(document.saisie.service_COMMVOY1_NURSERY !=null){
	radioBt = document.saisie.service_COMMVOY1_NURSERY;
var val=''
	for(i=0; i<radioBt.length; i++) { if(radioBt[i].checked) val = radioBt[i].value;}
	fieldsCommvoy = fieldsCommvoy+ "&service_COMMVOY1_NURSERY="+ val;}
 if(document.saisie.service_COMMVOY1_HAND !=null){
	radioBt = document.saisie.service_COMMVOY1_HAND;
var val=''
	for(i=0; i<radioBt.length; i++) { if(radioBt[i].checked) val = radioBt[i].value;}
	fieldsCommvoy = fieldsCommvoy+ "&service_COMMVOY1_HAND="+ val;}
 if(document.saisie.service_COMMVOY1_BIKE !=null){
	radioBt = document.saisie.service_COMMVOY1_BIKE;
var val=''
	for(i=0; i<radioBt.length; i++) { if(radioBt[i].checked) val = radioBt[i].value;}
	fieldsCommvoy = fieldsCommvoy+ "&service_COMMVOY1_BIKE="+ val;}
 if(document.saisie.service_COMMVOY1_PLACEMENT_FIRSTCLASS_TGV !=null){
	radioBt = document.saisie.service_COMMVOY1_PLACEMENT_FIRSTCLASS_TGV;
var val=''
	for(i=0; i<radioBt.length; i++) { if(radioBt[i].checked) val = radioBt[i].value;}
	fieldsCommvoy = fieldsCommvoy+ "&service_COMMVOY1_PLACEMENT_FIRSTCLASS_TGV="+ val;}
 if(document.saisie.service_COMMVOY1_PLACEMENT_CORAIL_TRAIN !=null){
	radioBt = document.saisie.service_COMMVOY1_PLACEMENT_CORAIL_TRAIN;
var val=''
	for(i=0; i<radioBt.length; i++) { if(radioBt[i].checked) val = radioBt[i].value;}
	fieldsCommvoy = fieldsCommvoy+ "&service_COMMVOY1_PLACEMENT_CORAIL_TRAIN="+ val;}
 if(document.saisie.service_COMMVOY1_SMOKER !=null){
	radioBt = document.saisie.service_COMMVOY1_SMOKER;
var val=''
	for(i=0; i<radioBt.length; i++) { if(radioBt[i].checked) val = radioBt[i].value;}
	fieldsCommvoy = fieldsCommvoy+ "&service_COMMVOY1_SMOKER="+ val;}
 if(document.saisie.service_COMMVOY1_PLACEMENT_DUPLEX !=null){
	radioBt = document.saisie.service_COMMVOY1_PLACEMENT_DUPLEX;
var val=''
	for(i=0; i<radioBt.length; i++) { if(radioBt[i].checked) val = radioBt[i].value;}
	fieldsCommvoy = fieldsCommvoy+ "&service_COMMVOY1_PLACEMENT_DUPLEX="+ val;}
 if(document.saisie.service_COMMVOY1_CLASS !=null){
	radioBt = document.saisie.service_COMMVOY1_CLASS;
var val=''
	for(i=0; i<radioBt.length; i++) { if(radioBt[i].checked) val = radioBt[i].value;}
	fieldsCommvoy = fieldsCommvoy+ "&service_COMMVOY1_CLASS="+ val;}
  var URL = "http://www.voyages-sncf.com/dynamic/_SvTermCommvoy1Ta?_DLG=SvTermCommvoy1Ta&_CMD=cmdIdentification&_LANG=FR&_AGENCY=VSC";
  URL = URL + fieldsCommvoy
  if (rubriqueLogin && !rubriqueLogin.closed) { rubriqueLogin.location.replace(URL);}
  else { rubriqueLogin=window.open(URL, 'Identification','width=600,height=210');}
}


function showInvitation()
{
  document.location.href = "/invit_voyage/home_invit_voyage_fr.htm";
}
function showDeNousAVous()
{
  document.location.href = "/nous_a_vous/home_nous_a_vous_fr.htm";
}
function showALaUne()
{
  document.location.href = "/a_la_une/home_a_la_une_fr.htm";
}


function frequentTravelSelected(liste)
{
	document.location = 'http://www.voyages-sncf.com/dynamic/_SvTermCommvoy1Ta?_DLG=SvTermCommvoy1Ta&_CMD=CMD_FREQUENT_TRAVEL_CHOOSEN&_LANG=FR&_AGENCY=VSC&regularTravel=' + liste.options[liste.selectedIndex].value
}


function changeCard(myIndex){}
function showConversionWindow(URL)
{
conversionWindow = window.open(URL, 'Conversion','width=740,height=600,scrollbars=no');
}
</SCRIPT>
<SCRIPT LANGUAGE=JAVASCRIPT>
var languagePrefix = "/img/FR/fr";
function showCalendar(UpdateWhichDay, UpdateWhichMonth, UpdateWhichDIV) {
  FieldForDay=UpdateWhichDay; FieldForMonth=UpdateWhichMonth; cible=UpdateWhichDIV; 
  window.open('/webgl/popup_cal.jsp','calendrier','status=no,width=249,height=250');
}
// initialisation des variables propres a la page
// declaration de cible pour le popup de calendrier
var today = new Date();
var cible;
var formName='saisie'; 
</SCRIPT>

</head>

<!-- BODY -->
<body bgcolor="#B0B0EB" leftmargin="0" topmargin="0" marginwidth="0" marginheight="0">


<SCRIPT language="JavaScript">


function validGoExpedia(expediaURL) {

		document.location.href = expediaURL;

}
</SCRIPT>
<link rel="stylesheet" href="/homepage/homepageV1.css" type="text/css">

<!-- FORM -->

<table border="0" cellspacing="0" cellpadding="0">
<tr><td>
<form name="saisie" action="http://www.voyages-sncf.com/dynamic/_SvTermCommvoy1Std" method="POST">


<INPUT TYPE="hidden" NAME="_DLG" VALUE="SvTermCommvoy1Ta">
<INPUT TYPE="hidden" NAME="_LANG" VALUE="FR">
<INPUT TYPE="hidden" NAME="_AGENCY" VALUE="VSC">
<INPUT TYPE="hidden" NAME="_CMD" VALUE="defaultCmd">
 



<script type="text/javascript" src="/src/gen.js"></script>
<script type="text/javascript" src="/src/frpopupcal.js"></script>
<script type="text/javascript" src="/src/dhtml.js"></script>
<script type="text/javascript" src="/src/resa.js"></script>
<script type="text/javascript" src="/src/Luhn.js"></script>

<script type="text/javascript">
nbJourLimite=60  // Nb de jour pendant lesquels on fait la recherche
nbMoisLimite=4   // Nb de mois pendant lesquels on fait la recherche
largeur=66
hauteur=28
var dimanche = new Image(largeur,hauteur)
dimanche.src = "/img/FR/frdimanche.gif"
var samedi = new Image(largeur,largeur)
samedi.src = "/img/FR/frsamedi.gif"
var vendredi = new Image(largeur,largeur)
vendredi.src = "/img/FR/frvendredi.gif"
var jeudi = new Image(largeur,largeur)
jeudi.src = "/img/FR/frjeudi.gif"
var mercredi = new Image(largeur,largeur)
mercredi.src = "/img/FR/frmercredi.gif"
var mardi = new Image(largeur,largeur)
mardi.src = "/img/FR/frmardi.gif"
var lundi = new Image(largeur,largeur)
lundi.src = "/img/FR/frlundi.gif"

// variables de scroll
var attente;
var attente2;
var bw=new checkBrowser();
var oDiv = new Array();//


function ComboControl()
{
	if(document.saisie.namePassenger_0){
	m=0;
	NomVoyageurTab = new Array(2);
	for (i=0 ; i<2 ; i++)
	{
		NomVoyageur = eval("document.saisie.namePassenger_"+i+".options[document.saisie.namePassenger_"+i+".selectedIndex].value") ;
		if ( NomVoyageur !="-1" && NomVoyageur !="-2" )
		{
			NomVoyageurTab[m] = NomVoyageur;
			m++;
		}
	}
	j=0;
	for (i=0 ; i<2 ; i++)
	{
		NomVoyageur = eval("document.saisie.namePassenger_"+i+".options[document.saisie.namePassenger_"+i+".selectedIndex].value") ;
		if ( NomVoyageur != "-1" && NomVoyageur != "-2" )
		{
			for (n=j+1 ; n<NomVoyageurTab.length ; n++)
			{
				if ( NomVoyageurTab[n] == NomVoyageur )
				{	
					alert("Vous avez sélectionné plusieurs fois le même passager. <BR> Veuillez n'en sélectionner qu'un.");
					myField=eval("document.saisie.namePassenger_"+i);
					myField.focus();
					return false ;
					break;
				}
			}j++;
		}
	}
	return true ;}
}

function valid()
{
	with (document.saisie) {
		var sDeparture = new String(OD_departure.value)
		var sArrival = new String(OD_arrival.value)
		var sVia = new String(OD_via.value)
		
		if (sDeparture == '') {
			alert("Attention ! Vous n'avez pas renseigné le lieu de départ.");
			return false;
			}
		
		if (sArrival == '') {
			alert("Attention ! Vous n'avez pas renseigné le lieu d'arrivée.");
			return false;
		}
		 
		if (sDeparture.length <=1 || sArrival.length <=1) {
			alert("Le nom de la ville est trop petit");			
			return false;
		}
		if(	sDeparture.toUpperCase() == sArrival.toUpperCase() ||
			sVia.toUpperCase() == sDeparture.toUpperCase()	||
			sVia.toUpperCase() == sArrival.toUpperCase() ) {
			alert("Attention! Vous avez sélectionné deux villes identiques. Veuillez modifier l'un ou l'autre de ces lieux. Merci");
			return false;
		}
		if (document.saisie.namePassenger_0)
		{
			if(!ComboControl()){
			return false ;
			}
		}

		//correction d'anomalie NAA le 12/07 pour un bon affichage de l'alert pour le tarif bambin
		passengerLine = new Array(2);
		//si tranche enfant 0 - 3 ans et carte selectionnee = enfant+ => ok sinon ko
		for (i=0 ; i<2 ; i++)
		{
			//est enfant
			AgeGroup = eval("document.saisie.age_bracket_"+i+".options[document.saisie.age_bracket_"+i+".selectedIndex].value") ;
			if ( AgeGroup == "A" )
			{
				card = eval("document.saisie.cardtype_"+i+".options[document.saisie.cardtype_"+i+".selectedIndex].value") ;
				//a carte enfant plus
				if (card == 3)
				{
					passengerLine[i] = true;
				}
				else
				{	
					passengerLine[i] = false;
				}
			}
			else 
			{
				passengerLine[i] = true;
			}

		}
		childFareAuthorized = true;
		for (i=0 ; i<2 ; i++)
		{
			
			if (!passengerLine[i])
			{
				childFareAuthorized = false;
				break;
			}
		}
		
		if (!childFareAuthorized)
		{
			if (!confirm("Nous ne sommes pas en mesure de vous proposer le forfait Bambin pour les enfants de 0 à 3 ans. Si vous souhaitez que l'enfant voyage gratuitement sans place attribuée, veuillez ne pas le saisir dans la liste de vos passagers. Cliquez sur OK pour continuer."))
			{
				return false;
			}
		}
		/////

	}
	return true
}

function get_departure_year()
{
// getMonth is a 0 based index, departure_month is the real month
	if ( GetSelectedValue(document.saisie.departure_month) < today.getMonth()){
		if (bw.nn) return 1900 + today.getYear() + 1;
		else return today.getYear()+1;
	}
	else{
		if (bw.nn) return 1900 + today.getYear();
		else return today.getYear();
	}
}

function get_return_year()
{
	// getMonth is a 0 based index, "+ HtmlFieldsConstants.RETURN_MONTH+ " is the real month
	if ( GetSelectedValue(document.saisie.return_month) < today.getMonth()){
		if (bw.nn) return 1900 + today.getYear() + 1;
		else return today.getYear()+1;
	}
	else{
		if (bw.nn) return 1900 + today.getYear();
		else return today.getYear();
	}
}

function validerDepart() {
    // cette fonction valide la date en cas de changement des SELECT

	departure_year = get_departure_year();
	return_year = get_return_year();
	
	with (document.saisie) {
	    //if (departure_day.selectedIndex) {
		// validate number of days in the month
		dateDepart = new Date(departure_year, GetSelectedValue(departure_month), 1);
    		oDateTest = new objDate(dateDepart);
		if(departure_day.selectedIndex+1 > oDateTest.nbJoursMois) {
    			departure_day.selectedIndex = oDateTest.nbJoursMois-1;
    		}
		// display Week day.
		dateDepart = new Date(departure_year, GetSelectedValue(departure_month), departure_day.selectedIndex+1);
		oDateTest = new objDate(dateDepart);
    		jourAff = oDateTest.jourSem;
    		ecrireDate('divDepart',jourSemArray[jourAff]);

    		// validation date de retour
    		// DEPARTURE_DAY is zero based index and the first element (index=0) is the first day: 1.
    		// RETURN_DAY is zero based index    but the first element (index=0) is empty.
    		if( (return_day.selectedIndex> 0) && (return_month.selectedIndex> 0)) {
	    		dateRetour = new Date(return_year, GetSelectedValue(return_month),return_day.selectedIndex);
			if(dateRetour<dateDepart) {
    	    			return_month.selectedIndex = departure_month.selectedIndex+1;
            			return_day.selectedIndex = departure_day.selectedIndex+1;
            			ecrireDate('divRetour',jourSemArray[jourAff]);
        		}
		}
	    // }
	}
}

function validerRetour() {
	
	// cette fonction valide la date en cas de changement des SELECT
	with(document.saisie) {
	   if (return_day.selectedIndex) {		
		
		if((return_day.options[return_month.selectedIndex].value>-1) && (return_month.options[return_month.selectedIndex].value> -1)) {
			departure_year = get_departure_year();
			return_year = get_return_year();
			
			dateRetour = new Date(return_year,GetSelectedValue(return_month), 1);
			oDateTest = new objDate(dateRetour);
			if(return_day.selectedIndex > oDateTest.nbJoursMois) {
				return_day.selectedIndex = oDateTest.nbJoursMois;
			}
        	
			// validate return_date
    	    		dateRetour = new Date(return_year, GetSelectedValue(return_month), GetSelectedValue(return_day));
			dateDepart = new Date(departure_year, GetSelectedValue(departure_month), GetSelectedValue(departure_day));
			if(dateDepart>dateRetour) {
       	       			return_day.selectedIndex = parseInt(GetSelectedIndex(departure_day)) + 1;
       	       			for(var i=0; i<return_month.options.length ; i++) {
       	       				if(return_month.options[i].value == GetSelectedValue(departure_month)) {
       	       					return_month.selectedIndex = i;
       	       				}
       	       			}
        		}
        
			// display week day
    	    		dateRetour = new Date(return_year,GetSelectedValue(return_month), GetSelectedValue(return_day));
	    		oDateTest = new objDate(dateRetour);
   	    		jourAff = oDateTest.jourSem;
   	
       			ecrireDate('divRetour',jourSemArray[jourAff]);
    		}
		else {
			ecrireDate('divRetour','');
    		}
    	    } // du if
	} // du with
}

function showCalendar(UpdateWhichDay, UpdateWhichMonth, UpdateWhichDIV) {
	FieldForDay=UpdateWhichDay; FieldForMonth=UpdateWhichMonth; cible=UpdateWhichDIV;
	window.open('http://www.voyages-sncf.com/dynamic/_SvTermCommvoy1Ta?_DLG=SvTermCommvoy1Ta&_CMD=CMD_POPUP_CALENDAR&_LANG=FR&_AGENCY=VSC','calendrier','status=no,width=249,height=250');
}

</script>


<input type="hidden" name="hf_help" value="COMMVOY1">


<!-- TRAVEL -->

		

  
   <!------------------------------------------------------------------
 		
 		               "VOTRE VOYAGE"

DESCRIPTION :
Description du voyage: depart, arrivee, trajet direct ou via

page to include in other pages

------------------------------------------------------------------->

<?PHP

print_titre("Voyage");

?>
  <table border="0" cellspacing="0" cellpadding="0">
    <tr> 


      <td align="right"><img src="frcoiga.gif" width="23" height="51"></td>
      <td> 
        <table border="0" cellspacing="0" cellpadding="0">
          <tr> 
            <td bgcolor="#755FFF"><img src="b.gif" width="1" height="1"></td>
            <td bgcolor="#755FFF"><img src="b.gif" width="604" height="1"></td>
          </tr>
          <tr> 
            <td bgcolor="#D2D1FF"><img src="b.gif" width="1" height="49"></td>
            <td align="left" bgcolor="#D2D1FF"> 
              <table border="0" cellspacing="0" cellpadding="0">
                <tr> 
                  <td><img src="b.gif" width="40" height="1"></td>
                  <td><img src="b.gif" width="5" height="1"></td>
                  <td><img src="b.gif" width="160" height="1"></td>
                  <td><img src="b.gif" width="5" height="1"></td>
                  <td><img src="b.gif" width="43" height="1"></td>
                  <td><img src="b.gif" width="5" height="1"></td>
                  <td><img src="b.gif" width="160" height="1"></td>
                  <td><img src="b.gif" width="8" height="1"></td>
                  <td><img src="b.gif" width="158" height="1"></td>
                </tr>
                <tr> 
                  <td class="default" align="left" nowrap>Départ:</td>
                  <td></td>
                  <td class="default" align="left"><b>Paris</b>
                   
                  <input type=hidden name="OD_departure"  value="Paris">
                  <input type=hidden name="OID_departure"  value="">
                  </td>
                  <td></td>
                  <td class="default" align="left" nowrap>Lieu d'Arrivée:</td>
                  <td></td>
                  <td class="default" align="left"><b>Auray</b>
                  <input type=hidden name="OD_arrival"  value="auray">
                  <input type=hidden name="OID_arrival"  value="">
                  </td>
                  <td rowspan="2" align="center" valign="top">
                  
                  	<INPUT type="checkbox" name ="OD_isDirect" value="Y" checked onClick='checkConsistencyBetweenViaAndDirect()';>
                 
                  </td>
                 <td align="left" class="default" nowrap bgcolor="#D2D1FF"> Trajet direct uniquement
                 
                   </td>
                  <td align="left" rowspan="2">&nbsp;</td>
                </tr>
                <tr> 
                  <td colspan="2"></td>
                  <td> 
                    <table border="0" cellspacing="0" cellpadding="0">
                      <tr>
                        <td><img src="b.gif" width="1" height="2"></td>
                      </tr>
                      <tr>
                     
                      
                		<INPUT type="HIDDEN" name="OD_via" value = ""> 	
                	
                     
                     
                     
                       </td>
                       
                      </tr>
                    </table>
                  </td>
                  <td colspan="3">
                  </td>
                  <!-- TD? -->
                </tr>
                
              </table>
            </td>
          </tr>
          <tr> 
            <td bgcolor="#755FFF"><img src="b.gif" width="1" height="1"></td>
            <td bgcolor="#755FFF"><img src="b.gif" width="1" height="1"></td>
          </tr>
        </table>
      </td>
      <td><img src="frcoidr.gif" width="23" height="51"></td>
    </tr>
  </table>


<!-- OUTWARD JOURNEY -->

<br>


<table border="0" cellspacing="0" cellpadding="0">
 <tr> 
  <td><img src="b.gif" width="13" height="1"></td>
  <td><img src="b.gif" width="61" height="1"></td>
  <td><img src="frcoiga.gif" width="23" height="51"></td>
  <td valign="top"> 
   <table border="0" cellspacing="0" cellpadding="0">
    <tr> 
     <td bgcolor="#D2D1FF"><img src="b.gif" width="1" height="1"></td>
     <td bgcolor="#D2D1FF"><img src="b.gif" width="556" height="1"></td>
    </tr>    
    <tr> 
     <td bgcolor="#D2D1FF"><img src="b.gif" width="1" height="49"></td>
     <td align="left" bgcolor="#D2D1FF">
      <table border="0" cellspacing="0" cellpadding="0">
       <tr>
        <td><img src="b.gif" width="20" height="1"></td>
        <td><img src="b.gif" width="5" height="1"></td>
        <td><img src="b.gif" width="65" height="1"></td>
        <td><img src="b.gif" width="5" height="1"></td>
        <td><img src="b.gif" width="145" height="1"></td>
        <td><img src="b.gif" width="5" height="1"></td>
        <td><img src="b.gif" width="95" height="1"></td>
        <td><img src="b.gif" width="5" height="1"></td>
        <td><img src="b.gif" width="30" height="1"></td>
        <td><img src="b.gif" width="5" height="1"></td>
        <td><img src="b.gif" width="46" height="1"></td>
        <td><img src="b.gif" width="5" height="1"></td>
        <td><img src="b.gif" width="10" height="1"></td>
        <td><img src="b.gif" width="5" height="1"></td>
        <td><img src="b.gif" width="46" height="1"></td>
        <td><img src="b.gif" width="8" height="1"></td>
       </tr>
       <tr>
        <td align="left" class="default" nowrap><b>Aller</b> *</td>
        <td>&nbsp;</td>
        <td valign="top" align="right" class="default"><img src="b.gif" name="OUTWARD" height="28" width="76"></td>
        <td>&nbsp;</td>
        <td align="left" nowrap><SELECT name="departure_day" onChange="validerDepart()">
	  <OPTION value="1" >01</OPTION>
	  <OPTION value="2" >02</OPTION>
	  <OPTION value="3" >03</OPTION>
	  <OPTION value="4" >04</OPTION>
	  <OPTION value="5" >05</OPTION>
	  <OPTION value="6" >06</OPTION>
	  <OPTION value="7" >07</OPTION>
	  <OPTION value="8" >08</OPTION>
	  <OPTION value="9" >09</OPTION>
	  <OPTION value="10" >10</OPTION>
	  <OPTION value="11" >11</OPTION>
	  <OPTION value="12" >12</OPTION>
	  <OPTION value="13" >13</OPTION>
	  <OPTION value="14" >14</OPTION>
	  <OPTION value="15" >15</OPTION>
	  <OPTION value="16" >16</OPTION>
	  <OPTION value="17" >17</OPTION>
	  <OPTION value="18" >18</OPTION>
	  <OPTION value="19" >19</OPTION>
	  <OPTION value="20" >20</OPTION>
	  <OPTION value="21" >21</OPTION>
	  <OPTION value="22" >22</OPTION>
	  <OPTION value="23" >23</OPTION>
	  <OPTION value="24" >24</OPTION>
	  <OPTION value="25" >25</OPTION>
	  <OPTION value="26" >26</OPTION>
	  <OPTION value="27"  selected>27</OPTION>
	  <OPTION value="28" >28</OPTION>
	  <OPTION value="29" >29</OPTION>
	  <OPTION value="30" >30</OPTION>
	  <OPTION value="31" >31</OPTION>
	  </SELECT><img src="b.gif" width="3" height="1">
	  <SELECT name="departure_month" onChange="validerDepart()">
	  <OPTION value="4" selected >Mai</OPTION>
	  <OPTION value="5">Juin</OPTION>
	  <OPTION value="6">Juillet</OPTION>
	  </SELECT><br><IMG SRC="b.gif" width="1" height="3"><br>

	</td>
        <td>&nbsp;</td>
        <td align="center"><table border="0" cellspacing="0" cellpadding="0">
          <tr>
           <td align="center" valign="middle"><INPUT type="radio" name="departure_choice" value="D" checked            ></td>
           <td align="right" class="default"> Train partant</td>
          </tr>
          <tr><td colspan="2" bgcolor="#D2D1FF"><img src="b.gif" width="1" height="2"></td></tr>
          <tr><td align="center" valign="middle"><INPUT type="radio" name="departure_choice" value="A"           ></td>
           <td align="left" class="default" bgcolor="#D2D1FF"> Train arrivant</td>
          </tr>
         </table>
        </td>
        <td>&nbsp;</td>
        <td align="center" class="default">entre</td>
        <td>&nbsp;</td>
        <td align="right"><SELECT name='departure_time_min' onChange="validerHeure(this,document.saisie.departure_time_max)"> 
	  <OPTION value="0">00h</OPTION>
	  <OPTION value="1">01h</OPTION>
	  <OPTION value="2">02h</OPTION>
	  <OPTION value="3">03h</OPTION>
	  <OPTION value="4">04h</OPTION>
	  <OPTION value="5">05h</OPTION>
	  <OPTION value="6">06h</OPTION>
	  <OPTION value="7">07h</OPTION>
	  <OPTION value="8" selected>08h</OPTION>
	  <OPTION value="9">09h</OPTION>
	  <OPTION value="10">10h</OPTION>
	  <OPTION value="11">11h</OPTION>
	  <OPTION value="12">12h</OPTION>
	  <OPTION value="13">13h</OPTION>
	  <OPTION value="14">14h</OPTION>
	  <OPTION value="15">15h</OPTION>
	  <OPTION value="16">16h</OPTION>
	  <OPTION value="17">17h</OPTION>
	  <OPTION value="18">18h</OPTION>
	  <OPTION value="19">19h</OPTION>
	  <OPTION value="20">20h</OPTION>
	  <OPTION value="21">21h</OPTION>
	  <OPTION value="22">22h</OPTION>
	  <OPTION value="23">23h</OPTION>
	  </SELECT>
	     </td>
	     <td>&nbsp;</td>
        <td class="default">et</td>
        <td>&nbsp;</td>
        <td align='left'><SELECT name='departure_time_max'>
	  <OPTION value="0">00h</OPTION>
	  <OPTION value="1">01h</OPTION>
	  <OPTION value="2">02h</OPTION>
	  <OPTION value="3">03h</OPTION>
	  <OPTION value="4">04h</OPTION>
	  <OPTION value="5">05h</OPTION>
	  <OPTION value="6">06h</OPTION>
	  <OPTION value="7">07h</OPTION>
	  <OPTION value="8">08h</OPTION>
	  <OPTION value="9">09h</OPTION>
	  <OPTION value="10">10h</OPTION>
	  <OPTION value="11">11h</OPTION>
	  <OPTION value="12" selected>12h</OPTION>
	  <OPTION value="13">13h</OPTION>
	  <OPTION value="14">14h</OPTION>
	  <OPTION value="15">15h</OPTION>
	  <OPTION value="16">16h</OPTION>
	  <OPTION value="17">17h</OPTION>
	  <OPTION value="18">18h</OPTION>
	  <OPTION value="19">19h</OPTION>
	  <OPTION value="20">20h</OPTION>
	  <OPTION value="21">21h</OPTION>
	  <OPTION value="22">22h</OPTION>
	  <OPTION value="23">23h</OPTION>
	  </SELECT>
        </td>
       </tr>
      </table>
     </td>
    </tr>
    <tr> 
     <td bgcolor="#755FFF"><img src="b.gif" width="1" height="1"></td> 
     <td bgcolor="#755FFF"><img src="b.gif" width="1" height="1"></td> 
    </tr>
   </table>
  </td>
  <td><img src="frcoidr.gif" width="23" height="51"></td>
 </tr>
</table>



<input type=hidden value="saisie" name="outwardMode">
	
		

<!-- RETURN JOURNEY -->
	
  <!------------------------------------------------------------------
 		
 		               "VOTRE VOYAGE"

DESCRIPTION :
Description du retour

page to include in other pages


------------------------------------------------------------------->


<table border="0" cellspacing="0" cellpadding="0">
 <tr><td colspan="4"><img src="b.gif" width="1" height="7"></td></tr>
 <tr> 
  <td><img src="b.gif" width="74" height="1"></td>
  <td><img src="frcoiga.gif" width="23" height="51"></td>
  <td valign="top"> 
   <table border="0" cellspacing="0" cellpadding="0" width="585">
    <tr> 
     <td bgcolor="#755FFF"><img src="b.gif" width="1" height="1"></td>
     <td bgcolor="#755FFF"><img src="b.gif" width="546" height="1"></td>
    </tr>
    <tr> 
     <td bgcolor="#D2D1FF"><img src="b.gif" width="1" height="49"></td>
     <td align="left" bgcolor="#D2D1FF">
      <table border="0" cellspacing="0" cellpadding="0">
       <tr>
        <td><img src="b.gif" width="70" height="1"></td>
        <td><img src="b.gif" width="5" height="1"></td>
        <td><img src="b.gif" width="65" height="1"></td>
        <td><img src="b.gif" width="5" height="1"></td>
        <td><img src="b.gif" width="150" height="1"></td>
        <td><img src="b.gif" width="5" height="1"></td>
        <td><img src="b.gif" width="85" height="1"></td>
        <td><img src="b.gif" width="5" height="1"></td>
        <td><img src="b.gif" width="24" height="1"></td>
        <td><img src="b.gif" width="5" height="1"></td>
        <td><img src="b.gif" width="54" height="1"></td>
        <td><img src="b.gif" width="5" height="1"></td>
        <td><img src="b.gif" width="9" height="1"></td>
        <td><img src="b.gif" width="5" height="1"></td>
        <td><img src="b.gif" width="54" height="1"></td>
       </tr>
       <tr>
        <td align="left" class="default"><b>Retour</b></td>
        <td>&nbsp;</td>
        <td valign="top" align="right" class="default"><img src="b.gif" name="RETURN" height="28" width="76"></td>
        <td>&nbsp;</td>
        <td align="left" nowrap><SELECT name="return_day" class="select1" onChange="validerRetour()">
	  <OPTION selected></OPTION>
	  <OPTION value="1" >01</OPTION>
	  <OPTION value="2" >02</OPTION>
	  <OPTION value="3" >03</OPTION>
	  <OPTION value="4" >04</OPTION>
	  <OPTION value="5" >05</OPTION>
	  <OPTION value="6" >06</OPTION>
	  <OPTION value="7" >07</OPTION>
	  <OPTION value="8" >08</OPTION>
	  <OPTION value="9" >09</OPTION>
	  <OPTION value="10" >10</OPTION>
	  <OPTION value="11" >11</OPTION>
	  <OPTION value="12" >12</OPTION>
	  <OPTION value="13" >13</OPTION>
	  <OPTION value="14" >14</OPTION>
	  <OPTION value="15" >15</OPTION>
	  <OPTION value="16" >16</OPTION>
	  <OPTION value="17" >17</OPTION>
	  <OPTION value="18" >18</OPTION>
	  <OPTION value="19" >19</OPTION>
	  <OPTION value="20" >20</OPTION>
	  <OPTION value="21" >21</OPTION>
	  <OPTION value="22" >22</OPTION>
	  <OPTION value="23" >23</OPTION>
	  <OPTION value="24" >24</OPTION>
	  <OPTION value="25" >25</OPTION>
	  <OPTION value="26" >26</OPTION>
	  <OPTION value="27" >27</OPTION>
	  <OPTION value="28" >28</OPTION>
	  <OPTION value="29" >29</OPTION>
	  <OPTION value="30" >30</OPTION>
	  <OPTION value="31" >31</OPTION>
	  </SELECT><img src="b.gif" width="3" height="1">
	  <SELECT name="return_month" class="select1" onChange='validerRetour()'>
	  <OPTION value="-1" selected></OPTION>
	  <OPTION value="4">Mai</OPTION>
	  <OPTION value="5">Juin</OPTION>
	  <OPTION value="6">Juillet</OPTION>
	  </SELECT><br><img src="b.gif" width="1" height="2"><br>

        <td>&nbsp;</td>
        <td align="center"><table border="0" cellspacing="0" cellpadding="0">
          <tr>
           <td align="center" valign="middle"><INPUT type="radio" name="return_choice" value="D" 
 checked            ></td>
           <td nowrap align="right" class="default" bgcolor="#D2D1FF"> Train partant</td>
          </tr>
          <tr><td colspan="2" bgcolor="#D2D1FF"><img src="b.gif" width="1" height="2"></td></tr>
          <tr>
           <td align="center" valign="middle"><INPUT type="radio" name="return_choice" value="A"
           ></td>
           <td nowrap align="left" class="default" bgcolor="#D2D1FF"> Train arrivant</td>
          </tr>
         </table>
        </td>
        <td>&nbsp;</td>
        <td class="default">entre</td>
        <td>&nbsp;</td>
        <td align="right"><SELECT name="return_time_min" class="select1" onChange="validerHeure(this,document.saisie.return_time_max, this.options[0].value)"> 
	  <OPTION value="-1" selected></OPTION>
	  <OPTION value="0">00h</OPTION>
	  <OPTION value="1">01h</OPTION>
	  <OPTION value="2">02h</OPTION>
	  <OPTION value="3">03h</OPTION>
	  <OPTION value="4">04h</OPTION>
	  <OPTION value="5">05h</OPTION>
	  <OPTION value="6">06h</OPTION>
	  <OPTION value="7">07h</OPTION>
	  <OPTION value="8">08h</OPTION>
	  <OPTION value="9">09h</OPTION>
	  <OPTION value="10">10h</OPTION>
	  <OPTION value="11">11h</OPTION>
	  <OPTION value="12">12h</OPTION>
	  <OPTION value="13">13h</OPTION>
	  <OPTION value="14">14h</OPTION>
	  <OPTION value="15">15h</OPTION>
	  <OPTION value="16">16h</OPTION>
	  <OPTION value="17">17h</OPTION>
	  <OPTION value="18">18h</OPTION>
	  <OPTION value="19">19h</OPTION>
	  <OPTION value="20">20h</OPTION>
	  <OPTION value="21">21h</OPTION>
	  <OPTION value="22">22h</OPTION>
	  <OPTION value="23">23h</OPTION>
	  </SELECT>
        </td>
        <td>&nbsp;</td>
        <td class="default">et</td>
        <td>&nbsp;</td>
        <td align="right"><SELECT name="return_time_max" class="select1">
	  <OPTION value="-1" selected></OPTION>
	  <OPTION value="0">00h</OPTION>
	  <OPTION value="1">01h</OPTION>
	  <OPTION value="2">02h</OPTION>
	  <OPTION value="3">03h</OPTION>
	  <OPTION value="4">04h</OPTION>
	  <OPTION value="5">05h</OPTION>
	  <OPTION value="6">06h</OPTION>
	  <OPTION value="7">07h</OPTION>
	  <OPTION value="8">08h</OPTION>
	  <OPTION value="9">09h</OPTION>
	  <OPTION value="10">10h</OPTION>
	  <OPTION value="11">11h</OPTION>
	  <OPTION value="12">12h</OPTION>
	  <OPTION value="13">13h</OPTION>
	  <OPTION value="14">14h</OPTION>
	  <OPTION value="15">15h</OPTION>
	  <OPTION value="16">16h</OPTION>
	  <OPTION value="17">17h</OPTION>
	  <OPTION value="18">18h</OPTION>
	  <OPTION value="19">19h</OPTION>
	  <OPTION value="20">20h</OPTION>
	  <OPTION value="21">21h</OPTION>
	  <OPTION value="22">22h</OPTION>
	  <OPTION value="23">23h</OPTION>
	  </SELECT>
        </td>
       </tr>
      </table>
     </td>
    </tr>
    <tr> 
     <td bgcolor="#755FFF"><img src="b.gif" width="1" height="1"></td>
     <td bgcolor="#755FFF"><img src="b.gif" width="1" height="1"></td>
    </tr>
   </table>
  </td>
  <td><img src="frcoidr.gif" width="23" height="51"></td>
 </tr>
</table>

<input type=hidden value="saisie" name="returnMode">


		

<!-----------Separateur------------------>  
  <table border="0" cellspacing="0" cellpadding="0">
    <tr>
      <td colspan="3"><IMG SRC="b.gif" width="1" height="5"></td>
    </tr>
    <tr> 
      <td><img src="b.gif" width="13" height="1"></td>
      <td><img src="b.gif" width="66" height="1"></td>
      <td bgcolor="#8885DA"><img src="b.gif" width="604" height="1"></td>
    </tr>
    <tr>
      <td colspan="3"><img src="b.gif" width="1" height="5"></td>
    </tr>
  </table>

  
<!-- PASSENGERS -->

		

  <!------------Passagers----------------->    




<INPUT TYPE=HIDDEN NAME="nbRowsPassengers" VALUE="2" >
<table border="0" cellspacing="0" cellpadding="0">
  <tr> 
    <td valign="top"><img src="b.gif" width="13" height="1"></td>
    <td valign="top" align="left" class="default" ><img src="b.gif" width="96" height="5"><br>
      </td>
    <td align="left" valign="top" class="default" nowrap>

    </td></tr>
  <tr> 
    <td valign="top"><img src="b.gif" width="13" height="1"></td>
    <td valign="top" align="left" class="default" ><img src="b.gif" width="96" height="5"><br>
      </td>
    <td align="left" valign="top" class="default">
    &nbsp;
    </td></tr></table>
<table border="0" cellspacing="0" cellpadding="0">
  <tr> 
    <td valign="top"><img src="b.gif" width="13" height="1"></td>
    <td valign="top" align="left" class="default" ><img src="b.gif" width="75" height="5"><br>
      <b>Passagers</b></td>
    <td align="left" valign="top">
      <table border="0" cellspacing="0" cellpadding="0">
	<tr>
     	<td align="left"><SPAN class="default">1</SPAN></td>
    	<td><img src="b.gif" width="5" height="1"></td>
    	<td><img src="frpbjaun.gif" width="8" height="21"></td>
    	<td><img src="b.gif" width="5" height="1"></td>
     <td><select name="age_bracket_0">
<option value='0_0' ></option>
<option value='A' >Enfant entre 0 et 3 ans</option>
<option value='B' >Enfant entre 4 et 11 ans</option>
<option value='C' >Adulte entre 12 et 25 ans</option>
<option value='D'  selected >Adulte entre 26 et 59 ans</option>
<option value='E' >Adulte 60 ans et +</option>
     </select></td>
       <td><img src="b.gif" width="8" height="1"></td>
     <td><select name="cardtype_0" class="select1">
<option value='0'  selected >Choisir une carte</option>
<option value='1' >CARTE SENIOR</option>
<option value='2' >CARTE 12-25</option>
<option value='3' >CARTE ENFANTS+</option>
	  </select></td>
     <td><img src="b.gif" width="9" height="1"></td>
     <td><select name="otherTariff_0" class="select1" onChange="ChangeFareAlert(0)"> 
<option value='0' >Choisir un tarif</option>
<option value='1' >PLEIN TARIF</option>
<option value='2' >DECOUVERTE A DEUX</option>
<option value='3' >DECOUVERTE SEJOUR</option>
<option value='15' >FORFAIT SECONDE AVEC SURCLASSEMENT PREMIERE</option>
<option value='16' selected>ABONNEMENT FREQUENCE</option>
<option value='17' >FREQUENCE SECONDE AVEC SURCLASSEMENT PREMIERE</option>


	  </select></td>
  </tr>
  <tr><td colspan="9"><img src="b.gif" width="1" height="2"></td></tr>
     	<tr><td align="left">&nbsp;</td>
    	<td><img src="b.gif" width="5" height="1"></td>
    	<td><img src="b.gif" width="8" height="21"></td>
    	<td><img src="b.gif" width="5" height="1"></td>
<td nowrap align="left" class="default">
     <INPUT type="checkbox" name="is_freq_traveler_0" checked>     <img src="b.gif" width="7" height="1">
      Grand Voyageur</td>
     <td><img src="b.gif" width="9" height="1"></td>
       <td colspan="3" class="default" nowrap>n° 29090106 <INPUT type="text" name="no_freq_traveler_0" value="093952732" size="9" maxlength="9" class="select1"></td></tr> <tr><td colspan="9"><img src="b.gif" width="1" height="8"></td></tr>  	
	<tr>
     	<td align="left"><SPAN class="default">2</SPAN></td>
    	<td><img src="b.gif" width="5" height="1"></td>
    	<td><img src="frpbjaun.gif" width="8" height="21"></td>
    	<td><img src="b.gif" width="5" height="1"></td>
     <td><select name="age_bracket_1">
<option value='0_0'  selected ></option>
<option value='A' >Enfant entre 0 et 3 ans</option>
<option value='B' >Enfant entre 4 et 11 ans</option>
<option value='C' >Adulte entre 12 et 25 ans</option>
<option value='D' >Adulte entre 26 et 59 ans</option>
<option value='E' >Adulte 60 ans et +</option>
     </select></td>
       <td><img src="b.gif" width="8" height="1"></td>
     <td><select name="cardtype_1" class="select1">
<option value='0'  selected >Choisir une carte</option>
<option value='1' >CARTE SENIOR</option>
<option value='2' >CARTE 12-25</option>
<option value='3' >CARTE ENFANTS+</option>
	  </select></td>
     <td><img src="b.gif" width="9" height="1"></td>
     <td><select name="otherTariff_1" class="select1" onChange="ChangeFareAlert(1)"> 
<option value='0' >Choisir un tarif</option>
<option value='1' >PLEIN TARIF</option>
<option value='2' >DECOUVERTE A DEUX</option>
<option value='3' >DECOUVERTE SEJOUR</option>
<option value='5' >CARTE 12-25 50%</option>
<option value='6' >CARTE ENFANTS+ 50%</option>
<option value='8' >CARTE FAMILLE NOMBREUSE 75%</option>
<option value='9' >CARTE FAMILLE NOMBREUSE 50%</option>
<option value='10' >CARTE FAMILLE NOMBREUSE 40%</option>
<option value='11' >CARTE FAMILLE NOMBREUSE 30%</option>
<option value='12' >CARTE SENIOR 50%</option>
<option value='14' >ABONNEMENT FORFAIT</option>
<option value='15' >FORFAIT SECONDE AVEC SURCLASSEMENT PREMIERE</option>
<option value='16' >ABONNEMENT FREQUENCE</option>
<option value='17' >FREQUENCE SECONDE AVEC SURCLASSEMENT PREMIERE</option>
<option value='18' >CARTE ENFANTS+POUR LES - DE 4 ANS</option>
<option value='20' >DECOUVERTE SENIOR</option>
<option value='21' >DECOUVERTE ENFANTS+</option>
<option value='24' >BUSINESSPASS 2NDE AVEC SURCLASSEMENT 1ERE</option>
<option value='25' >BUSINESSPASS 1ERE OU 2NDE CLASSE</option>
<option value='26' >Place assise 2ème classe Decouverte J30 Adulte (30.0 EUR/196.79 FRF) </option>
<option value='28' >Place assise 2ème classe Decouverte J8 Adulte (40.0 EUR/262.38 FRF) </option>
<option value='29' >Place assise 2ème classe Decouverte J8 Adulte (43.0 EUR/282.06 FRF) </option>
	  </select></td>
  </tr>
  <tr><td colspan="9"><img src="b.gif" width="1" height="2"></td></tr>
     	<tr><td align="left">&nbsp;</td>
    	<td><img src="b.gif" width="5" height="1"></td>
    	<td><img src="b.gif" width="8" height="21"></td>
    	<td><img src="b.gif" width="5" height="1"></td>
<td nowrap align="left" class="default">
     <INPUT type="checkbox" name="is_freq_traveler_1" >     <img src="b.gif" width="7" height="1">
      Grand Voyageur</td>
     <td><img src="b.gif" width="9" height="1"></td>
       <td colspan="3" class="default" nowrap>n° 29090106     <INPUT type="text" name="no_freq_traveler_1" value="" size="9" maxlength="9" class="select1"></td></tr> <tr><td colspan="9"><img src="b.gif" width="1" height="8"></td></tr>  	
      </table>
  	 <input type="hidden" name="defaultFareValue" value=""></td> 
  </tr> 

</table>


<script language="javascript">
function ChangeFareAlert(n)
{
	
}

function GetDefaultValue()
{
	document.saisie.defaultFareValue.value=document.saisie.otherTariff_0.selectedIndex ;
}
GetDefaultValue();
</script>  

  <!------------Separateur----------------->  
  <table border="0" cellspacing="0" cellpadding="0">
    <!--<tr>
      <td colspan="3"><img src="b.gif" width="1" height="13"></td>
    </tr>
    <tr> 
      <td colspan="2"></td>
      <td class="default"> Si vous souhaitez <A HREF="#"><SPAN class="rougesouligne">un 
        tarif particulier</SPAN></A></TD>
    </tr> -->
    <tr>
      <td colspan="3"><img src="b.gif" width="1" height="5"></td>
    </tr>
    <tr> 
      <td><img src="b.gif" width="13" height="1"></td>
      <td><img src="b.gif" width="66" height="1"></td>
      <td bgcolor="#8885DA"><img src="b.gif" width="604" height="1"></td>
    </tr>
    <tr>
      <td colspan="3"><img src="b.gif" width="1" height="5"></td>
    </tr>
  </table>
  	
   <!-- COMFORT -->
   
  	


   
	

  <!-----------Classe------------------>  
 <table border="0" cellspacing="0" cellpadding="0">
    <tr> 
      <td><img src="b.gif" width="13" height="1"></td>
      <td class="default" valign="top" align="left"><img src="b.gif" width="66" height="4"><br>
        <b>Confort</b></td>
      <td> 
        <table border="0" cellspacing="0" cellpadding="0">
        
        

	
	
	<tr> 
	<td class="default" nowrap><b>Classe </b></td>
	<td><img src="b.gif" width="11" height="1"></td>
	
	<td> 
	<input name="service_COMMVOY1_CLASS"
	type="radio" value="SEATSECOND"
	
	 checked >
		
	</td>
	<td><img src="b.gif" width="3" height="1"></td>
	<td nowrap class="default">
	2nde classe
	
	 </td>
	<td nowrap class="default">&nbsp;</td>		

	

	
	
	<td> 
	<input name="service_COMMVOY1_CLASS"
	type="radio" value="SEATFIRST"
	
	>
		
	</td>
	<td><img src="b.gif" width="3" height="1"></td>
	<td nowrap class="default">
	1ère classe
	
	 </td>
	<td nowrap class="default">&nbsp;</td>		

	
	</tr>
	<tr>
	<td><img src="b.gif" width="1" height="3"></td>
	</tr>
		
	

	
	
	<tr> 
	<td class="default" nowrap><b>Fumeur </b></td>
	<td><img src="b.gif" width="11" height="1"></td>
	
	<td> 
	<input name="service_COMMVOY1_SMOKER"
	type="radio" value="SMOKING"
	
	>
		
	</td>
	<td><img src="b.gif" width="3" height="1"></td>
	<td nowrap class="default">
	Oui
	
	 </td>
	<td nowrap class="default">&nbsp;</td>		

	

	
	
	<td> 
	<input name="service_COMMVOY1_SMOKER"
	type="radio" value="NOSMOKING"
	
	 checked >
		
	</td>
	<td><img src="b.gif" width="3" height="1"></td>
	<td nowrap class="default">
	Non
	
	 </td>
	<td nowrap class="default">&nbsp;</td>		

	
	<td> 
	<input name="service_COMMVOY1_SMOKER" type="radio" value="SMOKINGINDIFFERENT">
		
	</td>
	<td><img src="b.gif" width="3" height="1"></td>
	<td nowrap class="default">
	Indifférent
	
	 </td>
	<td nowrap class="default">&nbsp;</td>		

	
	</tr>
	<tr>
	<td><img src="b.gif" width="1" height="3"></td>
	</tr>
		
	

	
	
	<tr> 
	<td class="default" nowrap><b>Réservation (1) </b></td>
	<td><img src="b.gif" width="11" height="1"></td>
	
	<td> 
	<input name="service_COMMVOY1_BOOKING"
	type="radio" value="RESERVATION"
	
	>
		
	</td>
	<td><img src="b.gif" width="3" height="1"></td>
	<td nowrap class="default">
	Oui
	
	 </td>
	<td nowrap class="default">&nbsp;</td>		

	

	
	
	<td> 
	<input name="service_COMMVOY1_BOOKING"
	type="radio" value=""
	
	 checked >
		
	</td>
	<td><img src="b.gif" width="3" height="1"></td>
	<td nowrap class="default">
	Non
	
	 </td>
	<td nowrap class="default">&nbsp;</td>		

	
	</tr>
	<tr>
	<td><img src="b.gif" width="1" height="3"></td>
	</tr>
		
	
         
         
        </table>
      </td>
      <td valign="top" align="left">
      
		<table width="100%" border="0">
		<tr>
		<td valign="middle" nowrap><a href="javascript:send('cmdAddServices');"><img src="frptplus.gif" width="17" height="17" border="0" alt="Ajoutez des services (couloir-fenêtre, restauration, etc.)"><img src="b.gif" width="5" height="17" border="0"></a></td>
		<td valign="middle" nowrap><a href="javascript:send('cmdAddServices');" class="noir2">Ajoutez des services (couloir-fenêtre, restauration, etc.)</a></td>
		</tr>
		<tr>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		</tr>
		</table>
	     
    </tr>
    <tr>
      <td colspan="3"><img src="b.gif" width="1" height="5"></td>
    </tr>
  </table>  
   


  <!------------Separateur----------------->  
  <table border="0" cellspacing="0" cellpadding="0">
    <tr> 
      <td><img src="b.gif" width="13" height="1"></td>
      <td><img src="b.gif" width="61" height="1"></td>
      <td bgcolor="#8885DA"><img src="b.gif" width="604" height="1"></td>
    </tr>
    <tr>
      <td colspan="3"><img src="b.gif" width="1" height="5"></td>
    </tr>
  </table>
  
   <!-- DISTRIBUTED COUNTRY -->
  
  
    
    
 

<table border="0" cellspacing="0" cellpadding="0" width="604">
<tr>
<td><img src="b.gif" width="13" height="1"></td>
<td nowrap class="default"><img src="b.gif" width="187" height="1"><br><b>
	Pays de réception  ou de retrait des billets
</b></td>
<td><img src="b.gif" width="16" height="1"></td>

<td width="98">
<SELECT name="country" >


<option value='CN' >CHINE</option>
<option value='CY' >CHYPRE</option>
<option value='CE' >COMORES</option>
<option value='CG' >CONGO LA REP. DEMOCRATIQUE DU</option>
<option value='EH' >ETHIOPIE</option>
<option value='FJ' >FIDJI</option>
<option value='FI' >FINLANDE</option>
<option value='FR'  selected >FRANCE</option>
<option value='MT' >MONTENEGRO</option>
<option value='MZ' >MOZAMBIQUE</option>
<option value='MY' >MYANMAR</option>
<option value='NA' >NAMIBIE</option>
<option value='NU' >NAURU</option>
<option value='NP' >NEPAL</option>
<option value='NG' >NIGER</option>
<option value='NI' >NIGERIA</option>
<option value='NO' >NORVEGE</option>
<option value='OM' >OMAN</option>
<option value='OU' >OUGANDA</option>
<option value='OZ' >OUZBEKISTAN</option>
<option value='PA' >PANAMA</option>
<option value='PG' >PAPAOUSIE NOUVELLE GUINEE</option>
<option value='PR' >PARAGUAY</option>
<option value='NL' >PAYS BAS</option>
<option value='PI' >PITCAIRN</option>
<option value='PL' >POLOGNE</option>
<option value='PT' >PORTUGAL</option>
<option value='QA' >QATAR</option>
<option value='LA' >REP. DEMOCRATIQUE POPULAIRE LAO</option>
<option value='SY' >REPUBLIQUE ARABE SYRIENNE</option>
<option value='LV' >REPUBLIQUE DE LETTONIE</option>
<option value='LT' >REPUBLIQUE DE LITUANIE</option>
<option value='RM' >REPUBLIQUE DE MOLDOVA</option>
<option value='BY' >REPUBLIQUE DU BELARUS</option>
<option value='SK' >REPUBLIQUE SLOVAQUE</option>
<option value='CS' >REPUBLIQUE TCHEQUE</option>
<option value='RO' >ROUMANIE</option>
<option value='RY' >ROYAUME UNI</option>
<option value='RU' >RUSSIE</option>
<option value='RW' >RWANDA</option>
<option value='KN' >SAINT KITTS ET NEVIS</option>
<option value='SM' >SAINT MARIN</option>
<option value='HE' >SAINTE HELENE</option>
<option value='LE' >SAINTE LUCIE</option>
<option value='SA' >SAMOA</option>
<option value='ST' >SAO TOME ET PRINCIPE</option>
<option value='SG' >SENEGAL</option>
<option value='SC' >SEYCHELLES</option>
<option value='SR' >SIERRA LEONE</option>
<option value='SI' >SLOVENIE</option>
<option value='SO' >SOMALIE</option>
<option value='SD' >SOUDAN</option>
<option value='SL' >SRI LANKA</option>
<option value='SE' >SUEDE</option>
<option value='CH' >SUISSE</option>
<option value='SN' >SURINAM</option>
<option value='SZ' >SWAZILAND</option>
<option value='TJ' >TADJIKISTAN</option>
<option value='TA' >TANZANIE</option>
<option value='TD' >TCHAD</option>
<option value='TO' >TOGO</option>
<option value='TG' >TONGA</option>
<option value='TT' >TRINITE ET TOBAGO</option>
<option value='TC' >TRISTAN DA CUNHA</option>
<option value='TN' >TUNISIE</option>
<option value='TK' >TURKMENISTAN</option>
<option value='TR' >TURQUIE</option>
<option value='TU' >TUVALU</option>
<option value='UA' >UKRAINE</option>
<option value='UR' >URUGUAY</option>
<option value='VU' >VANUATU</option>
<option value='VA' >VATICAN</option>
<option value='VI' >VIET-NAM</option>
<option value='YE' >YEMEN</option>
<option value='YU' >YOUGOSLAVIE</option>
<option value='ZA' >ZAMBIE</option>
<option value='ZI' >ZIMBABWE</option>

</SELECT>
</td>
<td><img src="b.gif" width="20" height="1"></td>
<td align=left><a href="javascript:sendAfterValidation('cmdValidation');">
	<img src="frcarval.gif" width="109" height="23" border="0" alt="Valider"></a>
	</td>
</tr>
<tr><td colspan="6"><img src="b.gif" width="1" height="7"></td></tr>
<tr>
<td colspan="3"></td>
<td colspan="3">

</td>
</tr>
</table>  
<script language="JavaScript">
function SortCountries()
{
tempCountryValue="";
tempCountriesArray = new Array();
j=0;
for (i=0; i<document.saisie.country.length;i++)
{
if(document.saisie.country.options[i].selected){
	tempCountryValue=document.saisie.country.options[i].value;
}
tempCountriesArray[i]=document.saisie.country.options[i].text+"|"+document.saisie.country.options[i].value;
tempCountriesArray.sort();
j++;
}
document.saisie.country.length=0;
document.saisie.country.length=j;
tab=new Array();
for (i=0; i<tempCountriesArray.length;i++)
{
tab=tempCountriesArray[i].split("|");
document.saisie.country.options[i].text=tab[0];
document.saisie.country.options[i].value=tab[1];
if (document.saisie.country.options[i].value == tempCountryValue){
	document.saisie.country.options[i].selected=true;}
}
}
//SortCountries();
</script>
  

</FORM></td></tr></table>

<?PHP
$db->close();



llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
