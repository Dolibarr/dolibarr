<?php
/* Copyright (C) 2026   Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2026	Nick Fragoulis
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY, without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 * or see https://www.gnu.org/
 */

/**
 * \file    htdocs/ai/class/mcp.class.php
 * \ingroup ai
 * \brief   File of class to handle MCP (Model Context Protocol).
 */

require_once DOL_DOCUMENT_ROOT . "/ai/class/mcptool.class.php";

/**
 * Class to handle MCP (Model Context Protocol).
 *
 * This class is responsible for discovering, loading, and executing tools that implement
 * the Model Context Protocol. It supports both native tools from the ai/tools directory
 * and external tools registered via hooks.
 *
 * Context-aware: pass McpHandler::CTX_ASSISTANT (default) or McpHandler::CTX_MCP_SERVER
 * to the constructor so that the correct allow-list constant is applied.
 */
class McpHandler
{
	const CTX_ASSISTANT = 'assistant';

	const CTX_MCP_SERVER = 'mcp_server';

	/** @var DoliDB Database handler */
	private $db;

	/** @var User User object */
	private $user;

	/** @var Conf Configuration object */
	private $conf;

	/**
	 * @var McpTool[] Array of loaded tool instances, keyed by their base filename or class name.
	 */
	private $loadedTools = [];

	/**
	 * @var McpTool[] Associative array mapping tool *names* (from schema) to their instances.
	 * This provides O(1) lookup for execution.
	 */
	private $toolsByName = [];


	/**
	 * The active tool context. Determines which allow-list constant is read.
	 * @var string
	 */
	private $toolcontext;

	/**
	 * Constructor.
	 *
	 * @param DoliDB    $db      Database handler object
	 * @param User      $user    User object
	 * @param Conf|null $conf    Configuration object. Falls back to global $conf when null.
	 * @param string    $toolcontext Pass McpHandler::CTX_ASSISTANT or McpHandler::CTX_MCP_SERVER.
	 *                               Defaults to CTX_ASSISTANT when empty.
	 */
	public function __construct($db, $user, $conf = null, $toolcontext = '')
	{
		$this->db = $db;
		$this->user = $user;

		if ($conf === null) {
			global $conf;
		}
		$this->conf = $conf;

		$this->toolcontext = (!empty($toolcontext)) ? $toolcontext : self::CTX_ASSISTANT;

		$this->loadTools();
	}

	/**
	 * Returns true if the given tool instance declares itself as a system tool.
	 *
	 * Detection is entirely delegated to the tool class via isSystem() — no tool
	 * names are hardcoded here. Any tool class that overrides isSystem() returning
	 * true is treated as a system tool automatically.
	 *
	 * @param McpTool $toolInstance The tool instance to evaluate.
	 * @return bool
	 */
	private function isSystemTool($toolInstance)
	{
		return (method_exists($toolInstance, 'isSystem') && $toolInstance->isSystem());
	}

	/**
	 * Returns the configured allow-list for the current context as an array of
	 * tool names.
	 *
	 * Logic:
	 *   constant not set / empty string → no restriction → returns array()
	 *   constant = 'NONE'               → all blocked    → returns array('__blocked__')
	 *   otherwise                       → returns the list of allowed names
	 *
	 * The sentinel '__blocked__' will never match any real tool name so
	 * in_array() checks against it always return false.
	 *
	 * @return string[]
	 */
	private function getAllowedToolsList()
	{
		if ($this->toolcontext === self::CTX_MCP_SERVER) {
			$constName = 'AI_MCP_SERVER_ALLOWED_TOOLS';
		} else {
			$constName = 'AI_ASSISTANT_ALLOWED_TOOLS';
		}

		$raw = getDolGlobalString($constName);

		if ($raw === '') {
			// Constant not yet configured — allow everything
			return array();
		}

		if ($raw === 'NONE') {
			// Admin explicitly disabled all tools via the preset button
			return array('__blocked__');
		}

		return array_values(array_filter(array_map('trim', explode(',', $raw))));
	}

	/**
	 * Resolves a raw allow-list constant value into an explicit PHP array of tool names.
	 *
	 * @param string   $raw                Raw value of the constant from getDolGlobalString().
	 * @param string[] $allDiscoveredTools Full list of all non-system tool names discovered.
	 * @return string[] Explicit list of currently allowed tool names.
	 */
	public static function resolveAllowList($raw, $allDiscoveredTools)
	{
		if ($raw === '') {
			// Not yet configured → implicitly all tools are allowed
			return $allDiscoveredTools;
		}
		if ($raw === 'NONE') {
			// Admin explicitly disabled everything
			return array();
		}
		// Explicit list stored by a previous save
		return array_values(array_filter(array_map('trim', explode(',', $raw))));
	}


	/**
	 * Load all available MCP tools.
	 *
	 * This method scans the ai/tools directory for native tools and executes the
	 * 'addMcpTools' hook to allow external modules to register their own tools.
	 *
	 * @return void
	 */
	private function loadTools()
	{
		$this->loadNativeTools();
		$this->loadExternalTools();
	}

	/**
	 * Load native tools from the specific tools directory.
	 *
	 * Scans the ai/tools/ directory for PHP files. It validates the file paths
	 * for security, attempts to load the corresponding class (following the
	 * convention "Tool" + PascalCase filename), and registers the tool if it
	 * is a valid instance of McpTool.
	 *
	 * @return void
	 */
	private function loadNativeTools()
	{
		$toolsDir = DOL_DOCUMENT_ROOT . '/ai/tools/';
		if (!is_dir($toolsDir)) {
			dol_syslog('[McpHandler] MCP tools directory not found: ' . $toolsDir, LOG_INFO);
			return;
		}

		$files = glob($toolsDir . '*.php');
		foreach ($files as $file) {
			try {
				// Validate the file path before inclusion
				$realFilePath = realpath($file);
				if ($realFilePath === false || strpos($realFilePath, realpath($toolsDir)) !== 0) {
					dol_syslog('[McpHandler] Attempted to load tool outside of allowed directory: ' . $file, LOG_WARNING);
					continue;
				}

				require_once $realFilePath;

				$basename = basename($file, '.class.php');
				$className = 'Tool' . str_replace(' ', '', ucwords(str_replace('_', ' ', $basename)));

				if (!class_exists($className)) {
					dol_syslog("[McpHandler] Tool class '{$className}' not found in file '{$file}'.", LOG_WARNING);
					continue;
				}

				$toolInstance = new $className($this->db, $this->user, $this->conf);

				if ($toolInstance instanceof McpTool) {
					$this->registerTool($basename, $toolInstance);
				} else {
					dol_syslog("[McpHandler] Tool class '{$className}' does not extend McpTool.", LOG_ERR);
				}
			} catch (\Throwable $e) {
				dol_syslog("[McpHandler] Failed to load tool from file '{$file}': " . $e->getMessage(), LOG_ERR);
			}
		}
	}
	/**
	 * Loads external tools registered via the 'addMcpTools' hook.
	 *
	 * Initializes the HookManager for the 'aimcp' context and executes the
	 * 'addMcpTools' hook. It expects modules to populate the result array
	 * with arrays containing valid McpTool instances.
	 *
	 * @return void
	 */
	private function loadExternalTools()
	{
		global $hookmanager;
		if (!is_object($hookmanager)) {
			require_once DOL_DOCUMENT_ROOT . '/core/class/hookmanager.class.php';
			$hookmanager = new HookManager($this->db);
		}

		$hookmanager->initHooks(['aimcp']);

		$parameters = ['db' => $this->db, 'user' => $this->user, 'conf' => $this->conf];
		$action = '';

		try {
			$hookmanager->executeHooks('addMcpTools', $parameters, $this, $action);

			if (!is_array($hookmanager->resArray)) {
				return;
			}

			foreach ($hookmanager->resArray as $moduleTools) {
				if (!is_array($moduleTools)) {
					continue;
				}
				foreach ($moduleTools as $toolInstance) {
					if ($toolInstance instanceof McpTool) {
						$this->registerTool(get_class($toolInstance), $toolInstance);
					} else {
						dol_syslog('[McpHandler] A module provided a tool that is not an instance of McpTool.', LOG_WARNING);
					}
				}
			}
		} catch (\Throwable $e) {
			dol_syslog('[McpHandler] Error during \'addMcpTools\' hook execution: ' . $e->getMessage(), LOG_ERR);
		}
	}

	/**
	 * Helper method to register a tool instance and populate lookup arrays.
	 *
	 * @param string   $key          A unique key for the tool (e.g., filename or class name).
	 * @param McpTool  $toolInstance The instantiated tool object.
	 *
	 * @return void
	 */
	private function registerTool(string $key, McpTool $toolInstance)
	{
		$this->loadedTools[$key] = $toolInstance;

		// Populate the lookup map
		foreach ($toolInstance->getDefinitions() as $def) {
			if (isset($def['name'])) {
				if (isset($this->toolsByName[$def['name']])) {
					dol_syslog(
						"[McpHandler] Tool name conflict: '{$def['name']}' is already registered by '" . get_class($this->toolsByName[$def['name']]) . "'. Skipping registration from '" . get_class($toolInstance) . "'.",
						LOG_WARNING
					);
				} else {
					$this->toolsByName[$def['name']] = $toolInstance;
				}
			}
		}
		dol_syslog('[McpHandler] Successfully registered MCP tool: ' . get_class($toolInstance), LOG_INFO);
	}

	/**
	 * Returns the full schema of every loaded tool with no allow-list filtering.
	 * Adds is_system and class_name metadata needed by admin/configure_tools.php.
	 *
	 * Must not be called from any user-facing entry point — admin use only.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function getToolsSchemaUnfiltered()
	{
		$schema = array();

		foreach ($this->loadedTools as $tool) {
			$isSystem  = $this->isSystemTool($tool);
			$className = get_class($tool);

			foreach ($tool->getDefinitions() as $def) {
				$def['is_system']  = $isSystem;
				$def['class_name'] = $className;
				$def['categories'] = $tool->getCategories();
				$schema[] = $def;
			}
		}

		return $schema;
	}

	/**
	 * Returns the schema of all tools permitted in the current context.
	 *
	 * System tools (isSystem() = true) are always included and tagged with
	 * is_system = true so callers can identify and exclude them from the schema
	 * sent to the LLM (system tools are parse_intent.php infrastructure —
	 * they must never be called directly by the model).
	 *
	 * All other tools are filtered against the tool context allow-list.
	 *
	 * @return array<int, array<string, mixed>> Array of tool schemas
	 */
	public function getToolsSchema(): array
	{
		$allowed = $this->getAllowedToolsList();
		$schema  = [];

		foreach ($this->loadedTools as $tool) {
			$isSystem = $this->isSystemTool($tool);

			foreach ($tool->getDefinitions() as $def) {
				$name = isset($def['name']) ? $def['name'] : '';

				if ($isSystem) {
					// Always include system tools but tag them so parse_intent.php
					// can strip them from $toolsForLLM while keeping them available
					// for the validation check (executeTool must still be able to
					// run respond_to_user, ask_for_clarification, etc.).
					$def['is_system']  = true;
					$def['categories'] = $tool->getCategories();
					$schema[] = $def;
					continue;
				}

				$def['is_system'] = false;

				if (empty($allowed)) {
					// No restriction configured — include everything
					$def['categories'] = $tool->getCategories();
					$schema[] = $def;
					continue;
				}

				if (in_array($name, $allowed, true)) {
					$def['categories'] = $tool->getCategories();
					$schema[] = $def;
				}
				// Not in $allowed — silently omitted; LLM never sees this tool
			}
		}

		return $schema;
	}

	/**
	 * Returns the schema of tools permitted in the current context, with system
	 * tools completely excluded. This is the exact list sent to the LLM.
	 *
	 * System tools (ask_for_confirmation, respond_to_user, etc.) must NEVER be
	 * visible to the model. If the LLM sees ask_for_confirmation in its schema
	 * it will call it directly with wrong arguments instead of the real action
	 * tool, causing an infinite confirmation loop on the client side.
	 *
	 * parse_intent.php uses this method to build $toolsForLLM, and keeps
	 * getToolsSchema() separately only for the post-LLM validation step
	 * (where system tools must still be accepted as valid responses).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function getToolsSchemaForLLM()
	{
		$allowed = $this->getAllowedToolsList();
		$schema  = array();

		foreach ($this->loadedTools as $tool) {
			// Check isSystem() class method first (requires conversation.class.php
			// to implement it). This is the preferred path for future extensibility.
			if ($this->isSystemTool($tool)) {
				continue;
			}

			foreach ($tool->getDefinitions() as $def) {
				$name = isset($def['name']) ? $def['name'] : '';

				// Check is_system flag in the definition array itself.
				// This is set directly in conversation.class.php getDefinitions()
				// and works even if the isSystem() class method is not yet deployed.
				if (!empty($def['is_system'])) {
					continue;
				}

				if (empty($allowed)) {
					// No restriction configured — include everything
					$def['categories'] = $tool->getCategories();
					$schema[] = $def;
					continue;
				}

				if (in_array($name, $allowed, true)) {
					$def['categories'] = $tool->getCategories();
					$schema[] = $def;
				}
			}
		}

		return $schema;
	}

	/**
	 * Execute a specific tool by its name.
	 *
	 * Enforces the tool context allow-list as a second gate so that even a crafted
	 * direct request cannot run a tool that was disabled in the admin UI.
	 *
	 * @param string               $toolName The name of the tool to execute.
	 * @param array<string, mixed> $args     The arguments to pass to the tool.
	 *
	 * @return array<string, mixed> The result of the tool execution or an error array.
	 */
	public function executeTool(string $toolName, array $args): array
	{
		if (!isset($this->toolsByName[$toolName])) {
			return ["error" => "Tool '{$toolName}' not found."];
		}

		$toolInstance = $this->toolsByName[$toolName];

		// enforce tool context allow-list (system tools always pass through)
		if (!$this->isSystemTool($toolInstance)) {
			$allowed = $this->getAllowedToolsList();

			if (!empty($allowed) && !in_array($toolName, $allowed, true)) {
				dol_syslog(
					"[McpHandler] Blocked execution of tool '$toolName' in tool context '{$this->toolcontext}' (not in allow-list).",
					LOG_WARNING
				);
				return array('error' => "Tool '" . $toolName . "' is not available in this tool context.");
			}
		}

		// execute
		try {
			dol_syslog('[McpHandler] Executing tool \'' . $toolName . '\' with args: ' . json_encode($args), LOG_INFO);
			$result = $toolInstance->execute($toolName, $args);
			dol_syslog('[McpHandler] Tool \'' . $toolName . '\' executed successfully.', LOG_INFO);
			return $result;
		} catch (\Throwable $e) {
			dol_syslog('[McpHandler] Error executing tool \'' . $toolName . '\': ' . $e->getMessage(), LOG_ERR);
			return ["error" => "An internal error occurred while executing the tool '{$toolName}'. Details have been logged."];
		}
	}
}
