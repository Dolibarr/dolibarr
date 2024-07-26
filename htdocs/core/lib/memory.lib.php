<?php
/* Copyright (C) 2009-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2021       Frédéric France     <frederic.france@netlogic.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 * or see https://www.gnu.org/
 */

/**
 *  \file		htdocs/core/lib/memory.lib.php
 *  \brief		Set of function for memory/cache management
 */

global $shmkeys, $shmoffset;

$shmkeys = array(
	'main' => 1,
	'admin' => 2,
	'dict' => 3,
	'companies' => 4,
	'suppliers' => 5,
	'products' => 6,
	'commercial' => 7,
	'compta' => 8,
	'projects' => 9,
	'cashdesk' => 10,
	'agenda' => 11,
	'bills' => 12,
	'propal' => 13,
	'boxes' => 14,
	'banks' => 15,
	'other' => 16,
	'errors' => 17,
	'members' => 18,
	'ecm' => 19,
	'orders' => 20,
	'users' => 21,
	'help' => 22,
	'stocks' => 23,
	'interventions' => 24,
	'donations' => 25,
	'contracts' => 26,
);
$shmoffset = 1000; // Max number of entries found into a language file. If too low, some entries will be overwritten.



/**
 * 	Save data into a memory area shared by all users, all sessions on server. Note: MAIN_CACHE_COUNT must be set.
 *
 *  @param	string      $memoryid		Memory id of shared area
 * 	@param	mixed		$data			Data to save. It must not be a null value.
 *  @param 	int			$expire			ttl in seconds, 0 never expire
 * 	@return	int							Return integer <0 if KO, 0 if nothing is done, Nb of bytes written if OK
 *  @see dol_getcache()
 */
function dol_setcache($memoryid, $data, $expire = 0)
{
	global $conf;

	$result = 0;

	if (strpos($memoryid, 'count_') === 0) {	// The memoryid key start with 'count_...'
		if (!getDolGlobalString('MAIN_CACHE_COUNT')) {
			return 0;
		}
	}

	if (isModEnabled('memcached') && class_exists('Memcached')) {
		// Using a memcached server
		global $dolmemcache;
		if (empty($dolmemcache) || !is_object($dolmemcache)) {
			$dolmemcache = new Memcached();
			$tmparray = explode(':', getDolGlobalString('MEMCACHED_SERVER'));
			$port = (empty($tmparray[1]) ? 0 : $tmparray[1]);
			$result = $dolmemcache->addServer($tmparray[0], ($port || strpos($tmparray[0], '/') !== false) ? $port : 11211);
			if (!$result) {
				return -1;
			}
		}

		$memoryid = session_name().'_'.$memoryid;
		//$dolmemcache->setOption(Memcached::OPT_COMPRESSION, false);
		$dolmemcache->add($memoryid, $data, $expire); // This fails if key already exists
		$rescode = $dolmemcache->getResultCode();
		if ($rescode == 0) {
			return is_array($data) ? count($data) : (is_scalar($data) ? strlen($data) : 0);
		} else {
			return -$rescode;
		}
	} elseif (isModEnabled('memcached') && class_exists('Memcache')) {	// This is a really not reliable cache ! Use Memcached instead.
		// Using a memcache server
		global $dolmemcache;
		if (empty($dolmemcache) || !is_object($dolmemcache)) {
			$dolmemcache = new Memcache();
			$tmparray = explode(':', getDolGlobalString('MEMCACHED_SERVER'));
			$port = (empty($tmparray[1]) ? 0 : $tmparray[1]);
			$result = $dolmemcache->addServer($tmparray[0], ($port || strpos($tmparray[0], '/') !== false) ? $port : 11211);
			if (!$result) {
				return -1;
			}
		}

		$memoryid = session_name().'_'.$memoryid;
		//$dolmemcache->setOption(Memcached::OPT_COMPRESSION, false);
		$result = $dolmemcache->add($memoryid, $data, 0, $expire); // This fails if key already exists
		if ($result) {
			return is_array($data) ? count($data) : (is_scalar($data) ? strlen($data) : 0);
		} else {
			return -1;
		}
	} elseif (getDolGlobalInt('MAIN_OPTIMIZE_SPEED') & 0x02) {	// This is a really not reliable cache ! Use Memcached instead.
		// Using shmop
		$result = dol_setshmop($memoryid, $data, $expire);
	} else {
		// No intersession cache system available, we use at least the perpage cache
		$conf->cache['cachememory_'.$memoryid] = $data;
		$result = is_array($data) ? count($data) : (is_scalar($data) ? strlen($data) : 0);
	}

	return $result;
}

/**
 * 	Read a memory area shared by all users, all sessions on server
 *
 *  @param	string	$memoryid		Memory id of shared area
 * 	@return	int|mixed				Return integer <0 if KO, data if OK, null if not found into cache or no caching feature enabled
 *  @see dol_setcache()
 */
function dol_getcache($memoryid)
{
	global $conf;

	if (strpos($memoryid, 'count_') === 0) {	// The memoryid key start with 'count_...'
		if (!getDolGlobalString('MAIN_CACHE_COUNT')) {
			return null;
		}
	}

	// Using a memcached server
	if (isModEnabled('memcached') && class_exists('Memcached')) {
		global $m;
		if (empty($m) || !is_object($m)) {
			$m = new Memcached();
			$tmparray = explode(':', getDolGlobalString('MEMCACHED_SERVER'));
			$port = (empty($tmparray[1]) ? 0 : $tmparray[1]);
			$result = $m->addServer($tmparray[0], ($port || strpos($tmparray[0], '/') !== false) ? $port : 11211);
			if (!$result) {
				return -1;
			}
		}

		$memoryid = session_name().'_'.$memoryid;
		//$m->setOption(Memcached::OPT_COMPRESSION, false);
		//print "Get memoryid=".$memoryid;
		$data = $m->get($memoryid);
		$rescode = $m->getResultCode();
		//print "memoryid=".$memoryid." - rescode=".$rescode." - count(response)=".count($data)."\n<br>";
		//var_dump($data);
		if ($rescode == 0) {
			return $data;
		} elseif ($rescode == 16) {		// = Memcached::MEMCACHED_NOTFOUND but this constant doe snot exists.
			return null;
		} else {
			return -$rescode;
		}
	} elseif (isModEnabled('memcached') && class_exists('Memcache')) {	// This is a really not reliable cache ! Use Memcached instead.
		global $m;
		if (empty($m) || !is_object($m)) {
			$m = new Memcache();
			$tmparray = explode(':', getDolGlobalString('MEMCACHED_SERVER'));
			$port = (empty($tmparray[1]) ? 0 : $tmparray[1]);
			$result = $m->addServer($tmparray[0], ($port || strpos($tmparray[0], '/') !== false) ? $port : 11211);
			if (!$result) {
				return -1;
			}
		}

		$memoryid = session_name().'_'.$memoryid;
		//$m->setOption(Memcached::OPT_COMPRESSION, false);
		$data = $m->get($memoryid);
		//print "memoryid=".$memoryid." - rescode=".$rescode." - data=".count($data)."\n<br>";
		//var_dump($data);
		if ($data) {
			return $data;
		} else {
			return null; // There is no way to make a difference between NOTFOUND and error when using Memcache. So do not use it, use Memcached instead.
		}
	} elseif (getDolGlobalInt('MAIN_OPTIMIZE_SPEED') & 0x02) {	// This is a really not reliable cache ! Use Memcached instead.
		// Using shmop
		$data = dol_getshmop($memoryid);
		return $data;
	} else {
		// No intersession cache system available, we use at least the perpage cache
		if (isset($conf->cache['cachememory_'.$memoryid])) {
			return $conf->cache['cachememory_'.$memoryid];
		}
	}

	return null;
}



/**
 * 	Return shared memory address used to store dataset with key memoryid
 *
 *  @param	string	$memoryid		Memory id of shared area ('main', 'agenda', ...)
 * 	@return	int						Return integer <0 if KO, Memoy address of shared memory for key
 */
function dol_getshmopaddress($memoryid)
{
	global $shmkeys, $shmoffset;
	if (empty($shmkeys[$memoryid])) {	// No room reserved for this memoryid, no way to use cache
		return 0;
	}
	return $shmkeys[$memoryid] + $shmoffset;
}

/**
 * 	Return list of contents of all memory area shared
 *
 * 	@return	array
 */
function dol_listshmop()
{
	global $shmkeys, $shmoffset;

	$resarray = array();
	foreach ($shmkeys as $key => $val) {
		$result = dol_getshmop($key);
		if (!is_numeric($result) || $result > 0) {
			$resarray[$key] = $result;
		}
	}
	return $resarray;
}

/**
 * 	Save data into a memory area shared by all users, all sessions on server
 *
 *  @param	int		$memoryid		Memory id of shared area ('main', 'agenda', ...)
 * 	@param	string	$data			Data to save. Must be a not null value.
 *  @param 	int		$expire			ttl in seconds, 0 never expire
 * 	@return	int						Return integer <0 if KO, 0=Caching not available, Nb of bytes written if OK
 */
function dol_setshmop($memoryid, $data, $expire)
{
	global $shmkeys, $shmoffset;

	//print 'dol_setshmop memoryid='.$memoryid."<br>\n";
	if (empty($shmkeys[$memoryid]) || !function_exists("shmop_write")) {
		return 0;
	}
	$shmkey = dol_getshmopaddress($memoryid);
	if (empty($shmkey)) {
		return 0; // No key reserved for this memoryid, we can't cache this memoryid
	}

	$newdata = serialize($data);
	$size = strlen($newdata);
	//print 'dol_setshmop memoryid='.$memoryid." shmkey=".$shmkey." newdata=".$size."bytes<br>\n";
	$handle = shmop_open($shmkey, 'c', 0644, 6 + $size);
	if ($handle) {
		$shm_bytes_written1 = shmop_write($handle, str_pad((string) $size, 6), 0);
		$shm_bytes_written2 = shmop_write($handle, $newdata, 6);
		if ($shm_bytes_written1 + $shm_bytes_written2 != 6 + dol_strlen($newdata)) {
			print "Couldn't write the entire length of data\n";
		}
		// @phan-suppress-next-line PhanDeprecatedFunctionInternal
		shmop_close($handle);
		return ($shm_bytes_written1 + $shm_bytes_written2);
	} else {
		print 'Error in shmop_open for memoryid='.$memoryid.' shmkey='.$shmkey.' 6+size=6+'.$size;
		return -1;
	}
}

/**
 * 	Read a memory area shared by all users, all sessions on server
 *
 *  @param	string	$memoryid		Memory id of shared area ('main', 'agenda', ...)
 * 	@return	int|null				Return integer <0 if KO, data if OK, null if no cache enabled or not found
 */
function dol_getshmop($memoryid)
{
	global $shmkeys, $shmoffset;

	$data = null;

	if (empty($shmkeys[$memoryid]) || !function_exists("shmop_open")) {
		return null;
	}
	$shmkey = dol_getshmopaddress($memoryid);
	if (empty($shmkey)) {
		return null; // No key reserved for this memoryid, we can't cache this memoryid
	}

	//print 'dol_getshmop memoryid='.$memoryid." shmkey=".$shmkey."<br>\n";
	$handle = @shmop_open($shmkey, 'a', 0, 0);
	if ($handle) {
		$size = (int) trim(shmop_read($handle, 0, 6));
		if ($size) {
			$data = unserialize(shmop_read($handle, 6, $size));
		} else {
			return -1;
		}
		// @phan-suppress-next-line PhanDeprecatedFunctionInternal
		shmop_close($handle);
	} else {
		return null; // Can't open existing block, so we suppose it was not created, so nothing were cached yet for the memoryid
	}
	return $data;
}
