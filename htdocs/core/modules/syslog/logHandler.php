<?php

require_once DOL_DOCUMENT_ROOT.'/core/modules/syslog/logHandlerInterface.php';

/**
 * Parent class for log handlers
 */
class LogHandler
{
	protected $ident=0;


	/**
	 * Content of the info tooltip.
	 *
	 * @return false|string
	 */
	public function getInfo()
	{
		return false;
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
	 * Is the module active ?
	 *
	 * @return boolean
	 */
	public function isActive()
	{
		return false;
	}

	/**
	 * Configuration variables of the module
	 *
	 * @return array
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
	 * @return array
	 */
	public function checkConfiguration()
	{
		return array();
	}

	/**
	 * Set current ident.
	 *
     * @param	int		$ident		1=Increase ident of 1, -1=Decrease ident of 1
	 * @return 	void
	 */
	public function setIdent($ident)
	{
		$this->ident+=$ident;
	}
}