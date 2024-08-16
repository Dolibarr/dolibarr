<?php
/* Copyright (C) 2023	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/debugbar/class/DataCollector/DolPhpCollector.php
 *	\brief      Class for debugbar collection
 *	\ingroup    debugbar
 */

use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;

/**
 * Class PhpCollector
 *
 * This class collects all PHP errors, notices, advice, trigger_error,...
 * Supports 15 different types included.
 */
class PhpCollector extends DataCollector implements Renderable
{
	/**
	 * Collector name.
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * List of messages. Each item includes:
	 *  'message', 'message_html', 'is_string', 'label', 'time'.
	 *
	 * @var array
	 */
	protected $messages = [];

	/**
	 * PHPCollector constructor.
	 *
	 * @param string $name The name used by this collector widget.
	 */
	public function __construct($name = 'Error handler')
	{
		$this->name = $name;
		set_error_handler([$this, 'errorHandler'], E_ALL);
	}

	/**
	 * Called by the DebugBar when data needs to be collected.
	 *
	 * @return array 	Array of collected data
	 */
	public function collect()
	{
		$messages = $this->getMessages();
		return [
			'count' => count($messages),
			'messages' => $messages,
		];
	}

	/**
	 * Returns a list of messages ordered by their timestamp.
	 *
	 * @return array<array{time:int}> A list of messages ordered by time.
	 */
	public function getMessages()
	{
		$messages = $this->messages;

		usort(
			$messages,
			/**
			 * @param array{time:int} $itemA Message A information
			 * @param array{time:int} $itemB Message B information
			 * @return int<-1,1> -1 if Item A before Item B, 0 if same, 1 if later.
			 */
			static function ($itemA, $itemB) {
				if ($itemA['time'] === $itemB['time']) {
					return 0;
				}
				return $itemA['time'] < $itemB['time'] ? -1 : 1;
			}
		);

		return $messages;
	}

	/**
	 * Returns a hash where keys are control names and their values an array of options as defined in
	 * {@see DebugBar\JavascriptRenderer::addControl()}
	 *
	 * @return array 	Array of details to render the widget.
	 */
	public function getWidgets()
	{
		$name = $this->getName();
		return [
			$name => [
				'icon' => 'list',
				'widget' => 'PhpDebugBar.Widgets.MessagesWidget',
				'map' => "$name.messages",
				'default' => '[]',
			],
			"$name:badge" => [
				'map' => "$name.count",
				'default' => 'null',
			],
		];
	}

	/**
	 * Returns the unique name of the collector.
	 *
	 * @return string The widget name.
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Exception error handler. Called from constructor with set_error_handler to add all details.
	 *
	 * @param int    $severity Error type.
	 * @param string $message  Message of error.
	 * @param string $fileName File where error is generated.
	 * @param int    $line     Line number where error is generated.
	 *
	 * @return void
	 */
	public function errorHandler($severity, $message, $fileName, $line)
	{
		for ($i = 0; $i < 15; $i++) {
			if ($type = $severity & (1 << $i)) {
				$label = $this->friendlyErrorType($type);
				$this->messages[] = [
					'message' => $message . ' (' . $fileName . ':' . $line . ')',
					'message_html' => null,
					'is_string' => true,
					'label' => $label,
					'time' => microtime(true),
				];
			}
		}
	}

	/**
	 * Return error name from error code.
	 *
	 * @info http://php.net/manual/es/errorfunc.constants.php
	 *
	 * @param int $type Error code.
	 *
	 * @return string Error name.
	 */
	private function friendlyErrorType($type)
	{
		$errors = [
			E_ERROR => 'ERROR',
			E_WARNING => 'WARNING',
			E_PARSE => 'PARSE',
			E_NOTICE => 'NOTICE',
			E_CORE_ERROR => 'CORE_ERROR',
			E_CORE_WARNING => 'CORE_WARNING',
			E_COMPILE_ERROR => 'COMPILE_ERROR',
			E_COMPILE_WARNING => 'COMPILE_WARNING',
			E_USER_ERROR => 'USER_ERROR',
			E_USER_WARNING => 'USER_WARNING',
			E_USER_NOTICE => 'USER_NOTICE',
			E_STRICT => 'STRICT',
			E_RECOVERABLE_ERROR => 'RECOVERABLE_ERROR',
			E_DEPRECATED => 'DEPRECATED',
			E_USER_DEPRECATED => 'USER_DEPRECATED',
		];

		$result = '';
		if (isset($errors[$type])) {
			$result = $errors[$type];
		}

		return $result;
	}
}
