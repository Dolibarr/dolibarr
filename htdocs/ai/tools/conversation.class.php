<?php
/* Copyright (C) 2026	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2026	Nick Fragoulis
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
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
 * \file htdocs/ai/tools/conversation.php
 * \ingroup ai
 * \brief MCP Server tool for minimal interaction with the user.
 *
 * These tools control the UI flow (modals) and do not perform backend actions themselves.
 * The actual logic for handling the tool's response is implemented in the client-side JavaScript.
 */


class ToolConversation extends McpTool
{
	/**
	 * Defines the conversational tools available to the AI.
	 * These tools control the interaction flow with the end-user.
	 *
	 * @return list<array<string, mixed>> Array of tool definitions.
	 */
	public function getDefinitions(): array
	{
		return [
			[
				"name" => "ask_for_clarification",
				"description" => "Asks the user for more information needed to complete a Dolibarr ERP-related task. The client-side JavaScript will append the user's response at the end of the original query to maintain context. Only use this when the question is clearly about Dolibarr ERP.",

				"inputSchema" => [
					"type" => "object",
					"properties" => [
						"question" => [
							"type" => "string",
							"description" => "The specific question to ask the user about Dolibarr ERP."
						]
					],
					"required" => ["question"]
				]
			],
			[
				"name" => "respond_to_user",
				"description" => "Sends a one-way informational message to the user about Dolibarr ERP objects and functions ONLY. This tool is strictly restricted to displaying information or errors related to Dolibarr ERP. Do NOT use this tool for general knowledge questions, personal advice, or topics unrelated to Dolibarr ERP.",
				"inputSchema" => [
					"type" => "object",
					"properties" => [
						"message" => [
							"type" => "string",
							"description" => "The informational message to display to the user."
						]
					],
					"required" => ["message"]
				]
			],
			[
				"name" => "ask_for_confirmation",
				"description" => "Asks the user to confirm a destructive or critical action before proceeding. The client-side JavaScript will store the original intent and re-execute it if the user confirms.",
				"inputSchema" => [
					"type" => "object",
					"properties" => [
						"action" => [
							"type" => "string",
							"description" => "A short, verb-based description of the action (e.g., 'delete invoice')."
						],
						"details" => [
							"type" => "string",
							"description" => "A clear, human-readable summary of what will be affected."
						]
					],
					"required" => ["action", "details"]
				]
			],
			[
				"name" => "reject_general_question",
				"description" => "Use this tool when the user asks a question that is not related to Dolibarr ERP objects and functions. This tool should not be used for any general knowledge questions, personal advice, or topics outside the scope of Dolibarr ERP.",
				"inputSchema" => [
					"type" => "object",
					"properties" => [
						"message" => [
							"type" => "string",
							"description" => "A polite message explaining that you can only answer questions about Dolibarr ERP."
						]
					],
					"required" => ["message"]
				]
			]
		];
	}

	/**
	 * Return categories this tool belongs to.
	 * Used by the intent parser to filter available tools.
	 *
	 * @return array<string> List of categories (e.g., ['billing', 'commercial'])
	 */
	public function getCategories(): array
	{
		return ['global'];
	}

	/**
	 * Executes a conversational tool.
	 * Since these tools don't perform backend actions, this method simply
	 * packages the provided arguments into a structured array for the MCP server
	 * to send to the client.
	 *
	 * @param string $name The name of the tool to execute.
	 * @param array<string, mixed> $args The arguments for the tool (key-value pairs).
	 * @return array{tool: string, arguments: array<string, mixed>}|null A structured command array or null if the tool is not found.
	 */
	public function execute(string $name, array $args): ?array
	{
		// These tools don't require specific permissions as they are for UI control.
		// The main application layer should handle any permission checks if needed.

		switch ($name) {
			case 'ask_for_clarification':
			case 'respond_to_user':
			case 'ask_for_confirmation':
			case 'reject_general_question':

				return [
					"tool" => $name,
					"arguments" => $args
				];

			default:
				return null;
		}
	}
}
