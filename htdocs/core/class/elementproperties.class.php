<?php


/**
 * class to manage element properties
 */
class ElementProperties
{

	/**
	 * @var DoliDb		Database handler (result of a new DoliDB)
	 */
	public $db;

	/**
	 * @var int The object identifier
	 */
	public $id;

	/**
	 * @var string 		Error string
	 * @see             $errors
	 */
	public $error;

	/**
	 * @var string[]	Array of error strings
	 */
	public $errors = array();

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'element_type';
}
