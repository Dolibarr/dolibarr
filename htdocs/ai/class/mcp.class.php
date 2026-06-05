<?php
/* Copyright (C) 2026	Laurent Destailleur		<eldy@users.sourceforge.net>
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
 */
class McpHandler
{
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
	 * Constructor.
	 *
	 * @param DoliDB $db   Database handler object
	 * @param User   $user User object
	 */
	public function __construct($db, $user)
	{
		$this->db = $db;
		$this->user = $user;

		global $conf;
		$this->conf = $conf;

		$this->loadTools();
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
	 * Get the combined schema of all loaded tools.
	 *
	 * @return array<int, array<string, mixed>> Array of tool schemas
	 */
	public function getToolsSchema(): array
	{
		$schema = [];
		foreach ($this->loadedTools as $tool) {
			foreach ($tool->getDefinitions() as $def) {
				$def['categories'] = $tool->getCategories();
				$schema[] = $def;
			}
		}
		return $schema;
	}

	/**
	 * Execute a specific tool by its name.
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

		try {
			$toolInstance = $this->toolsByName[$toolName];
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
