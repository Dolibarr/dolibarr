<?php
/* Copyright (C) 2014      Ion Agorria          <ion@agorria.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/product/class/priceparser.class.php
 *	\ingroup    product
 *	\brief      File of class to calculate prices using expression
 */
require_once DOL_DOCUMENT_ROOT.'/includes/evalmath/evalmath.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/priceexpression.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';

/**
 * Class to parse product price expressions
 */
class PriceParser
{
    protected $db;
    // Limit of expressions per price
    public $limit = 100;
    // The error that ocurred when parsing price
    public $error;
    // The expression that caused the error
    public $error_expr;
    //The special char
    public $special_chr = "#";
    //The separator char
    public $separator_chr = ";";

	/**
	 *  Constructor
	 *
	 *  @param      DoliDB		$db      Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 *	Returns translated error
	 *
	 *	@return string      Translated error
	 */
	public function translatedError()
	{
		global $langs;
		$langs->load("errors");
		/*
		-No arg
		 9, an unexpected error occured
		14, division by zero
		19, expression not found
		20, empty expression

		-1 Arg
		 1, cannot assign to constant '%s'
		 2, cannot redefine built-in function '%s'
		 3, undefined variable '%s' in function definition
		 4, illegal character '%s'
		 5, unexpected '%s'
		 8, unexpected operator '%s'
		10, operator '%s' lacks operand
		11, expecting '%s'
		17, undefined variable '%s'
		21, empty result '%s'
		22, negative result '%s'

		-2 Args
		 6, wrong number of arguments (%s given, %s expected)

		-internal errors
		 7, internal error
		12, internal error
		13, internal error
		15, internal error
		16, internal error
		18, internal error
		*/
		if (empty($this->error)) {
			return $langs->trans("ErrorPriceExpressionUnknown", 0); //this is not supposed to happen
		}
		list($code, $info) = $this->error;
		if (in_array($code, array(9, 14, 19, 20))) //Errors which have 0 arg
		{
			return $langs->trans("ErrorPriceExpression".$code);
		}
		else if (in_array($code, array(1, 2, 3, 4, 5, 8, 10, 11, 17, 21, 22))) //Errors which have 1 arg
		{
			return $langs->trans("ErrorPriceExpression".$code, $info);
		}
		else if (in_array($code, array(6))) //Errors which have 2 args
		{
			return $langs->trans("ErrorPriceExpression".$code, $info[0], $info[1]);
		}
		else if (in_array($code, array(7, 12, 13, 15, 16, 18))) //Internal errors
		{
			return $langs->trans("ErrorPriceExpressionInternal", $code);
		}
		else //Unknown errors
		{
			return $langs->trans("ErrorPriceExpressionUnknown", $code);
		}
	}

	/**
	 *	Calculates price based on expression
	 *
	 *	@param	array  	$values     	Strings to replaces
	 *	@param	String 	$expression     The expression to parse
     *  @return int 					> 0 if OK, < 1 if KO
	 */
	public function parseExpression($values, $expression)
	{
		//Check if empty
		$expression = trim($expression);
		if (empty($expression))
		{
			$this->error = array(20, null);
			return -1;
		}

		//Prepare the lib, parameters and values
		$em = new EvalMath();
		$em->suppress_errors = true; //Don't print errors on page
		$this->error_expr = null;
		$search = array();
		$replace = array();
		foreach ($values as $key => $value) {
			if ($value !== null) {
				$search[] = $this->special_chr.$key.$this->special_chr;
				$replace[] = $value;
			}
		}

		//Iterate over each expression splitted by $separator_chr
		$expression = str_replace("\n", $this->separator_chr, $expression);
		$expressions = explode($this->separator_chr, $expression);
		$expressions = array_slice($expressions, 0, $limit);
		foreach ($expressions as $expr) {
			$expr = trim($expr);
			if (!empty($expr))
			{
				$expr = str_ireplace($search, $replace, $expr);
				$last_result = $em->evaluate($expr);
				$this->error = $em->last_error_code;
				if ($this->error !== null) { //$em->last_error is null if no error happened, so just check if error is not null
					$this->error_expr = $expr;
					return -2;
				}
			}
		}
		$vars = $em->vars();
		if (empty($vars["price"])) {
			$vars["price"] = $last_result;
		}
		if ($vars["price"] === null)
		{
			$this->error = array(21, $expression);
			return -3;
		}
		if ($vars["price"] < 0)
		{
			$this->error = array(22, $expression);
			return -4;
		}
		return $vars["price"];
	}

	/**
	 *	Calculates supplier product price based on product id and string expression
	 *
	 *	@param	int					$product_id    	The Product id to get information
	 *	@param	string 				$expression     The expression to parse
	 *	@param	int					$quantity     	Min quantity
	 *	@param	int					$tva_tx     	VAT rate
	 *	@param	array 				$extra_values   Any aditional values for expression
     *  @return int 				> 0 if OK, < 1 if KO
	 */
	public function parseProductSupplierExpression($product_id, $expression, $quantity = null, $tva_tx = null, $extra_values = array())
	{
		//Accessible values by expressions
		$expression_values = array(
			"quantity" => $quantity,
			"tva_tx" => $tva_tx,
		);
		$expression_values = array_merge($expression_values, $extra_values);

		//Retreive all extrafield for product and add it to expression_values
		$extrafields = new ExtraFields($this->db);
		$extralabels = $extrafields->fetch_name_optionals_label('product', true);
		$product = new Product($this->db);
		$product->fetch_optionals($product_id, $extralabels);
		foreach($extrafields->attribute_label as $key=>$label)
		{
			$expression_values['options_'.$key] = $product->array_options['options_'.$key];
		}

		//Parse the expression and return the price
		return $this->parseExpression($expression_values, $expression);
	}

	/**
	 *	Calculates supplier product price based on product id and expression id
	 *
	 *	@param	int					$product_id    	The Product id to get information
	 *	@param	int 				$expression_id  The expression to parse
	 *	@param	int					$quantity     	Min quantity
	 *	@param	int					$tva_tx     	VAT rate
	 *	@param	array 				$extra_values   Any aditional values for expression
     *  @return int 				> 0 if OK, < 1 if KO
	 */
	public function parseProductSupplier($product_id, $expression_id, $quantity = null, $tva_tx = null, $extra_values = array())
	{
		$price_expression = new PriceExpression($this->db);
		$res = $price_expression->fetch($expression_id);
		if ($res > 1) {
			$this->error = array(19, null);
			return -1;
		}

		//Parse the expression and return the price
		return $this->parseProductSupplierExpression($product_id, $price_expression->expression, $quantity, $tva_tx, $extra_values);
	}
}