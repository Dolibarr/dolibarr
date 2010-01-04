<?php
/* Copyright (C) 2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *  \file		htdocs/lib/memory.lib.php
 *  \brief		Set of function for memory management
 *  \version	$Id$
 */

global $shmkeys,$shmoffset;

$shmkeys=array('main'=>1,'admin'=>2,'dict'=>3,'companies'=>4,'suppliers'=>5,'products'=>6,
				'commercial'=>7,'compta'=>8,'projects'=>9,'cashdesk'=>10,'agenda'=>11,'bills'=>12,
				'propal'=>13,'boxes'=>14,'banks'=>15,'other'=>16,'errors'=>17,'members'=>18,'ecm'=>19,
				'orders'=>20,'users'=>21,'help'=>22,'stocks'=>23,'interventions'=>24,
				'donations'=>25,'contracts'=>26);
$shmoffset=100;



/**	\brief      Return list of contents of all memory area shared
 * 	\return		int				0=Nothing is done, <0 if KO, >0 if OK
 */
function dol_listshmop()
{
	global $shmkeys,$shmoffset;

	$resarray=array();
	foreach($shmkeys as $key => $val)
	{
		$result=dol_getshmop($key);
		if (! is_numeric($result) || $result > 0) $resarray[$key]=$result;
	}
	return $resarray;
}

/**	\brief      Read a memory area shared by all users, all sessions on server
 *  \param      $memoryid		Memory id of shared area
 * 	\return		int				0=Nothing is done, <0 if KO, >0 if OK
 */
function dol_getshmop($memoryid)
{
	global $shmkeys,$shmoffset;

	if (empty($shmkeys[$memoryid]) || ! function_exists("shmop_open")) return 0;
	$shmkey=($shmkeys[$memoryid]+$shmoffset);
	//print 'dol_getshmop memoryid='.$memoryid." shmkey=".$shmkey."<br>\n";
	$handle=@shmop_open($shmkey,'a',0,0);
	if ($handle)
	{
		$size=trim(shmop_read($handle,0,6));
		if ($size) $data=unserialize(shmop_read($handle,6,$size));
		else return -1;
		shmop_close($handle);
	}
	else
	{
		return -2;
	}
	return $data;
}

/**	\brief      Save data into a memory area shared by all users, all sessions on server
 *  \param      $memoryid		Memory id of shared area
 * 	\param		$data			Data to save
 * 	\return		int				<0 if KO, Nb of bytes written if OK
 */
function dol_setshmop($memoryid,$data)
{
	global $shmkeys,$shmoffset;

	//print 'dol_setshmop memoryid='.$memoryid."<br>\n";
	if (empty($shmkeys[$memoryid]) || ! function_exists("shmop_write")) return 0;
	$shmkey=$shmkeys[$memoryid]+$shmoffset;
	$newdata=serialize($data);
	$size=strlen($newdata);
	//print 'dol_setshmop memoryid='.$memoryid." shmkey=".$shmkey." newdata=".$size."bytes<br>\n";
	$handle=shmop_open($shmkey,'c',0644,6+$size);
	if ($handle)
	{
		$shm_bytes_written1=shmop_write($handle,str_pad($size,6),0);
		$shm_bytes_written2=shmop_write($handle,$newdata,6);
		if (($shm_bytes_written1 + $shm_bytes_written2) != (6+strlen($newdata)))
		{
   			print "Couldn't write the entire length of data\n";
		}
		shmop_close($handle);
		return ($shm_bytes_written1+$shm_bytes_written2);
	}
	else
	{
		print 'Error in shmop_open';
		return -1;
	}
}

?>
