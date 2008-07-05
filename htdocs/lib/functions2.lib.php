<?php
/* Copyright (C) 2008 Laurent Destailleur         <eldy@users.sourceforge.net>
 * Copyright (C) 2008 Raphael Bertrand (Resultic) <raphael.bertrand@resultic.fr>
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
 * or see http://www.gnu.org/
 */

/**
 \file			htdocs/lib/functions.lib.php
 \brief			Ensemble de fonctions de base de dolibarr sous forme d'include
 \version		$Id$
 */


/**
 * Return next value for a mask
 *
 * @param unknown_type $db
 * @param 	$mask
 * @param unknown_type $table
 * @param unknown_type $field
 * @param unknown_type $where
 * @param unknown_type $valueforccc
 * @param unknown_type $date
 * @return 	string		New value
 */
function get_next_value($db,$mask,$table,$field,$where='',$valueforccc='',$date='')
{
	// Clean parameters
	if ($date == '') $date=time();
	
	// Extract value for mask counter, mask raz and mask offset
	if (! eregi('\{(0+)([@\+][0-9]+)?([@\+][0-9]+)?\}',$mask,$reg)) return 'ErrorBadMask';
	$masktri=$reg[1].$reg[2].$reg[3];
	$maskcounter=$reg[1];
	$maskraz=-1;
	$maskoffset=0;
	if (strlen($maskcounter) < 3) return 'CounterMustHaveMoreThan3Digits';

	// Extract value for third party mask counter
	if (eregi('\{(c+)(0*)\}',$mask,$regClientRef))
	{
		$maskrefclient=$regClientRef[1].$regClientRef[2];
		$maskrefclient_maskclientcode=$regClientRef[1];
		$maskrefclient_maskcounter=$regClientRef[2];
		$maskrefclient_maskoffset=0; //default value of maskrefclient_counter offset
		$maskrefclient_clientcode=substr($valueforccc,0,strlen($maskrefclient_maskclientcode));//get n first characters of client code to form maskrefclient_clientcode
		$maskrefclient_clientcode=str_pad($maskrefclient_clientcode,strlen($maskrefclient_maskclientcode),"#",STR_PAD_RIGHT);//padding maskrefclient_clientcode for having exactly n characters in maskrefclient_clientcode
		$maskrefclient_clientcode=sanitize_string($maskrefclient_clientcode);//sanitize maskrefclient_clientcode for sql insert and sql select like
		if (strlen($maskrefclient_maskcounter) > 0 && strlen($maskrefclient_maskcounter) < 3) return 'CounterMustHaveMoreThan3Digits';
	}
	else $maskrefclient='';

	$maskwithonlyymcode=$mask;
	$maskwithonlyymcode=eregi_replace('\{(0+)([@\+][0-9]+)?([@\+][0-9]+)?\}',$maskcounter,$maskwithonlyymcode);
	$maskwithonlyymcode=eregi_replace('\{dd\}','dd',$maskwithonlyymcode);
	$maskwithonlyymcode=eregi_replace('\{(c+)(0*)\}',$maskrefclient,$maskwithonlyymcode);
	$maskwithnocode=$maskwithonlyymcode;
	$maskwithnocode=eregi_replace('\{yyyy\}','yyyy',$maskwithnocode);
	$maskwithnocode=eregi_replace('\{yy\}','yy',$maskwithnocode);
	$maskwithnocode=eregi_replace('\{y\}','y',$maskwithnocode);
	$maskwithnocode=eregi_replace('\{mm\}','mm',$maskwithnocode);
	// Now maskwithnocode = 0000ddmmyyyyccc for example
	// and maskcounter    = 0000 for example
	//print "maskwithonlyymcode=".$maskwithonlyymcode." maskwithnocode=".$maskwithnocode."\n<br>";

	// If an offset is asked
	if (! empty($reg[2]) && eregi('^\+',$reg[2])) $maskoffset=eregi_replace('^\+','',$reg[2]);
	if (! empty($reg[3]) && eregi('^\+',$reg[3])) $maskoffset=eregi_replace('^\+','',$reg[3]);

	// Define $sqlwhere
	// If a restore to zero after a month is asked we check if there is already a value for this year.
	if (! empty($reg[2]) && eregi('^@',$reg[2]))  $maskraz=eregi_replace('^@','',$reg[2]);
	if (! empty($reg[3]) && eregi('^@',$reg[3]))  $maskraz=eregi_replace('^@','',$reg[3]);
	if ($maskraz >= 0)
	{
		if ($maskraz > 12) return 'ErrorBadMaskBadRazMonth';

		// Define reg
		if ($maskraz > 1 && ! eregi('^(.*)\{(y+)\}\{(m+)\}',$maskwithonlyymcode,$reg)) return 'ErrorCantUseRazInStartedYearIfNoYearMonthInMask';
		if ($maskraz <= 1 && ! eregi('^(.*)\{(y+)\}',$maskwithonlyymcode,$reg)) return 'ErrorCantUseRazIfNoYearInMask';
		//print "x".$maskwithonlyymcode." ".$maskraz;

		// Define $yearcomp and $monthcomp (that will be use in the select where to search max number)
		$monthcomp=$maskraz;
		$yearoffset=0;
		$yearcomp=0;
		if (date("m",$date) < $maskraz) { $yearoffset=-1; }	// If current month lower that month of return to zero, year is previous year
		if (strlen($reg[2]) == 4) $yearcomp=sprintf("%04d",date("Y",$date)+$yearoffset);
		if (strlen($reg[2]) == 2) $yearcomp=sprintf("%02d",date("y",$date)+$yearoffset);
		if (strlen($reg[2]) == 1) $yearcomp=substr(date("y",$date),2,1)+$yearoffset;
			
		$sqlwhere='';
		$sqlwhere.='SUBSTRING('.$field.', '.(strlen($reg[1])+1).', '.strlen($reg[2]).') >= '.$yearcomp;
		if ($monthcomp > 1)	// Test useless if monthcomp = 1 (or 0 is same as 1)
		{
			$sqlwhere.=' AND SUBSTRING('.$field.', '.(strlen($reg[1])+strlen($reg[2])+1).', '.strlen($reg[3]).') >= '.$monthcomp;
		}
	}
	//print "masktri=".$masktri." maskcounter=".$maskcounter." maskraz=".$maskraz." maskoffset=".$maskoffset."<br>\n";

	// Define $sqlstring
	$posnumstart=strpos($maskwithnocode,$maskcounter);	// Pos of counter in final string (from 0 to ...)
	if ($posnumstart < 0) return 'ErrorBadMaskFailedToLocatePosOfSequence';
	$sqlstring='SUBSTRING('.$field.', '.($posnumstart+1).', '.strlen($maskcounter).')';
	//print "x".$sqlstring;

	// Define $maskLike
	$maskLike = sanitize_string($mask);
	$maskLike = str_replace("%","_",$maskLike);
	// Replace protected special codes with matching number of _ as wild card caracter
	$maskLike = str_replace(sanitize_string('{yyyy}'),'____',$maskLike);
	$maskLike = str_replace(sanitize_string('{yy}'),'__',$maskLike);
	$maskLike = str_replace(sanitize_string('{y}'),'_',$maskLike);
	$maskLike = str_replace(sanitize_string('{mm}'),'__',$maskLike);
	$maskLike = str_replace(sanitize_string('{dd}'),'__',$maskLike);
	$maskLike = str_replace(sanitize_string('{'.$masktri.'}'),str_pad("",strlen($maskcounter),"_"),$maskLike);
	if ($maskrefclient) $maskLike = str_replace(sanitize_string('{'.$maskrefclient.'}'),str_pad("",strlen($maskrefclient),"_"),$maskLike);

	// Get counter in database
	$counter=0;
	$sql = "SELECT MAX(".$sqlstring.") as val";
	$sql.= " FROM ".MAIN_DB_PREFIX.$table;
	//		$sql.= " WHERE ".$field." not like '(%'";
	$sql.= " WHERE ".$field." like '".$maskLike."'";
	if ($where) $sql.=$where;
	if ($sqlwhere) $sql.=' AND '.$sqlwhere;

	dolibarr_syslog("functions2::get_next_value sql=".$sql, LOG_DEBUG);
	$resql=$db->query($sql);
	if ($resql)
	{
		$obj = $db->fetch_object($resql);
		$counter = $obj->val;
	}
	else dolibarr_print_error($db);
	if (empty($counter) || eregi('[^0-9]',$counter)) $counter=$maskoffset;
	$counter++;

	if ($maskrefclient_maskcounter)
	{
		//print "maskrefclient_maskcounter=".$maskrefclient_maskcounter." maskwithnocode=".$maskwithnocode." maskrefclient=".$maskrefclient."\n<br>";
			
		// Define $sqlstring
		$maskrefclient_posnumstart=strpos($maskwithnocode,$maskrefclient_maskcounter,strpos($maskwithnocode,$maskrefclient));	// Pos of counter in final string (from 0 to ...)
		if ($maskrefclient_posnumstart <= 0) return 'ErrorBadMask';
		$maskrefclient_sqlstring='SUBSTRING('.$field.', '.($maskrefclient_posnumstart+1).', '.strlen($maskrefclient_maskcounter).')';
		//print "x".$sqlstring;

		// Define $maskrefclient_maskLike
		$maskrefclient_maskLike = sanitize_string($mask);
		$maskrefclient_maskLike = str_replace("%","_",$maskrefclient_maskLike);
		// Replace protected special codes with matching number of _ as wild card caracter
		$maskrefclient_maskLike = str_replace(sanitize_string('{yyyy}'),'____',$maskrefclient_maskLike);
		$maskrefclient_maskLike = str_replace(sanitize_string('{yy}'),'__',$maskrefclient_maskLike);
		$maskrefclient_maskLike = str_replace(sanitize_string('{y}'),'_',$maskrefclient_maskLike);
		$maskrefclient_maskLike = str_replace(sanitize_string('{mm}'),'__',$maskrefclient_maskLike);
		$maskrefclient_maskLike = str_replace(sanitize_string('{dd}'),'__',$maskrefclient_maskLike);
		$maskrefclient_maskLike = str_replace(sanitize_string('{'.$masktri.'}'),str_pad("",strlen($maskcounter),"_"),$maskrefclient_maskLike);
		$maskrefclient_maskLike = str_replace(sanitize_string('{'.$maskrefclient.'}'),$maskrefclient_clientcode.str_pad("",strlen($maskrefclient_maskcounter),"_"),$maskrefclient_maskLike);

		// Get counter in database
		$maskrefclient_counter=0;
		$maskrefclient_sql = "SELECT MAX(".$maskrefclient_sqlstring.") as val";
		$maskrefclient_sql.= " FROM ".MAIN_DB_PREFIX.$table;
		//$sql.= " WHERE ".$field." not like '(%'";
		$maskrefclient_sql.= " WHERE ".$field." like '".$maskrefclient_maskLike."'";
		if ($sqlwhere) $maskrefclient_sql.=' AND '.$sqlwhere; //use the same sqlwhere as general mask
		$maskrefclient_sql.=' AND (SUBSTRING('.$field.', '.(strpos($maskwithnocode,$maskrefclient)+1).', '.strlen($maskrefclient_maskclientcode).")='".$maskrefclient_clientcode."')";
			
		dolibarr_syslog("functions2::get_next_value maskrefclient_sql=".$maskrefclient_sql, LOG_DEBUG);
		$maskrefclient_resql=$db->query($maskrefclient_sql);
		if ($maskrefclient_resql)
		{
			$maskrefclient_obj = $db->fetch_object($maskrefclient_resql);
			$maskrefclient_counter = $maskrefclient_obj->val;
		}
		else dolibarr_print_error($db);
		if (empty($maskrefclient_counter) || eregi('[^0-9]',$maskrefclient_counter)) $maskrefclient_counter=$maskrefclient_maskoffset;
		$maskrefclient_counter++;
	}

	// Build numFinal
	$numFinal = $mask;

	// We replace special codes except refclient
	$numFinal = str_replace('{yyyy}',date("Y",$date),$numFinal);
	$numFinal = str_replace('{yy}',date("y",$date),$numFinal);
	$numFinal = str_replace('{y}' ,substr(date("y",$date),2,1),$numFinal);
	$numFinal = str_replace('{mm}',date("m",$date),$numFinal);
	$numFinal = str_replace('{dd}',date("d",$date),$numFinal);
	if ($maskclientcode) $numFinal = str_replace(('{'.$maskclientcode.'}'),$clientcode,$numFinal);

	// Now we replace the counter
	$maskbefore='{'.$masktri.'}';
	$maskafter=str_pad($counter,strlen($maskcounter),"0",STR_PAD_LEFT);
	//print 'x'.$maskbefore.'-'.$maskafter.'y';
	$numFinal = str_replace($maskbefore,$maskafter,$numFinal);

	// Now we replace the refclient
	if ($maskrefclient)
	{
		//print "maskrefclient=".$maskrefclient." maskwithonlyymcode=".$maskwithonlyymcode." maskwithnocode=".$maskwithnocode."\n<br>";
		$maskrefclient_maskbefore='{'.$maskrefclient.'}';
		$maskrefclient_maskafter=$maskrefclient_clientcode.str_pad($maskrefclient_counter,strlen($maskrefclient_maskcounter),"0",STR_PAD_LEFT);
		$numFinal = str_replace($maskrefclient_maskbefore,$maskrefclient_maskafter,$numFinal);
	}

	dolibarr_syslog("functions2::get_next_value return ".$numFinal,LOG_DEBUG);
	return $numFinal;
}