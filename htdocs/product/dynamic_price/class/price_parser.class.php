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
		$product->fetch_optionals($product->id, $extralabels);
		foreach ($extrafields->attribute_label as $key=>$label)
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

		//Check if empty
		$expression = trim($expression);
		if (empty($expression))
		{
			$this->error = array(20, null);
			return -2;
		}

		//Prepare the lib, parameters and values
		$em = new EvalMath();
		$em->suppress_errors = true; //Don't print errors on page
		$this->error_expr = null;
		$last_result = null;

		//Iterate over each expression splitted by $separator_chr
		$expression = str_replace("\n", $this->separator_chr, $expression);
		foreach ($values as $key => $value)
		{
			$expression = str_replace($this->special_chr.$key.$this->special_chr, "$value", $expression);
		}
		$expressions = explode($this->separator_chr, $expression);
		$expressions = array_slice($expressions, 0, $this->limit);
		foreach ($expressions as $expr) {
			$expr = trim($expr);
			if (!empty($expr))
			{
				$last_result = $em->evaluate($expr);
				$this->error = $em->last_error_code;
				if ($this->error !== null) { //$em->last_error is null if no error happened, so just check if error is not null
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
			$this->error = array(21, $expression);
			return -4;
		}
		if ($vars["price"] < 0)
		{
			$this->error = array(22, $expression);
			return -5;
		}
		return $vars["price"];
	}

	/**
	 *	Calculates product price based on product id and string expression
	 *
	 *	@param	Product				$product    	The Product object to get information
	 *	@param	string 				$expression     The expression to parse
	 *	@param	array 				$extra_values   Any aditional values for expression
	 *  @return int 				> 0 if OK, < 1 if KO
	 */
	public function parseProductExpression($product, $expression, $extra_values = array())
	{
		//Get the supplier min
		$productFournisseur = new ProductFournisseur($this->db);
		$supplier_min_price = $productFournisseur->find_min_price_product_fournisseur($product->id);

		//Accessible values by expressions
		$extra_values = array_merge($extra_values, array(
			"supplier_min_price" => $supplier_min_price,
		));

		//Parse the expression and return the price, if not error occurred check if price is higher than min
		$result = $this->parseExpression($product, $expression, $extra_values);
		if (empty($this->error)) {
			if ($result < $product->price_min) {
				$result = $product->price_min;
			}
		}
		return $result;
	}

	/**
	 *	Calculates product price based on product id and expression id
	 *
	 *	@param	Product				$product    	The Product object to get information
	 *	@param	array 				$extra_values   Any aditional values for expression
	 *  @return int 								> 0 if OK, < 1 if KO
	 */
	public function parseProduct($product, $extra_values = array())
	{
		//Get the expression from db
		$price_expression = new PriceExpression($this->db);
		$res = $price_expression->fetch($product->fk_price_expression);
		if ($res < 1) {
			$this->error = array(19, null);
			return -1;
		}

		//Parse the expression and return the price
		return $this->parseProductExpression($product, $price_expression->expression, $extra_values);
	}

	/**
	 *	Calculates supplier product price based on product id and string expression
	 *
	 *	@param	int					$product_id    	The Product id to get information
	 *	@param	string 				$expression     The expression to parse
	 *	@param	int					$quantity     	Supplier Min quantity
	 *	@param	int					$tva_tx     	Supplier VAT rate
	 *	@param	array 				$extra_values   Any aditional values for expression
	 *  @return int 				> 0 if OK, < 1 if KO
	 */
	public function parseProductSupplierExpression($product_id, $expression, $quantity = null, $tva_tx = null, $extra_values = array())
	{
		//Get the product data
		$product = new ProductFournisseur($this->db);
		$product->fetch($product_id, '', '', 1);

		//Accessible values by expressions
		$extra_values = array_merge($extra_values, array(
			"supplier_quantity" => $quantity,
			"supplier_tva_tx" => $tva_tx,
		));
		return $this->parseExpression($product, $expression, $extra_values);
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
		//Get the expression from db
		$price_expression = new PriceExpression($this->db);
		$res = $price_expression->fetch($expression_id);
		if ($res < 1) {
			$this->error = array(19, null);
			return -1;
		}

		//Parse the expression and return the price
		return $this->parseProductSupplierExpression($product_id, $price_expression->expression, $quantity, $tva_tx, $extra_values);
	}
}