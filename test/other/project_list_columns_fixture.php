<?php
/* Copyright (C) 2026 Pierre Ardoin <developpeur@lesmetiersdubatiment.fr>
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

/**
 * Isolated project list regression fixture, invoked by test_project_list_columns.py.
 * Native Project methods run with fixture users, database and hooks; no instance is loaded.
 */
$input = json_decode(file_get_contents('php://stdin'), true);
define('DOL_DOCUMENT_ROOT', $input['root'].'/htdocs');
define('DOL_URL_ROOT', '/dolibarr');
define('MAIN_DB_PREFIX', 'fixture_');
define('DOL_DATA_ROOT', sys_get_temp_dir());
define('DOL_VERSION', 'develop');
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/html.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/conf.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/translate.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
$conf = new Conf();
$conf->entity = $input['entity'] ?? 1;
$conf->modules['project'] = !empty($input['enabled']) ? 'modProjet' : '';
$conf->global->MAIN_ENABLE_AJAX_TOOLTIP = $input['ajax'] ?? 1;
$conf->global->MAIN_LOGTOFILE = 0;
$conf->global->MAIN_LOGTOHTML = 0;
$conf->theme = 'eldy';
$conf->dol_no_mouse_hover = 0;
$dolibarr_main_document_root = DOL_DOCUMENT_ROOT;
$langs = new Translate(DOL_DOCUMENT_ROOT.'/langs', $conf);
$langs->setDefaultLang('en_US');
$langs->loadLangs(array('main', 'projects'));
$user = new class($input) {
	/** @var int Fixture user ID. */
	public $id = 7;
	/** @var int Fixture third-party restriction. */
	public $socid;
	/** @var array<string,mixed> Fixture permissions. */
	private $permissions;
	/**
	 * Initialize the fixture.
	 *
	 * @param array<string,mixed> $data Fixture configuration.
	 * @return void
	 */
	public function __construct($data)
	{
		$this->socid = $data['socid'] ?? 0;
		$this->permissions = $data;
	}
	/**
	 * Return the configured project permission.
	 *
	 * @param string $module Module name.
	 * @param string ...$args Native permission path.
	 * @return bool
	 */
	public function hasRight($module, ...$args)
	{
		return $args == array('all', 'lire') ? !empty($this->permissions['all']) : !empty($this->permissions['read']);
	}
};
$hookmanager = new class {
	/** @var string Native hook output. */
	public $resPrint = '';
	/**
	 * Ignore hook initialization in the isolated fixture.
	 *
	 * @param list<string> $contexts Hook contexts.
	 * @return void
	 */
	public function initHooks($contexts)
	{}
	/**
	 * Return a neutral hook result.
	 *
	 * @param mixed ...$args Unused native hook arguments.
	 * @return int
	 */
	public function executeHooks(...$args)
	{
		return 0;
	}
};
$mc = new class($input) {
	/** @var string Simulated shared entity IDs. */
	private $entities;
	/**
	 * Initialize the fixture.
	 *
	 * @param array<string,mixed> $data Fixture configuration.
	 * @return void
	 */
	public function __construct($data)
	{
		$this->entities = $data['entities'] ?? '1';
	}
	/**
	 * Return the simulated entity sharing scope.
	 *
	 * @param mixed ...$args Unused native entity arguments.
	 * @return string
	 */
	public function getEntity(...$args)
	{
		return $this->entities;
	}
};
$db = new class($input) {
	/** @var list<string> Recorded queries. */
	public $queries = array();
	/** @var list<array<string,mixed>> Fixture project rows. */
	private $rows;
	/** @var string Native SQL dialect used by the fixture. */
	public $type = 'mysqli';
	/**
	 * Initialize the fixture.
	 *
	 * @param array<string,mixed> $data Fixture configuration.
	 * @return void
	 */
	public function __construct($data)
	{
		$this->rows = $data['projects'] ?? array();
	}
	/**
	 * Record the native query and return fixture rows.
	 *
	 * @param string $sql Native SQL query.
	 * @return object{rows:list<array<string,mixed>>,index:int}
	 */
	public function query($sql)
	{
		$this->queries[] = $sql;
		$rows = strpos($sql, 'c_type_contact') !== false && strpos($sql, 'SELECT ctc') === 0 ? array(array('rowid'=>1,'code'=>'PROJECTLEADER')) : $this->rows;
		return (object) array('rows'=>$rows, 'index'=>0);
	}
	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps -- Native database interface names.
	/**
	 * Read the next fixture row as an object.
	 *
	 * @param object{rows:list<array<string,mixed>>,index:int} $result Fixture cursor.
	 * @return stdClass|false
	 */
	public function fetch_object($result)
	{
		return isset($result->rows[$result->index]) ? (object) $result->rows[$result->index++] : false;
	}
	/**
	 * Read the next fixture row as a numeric array.
	 *
	 * @param object{rows:list<array<string,mixed>>,index:int} $result Fixture cursor.
	 * @return list<mixed>|false
	 */
	public function fetch_row($result)
	{
		return isset($result->rows[$result->index]) ? array_values($result->rows[$result->index++]) : false;
	}
	/**
	 * Count fixture rows.
	 *
	 * @param object{rows:list<array<string,mixed>>,index:int} $result Fixture cursor.
	 * @return int
	 */
	public function num_rows($result)
	{
		return count($result->rows);
	}
	// phpcs:enable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Release a fixture cursor with no external resource.
	 *
	 * @param object{rows:list<array<string,mixed>>,index:int} $result Fixture cursor.
	 * @return void
	 */
	public function free($result)
	{}
	/**
	 * Quote values for the SQL fixture.
	 *
	 * @param string $value SQL value.
	 * @return string
	 */
	public function escape($value)
	{
		return str_replace("'", "''", $value);
	}
	/**
	 * Restrict fixture identifiers and numeric ID lists.
	 *
	 * @param string $value Identifier or ID list.
	 * @return string
	 */
	public function sanitize($value)
	{
		return preg_replace('/[^0-9_,.\-a-z]/i', '', $value);
	}
};
$_SERVER['PHP_SELF'] = '/fourn/commande/list.php';
$_SESSION = array();
$source = file_get_contents($input['root'].'/htdocs/'.$input['file']);
$canreadprojects = isModEnabled('project') && $user->hasRight('projet', 'lire');
$object = (object) array('fields'=>array('fk_project'=>array('position'=>52), 'fk_projet'=>array('position'=>90)));
$arrayfields = $input['selected'] ?? array();
$metastart = strpos($source, '// Project columns use the same access scope');
if ($metastart === false) { $metastart = strpos($source, '$canreadprojects ='); }
$metaend = strpos($source, '$search_projectlist_ref = GETPOST', $metastart);
eval(substr($source, $metastart, $metaend-$metastart));
$metadata = $arrayfields;
$start = strpos($source, '$projectforlist = new Project($db);');
preg_match('/^(\h*)if \(\$canreadprojects\) \{/m', substr($source, $start), $opening, PREG_OFFSET_CAPTURE);
$ifstart = $start + $opening[0][1];
preg_match('/^'.preg_quote($opening[1][0], '/').'\}/m', substr($source, $ifstart), $closing, PREG_OFFSET_CAPTURE);
$end = $ifstart + $closing[0][1] + strlen($closing[0][0]);
$scope = substr($source, $start, $end-$start);
$projectsforlist = '0';
eval($scope);
$output = array('queries'=>$db->queries, 'allowed'=>$projectsforlist, 'metadata'=>$metadata);
if (($input['stage'] ?? '') === 'scope') { echo json_encode($output); exit; }
$refkey = $input['refkey']; $titlekey=$input['titlekey'];
$arrayfields = array($refkey=>array('label'=>'RefProject', 'checked'=> $input['refchecked'] ?? 1), $titlekey=>array('label'=>'ProjectLabel', 'checked'=>$input['titlechecked'] ?? 1));
$obj = (object) $input['row']; $objp = $obj;
$i=0; $totalarray=array('nbfield'=>0);
/**
 * Extract an executable native column fragment.
 *
 * @param string $source Native list PHP source.
 * @param string $key Native selected-field key.
 * @param string $stage Rendering stage: filter, head or value.
 * @return string
 */
function fixtureColumnBlock($source, $key, $stage)
{
	$offset=0;
	while (($start=strpos($source, "if (!empty(\$arrayfields['".$key."']['checked'])) {", $offset)) !== false) {
		$tokens=token_get_all('<?php '.substr($source, $start)); $depth=0; $length=0; $opened=false;
		foreach ($tokens as $token) {
			if (is_array($token)) { if ($token[0] !== T_OPEN_TAG) { $length+=strlen($token[1]); } } else {
				$length+=strlen($token);
				if ($token==='{') { $depth++;$opened=true;
				}
				if ($token==='}' && --$depth===0 && $opened) { break;
				}
			}
		}
		$block=substr($source, $start, $length); $offset=$start+$length;
		$kind=strpos($block, 'print_liste_field_titre')!==false ? 'head' : (strpos($block, 'liste_titre')!==false ? 'filter' : 'value');
		if ($kind===$stage) { return $block;
		}
	}
	throw new RuntimeException('Missing '.$stage.' '.$key);
}
$output['html']=array();
foreach (array('ref'=>$refkey,'title'=>$titlekey) as $name=>$key) {
	ob_start();eval(fixtureColumnBlock($source, $key, 'value'));$output['html'][$name]=ob_get_clean();
}
$output['cellcount']=$totalarray['nbfield'];
$sql='SELECT '.$input['relation_alias'].'.rowid as object_id'; $sqlSelect=$sql;
preg_match('/\$(?:sql|sqlSelect) \.= [^\n]*as projectlist_id[^\n]*;/', $source, $select);
eval($select[0]);if ($input['file']==='commande/list_det.php') { $sql=$sqlSelect; }
$sql.=' FROM fixture_objects as '.$input['relation_alias'];
preg_match('/\$sql \.= [^\n]*(?:projet as|projet AS)[^\n]*IN \([^\n]*;/', $source, $join);
if (!$join) {
	preg_match('/\$sql \.= [^\n]* ON p.rowid = d.fk_projet[^\n]*;/', $source, $on);
	$sql.=' LEFT JOIN fixture_projet as p';eval($on[0]);
} else { eval($join[0]); }
$sql.=' WHERE 1=1';
$search_projectlist_ref=$input['search_ref'] ?? '';
$search_projectlist_title=$input['search_title'] ?? '';
foreach (array('ref','title') as $name) {
	preg_match('/if \(\$search_projectlist_'.$name." !== ''\\) \\{\\s*\\\$sql \\.= natural_search\\([^\\n]+\\);\\s*\\}/", $source, $filter);
	eval($filter[0]);
}
$output['sql']=$sql;
echo json_encode($output);
