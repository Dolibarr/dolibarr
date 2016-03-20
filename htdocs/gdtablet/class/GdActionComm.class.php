<?php

require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';

class GdActionComm extends ActionComm
{
	public $color;

	public function fetchCategoryColor()
	{
		require_once __DIR__.'/../lib/other.lib.php';

		$this->fetch_thirdparty();
		return GdtabletMisc::getCategoryColor($this->db, $this->thirdparty->id);
	}
}