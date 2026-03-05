<?php


if (!function_exists('dolBuildUrl')) {
	/**
	 * Return path of url.
	 *
	 * @param	string							$url				Relative path to file
	 * @param	array<string,int|float|string>	$params     		params for the http query
	 * @param	bool							$addtoken			does we need to add token
	 * @return string												path
	 */
	function dolBuildUrl($url, $params = [], $addtoken = false)
	{
		global $db, $hookmanager;

		if (!is_object($hookmanager)) {
			include_once DOL_DOCUMENT_ROOT . '/core/class/hookmanager.class.php';
			$hookmanager = new HookManager($db);
		}
		if ((!isset($params['mainmenu']) || empty($params['mainmenu'])) && GETPOSTISSET('mainmenu')) {
			$params = array_merge($params, ['mainmenu' => (GETPOST('mainmenu', 'restricthtml'))]);
		}
		if ((!isset($params['leftmenu'])/*  || empty($params['leftmenu']) */) && GETPOSTISSET('leftmenu')) { // do not fill leftmenu if we have leftmenu=
			$params = array_merge($params, ['leftmenu' => (GETPOST('leftmenu', 'restricthtml'))]);
		}
		$parameters = [
			'path' => &$url,
			'params' => &$params,
			'addtoken' => &$addtoken,
		];
		$hookmanager->executeHooks('buildurl', $parameters);
		if ($addtoken) {
			$params = array_merge($params, ['token' => newToken()]);
		}
		// TODO TO REMOVE
		if (getDolGlobalString('MAIN_DEBUG_DOL_BUILDURL')) {
			$params = array_merge($params, ['debug' => 'debug']);
		}
		if ($params) {
			$url .= '?' . http_build_query($params);
		}

		return $url;
	}

}


if (!function_exists('getDolDefaultContextPage')) {
	/**
	 * Return the default context page string
	 *
	 * @param	string		$s					Page path
	 * @return 	string							Value returned
	 */
	function getDolDefaultContextPage($s)
	{
		return str_replace('_', '', basename(dirname($s)).basename($s, '.php'));
	}
}
