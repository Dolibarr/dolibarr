<?php

use \DebugBar\DataCollector\RequestDataCollector;

/**
 * DolRequestDataCollector class
 */

class DolHooksCollector extends RequestDataCollector
{
	/**
	 * Collects the data from the collectors
	 *
	 * @return array
	 */
	public function collect()
	{
        /**
         * @global $hookmanager HookManager
         */
        global $hookmanager;

        $data = ['hooks' => []];
        if (empty($hookmanager->hooksHistory)) {
            return $data;
        }
		$i = 0;
		foreach ($hookmanager->hooksHistory as $key => $hookHistory) {
            $i++;
			$hookHistory['contexts'] = implode(', ', $hookHistory['contexts']);
            $data['hooks']["[$i] {$hookHistory['name']}"] = $hookHistory;

//            $data["[$key] {$hookHistory['name']}"] = "{$hookHistory['file']} (L{$hookHistory['line']}). Contexts: "
//                . implode(', ', $hookHistory['contexts']);
		}
        $data['nb_of_hooks'] = count($data['hooks']);

		return $data;
	}

	/**
	 *	Return widget settings
	 *
	 *  @return string[][]
     */
	public function getWidgets()
	{
		global $langs;

		$langs->load("other");

		return [
			$langs->transnoentities('Hooks') => [
				"icon" => "tags",
				"widget" => "PhpDebugBar.Widgets.HookListWidget",
				"map" => "hooks.hooks",
				"default" => "{}"
            ],
            "{$langs->transnoentities('Hooks')}:badge" => [
                "map" => "hooks.nb_of_hooks",
                "default" => 0
            ]
        ];
	}

    /**
     * @return string
     */
    public function getName()
    {
        return 'hooks';
    }
}
