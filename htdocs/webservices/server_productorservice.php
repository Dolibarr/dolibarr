<?php
/* Copyright (C) 2006-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2012      JF FERRY             <jfefe@aternatik.fr>
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
 *       \file       htdocs/webservices/server_productorservice.php
 *       \brief      File that is entry point to call Dolibarr WebServices
 */

// This is to make Dolibarr working with Plesk
set_include_path($_SERVER['DOCUMENT_ROOT'].'/htdocs');

require_once '../master.inc.php';
require_once NUSOAP_PATH.'/nusoap.php';        // Include SOAP
require_once DOL_DOCUMENT_ROOT.'/core/lib/ws.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';

require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once(DOL_DOCUMENT_ROOT."/categories/class/categorie.class.php");



dol_syslog("Call Dolibarr webservices interfaces");

$langs->load("main");

// Enable and test if module web services is enabled
if (empty($conf->global->MAIN_MODULE_WEBSERVICES))
{
    $langs->load("admin");
    dol_syslog("Call Dolibarr webservices interfaces with module webservices disabled");
    print $langs->trans("WarningModuleNotActive",'WebServices').'.<br><br>';
    print $langs->trans("ToActivateModule");
    exit;
}

// Create the soap Object
$server = new nusoap_server();
$server->soap_defencoding='UTF-8';
$server->decode_utf8=false;
$ns='http://www.dolibarr.org/ns/';
$server->configureWSDL('WebServicesDolibarrProductOrService',$ns);
$server->wsdl->schemaTargetNamespace=$ns;


// Define WSDL Authentication object
$server->wsdl->addComplexType(
    'authentication',
    'complexType',
    'struct',
    'all',
    '',
    array(
        'dolibarrkey' => array('name'=>'dolibarrkey','type'=>'xsd:string'),
    	'sourceapplication' => array('name'=>'sourceapplication','type'=>'xsd:string'),
    	'login' => array('name'=>'login','type'=>'xsd:string'),
        'password' => array('name'=>'password','type'=>'xsd:string'),
        'entity' => array('name'=>'entity','type'=>'xsd:string'),
    )
);
// Define WSDL Return object
$server->wsdl->addComplexType(
    'result',
    'complexType',
    'struct',
    'all',
    '',
    array(
        'result_code' => array('name'=>'result_code','type'=>'xsd:string'),
        'result_label' => array('name'=>'result_label','type'=>'xsd:string'),
    )
);

// Define other specific objects
$server->wsdl->addComplexType(
    'product',
    'complexType',
    'struct',
    'all',
    '',
    array(
    	'id' => array('name'=>'id','type'=>'xsd:string'),
		'ref' => array('name'=>'ref','type'=>'xsd:string'),
        'ref_ext' => array('name'=>'ref_ext','type'=>'xsd:string'),
    	'type' => array('name'=>'type','type'=>'xsd:string'),
		'label' => array('name'=>'label','type'=>'xsd:string'),
        'description' => array('name'=>'description','type'=>'xsd:string'),
        'date_creation' => array('name'=>'date_creation','type'=>'xsd:dateTime'),
        'date_modification' => array('name'=>'date_modification','type'=>'xsd:dateTime'),
        'note' => array('name'=>'note','type'=>'xsd:string'),
    	'status_tobuy' => array('name'=>'status_tobuy','type'=>'xsd:string'),
    	'status_tosell' => array('name'=>'status_tosell','type'=>'xsd:string'),
    	'barcode' => array('name'=>'barcode','type'=>'xsd:string'),
    	'barcode_type' => array('name'=>'barcode_type','type'=>'xsd:string'),
		'country_id' => array('name'=>'country_id','type'=>'xsd:string'),
    	'country_code' => array('name'=>'country_code','type'=>'xsd:string'),
    	'customcode' => array('name'=>'customcode','type'=>'xsd:string'),

    	'price_net' => array('name'=>'price_net','type'=>'xsd:string'),
    	'price' => array('name'=>'price','type'=>'xsd:string'),
    	'price_min_net' => array('name'=>'price_min_net','type'=>'xsd:string'),
    	'price_min' => array('name'=>'price_min','type'=>'xsd:string'),

    	'price_base_type' => array('name'=>'price_base_type','type'=>'xsd:string'),

    	'vat_rate' => array('name'=>'vat_rate','type'=>'xsd:string'),
    	'vat_npr' => array('name'=>'vat_npr','type'=>'xsd:string'),
    	'localtax1_tx' => array('name'=>'localtax1_tx','type'=>'xsd:string'),
    	'localtax2_tx' => array('name'=>'localtax2_tx','type'=>'xsd:string'),

    	'stock_alert' => array('name'=>'stock_alert','type'=>'xsd:string'),
    	'stock_real' => array('name'=>'stock_real','type'=>'xsd:string'),
    	'stock_pmp' => array('name'=>'stock_pmp','type'=>'xsd:string'),
		'canvas' => array('name'=>'canvas','type'=>'xsd:string'),
		'import_key' => array('name'=>'import_key','type'=>'xsd:string'),

		'dir' => array('name'=>'dir','type'=>'xsd:string'),
		'images' => array('name'=>'images','type'=>'tns:ImagesArray')
    )
);


/*
 * Image of product
 */
$server->wsdl->addComplexType(
	'ImagesArray',
	'complexType',
	'array',
	'sequence',
	'',
	array(
		'image' => array(
		'name' => 'image',
		'type' => 'tns:image',
		'minOccurs' => '0',
		'maxOccurs' => 'unbounded'
		)
	)
);

/*
 * An image
 */
$server->wsdl->addComplexType(
	'image',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'photo' => array('name'=>'photo','type'=>'xsd:string'),
		'photo_vignette' => array('name'=>'photo_vignette','type'=>'xsd:string'),
		'imgWidth' => array('name'=>'imgWidth','type'=>'xsd:string'),
		'imgHeight' => array('name'=>'imgHeight','type'=>'xsd:string')
	)
);


// Define other specific objects
$server->wsdl->addComplexType(
    'filterproduct',
    'complexType',
    'struct',
    'all',
    '',
    array(
        //'limit' => array('name'=>'limit','type'=>'xsd:string'),
		'type' => array('name'=>'type','type'=>'xsd:string'),
	    'status_tobuy' => array('name'=>'status_tobuy','type'=>'xsd:string'),
	    'status_tosell' => array('name'=>'status_tosell','type'=>'xsd:string'),
    )
);

/*$server->wsdl->addComplexType(
    'ProductsArray',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    array(),
    array(
        array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'tns:product[]')
    ),
    'tns:product'
);*/
$server->wsdl->addComplexType(
    'ProductsArray2',
    'complexType',
    'array',
    'sequence',
    '',
    array(
        'product' => array(
            'name' => 'product',
            'type' => 'tns:product',
            'minOccurs' => '0',
            'maxOccurs' => 'unbounded'
        )
    )
);



// 5 styles: RPC/encoded, RPC/literal, Document/encoded (not WS-I compliant), Document/literal, Document/literal wrapped
// Style merely dictates how to translate a WSDL binding to a SOAP message. Nothing more. You can use either style with any programming model.
// http://www.ibm.com/developerworks/webservices/library/ws-whichwsdl/
$styledoc='rpc';       // rpc/document (document is an extend into SOAP 1.0 to support unstructured messages)
$styleuse='encoded';   // encoded/literal/literal wrapped
// Better choice is document/literal wrapped but literal wrapped not supported by nusoap.


// Register WSDL
$server->register(
    'getProductOrService',
    // Entry values
    array('authentication'=>'tns:authentication','id'=>'xsd:string','ref'=>'xsd:string','ref_ext'=>'xsd:string'),
    // Exit values
    array('result'=>'tns:result','product'=>'tns:product'),
    $ns,
    $ns.'#getProductOrService',
    $styledoc,
    $styleuse,
    'WS to get product or service'
);

// Register WSDL
$server->register(
    'createProductOrService',
    // Entry values
    array('authentication'=>'tns:authentication','product'=>'tns:product'),
    // Exit values
    array('result'=>'tns:result','id'=>'xsd:string'),
    $ns,
    $ns.'#createProductOrService',
    $styledoc,
    $styleuse,
    'WS to create a product or service'
);

// Register WSDL
$server->register(
    'getListOfProductsOrServices',
    // Entry values
    array('authentication'=>'tns:authentication','filterproduct'=>'tns:filterproduct'),
    // Exit values
    array('result'=>'tns:result','products'=>'tns:ProductsArray2'),
    $ns,
    $ns.'#getListOfProductsOrServices',
    $styledoc,
    $styleuse,
    'WS to get list of all products or services id and ref'
);

// Register WSDL
$server->register(
	'getProductsForCategory',
	// Entry values
	array('authentication'=>'tns:authentication','id'=>'xsd:string'),
	// Exit values
	array('result'=>'tns:result','products'=>'tns:ProductsArray2'),
	$ns,
	$ns.'#getProductsForCategory',
	$styledoc,
	$styleuse,
	'WS to get list of all products or services for a category'
);


/**
 * Get produt or service
 *
 * @param	array		$authentication		Array of authentication information
 * @param	int			$id					Id of object
 * @param	string		$ref				Ref of object
 * @param	ref_ext		$ref_ext			Ref external of object
 * @return	mixed
 */
function getProductOrService($authentication,$id='',$ref='',$ref_ext='')
{
    global $db,$conf,$langs;

    dol_syslog("Function: getProductOrService login=".$authentication['login']." id=".$id." ref=".$ref." ref_ext=".$ref_ext);

    if ($authentication['entity']) $conf->entity=$authentication['entity'];

    // Init and check authentication
    $objectresp=array();
    $errorcode='';$errorlabel='';
    $error=0;
    $fuser=check_authentication($authentication,$error,$errorcode,$errorlabel);
    // Check parameters
    if (! $error && (($id && $ref) || ($id && $ref_ext) || ($ref && $ref_ext)))
    {
        $error++;
        $errorcode='BAD_PARAMETERS'; $errorlabel="Parameter id, ref and ref_ext can't be both provided. You must choose one or other but not both.";
    }

    if (! $error)
    {
        $fuser->getrights();

        if ($fuser->rights->produit->lire || $fuser->rights->service->lire)
        {
            $product=new Product($db);
            $result=$product->fetch($id,$ref,$ref_ext);
            if ($result > 0)
            {
            	$dir = (!empty($conf->product->dir_output)?$conf->product->dir_output:$conf->service->dir_output);
            	$pdir = get_exdir($product->id,2) . $product->id ."/photos/";
            	$dir = $dir . '/'. $pdir;

                // Create
                $objectresp = array(
			    	'result'=>array('result_code'=>'OK', 'result_label'=>''),
			        'product'=>array(
				    	'id' => $product->id,
			   			'ref' => $product->ref,
			   			'ref_ext' => $product->ref_ext,
			    		'label' => $product->label,
			    		'description' => $product->description,
			    		'date_creation' => dol_print_date($product->date_creation,'dayhourrfc'),
			    		'date_modification' => dol_print_date($product->date_modification,'dayhourrfc'),
			            'note' => $product->note,
			            'status_tosell' => $product->status,
			            'status_tobuy' => $product->status_buy,
                		'type' => $product->type,
				        'barcode' => $product->barcode,
				        'barcode_type' => $product->barcode_type,
                		'country_id' => $product->country_id>0?$product->country_id:'',
				        'country_code' => $product->country_code,
				        'custom_code' => $product->customcode,

			        	'price_net' => $product->price,
			        	'price' => $product->price_ttc,
			        	'price_min_net' => $product->price_min,
			        	'price_min' => $product->price_min_ttc,
			        	'price_base_type' => $product->price_base_type,
				        'vat_rate' => $product->tva_tx,
				        //! French VAT NPR
				        'vat_npr' => $product->tva_npr,
				        //! Spanish local taxes
				        'localtax1_tx' => $product->localtax1_tx,
				        'localtax2_tx' => $product->localtax2_tx,

                		'price_base_type' => $product->price_base_type,

				        'stock_real' => $product->stock_reel,
                		'stock_alert' => $product->seuil_stock_alerte,
				        'pmp' => $product->pmp,
                		'import_key' => $product->import_key,
                		'dir' => $pdir,
                		'images' => $product->liste_photos($dir,$nbmax=10)
                ));
            }
            else
            {
                $error++;
                $errorcode='NOT_FOUND'; $errorlabel='Object not found for id='.$id.' nor ref='.$ref.' nor ref_ext='.$ref_ext;
            }
        }
        else
        {
            $error++;
            $errorcode='PERMISSION_DENIED'; $errorlabel='User does not have permission for this request';
        }
    }

    if ($error)
    {
        $objectresp = array('result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel));
    }

    return $objectresp;
}


/**
 * Create an invoice
 *
 * @param	array		$authentication		Array of authentication information
 * @param	Product		$product			Product
 * @return	array							Array result
 */
function createProductOrService($authentication,$product)
{
    global $db,$conf,$langs;

    $now=dol_now();

    dol_syslog("Function: createProductOrService login=".$authentication['login']);

    if ($authentication['entity']) $conf->entity=$authentication['entity'];

    // Init and check authentication
    $objectresp=array();
    $errorcode='';$errorlabel='';
    $error=0;
    $fuser=check_authentication($authentication,$error,$errorcode,$errorlabel);
    // Check parameters
    if ($product['price_net'] > 0) $product['price_base_type']='HT';
    if ($product['price'] > 0)     $product['price_base_type']='TTC';

    if ($product['price_net'] > 0 && $product['price'] > 0)
    {
        $error++; $errorcode='KO'; $errorlabel="You must choose between price or price_net to provide price.";
    }


    if (! $error)
    {
        include_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';

        $newobject=new Product($db);
        $newobject->ref=$product['ref'];
        $newobject->ref_ext=$product['ref_ext'];
        $newobject->type=$product['type'];
        $newobject->libelle=$product['label'];    // TODO deprecated
        $newobject->label=$product['label'];
        $newobject->description=$product['description'];
        $newobject->note=$product['note'];
        $newobject->status=$product['status_tosell'];
        $newobject->status_buy=$product['status_tobuy'];
        $newobject->price=$product['price_net'];
        $newobject->price_ttc=$product['price'];
        $newobject->tva_tx=$product['vat_rate'];
        $newobject->price_base_type=$product['price_base_type'];
        $newobject->date_creation=$now;

        $newobject->stock_reel=$product['stock_real'];
        $newobject->pmp=$product['pmp'];
        $newobject->seuil_stock_alert=$product['stock_alert'];

        $newobject->country_id=$product['country_id'];
        if ($product['country_code']) $newobject->country_id=getCountry($product['country_code'],3);
        $newobject->customcode=$product['customcode'];

        $newobject->canvas=$product['canvas'];
        /*foreach($product['lines'] as $line)
        {
            $newline=new FactureLigne($db);
            $newline->type=$line['type'];
            $newline->desc=$line['desc'];
            $newline->fk_product=$line['fk_product'];
            $newline->total_ht=$line['total_net'];
            $newline->total_vat=$line['total_vat'];
            $newline->total_ttc=$line['total'];
            $newline->vat=$line['vat_rate'];
            $newline->qty=$line['qty'];
            $newline->fk_product=$line['product_id'];
        }*/
        //var_dump($product['ref_ext']);
        //var_dump($product['lines'][0]['type']);

        $db->begin();

        $result=$newobject->create($fuser,0);
        if ($result <= 0)
        {
            $error++;
        }

        if (! $error)
        {
            $db->commit();
            $objectresp=array('result'=>array('result_code'=>'OK', 'result_label'=>''),'id'=>$newobject->id,'ref'=>$newobject->ref);
        }
        else
        {
            $db->rollback();
            $error++;
            $errorcode='KO';
            $errorlabel=$newobject->error;
        }

    }

    if ($error)
    {
        $objectresp = array('result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel));
    }

    return $objectresp;
}


/**
 * getListOfProductsOrServices
 *
 * @param	array		$authentication		Array of authentication information
 * @param	array		$filterproduct		Filter fields
 * @return	array							Array result
 */
function getListOfProductsOrServices($authentication,$filterproduct)
{
    global $db,$conf,$langs;

    $now=dol_now();

    dol_syslog("Function: getListOfProductsOrServices login=".$authentication['login']);

    if ($authentication['entity']) $conf->entity=$authentication['entity'];

    // Init and check authentication
    $objectresp=array();
    $arrayproducts=array();
    $errorcode='';$errorlabel='';
    $error=0;
    $fuser=check_authentication($authentication,$error,$errorcode,$errorlabel);
    // Check parameters

    if (! $error)
    {
        $sql ="SELECT rowid, ref, ref_ext";
        $sql.=" FROM ".MAIN_DB_PREFIX."product";
        $sql.=" WHERE entity=".$conf->entity;
        foreach($filterproduct as $key => $val)
        {
        	if ($key == 'type' && $val >= 0)   	$sql.=" AND fk_product_type = ".$db->escape($val);
        	if ($key == 'tosell') 				$sql.=" AND to_sell = ".$db->escape($val);
        	if ($key == 'tobuy')  				$sql.=" AND to_buy = ".$db->escape($val);
        }
		$resql=$db->query($sql);
        if ($resql)
        {
         	$num=$db->num_rows($resql);

         	$i=0;
         	while ($i < $num)
         	{
         		$obj=$db->fetch_object($resql);
         		$arrayproducts[]=array('id'=>$obj->rowid,'ref'=>$obj->ref,'ref_ext'=>$obj->ref_ext);
         		$i++;
         	}
        }
        else
        {
            $error++;
            $errorcode=$db->lasterrno();
            $errorlabel=$db->lasterror();
        }
    }

    if ($error)
    {
        $objectresp = array(
			'result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel),
        	'products'=>$arrayproducts
        );
    }
    else
    {
        $objectresp = array(
			'result'=>array('result_code' => 'OK', 'result_label' => ''),
        	'products'=>$arrayproducts
        );
    }

    return $objectresp;
}


//  return category infos and children
function getProductsForCategory($authentication,$id)
{
	global $db,$conf,$langs;

	dol_syslog("Function: getProductsForCategory login=".$authentication['login']." id=".$id);

	if ($authentication['entity']) $conf->entity=$authentication['entity'];

	$objectresp=array();
	$errorcode='';$errorlabel='';
	$error=0;

	$fuser=check_authentication($authentication,$error,$errorcode,$errorlabel);


	if (! $error && !$id)
	{
		$error++;
		$errorcode='BAD_PARAMETERS'; $errorlabel="Parameter id must be provided.";
	}


	if (! $error)
	{
		$fuser->getrights();

		if ($fuser->rights->produit->lire)
		{
			$categorie=new Categorie($db);
			$result=$categorie->fetch($id);
			if ($result > 0)
			{
				$table = "product";
				$field = "product";
				$sql  = "SELECT fk_".$field." FROM ".MAIN_DB_PREFIX."categorie_".$table;
				$sql .= " WHERE fk_categorie = ".$id;
				$sql .= " ORDER BY fk_".$field." ASC" ;


				dol_syslog("GetProductsForCategory::get_type sql=".$sql);
				$res  = $db->query($sql);
				if ($res)
				{
					while ($rec = $db->fetch_array($res))
					{
						$obj = new Product($db);
						$obj->fetch($rec['fk_'.$field]);
						if($obj->status > 0 )
						{
							$dir = (!empty($conf->product->dir_output)?$conf->product->dir_output:$conf->service->dir_output);
							$pdir = get_exdir($obj->id,2) . $obj->id ."/photos/";
							$dir = $dir . '/'. $pdir;

							$products[] = array(
						    	'id' => $obj->id,
					   			'ref' => $obj->ref,
					   			'ref_ext' => $obj->ref_ext,
					    		'label' => $obj->label,
					    		'description' => $obj->description,
					    		'date_creation' => dol_print_date($obj->date_creation,'dayhourrfc'),
					    		'date_modification' => dol_print_date($obj->date_modification,'dayhourrfc'),
					            'note' => $obj->note,
					            'status_tosell' => $obj->status,
					            'status_tobuy' => $obj->status_buy,
		                		'type' => $obj->type,
						        'barcode' => $obj->barcode,
						        'barcode_type' => $obj->barcode_type,
		                		'country_id' => $obj->country_id>0?$obj->country_id:'',
						        'country_code' => $obj->country_code,
						        'custom_code' => $obj->customcode,

						        'price_net' => $obj->price,
						        'price' => $obj->price_ttc,
						        'vat_rate' => $obj->tva_tx,

								'price_base_type' => $obj->price_base_type,

						        'stock_real' => $obj->stock_reel,
		                		'stock_alert' => $obj->seuil_stock_alerte,
						        'pmp' => $obj->pmp,
		                		'import_key' => $obj->import_key,
		                		'dir' => $pdir,
		                		'images' => $obj->liste_photos($dir,$nbmax=10)
							);
						}

					}

					// Retour
					$objectresp = array(
					'result'=>array('result_code'=>'OK', 'result_label'=>''),
					'products'=> $products
					);

				}
				else
				{
					$errorcode='NORECORDS_FOR_ASSOCIATION'; $errorlabel='No products associated'.$sql;
					$objectresp = array('result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel));
					dol_syslog("getProductsForCategory:: ".$c->error, LOG_DEBUG);

				}
			}
			else
			{
				$error++;
				$errorcode='NOT_FOUND'; $errorlabel='Object not found for id='.$id;
			}
		}
		else
		{
			$error++;
			$errorcode='PERMISSION_DENIED'; $errorlabel='User does not have permission for this request';
		}
	}

	if ($error)
	{
		$objectresp = array('result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel));
	}

	return $objectresp;
}



// Return the results.
$server->service($HTTP_RAW_POST_DATA);

?>
