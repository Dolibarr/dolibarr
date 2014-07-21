<?php

/**
 * LogHandlerInterface
 */
interface LogHandlerInterface
{
	/**
	 * 	Return name of logger
	 *
	 * 	@return	string		Name of logger
	 */
	public function getName();


	/**
	 * 	Return version of logger
	 *
	 * 	@return	string		Version of logger
	 */
	public function getVersion();

	/**
	 * 	Return information on logger
	 *
	 * 	@return	string		Version of logger
	 */
	public function getInfo();

	/**
	 * 	Return array of configuration data
	 *
	 * 	@return	array		Return array of configuration data
	 */
	public function configure();

	/**
	 * 	Return if configuration is valid
	 *
	 * 	@return	boolean		True if configuration ok
	 */
	public function checkConfiguration();

	/**
	 * 	Return if logger active
	 *
	 * 	@return	boolen		True if active
	 */
	public function isActive();

	/**
	 * 	Output log content
	 *
	 *	@param	string	$content	Content to log
	 * 	@return	void
	 */
	public function export($content);
}