<?php
/* Copyright (C) 2015      Ion Agorria          <ion@agorria.com>
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
 *	\file       htdocs/product/dynamic_price/class/price_parser.class.php
 *	\ingroup    product
 *	\brief      File of class to calculate prices using expression
 */
require_once DOL_DOCUMENT_ROOT.'/includes/evalmath/evalmath.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/dynamic_price/class/price_expression.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/dynamic_price/class/price_global_variable.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/dynamic_price/class/price_global_variable_updater.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';

/**
 * Class to parse product price expressions
 */
class PriceParser
{
	protected $db;
	// Limit of expressions per price
	public $limit = 100;
	// The error that occurred when parsing price
	public $error_parser;
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
		24, variable '%s' exists but has no value

		-2 Args
		 6, wrong number of arguments (%s given, %s expected)
		23, unknown or non set variable '%s' after %s

		-internal errors
		 7, internal error
		12, internal error
		13, internal error
		15, internal error
		16, internal error
		18, internal error
		*/
		if (empty($this->error_parser)) {
			return $langs->trans("ErrorPriceExpressionUnknown", 0); //this is not supposed to happen
		}
		list($code, $info) = $this->error_parser;
		if (in_array($code, array(9, 14, 19, 20))) //Errors which have 0 arg
		{
			return $langs->trans("ErrorPriceExpression".$code);
		}
		else if (in_array($code, array(1, 2, 3, 4, 5, 8, 10, 11, 17, 21, 22, 24))) //Errors which have 1 arg
		{
			return $langs->trans("ErrorPriceExpression".$code, $info);
		}
		else if (in_array($code, array(6, 23))) //Errors which have 2 args
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
	 *	@param	Product	$product    	The Product object to get information
	 *	@param	String 	$expression     The expression to parse
	 *	@param	array  	$values     	Strings to replaces
	 *  @return int 					> 0 if OK, < 1 if KO
	 */
	public function parseExpression($product, $expression, $values)
	{
		global $user;

		//Check if empty
		$expression = trim($expression);
		if (empty($expression))
		{
			$this->error_parser = array(20, null);
			return -2;
		}

		//Accessible product values by expressions
		$values = array_merge($values, array(
			"tva_tx" => $product->tva_tx,
			"localtax1_tx" => $product->localtax1_tx,
			"localtax2_tx" => $product->localtax2_tx,
			"weight" => $product->weight,
			"length" => $product->length,
			"surface" => $product->surface,
			"price_min" => $product->price_min,
		));

		//Retrieve all extrafield for product and add it to values
		$extrafields = new ExtraFields($this->db);
		$extralabels = $extrafields->fetch_name_optionals_label('product', true);
		$product->fetch_optionals();
		foreach ($extrafields->attributes[$product->table_element]['label'] as $key=>$label)
		{
			$values["extrafield_".$key] = $product->array_options['options_'.$key];
		}

		//Process any pending updaters
		$price_updaters = new PriceGlobalVariableUpdater($this->db);
		foreach ($price_updaters->listPendingUpdaters() as $entry) {
            //Schedule the next update by adding current timestamp (secs) + interval (mins)
            $entry->update_next_update(dol_now() + ($entry->update_interval * 60), $user);
            //Do processing
			$res = $entry->process();
            //Store any error or clear status if OK
            $entry->update_status($res < 1?$entry->error:'', $user);
		}

		//Get all global values
		$price_globals = new PriceGlobalVariable($this->db);
		foreach ($price_globals->listGlobalVariables() as $entry)
		{
			$values["global_".$entry->code] = $entry->value;
		}

		//Remove internal variables
		unset($values["supplier_id"]);

		//Prepare the lib, parameters and values
		$em = new EvalMath();
		$em->suppress_errors = true; //Don't print errors on page
		$this->error_expr = null;
		$last_result = null;

		//Fill each variable in expression from values
		$expression = str_replace("\n", $this->separator_chr, $expression);
		foreach ($values as $key => $value)
		{
            if ($value === null && strpos($expression, $key) !== false) {
                $this->error_parser = array(24, $key);
                return -7;
            }
            $expression = str_replace($this->special_chr.$key.$this->special_chr, strval($value), $expression);
        }

		//Check if there is unfilled variable
		if (strpos($expression, $this->special_chr) !== false)
		{
			$data = explode($this->special_chr, $expression);
			$variable = $this->special_chr.$data[1];
			if (isset($data[2])) $variable.= $this->special_chr;
			$this->error_parser = array(23, array($variable, $expression));
			return -6;
		}

		//Iterate over each expression splitted by $separator_chr
		$expressions = explode($this->separator_chr, $expression);
		$expressions = array_slice($expressions, 0, $this->limit);
		foreach ($expressions as $expr) {
			$expr = trim($expr);
			if (!empty($expr))
			{
				$last_result = $em->evaluate($expr);
				$this->error_parser = $em->last_error_code;
				if ($this->error_parser !== null) { //$em->last_error_code is null if no error happened, so just check if error_parser is not null
					$this->error_expr = $expr;
					return -3;
				}
			}
		}
		$vars = $em->vars();
		if (empty($vars["price"])) {
			$vars["price"] = $last_result;
		}
		if (!isset($vars["price"]))
		{
			$this->error_parser = array(21, $expression);
			return -4;
		}
		if ($vars["price"] < 0)
		{
			$this->error_parser = array(22, $expression);
			return -5;
		}
		return $vars["price"];
	}

	/**
	 *	Calculates product price based on product id and associated expression
	 *
	 *	@param	Product				$product    	The Product object to get information
	 *	@param	array 				$extra_values   Any aditional values for expression
	 *	@return int 						> 0 if OK, < 1 if KO
	 */
	public function parseProduct($product, $extra_values = array())
	{
		//Get the expression from db
		$price_expression = new PriceExpression($this->db);
		$res = $price_expression->fetch($product->fk_price_expression);
		if ($res < 1) {
			$this->error_parser = array(19, null);
			return -1;
		}

		//Get the supplier min price
		$productFournisseur = new ProductFournisseur($this->db);
		$res = $productFournisseur->find_min_price_product_fournisseur($product->id, 0, 0);
		if ($res<1) {
			$this->error_parser = array(25, null);
			return -1;
		}
		$supplier_min_price = $productFournisseur->fourn_unitprice;

		//Accessible values by expressions
		$extra_values = array_merge($extra_values, array(
			"supplier_min_price" => $supplier_min_price,
		));

		//Parse the expression and return the price, if not error occurred check if price is higher than min
		$result = $this->parseExpression($product, $price_expression->expression, $extra_values);
		if (empty($this->error_parser)) {
			if ($result < $product->price_min) {
				$result = $product->price_min;
			}
		}
		return $result;
	}

	/**
	 *	Calculates supplier product price based on product supplier price and associated expression
	 *
	 *	@param	ProductFournisseur	$product_supplier   The Product supplier object to get information
	 *	@param	array 				$extra_values       Any aditional values for expression
	 *  @return int 				> 0 if OK, < 1 if KO
	 */
	public function parseProductSupplier($product_supplier, $extra_values = array())
	{
		//Get the expression from db
		$price_expression = new PriceExpression($this->db);
		$res = $price_expression->fetch($product_supplier->fk_supplier_price_expression);
		if ($res < 1)
		{
			$this->error_parser = array(19, null);
			return -1;
		}

		//Get the product data (use ignore_expression to avoid possible recursion)
		$product_supplier->fetch($product_supplier->id, '', '', 1);

		//Accessible values by expressions
		$extra_values = array_merge($extra_values, array(
			"supplier_quantity" => $product_supplier->fourn_qty,
			"supplier_tva_tx" => $product_supplier->fourn_tva_tx,
		));

		//Parse the expression and return the price
		return $this->parseExpression($product_supplier, $price_expression->expression, $extra_values);
	}

	/**
	 *	Tests string expression for validity
	 *
	 *	@param	int					$product_id    	The Product id to get information
	 *	@param	string 				$expression     The expression to parse
	 *	@param	array 				$extra_values   Any aditional values for expression
	 *  @return int 				> 0 if OK, < 1 if KO
	 */
	public function testExpression($product_id, $expression, $extra_values = array())
	{
		//Get the product data
		$product = new Product($this->db);
		$product->fetch($product_id, '', '', 1);

		//Values for product expressions
		$extra_values = array_merge($extra_values, array(
			"supplier_min_price" => 1,
		));

		//Values for supplier product expressions
		$extra_values = array_merge($extra_values, array(
			"supplier_quantity" => 2,
			"supplier_tva_tx" => 3,
		));
		return $this->parseExpression($product, $expression, $extra_values);
	}
}
