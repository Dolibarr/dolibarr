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
 *  \file		htdocs/lib/dolgeoip.class.php
 * 	\ingroup	geoip
 *  \brief		Library for managing module geoip
 *  \version	$Id$
 */


/**
 * 		\class      DolGeoIp
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
	 * @param 	$type		'country' or 'city'
	 * @param	$datfile	Data file
	 * @return GeoIP
	 */
	function DolGeoIP($type,$datfile)
	{
		if ($type == 'country') require_once(DOL_DOCUMENT_ROOT."/includes/geoip/geoip.inc");
		else if ($type == 'city') require_once(DOL_DOCUMENT_ROOT."/includes/geoip/geoipcity.inc");
		else { print 'ErrorBadParameterInConstructor'; return 0; }

		if (empty($type) || empty($datfile))
		{
			dol_syslog("DolGeoIP::DolGeoIP parameter datafile not defined", LOG_ERR);
			dol_print_error('DolGeoIP constructor was called with no datafile parameter');
			return 0;
		}
		if (! file_exists($datfile))
		{
			dol_syslog("DolGeoIP::DolGeoIP datafile ".$datfile." can not be read", LOG_ERR);
			$this->error='ErrorGeoIPClassNotInitialized';
			$this->errorlabel="Datafile ".$datfile." not found";
			print $this->errorlabel;
			return 0;
		}

		$this->gi = geoip_open($datfile,GEOIP_STANDARD);
	}

	/**
	 * Return in lower cas the country code
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

	function getCountryCodeFromName($ip)
	{
		if (empty($this->gi))
		{
			return '';
		}
		return geoip_country_code_by_name($this->gi, $ip);
	}

	function close()
	{
		geoip_close($this->gi);
	}

}
?>
