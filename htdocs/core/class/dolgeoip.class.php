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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *  \file		htdocs/core/class/dolgeoip.class.php
 * 	\ingroup	geoip
 *  \brief		Library for managing module geoip
 */


/**
 * 		\class      DolGeoIP
 *      \brief      Classe to manage GeoIP
 *      \remarks    Usage:
 *		\remarks	$geoip=new GeoIP('country',$datfile);
 *		\remarks	$geoip->getCountryCodeFromIP($ip);
 *		\remarks	$geoip->close();
 */
class DolGeoIP
{
	var $gi;

	/**
	 * Constructor
	 *
	 * @param 	string	$type		'country' or 'city'
	 * @param	string	$datfile	Data file
	 * @return 	GeoIP
	 */
	function DolGeoIP($type,$datfile)
	{
		if ($type == 'country')
		{
		    // geoip may have been already included with PEAR
		    if (! function_exists('geoip_country_code_by_name')) $res=include_once(GEOIP_PATH."geoip.inc");
		}
		else if ($type == 'city')
		{
		    // geoip may have been already included with PEAR
		    if (! function_exists('geoip_country_code_by_name')) $res=include_once(GEOIP_PATH."geoipcity.inc");
		}
		else { print 'ErrorBadParameterInConstructor'; return 0; }

		if (empty($type) || empty($datfile))
		{
			//dol_syslog("DolGeoIP::DolGeoIP parameter datafile not defined", LOG_ERR);
			$this->errorlabel='DolGeoIP constructor was called with no datafile parameter';
			//dol_print_error('','DolGeoIP constructor was called with no datafile parameter');
			print $this->errorlabel;
			return 0;
		}
		if (! file_exists($datfile))
		{
			//dol_syslog("DolGeoIP::DolGeoIP datafile ".$datfile." can not be read", LOG_ERR);
			$this->error='ErrorGeoIPClassNotInitialized';
			$this->errorlabel="Datafile ".$datfile." not found";
			print $this->errorlabel;
			return 0;
		}

		$this->gi = geoip_open($datfile,GEOIP_STANDARD);
	}

	/**
	 * Return in lower case the country code from an ip
	 *
	 * @param	$ip		IP to scan
	 * @return	string	Country code (two letters)
	 */
	function getCountryCodeFromIP($ip)
	{
		if (empty($this->gi))
		{
			return '';
		}
		return strtolower(geoip_country_code_by_addr($this->gi, $ip));
	}

	/**
	 * Return in lower case the country code from a host name
	 *
	 * @param	$name	FQN of host (example: myserver.xyz.com)
	 * @return	string	Country code (two letters)
	 */
	function getCountryCodeFromName($name)
	{
		if (empty($this->gi))
		{
			return '';
		}
		return geoip_country_code_by_name($this->gi, $name);
	}

	/**
	 * Return verion of data file
	 *
	 * @return	string		Version of datafile
	 */
	function getVersion()
	{
		return '';
	}

	/**
	 * Close geoip object
	 *
	 * @return	void
	 */
	function close()
	{
	    if (function_exists('geoip_close'))    // With some geoip with PEAR, geoip_close function may not exists
	    {
	        geoip_close($this->gi);
	    }
	}
}
?>
