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

    dol_include_once('/contact/class/contact.class.php');
    $contact = new Contact($db);

    // Mail an RP
    $substitutionarray['__RP_NAME__'] = $object->ref;
	$contact->fetch($object->shipping);
	$substitutionarray['__RP_LIEFER_NAME__'] = $contact->firstname . ' ' . $contact->lastname;
	$contact->fetch($object->contract_adr);
	$substitutionarray['__RP_VERTRAG_NAME__'] = $contact->firstname . ' ' . $contact->lastname;
}

?>
