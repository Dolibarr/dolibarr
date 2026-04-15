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
 */

/**
 * \file    htdocs/ai/class/mcptool.class.php
 * \ingroup ai
 * \brief   File of class to manage MCP Tools.
 */

/**
 * Abstract base class for all MCP (Model Context Protocol) tools.
 *
 * All AI tools must extend this class. It provides a standard interface for defining
 * tool capabilities and executing them, along with common utility methods for interacting
 * with Dolibarr.
 */
abstract class McpTool
{
	/** @var DoliDB Database handler */
	protected $db;

	/** @var User User object */
	protected $user;

	/** @var Conf Configuration object */
	protected $conf;

	/**
	 * Constructor.
	 *
	 * @param DoliDB $db   Database handler object
	 * @param User   $user User object
	 * @param Conf   $conf Configuration object
	 */
	public function __construct($db, $user, $conf)
	{
		$this->db = $db;
		$this->user = $user;
		$this->conf = $conf;
	}

	/**
	 * Return the list of tools provided by this class.
	 *
	 * This method must be implemented by child classes to define their capabilities.
	 * It should return an array of tool definitions, typically in a JSON schema format.
	 *
	 * @return list<array<string, mixed>> Array of tool definitions.
	 */
	abstract public function getDefinitions(): array;

	/**
	 * Execute a specific tool.
	 *
	 * This method must be implemented by child classes to contain the logic for
	 * executing the tools defined in `getDefinitions`.
	 *
	 * @param string               $toolName The name of the tool to execute.
	 * @param array<string, mixed> $args     Associative array of arguments for the tool.
	 *
	 * @return mixed The result of the tool execution.
	 */
	abstract public function execute(string $toolName, array $args);

	/**
	 * Return categories this tool belongs to.
	 * Used by the intent parser to filter available tools.
	 *
	 * @return array<string> List of categories (e.g., ['billing', 'commercial'])
	 */
	public function getCategories(): array
	{
		return ['global']; // Default
	}
}
