<?php

class UnitsTools
{

	/**
	 * Get unit from code
	 * @param string $code code of unit
	 * @param string $mode 0= id , short_label=Use short label as value, code=use code
	 * @return int            <0 if KO, Id of code if OK
	 */
	static public function getUnitFromCode($code, $mode = 'code')
	{
		global $db;

		if($mode == 'short_label'){
			return dol_getIdFromCode($db, $code, 'c_units', 'short_label', 'rowid');
		}
		elseif($mode == 'code'){
			return dol_getIdFromCode($db, $code, 'c_units', 'code', 'rowid');
		}

		return $code;
	}

	/**
	 * Unit converter
	 * @param double $value value to convert
	 * @param int $fk_unit current unit id of value
	 * @param int $fk_new_unit the id of unit to convert in
	 * @return double
	 */
	static public function unitConverter($value, $fk_unit, $fk_new_unit = 0)
	{
		global $db;

		$value  = doubleval(price2num($value));
		$fk_unit = intval($fk_unit);

		// Calcul en unité de base
		$scaleUnitPow = self::scaleOfUnitPow($fk_unit);

		// convert to standard unit
		$value  = $value * $scaleUnitPow;
		if($fk_new_unit !=0 ){
			// Calcul en unité de base
			$scaleUnitPow = self::scaleOfUnitPow($fk_new_unit);
			if(!empty($scaleUnitPow))
			{
				// convert to new unit
				$value  = $value / $scaleUnitPow;
			}
		}
		return round($value, 2);
	}



	/**
	 * get scale of unit factor
	 * @param $id int id of unit in dictionary
	 * @return float|int
	 */
	static public function scaleOfUnitPow($id)
	{
		$base = 10;
		// TODO : add base col into unit dictionary table
		$unit = self::dbGetRow('SELECT scale, unit_type from '.MAIN_DB_PREFIX.'c_units WHERE rowid = '.intval($id));
		if($unit){
			// TODO : if base exist in unit dictionary table remove this convertion exception and update convertion infos in database exemple time hour currently scale 3600 will become scale 2 base 60
			if($unit->unit_type == 'time'){
				return doubleval($unit->scale);
			}

			return pow($base, doubleval($unit->scale));
		}

		return 0;
	}

	/**
	 * return first result from query
	 * @param string $sql the sql query string
	 * @return bool| var
	 */
	static public function dbGetvalue($sql)
	{
		global $db;
		$sql .= ' LIMIT 1;';

		$res = $db->query($sql);
		if ($res)
		{
			$Tresult = $db->fetch_row($res);
			return $Tresult[0];
		}

		return false;
	}

	/**
	 * return first result from query
	 * @param string $sql the sql query string
	 * @return bool| var
	 */
	static public function dbGetRow($sql)
	{
		global $db;
		$sql .= ' LIMIT 1;';

		$res = $db->query($sql);
		if ($res)
		{
			return $db->fetch_object($res);
		}

		return false;
	}
}
