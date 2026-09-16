<?php
/* Copyright (C) 2026	Nick Fragoulis
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
 * along with this program. If not, see <https://gnu.org>.
 */

/**
 * \file htdocs/ai/tools/coffeemaker.class.php
 * \ingroup ai
 * \brief MCP Server tool for Smart Coffee Maker integration (Easter Egg edition).
 */

require_once DOL_DOCUMENT_ROOT . '/core/lib/geturl.lib.php';

/**
 * Class ToolCoffeeMaker
 *
 * Easter Egg tool to bridge Dolibarr MCP with local Home Assistant coffee configurations.
 */
class ToolCoffeeMaker extends McpTool
{
	/**
	 * Beverages the bridge accepts; the Home Assistant script receives the
	 * value verbatim as coffee_type and decides what its machine can brew.
	 */
	const COFFEE_TYPES = array(
		'espresso', 'ristretto', 'cappuccino', 'latte', 'americano', 'macchiato', 'flat_white', 'mocha',
		'nes', 'frappe', 'freddo_espresso', 'freddo_cappuccino', 'greek',
		'cafe_au_lait', 'noisette', 'allonge',
		'milchkaffee', 'eiskaffee', 'pharisaer',
		'cortado', 'cafe_con_leche', 'carajillo', 'bombon'
	);
	const COFFEE_INTENSITIES = array('mild', 'normal', 'strong');

	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = $db;
	}

	/**
	 * Returns tool definitions if the hidden constant is enabled.
	 *
	 * @return list<array<string, mixed>> Array of tool definitions or empty array if hidden.
	 */
	public function getDefinitions(): array
	{
		// Secret validation check to determine visibility
		if (!getDolGlobalInt('AI_COFFEE_EASTER_EGG')) {
			return []; // Hidden by default, behaves as non-existent
		}

		return [
			[
				"name" => "make_coffee",
				"description" => "Prepares a cup of coffee using the local Home Assistant smart device framework.",
				"inputSchema" => [
					"type" => "object",
					"properties" => [
						"type" => [
							"type" => "string",
							"enum" => self::COFFEE_TYPES,
							"description" => "The type of coffee to brew. Default is espresso. Greek: 'nes' (instant), 'frappe', 'freddo_espresso', 'freddo_cappuccino', 'greek' (ellinikos). French: 'cafe_au_lait', 'noisette' (espresso, dash of milk), 'allonge' (lungo). German: 'milchkaffee', 'eiskaffee' (with ice cream), 'pharisaer' (with rum and cream). Spanish: 'cortado', 'cafe_con_leche', 'carajillo' (with brandy), 'bombon' (condensed milk).",
							"default" => "espresso"
						],
						"intensity" => [
							"type" => "string",
							"enum" => self::COFFEE_INTENSITIES,
							"description" => "The strength of the coffee flavor profile.",
							"default" => "normal"
						]
					],
					"required" => ["type"]
				]
			]
		];
	}

	/**
	 * Return categories this tool belongs to.
	 *
	 * @return array<string> List of categories
	 */
	public function getCategories(): array
	{
		return ['smarthome', 'office'];
	}

	/**
	 * Executes the requested tool function based on its name.
	 *
	 * @param string $name The name of the tool to execute.
	 * @param array<string, mixed> $args The arguments for the tool.
	 * @return mixed The result of the tool execution or an error array.
	 */
	public function execute(string $name, array $args)
	{
		// Block execution if the easter egg is turned off
		if (!getDolGlobalInt('AI_COFFEE_EASTER_EGG')) {
			return ["error" => "Tool function '$name' not found."];
		}

		switch ($name) {
			case 'make_coffee':
				return $this->brewCoffee($args);
			default:
				return ["error" => "Tool function '$name' not found."];
		}
	}

	/**
	 * Triggers the Home Assistant API dynamically using stored configuration.
	 *
	 * @param array<string, mixed> $args Input filters (type, intensity)
	 * @return array<string, string> Status message or error description.
	 */
	private function brewCoffee(array $args): array
	{
		$type = $args['type'] ?? 'espresso';
		$intensity = $args['intensity'] ?? 'normal';
		// The enum constrains the model, not a direct caller: validate before
		// anything reaches Home Assistant.
		if (!in_array($type, self::COFFEE_TYPES, true)) {
			return ["error" => "Unknown coffee type '".$type."'. Supported: ".implode(', ', self::COFFEE_TYPES)."."];
		}
		if (!in_array($intensity, self::COFFEE_INTENSITIES, true)) {
			return ["error" => "Unknown intensity '".$intensity."'. Supported: ".implode(', ', self::COFFEE_INTENSITIES)."."];
		}

		$ha_url = getDolGlobalString('AI_COFFEE_HA_URL');
		$token = getDolGlobalString('AI_COFFEE_HA_TOKEN');
		$script_entity = getDolGlobalString('AI_COFFEE_HA_SCRIPT', 'script.brew_smart_coffee');

		if (empty($ha_url) || empty($token)) {
			return ["error" => "Home Assistant configuration is incomplete: set the constants AI_COFFEE_HA_URL and AI_COFFEE_HA_TOKEN (Home > Setup > Other), and optionally AI_COFFEE_HA_SCRIPT (default script.brew_smart_coffee)."];
		}

		// A long-lived HA token over plain http to a public host would cross the
		// internet in cleartext. Refusing would break odd-but-legit setups, so
		// warn instead.
		$host = (string) parse_url($ha_url, PHP_URL_HOST);
		$scheme = (string) parse_url($ha_url, PHP_URL_SCHEME);
		if ($scheme == 'http' && $host != 'localhost' && !preg_match('/\.local$/', $host) && filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
			dol_syslog("CoffeeMaker: AI_COFFEE_HA_URL uses plain http to a public host - the Home Assistant token is sent in cleartext. Use https (Nabu Casa or a TLS reverse proxy).", LOG_WARNING);
		}

		// Build proper HA API endpoint
		$script_name = str_replace('script.', '', $script_entity);
		$endpoint = rtrim($ha_url, '/') . '/api/services/script/' . $script_name;

		$payload_data = [
			'entity_id' => $script_entity,
			'coffee_type' => $type,
			'coffee_intensity' => $intensity
		];

		$headers = [
			"Authorization: Bearer " . $token,
			"Content-Type: application/json"
		];

		// localurl=2 (external AND local): Home Assistant almost always lives on
		// the LAN (192.168.x / homeassistant.local) and getURLContent() refuses
		// private/reserved ranges by default. Short timeouts: a chat turn must
		// not hang on an unreachable coffee machine.
		$result = getURLContent($endpoint, 'POST', json_encode($payload_data), 1, $headers, array('http', 'https'), 2, -1, 5, 10);

		if ($result['curl_error_no'] != 0 || !in_array($result['http_code'], [200, 201])) {
			dol_syslog("CoffeeMaker Error: " . (empty($result['curl_error_msg']) ? $result['http_code'] : $result['curl_error_msg']), LOG_ERR);
			return ["error" => "Failed to communicate with coffee machine."];
		}

		return [
			"status" => "success",
			"info" => "Your {$intensity} {$type} is brewing!"
		];
	}
}
