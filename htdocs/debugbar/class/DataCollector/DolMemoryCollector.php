<?php

use \DebugBar\DataCollector\MemoryCollector;

/**
 * DolMemoryCollector class
 */

class DolMemoryCollector extends MemoryCollector
{
	/**
	 *	Return value of indicator
	 *
	 *  @return void
	 */
	public function collect()
	{
		global $conf, $langs;

		$this->updatePeakUsage();
		return array(
			'peak_usage' => $this->peakUsage,
			//'peak_usage_str' => $this->getDataFormatter()->formatBytes($this->peakUsage, 2)
			'peak_usage_str' => (empty($conf->dol_optimize_smallscreen) ? dol_print_size($this->peakUsage, 0) : dol_print_size($this->peakUsage, 1))
		);
	}

	/**
	 *	Return widget settings
	 *
	 *  @return void
	 */
	public function getWidgets()
	{
		global $langs;

		$langs->load("other");

		return array(
			"memory" => array(
				"icon" => "cogs",
				"tooltip" => $langs->transnoentities('MemoryUsage'),
				"map" => "memory.peak_usage_str",
				"default" => "'0B'"
			)
		);
	}
}
