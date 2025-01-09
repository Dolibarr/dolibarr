<?php
/* Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 */
/*
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
 * Parent class for log handlers
 */
abstract class LogHandler
{
	/**
	 * @var string Code for the handler
	 */
	public $code;

	/**
	 * @var int
	 */
	protected $ident = 0;

	/**
	 * @var string[] Array of error messages
	 */
	public $errors = [];


	/**
	 * Return name of logger
	 *
	 * @return string
	 */
	public function getName()
	{
		return ucfirst($this->code);
	}

	/**
	 * Content of the info tooltip.
	 *
	 * @return string
	 */
	public function getInfo()
	{
		return '';
	}

	/**
	 * Return warning if something is wrong with logger
	 *
	 * @return string
	 */
	public function getWarning()
	{
		return '';
	}

	/**
	 * Version of the module ('x.y.z' or 'dolibarr' or 'experimental' or 'development')
	 *
	 * @return string
	 */
	public function getVersion()
	{
		return 'development';
	}

	/**
	 * Is the logger active ?
	 *
	 * @return int<0,1>		1 if logger enabled
	 */
	public function isActive()
	{
		return 0;
	}

	/**
	 *	Configuration variables of the module
	 *
	 * 	@return	array<array{name:string,constant:string,default:string,css?:string}>	Return array of configuration data
	 */
	public function configure()
	{
		return array();
	}

	/**
	 * Function that checks if the configuration is valid.
	 * It will be called after setting the configuration.
	 * The function returns an array with error messages
	 *
	 * @return bool
	 */
	public function checkConfiguration()
	{
		return true;
	}

	/**
	 * Set current ident.
	 *
	 * @param	int<-1,1>	$ident		1=Increase ident of 1, -1=Decrease ident of 1
	 * @return 	void
	 */
	public function setIdent($ident)
	{
		$this->ident += $ident;
	}

	/**
	 * Export the message
	 *
	 * @param	array{level:int,ip:string,ospid:string,osuser:string,message:string}	$content 	Array containing the info about the message
	 * @param   string  $suffixinfilename   When output is a file, append this suffix into default log filename.
	 * @return  void
	 */
	public function export($content, $suffixinfilename = '')
	{
		// Code to output log
		return;
	}
}
