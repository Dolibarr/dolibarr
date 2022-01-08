<?php
namespace Luracast\Restler\Data;

/**
 * Validation classes should implement this interface
 *
 * @category   Framework
 * @package    Restler
 * @author     R.Arul Kumaran <arul@luracast.com>
 * @copyright  2010 Luracast
 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link       http://luracast.com/products/restler/
 *
 */
interface iValidate {

    /**
     * method used for validation.
     *
     * @param mixed $input
     *            data that needs to be validated
     * @param ValidationInfo $info
     *            information to be used for validation
     * @return boolean false in case of failure or fixed value in the expected
     *         type
     * @throws \Luracast\Restler\RestException 400 with information about the
     * failed
     * validation
     */
    public static function validate($input, ValidationInfo $info);
}

