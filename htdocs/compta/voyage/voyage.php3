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
<form name=saisie method=post action="http://www.voyages-sncf.com/dynamic/_SvHomePage?_DLG=SvHomePage&_CMD=CmdExpressBooking&_LANG=FR&_AGENCY=VSC&WB=HPMSN">
<input type=hidden name="departure_time_min" value=8>
<input type=hidden name="departure_time_max" value=12>
<input type=hidden name="return_time_min" value=-1>
<input type=hidden name="return_time_max" value=-1>
<input type=hidden name="departure_choice" value=D>

<input type=hidden name="return_choice" value=D>
<input type=hidden name="country" value=FR>
<input type=hidden name=age_bracket_0 value=D>

<table width=100% cellpadding=0 cellspacing=0 border=0 align=center><tr><td>


<br></td></tr>
<tr height=5><td bgcolor=#B4B7B3 align=center><img src=/b.gif height=5 width=1></td></tr>
<tr><td><img src=/img/spaceit.gif HEIGHT=4 BORDER=0><br>
<table width=100% border=0>
<tr><td><font class=fname>Au départ de :<br></font><input type=text value="Paris" name="OD_departure" class=text1 maxlength=100></td><td width=10>&nbsp;</td>
<td><font class=fname>A destination de :<br></font><input type=text value="Auray" name="OD_arrival" class=text1 maxlength=100></td>
<td width=10>&nbsp;</td>

<td align=right class=netSize>
<font class=fname>Adultes :<br></font>

<input type=hidden name="nbRowsPassengers" value=1>
<select name=nbpass_0 id=nbpass_0>
<option value=1>&nbsp;1<option value=2>&nbsp;2<option value=3>&nbsp;3
<option value=4>&nbsp;4<option value=5>&nbsp;5<option value=6>&nbsp;6</select>
</td><td width=10>&nbsp;</td></tr></table>
<table width=100% border=0><tr>
<td valign=top class=netSize><font class=fname>Départ :<br></font>

<select name="departure_day">
<option value="1">01
<option value="2">02
<option value="3">03
<option value="4">04<option value="5">05<option value="6">06<option value="7">07<option value="8">08<option value="9">09<option value="10">10<option value="11">11<option value="12" selected>12<option value="13">13<option value="14">14<option value="15">15<option value="16">16<option value="17">17<option value="18">18<option value="19">19<option value="20">20<option value="21">21<option value="22">22<option value="23">23<option value="24">24<option value="25">25<option value="26">26<option value="27">27<option value="28">28<option value="29">29<option value="30">30<option value="31">31

</select><img src=/b.gif width=3 height=1>
<select name="departure_month">
<option value="4" selected >Mai</option>
<option value="5">Juin</option>
<option value="6">Juillet</option>
</select><br></td>
<td valign=top class=netSize colspan=2><font class=fname>&nbsp;<br></font>
<select name=dtime>
<option value=0-4>00h-04h
<option value=1-5>01h-05h
<option value=2-6>02h-06h
<option value=3-7>03h-07h<option value=4-8>04h-08h<option value=5-9>05h-09h
<option value=6-10>06h-10h<option value=7-11>07h-11h<option value=8-12 selected>08h-12h
<option value=9-13>09h-13h<option value=10-14>10h-14h<option value=11-15>11h-15h
<option value=12-16>12h-16h<option value=13-17>13h-17h<option value=14-18>14h-18h
<option value=15-19>15h-19h<option value=16-20>16h-20h<option value=17-21>17h-21h
<option value=18-22>18h-22h<option value=19-23>19h-23h<option value=20-0>20h-00h

<option value=21-1>21h-01h<option value=22-2>22h-02h<option value=23-3>23h-03h</select></td>
<td colspan=2 valing=bottom><font class=fname>
<input type=radio name=service_COMMVOY1_CLASS value="SEATFIRST">&nbsp;1?re classe<br>
<input type=radio name=service_COMMVOY1_CLASS value="SEATSECOND" checked>&nbsp;2e classe</font></td>
<td colspan=2 valing=bottom><font class=fname><input type=radio name=service_COMMVOY1_SMOKER value=SMOKING>&nbsp;Fumeur<br>
<input type=radio name=service_COMMVOY1_SMOKER value=NOSMOKING checked>&nbsp;Non fumeur</font></td></tr>
<tr><td valign=top class=netSize><font class=fname>Retour :<br></font>
<select name="return_day" style=font-size:12px;font-family:Arial onChange=validerRetour()><option>
<option value=1>01<option value=2>02<option value=3>03<option value=4>04<option value=5>05
<option value=6>06<option value=7>07<option value=8>08<option value=9>09<option value=10>10
<option value=11>11<option value=12>12<option value=13>13<option value=14>14<option value=15>15
<option value=16>16<option value=17>17<option value=18>18<option value=19>19<option value=20>20
<option value=21>21<option value=22>22<option value=23>23<option value=24>24<option value=25>25

<option value=26>26<option value=27>27<option value=28>28<option value=29>29<option value=30>30
<option value=31>31</select><img src=/b.gif width=3 height=1><select name="return_month" onChange=validerRetour()> <option value="-1" selected></option>
<option value="4">Mai</option>
<option value="5">Juin</option>
<option value="6">Juillet</option>

</select><br></td>
<td valign=top class=netSize colspan=2><font class=fname>&nbsp;<br></font><select name=rtime><option VALUE="" selected>&nbsp;<option value=0-4>00h-04h<option value=1-5>01h-05h
<option value=2-6>02h-06h<option value=3-7>03h-07h<option value=4-8>04h-08h<option value=5-9>05h-09h<option value=6-10>06h-10h<option value=7-11>07h-11h<option value=8-12>08h-12h
<option value=9-13>09h-13h<option value=10-14>10h-14h<option value=11-15>11h-15h<option value=12-16>12h-16h<option value=13-17>13h-17h<option value=14-18>14h-18h
<option value=15-19>15h-19h<option value=16-20>16h-20h<option value=17-21>17h-21h<option value=18-22>18h-22h<option value=19-23>19h-23h<option value=20-0>20h-00h

<option value=21-1>21h-01h<option value=22-2>22h-02h<option value=23-3>23h-03h
</select></td>
<td colspan="2" valing="bottom">

<input type="radio" name="hor_ou_resa" value="HORAIRE" checked >&nbsp;Consultation horaires<br>
<input type="radio" name="hor_ou_resa" value="RESA" >&nbsp;Réservation

</td>
<tr><td colspan=4 align="right" valign="bottom" class=netSize>

<input type="Submit" name=Search value="Valider" class=greenGoBtn ID="Submit2">
</td>
</tr>
</table>
</td></tr></table><img src=/img/spaceit.gif height=5><br>
</form>


<?PHP
$db->close();



llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
