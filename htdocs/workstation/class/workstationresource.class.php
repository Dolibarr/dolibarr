<?php

class WorkstationResource extends CommonObject
{
	/** @var string $table_element Table name in SQL */
	public $table_element = 'workstation_workstation_resource';

	/** @var string $element Name of the element (tip for better integration in Dolibarr: this value should be the reflection of the class name with ucfirst() function) */
	public $element = 'workstationresource';

	public $fields = array(
		'fk_workstation' => array ('type' => 'integer'),
		'fk_resource' => array ('type' => 'integer')
	);

	/**
	 * WorkstationResource constructor.
	 * @param DoliDB    $db    Database connector
	 */
	public function __construct($db)
	{
		$this->db = $db;

		// Unset fields that are disabled
		foreach ($this->fields as $key => $val)
		{
			if (isset($val['enabled']) && empty($val['enabled']))
			{
				unset($this->fields[$key]);
			}
		}

		// Translate some data of arrayofkeyval
		if (is_object($langs))
		{
			foreach ($this->fields as $key => $val)
			{
				if (!empty($val['arrayofkeyval']) && is_array($val['arrayofkeyval']))
				{
					foreach ($val['arrayofkeyval'] as $key2 => $val2)
					{
						$this->fields[$key]['arrayofkeyval'][$key2] = $langs->trans($val2);
					}
				}
			}
		}
	}

	static public function getAllResourcesOfWorkstation($fk_workstation) {

		global $db;

		$obj = new self($db);
		$sql = 'SELECT fk_resource FROM '.MAIN_DB_PREFIX.$obj->table_element.' WHERE fk_workstation = '.$fk_workstation;
		$resql = $db->query($sql);

		$TRes = array();
		if(!empty($resql)) {
			while($res = $db->fetch_object($resql)) {
				$TRes[] = $res->fk_resource;
			}
		}

		return $TRes;

	}

	static public function deleteAllResourcesOfWorkstation($fk_workstation) {

		global $db;

		$obj = new self($db);
		$sql = 'DELETE FROM '.MAIN_DB_PREFIX.$obj->table_element.' WHERE fk_workstation = '.$fk_workstation;
		$resql = $db->query($sql);

		if(empty($resql)) return 0;

		return 1;

	}

}
