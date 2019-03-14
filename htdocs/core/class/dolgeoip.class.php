<?php
/* Copyright (C) 2009-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *  \file		htdocs/core/class/dolgeoip.class.php
 * 	\ingroup	geoip
 *  \brief		File of class to manage module geoip
 */


/**
 * 		\class      DolGeoIP
 *      \brief      Classe to manage GeoIP
 *      			Usage:
 *					$geoip=new GeoIP('country',$datfile);
 *					$geoip->getCountryCodeFromIP($ip);
 *					$geoip->close();
 */
class DolGeoIP
{
    public $gi;

	/**
	 * Constructor
	 *
	 * @param 	string	$type		'country' or 'city'
	 * @param	string	$datfile	Data file
	 */
	public function __construct($type, $datfile)
	{
		if ($type == 'country')
		{
		    // geoip may have been already included with PEAR
		    if (! function_exists('geoip_country_code_by_name')) $res=include_once GEOIP_PATH.'geoip.inc';
		}
		elseif ($type == 'city')
		{
		    // geoip may have been already included with PEAR
		    if (! function_exists('geoip_country_code_by_name')) $res=include_once GEOIP_PATH.'geoipcity.inc';
		}
		else { print 'ErrorBadParameterInConstructor'; return 0; }

		// Here, function exists (embedded into PHP or exists because we made include)
		if (empty($type) || empty($datfile))
		{
			$this->errorlabel='Constructor was called with no datafile parameter';
			dol_syslog('DolGeoIP '.$this->errorlabel, LOG_ERR);
			return 0;
		}
		if (! file_exists($datfile) || ! is_readable($datfile))
		{
			$this->error='ErrorGeoIPClassNotInitialized';
			$this->errorlabel="Datafile ".$datfile." not found";
			dol_syslog('DolGeoIP '.$this->errorlabel, LOG_ERR);
			return 0;
		}

		if (function_exists('geoip_open'))
		{
			$this->gi = geoip_open($datfile, GEOIP_STANDARD);
		}
		else
		{
		    $this->gi = 'NOGI';    // We are using embedded php geoip functions
		    //print 'function_exists(geoip_country_code_by_name))='.function_exists('geoip_country_code_by_name');
		    //print geoip_database_info();
		}
	}

	/**
	 * Return in lower case the country code from an ip
	 *
	 * @param	string	$ip		IP to scan
	 * @return	string			Country code (two letters)
	 */
	public function getCountryCodeFromIP($ip)
	{
		if (empty($this->gi))
		{
			return '';
		}
		if ($this->gi == 'NOGI')
		{
		    // geoip_country_code_by_addr does not exists
    		return strtolower(geoip_country_code_by_name($ip));
		}
		else
		{
		    if (! function_exists('geoip_country_code_by_addr')) return strtolower(geoip_country_code_by_name($this->gi, $ip));
		    return strtolower(geoip_country_code_by_addr($this->gi, $ip));
		}
	}

	/**
	 * Return in lower case the country code from a host name
	 *
	 * @param	string	$name	FQN of host (example: myserver.xyz.com)
	 * @return	string			Country code (two letters)
	 */
	public function getCountryCodeFromName($name)
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
     * @return  string      Version of datafile
     */
    public function getVersion()
    {
        if ($this->gi == 'NOGI') return geoip_database_info();
        return 'Not available (not using PHP internal geo functions)';
    }

    /**
     * Close geoip object
     *
     * @return	void
     */
    public function close()
    {
        if (function_exists('geoip_close')) {
            // With some geoip with PEAR, geoip_close function may not exists
            geoip_close($this->gi);
        }
    }
}
