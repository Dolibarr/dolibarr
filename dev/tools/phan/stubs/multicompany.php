<?php
/* Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 */
class ActionsMulticompany
{
	/**
	 * @param DoliDB $db
	 */
	public function __construct($db)
	{
	}
	/** @ver string */
	public $id;
	/** @ver string */
	public $label;
	/** @var array{stock:string[],referent:string} */
	public $sharings;
	/** @ver DoliDB */
	public $db;
}
