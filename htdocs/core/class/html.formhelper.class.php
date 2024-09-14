<?php
/* Copyright (C) 2024		Alex Vives			<avives@nubenet.digital>
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
 * \file       htdocs/core/class/html.formhelper.class.php
 * \ingroup    core
 * \brief      File of class with html helpers
 */
class FormHelper
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}
	/**
	 * Opens the table element.
	 *
	 * @param string $class       Optional CSS class for the table.
	 * @param string $id          Optional ID for the table.
	 * @param array<string, string> $attributes Additional attributes for the table (optional).
	 *
	 * @return string
	 */
	public function openTable($class = 'noborder centpercent', $id = '', array $attributes = array())
	{
		$attrString = $this->buildAttributes($attributes);
		return '<table' . ($class ? ' class="' . $class . '"' : '') . ($id ? ' id="' . $id . '"' : '') . ' ' . $attrString . '>';
	}

	/**
	 * Closes the table element.
	 *
	 * @return string
	 */
	public function closeTable()
	{
		return '</table>';
	}

	/**
	 * Creates a table row (tr) element with support for individual column classes and attributes.
	 *
	 * @param array<int, array<string, mixed>> $columns Array of columns where each column is an array with string keys like 'content', 'class', etc.
	 * @param string $rowClass   Optional CSS class for the row.
	 * @param string $rowId      Optional ID for the row.
	 * @param array<string, string> $attributes Attributes for the row.
	 *
	 * @return string
	 */
	public function addRow(array $columns = array(), $rowClass = '', $rowId = '', array $attributes = array())
	{
		$attrString = $this->buildAttributes($attributes);
		$out = '<tr' . ($rowClass ? ' class="' . $rowClass . '"' : '') . ($rowId ? ' id="' . $rowId . '"' : '') . ' ' . $attrString . '>';
		foreach ($columns as $column) {
			// Check if column is an array with content, class, id, and other attributes
			if (is_array($column)) {
				$content = isset($column['content']) ? $column['content'] : '';
				$class = isset($column['class']) ? ' class="' . $column['class'] . '"' : '';
				$id = isset($column['id']) ? ' id="' . $column['id'] . '"' : '';
				$colAttributes = isset($column['attributes']) ? $this->buildAttributes($column['attributes']) : '';
			} else {
				$content = $column;
				$class = '';
				$id = '';
				$colAttributes = '';
			}
			$out .= '<td' . $class . $id . ' ' . $colAttributes . '>' . $content . '</td>';
		}
		$out .= '</tr>';
		return $out;
	}

	/**
	 * Creates a header row (th) element with support for additional attributes.
	 *
	 * @param array<int, string> $headers Array of header labels, where each entry is a string representing the column header.
	 * @param string $class      Optional CSS class for the row.
	 * @param int    $rowspan    Rowspan attribute (optional).
	 * @param int    $colspan    Colspan attribute (optional).
	 * @param string $id         Optional ID for the row.
	 * @param array<string, string> $attributes Attributes for the header row.
	 *
	 * @return string
	 */
	public function addHeaderRow(array $headers = array(), $class = 'liste_titre', $rowspan = 1, $colspan = 1, $id = '', array $attributes = array())
	{
		$attrString = $this->buildAttributes($attributes);
		$out = '<tr' . ($class ? ' class="' . $class . '"' : '') . ($id ? ' id="' . $id . '"' : '') . ' ' . $attrString . '>';
		foreach ($headers as $header) {
			if (is_array($header)) {
				$content = isset($header['content']) ? $header['content'] : '';
				$headerClass = isset($header['class']) ? ' class="' . $header['class'] . '"' : '';
				$headerId = isset($header['id']) ? ' id="' . $header['id'] . '"' : '';
				$headerAttributes = isset($header['attributes']) ? $this->buildAttributes($header['attributes']) : '';
			} else {
				$content = $header;
				$headerClass = '';
				$headerId = '';
				$headerAttributes = '';
			}
			$out .= '<th'
				. ($rowspan > 1 ? ' rowspan="' . $rowspan . '"' : '')
				. ($colspan > 1 ? ' colspan="' . $colspan . '"' : '')
				. $headerClass . $headerId . ' ' . $headerAttributes . '>';
			$out .= $content;
			$out .= '</th>';
		}
		$out .= '</tr>';
		return $out;
	}

	/**
	 * Creates a single column (td) inside a row with support for additional attributes.
	 *
	 * @param string $content    Content for the column (string or HTML).
	 * @param string $class      Optional CSS class for the column.
	 * @param int    $rowspan    Rowspan attribute (optional).
	 * @param int    $colspan    Colspan attribute (optional).
	 * @param string $id         Optional ID for the column.
	 * @param array<string, string> $attributes Attributes for the header row.
	 *
	 * @return string
	 */
	public function addColumn($content = '', $class = '', $rowspan = 1, $colspan = 1, $id = '', array $attributes = array())
	{
		$attrString = $this->buildAttributes($attributes);
		$out = '<td'
			. ($class ? ' class="' . $class . '"' : '')
			. ($id ? ' id="' . $id . '"' : '')
			. ($rowspan > 1 ? ' rowspan="' . $rowspan . '"' : '')
			. ($colspan > 1 ? ' colspan="' . $colspan . '"' : '')
			. ' ' . $attrString . '>';
		$out .= $content;
		$out .= '</td>';
		return $out;
	}

	/**
	 * Helper function to build custom attributes string from an array.
	 *
	 * @param array<string,string> $attributes Array of custom attributes (optional).
	 *
	 * @return string
	 */
	private function buildAttributes(array $attributes = array())
	{
		// Asegurarse de que $attributes sea un array
		if (!is_array($attributes)) {
			$attributes = array();
		}

		$attrString = '';
		foreach ($attributes as $key => $value) {
			$attrString .= ' ' . $key . '="' . htmlspecialchars($value, ENT_QUOTES) . '"';
		}
		return $attrString;
	}

	/**
	 * Closes a column (td) element.
	 *
	 * @return string
	 */
	public function closeColumn()
	{
		return '</td>';
	}


	/**
	 * Returns the HTML input field for the given field name and value.
	 *
	 * @param string $name          The name of the input field.
	 * @param string $value         The value to populate the input field with.
	 * @param string $type          The type of input field (default is 'text').
	 * @param string $id            The ID of the input field (optional).
	 * @param string $class         The CSS class for the input field (optional).
	 * @return string The HTML input field as a string.
	 */
	public function createInputField($name, $value = '', $type = 'text', $id = '', $class = '')
	{
		$out = '<input type="' . $type . '" id="' . $id . '" name="' . $name . '" value="' . dol_escape_htmltag($value) . '" class="' . $class . '">';
		return $out;
	}

	/**
	 * Closes the form by returning the closing form tag.
	 *
	 * @return string The HTML closing form tag.
	 */
	public function closeForm()
	{
		// Close the form tag
		$out = '</form>';
		return $out;
	}

	/**
	 * Opens a form with the specified name, action, method, and hidden options.
	 *
	 * @param string              $form_name      The name of the form.
	 * @param string              $action         The URL where the form data will be sent (default is an empty string).
	 * @param string              $method         The HTTP method to use (default is 'POST').
	 * @param array<string,string> $hiddenoptions  An associative array of hidden input fields to include (optional).
	 *
	 * @return string The opening form tag and hidden fields as HTML.
	 */
	public function openForm($form_name, $action = '', $method = 'POST', array $hiddenoptions = array())
	{
		// Create the opening form tag with name, action, and method attributes
		$out = '<form name="' . $form_name . '" action="' . $action . '" method="' . $method . '">';

		// Add any hidden input fields provided in the $hiddenoptions array
		foreach ($hiddenoptions as $key => $value) {
			$out .= '<input type="hidden" name="' . $key . '" value="' . $value . '">';
		}

		// Add a hidden input field for a security token
		$out .= '<input type="hidden" name="token" value="' . newToken() . '">';

		return $out;
	}
}
