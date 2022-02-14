<?php
/**        Function called to complete substitution array (before generating on ODT, or a personalized email)
 *        functions xxx_completesubstitutionarray are called by make_substitutions() if file
 *        is inside directory htdocs/core/substitutions
 *
 * @param array $substitutionarray Array with substitution key=>val
 * @param Translate $langs Output langs
 * @param Object $object Object to use to get values
 * @return    void                    The entry parameter $substitutionarray is modified
 */
function handson_completesubstitutionarray(&$substitutionarray, $langs, $object)
{
    global $conf, $db;

    $vertragID = '42';
    $substitutionarray['__VERTRAG__'] = $vertragID;
}

?>
