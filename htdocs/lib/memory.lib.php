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


/**	\brief      Read a memory area shared by all users, all sessions on server
 *  \param      $memoryid		Memory id of shared area
 * 	\return		int				0=Nothing is done, <0 if KO, >0 if OK
 */
function dol_getshmop($memoryid,$size)
{
	$shmkey = ftok($memoryid, 'D');
	print 'dol_getshmop memoryid='.$memoryid." shmkey=".$shmkey."<br>\n";
	if (! function_exists("shmop_open")) return 0;
	$handle=@shmop_open($shmkey,'a',0,0);
	if ($handle)
	{
		$data=unserialize(shmop_read($handle,0,$size));
		shmop_close($handle);
	}
	return $data;
}

/**	\brief      Save data into a memory area shared by all users, all sessions on server
 *  \param      $memoryid		Memory id of shared area
 * 	\param		$data			Data to save
 * 	\return		int				<0 if KO, Nb of bytes written if OK
 */
function dol_setshmop($memoryid,$data,$size=0)
{
	$shmkey = ftok($memoryid, 'D');
	$newdata=serialize($data);
	if (! $size) $size=strlen($newdata);
	print 'dol_setshmop memoryid='.$memoryid." shmkey=".$shmkey." newdata=".strlen($newdata)."bytes size=".$size."<br>\n";
	if (! function_exists("shmop_write")) return 0;
	$handle=shmop_open($shmkey,'c',0644,$size);
	if ($handle)
	{
		$shm_bytes_written=shmop_write($handle,$newdata,0);
		if ($shm_bytes_written != strlen($newdata)) 
		{
   			print "Couldn't write the entire length of data\n";
		}
		shmop_close($handle);
		return $shm_bytes_written;
	}
	else
	{
		print 'Error in shmop_open';
		return -1;
	}
}


/**
 * Declare function ftok
 */
if( !function_exists('ftok') )
{
   function ftok($filename = "", $proj = "")
   {
       $filename = $filename . $proj;
       for ($key = array(); sizeof($key) < strlen($filename); $key[] = ord(substr($filename, sizeof($key), 1)));
       return dechex(array_sum($key));
   }
}

?>
