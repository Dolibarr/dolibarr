<?php
/* Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 *
 * Note: in this context Entity == Company.
 */
class ActionsMulticompany
{
	/**
	 * @param DoliDB $db
	 */
	public function __construct($db)
	{
	}
	/** @var string */
	public $id;
	/** @var string */
	public $label;
	/** @var array{stock:string[],referent:string} */
	public $sharings;
	/** @var DoliDB */
	public $db;

	/**
	 * @param string $login
	 * @param bool $bool1
	 * @param bool $bool2
	 * @return array<int,string>
	 */
	public function getEntitiesList($login = '', $bool1 = false, $bool2 = false)
	{
	}

	/**
	 * @param string $entity
	 * @return void
	 */
	public function getInfo($entity)
	{
	}

	/**
	 * @param int $id
	 * @param string $key
	 * @param string $param_str
	 * @param bool $bool1
	 * @param bool $bool2
	 * @param bool $bool3
	 * @param bool $bool4
	 * @param bool $bool5
	 * @return string */
	public function select_entities($id, $key = '', $param_str = '', $bool1 = false, $bool2 = false, $bool3 = false, $bool4 = false, $bool5 = false)
	{
	}

	/**
	 * @param Conf $conf
	 * @return void
	 */
	public function setValues($conf)
	{
	}
	/**
	 * @param string $element
	 * @param int<0,1> $shared
	 * @param ?CommonObject $currentobject
	 * @return string
	 */
	public function getEntity($element, $shared = 1, $currentobject = 0)
	{
	}
	/**
	 * @param CommonObject $currentobject
	 * @return int
	 */
	public function setEntity($currentobject)
	{
	}
	/**
	 * @param int $entityid
	 * @return int<-1,0>
	 */
	public function switchEntity($entityid)
	{
	}
	/**
	 * @param int $id
	 * @param string $entitytotest
	 * @return int
	 */
	public function checkRight($id, $entitytotest)
	{
	}
}
