<?php
/* Copyright (C) 2013	Jean-François Ferry	<jfefe@aternatik.fr>
 * Copyright (C) 2016	Gilles Poirier		<glgpoirier@gmail.com>
 * Copyright (C) 2016   Ion Agorria         <ion@agorria.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file		htdocs/core/lib/resource.lib.php
 *	\ingroup	resource
 *	\brief		This file is library for resource module
 */

/**
 * Prepare head for tabs
 *
 * @param   Dolresource $object    Resource object
 * @return  array                  Array of head entries
 */
function resource_prepare_head($object)
{
	global $langs, $conf;
	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath('/resource/card.php',1).'?id='.$object->id;
	$head[$h][1] = $langs->trans("ResourceCard");
	$head[$h][2] = 'resource';
	$h++;

	if (empty($conf->global->MAIN_DISABLE_CONTACTS_TAB))
	{
	    $nbContact = count($object->liste_contact(-1,'internal')) + count($object->liste_contact(-1,'external'));
	    $head[$h][0] = DOL_URL_ROOT.'/resource/contact.php?id='.$object->id;
		$head[$h][1] = $langs->trans('ContactsAddresses');
		if ($nbContact > 0) $head[$h][1].= ' <span class="badge">'.$nbContact.'</span>';
		$head[$h][2] = 'contact';
		$h++;
	}

    $head[$h][0] = dol_buildpath('/resource/linked.php',1).'?id='.$object->id;
    $head[$h][1] = $langs->trans("Referers");
    $head[$h][2] = 'linked';
    $h++;

    $head[$h][0] = dol_buildpath('/resource/log.php',1).'?id='.$object->id;
    $head[$h][1] = $langs->trans("Records");
    $head[$h][2] = 'log';
    $h++;

    if ($object->management_type == Dolresource::MANAGEMENT_TYPE_SCHEDULE)
    {
        $head[$h][0] = dol_buildpath('/resource/list_schedule.php', 1) . '?id=' . $object->id;
        $head[$h][1] = $langs->trans("Schedule");
        $head[$h][2] = 'schedule';
        $h++;

        $head[$h][0] = dol_buildpath('/resource/list_placement.php', 1) . '?id=' . $object->id;
        $head[$h][1] = $langs->trans("Placements");
        $head[$h][2] = 'placement';
        $h++;
    }

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to remove a tab
	complete_head_from_modules($conf,$langs,$object,$head,$h,'resource');

	if (empty($conf->global->MAIN_DISABLE_NOTES_TAB))
	{
		$nbNote = 0;
		if(!empty($object->note_private)) $nbNote++;
		if(!empty($object->note_public)) $nbNote++;
		$head[$h][0] = DOL_URL_ROOT.'/resource/note.php?id='.$object->id;
		$head[$h][1] = $langs->trans('Notes');
		if ($nbNote > 0) $head[$h][1].= ' <span class="badge">'.$nbNote.'</span>';
		$head[$h][2] = 'note';
		$h++;
	}

	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	$upload_dir = $conf->resource->dir_output . "/" . dol_sanitizeFileName($object->ref);
	$nbFiles = count(dol_dir_list($upload_dir,'files',0,'','(\.meta|_preview\.png)$'));
	$head[$h][0] = DOL_URL_ROOT.'/resource/document.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Documents");
	if($nbFiles > 0) $head[$h][1].= ' <span class="badge">'.$nbFiles.'</span>';
	$head[$h][2] = 'documents';
	$h++;

	/*$head[$h][0] = DOL_URL_ROOT.'/resource/info.php?id='.$object->id;
	$head[$h][1] = $langs->trans('Info');
	$head[$h][2] = 'info';
	$h++;*/

	complete_head_from_modules($conf,$langs,$object,$head,$h,'resource', 'remove');

	return $head;
}

function resource_admin_prepare_head() {

	global $langs, $conf, $user;

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/admin/resource.php';
	$head[$h][1] = $langs->trans("ResourceSetup");
	$head[$h][2] = 'general';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to remove a tab
	complete_head_from_modules($conf,$langs,null,$head,$h,'resource_admin');

	$head[$h][0] = DOL_URL_ROOT.'/admin/resource_extrafields.php';
	$head[$h][1] = $langs->trans("ExtraFields");
	$head[$h][2] = 'attributes';
	$h++;

	complete_head_from_modules($conf,$langs,null,$head,$h,'resource_admin','remove');

	return $head;
}

/**
 *  Check each resources in service line's and occupies if available.
 *
 * @param   CommonObject    $object       Object
 * @param   array           $target       Specific statuses to update only
 * @param   int             $status       Status to set on resources
 * @param   int             $booker_id    Booker id
 * @param   string          $booker_type  Booker type
 * @return  int                           <0 if KO, 0 > if OK
 */
function occupyAllResources($object, $target, $status, $booker_id=null, $booker_type=null)
{
    global $langs, $conf, $hookmanager, $user, $db;

    if (empty($booker_id)) $booker_id = $object->id;
    if (empty($booker_type)) $booker_type = $object->element;
    dol_syslog(__METHOD__." object_id=".$object->id." object_type=".$object->element, LOG_DEBUG);
    dol_syslog("target=".implode(",", $target)."status=".$status." booker_id=".$booker_id." booker_type=".$booker_type, LOG_DEBUG);

    $error = 0;
    $return = null;
    $db->begin();
    $link = new ResourceLink($db);

    if ($object->element == 'propal' || $object->element == 'facture') //Proposal/Bill
    {
        foreach ($object->lines as $line)
        {
            $result = occupyAllResources($line, $target, $status, $booker_id, $booker_type);
            if ($result < 0)
            {
                $error++;
                break;
            }
        }
    }
    else if ($object->element == 'propaldet' || $object->element == 'facturedet') //Proposal/Bill line
    {
        if ($object->fk_product && $object->product_type == Product::TYPE_SERVICE && $object->date_start && $object->date_end)
        {
            $qty = !empty($conf->global->RESOURCE_OCCUPATION_BY_QTY) ? intval($object->qty) : 1;
            $element = 'service';
            $tree = $link->getFullTree($object->fk_product, $element, true, $object->date_start, $object->date_end, $booker_id, $booker_type);
            if (is_numeric($tree))
            {
                setEventMessages($link->error, $link->errors, 'errors');
                $error++;
            }
            else
            {
                $roots = null;

                //Call hook
                $parameters=array(
                    'qty'=>$qty,
                    'tree'=>$tree,
                    'status'=>$status,
                    'booker_id'=>$booker_id,
                    'booker_type'=>$booker_type,
                );
                $action='';
                $reshook=$hookmanager->executeHooks('occupyLineResources', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
                if ($reshook < 0)
                {
                    setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
                    $error++;
                }
                elseif ($reshook == 0 && !empty($hookmanager->resArray))
                {
                    $result = $hookmanager->resArray;
                    if (isset($result['qty'])) $qty = $result['qty'];
                    if (isset($result['tree'])) $tree = $result['tree'];
                    if (isset($result['roots'])) $roots = $result['roots'];
                    if (isset($result['error'])) $error = $result['error'];
                    if (isset($result['return'])) $return = $result['return'];
                }

                if (!$error && !isset($return))
                {
                    //If hook didn't provide the roots calculate from tree
                    if ($roots === null)
                    {
                        $roots = $link->getAvailableRoots($tree, $qty);
                    }

                    //There are root resources? Also check if we need to return hook value
                    $available = $roots['available'];
                    $notavailable = $roots['notavailable'];
                    $total = count($available) + count($notavailable);
                    if ($total > 0)
                    {
                        $need = $roots['need'];
                        $resource_error = '';

                        //Check if qty was not satisfied
                        if ($need > 0)
                        {
                            if (!empty($notavailable) && $need <= $total)
                            {
                                $trans = ResourceStatus::translated();
                                foreach ($notavailable as $id => $data)
                                {
                                    $status_priority = $data['status_priority'];
                                    $resource_error = $langs->transnoentities('ResourceStatus', $data['path'], $trans[$status_priority]);
                                    break;
                                }
                            }
                            else
                            {
                                $resource_error = $langs->transnoentities('ErrorResourceNotEnoughAvailable', $need);
                            }
                        }

                        if (!empty($resource_error))
                        {
                            setEventMessages($langs->transnoentities('ErrorServiceResource', $object->product_label, $resource_error), null, 'errors');
                            $error++;
                        }
                        else
                        {
                            //Free any resources that we have taken before occupying
                            foreach ($tree as $data)
                            {
                                if ($data['status'] == ResourceStatus::TAKEN)
                                {
                                    /** @var Dolresource $resource */
                                    $resource = $data['resource'];
                                    $result = $resource->freeResource($user, $object->date_start, $object->date_end, $status, $booker_id, $booker_type);
                                    if ($result < 0)
                                    {
                                        setEventMessages($resource->ref." ".$resource->error, $resource->errors, 'errors');
                                        $error++;
                                        break;
                                    }
                                }
                            }

                            //Iterate each available roots and occupy their dependant resources
                            require_once DOL_DOCUMENT_ROOT.'/resource/class/resourcelog.class.php';
                            foreach ($available as $root)
                            {
                                foreach ($root['dependency'] as $id => $resource)
                                {
                                    /** @var Dolresource $resource */
                                    $result = $resource->setStatus($user, $object->date_start, $object->date_end, $target, $status, $booker_id, $booker_type, false, ResourceLog::RESOURCE_OCCUPY);
                                    if ($result < 0)
                                    {
                                        setEventMessages($resource->ref." ".$resource->error, $resource->errors, 'errors');
                                        $error++;
                                        break 2;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    // Commit or rollback
    if ($error)
    {
        $db->rollback();
        return -1*$error;
    }
    else
    {
        $db->commit();
        return isset($return) ? $return : 1;
    }
}

/**
 *  Switches all resources associated to this object to another booker
 *
 * @param   CommonObject    $object             Object
 * @param   int             $status             Status
 * @param   int             $booker_id          Booker id
 * @param   string          $booker_type        Booker type
 * @param   int             $new_booker_id      New booker id
 * @param   string          $new_booker_type    New booker type
 * @return  int                                 <0 if KO, 0 > if OK
 */
function switchAllResources($object, $status, $booker_id, $booker_type, $new_booker_id, $new_booker_type)
{
    global $langs, $hookmanager, $user, $db;

    dol_syslog(__METHOD__." object_id=".$object->id." object_type=".$object->element." new_booker_id=".$new_booker_id." new_booker_type=".$new_booker_type, LOG_DEBUG);
    dol_syslog("status=".$status." booker_id=".$booker_id." booker_type=".$booker_type, LOG_DEBUG);

    $error = 0;
    $return = null;
    $db->begin();
    $link = new ResourceLink($db);

    if ($object->element == 'propal' || $object->element == 'facture') //Proposal/Bill
    {
        foreach ($object->lines as $line)
        {
            $result = switchAllResources($line, $status, $booker_id, $booker_type, $new_booker_id, $new_booker_type);
            if ($result < 0)
            {
                $error++;
                break;
            }
        }
    }
    else if ($object->element == 'propaldet' || $object->element == 'facturedet') //Proposal/Bill line
    {
        if ($object->fk_product && $object->product_type == Product::TYPE_SERVICE && $object->date_start && $object->date_end)
        {
            //Get all linked resources
            $resources = $link->getResourcesLinked($object->fk_product, 'service');
            if (is_int($resources) && $resources < 0)
            {
                setEventMessages($link->error, $link->errors, 'errors');
                $error++;
            }
            else
            {
                //Call hook
                $parameters=array(
                    'resources'=>$resources,
                    'status'=>$status,
                    'booker_id'=>$booker_id,
                    'booker_type'=>$booker_type,
                );
                $action='';
                $reshook=$hookmanager->executeHooks('switchLineResources', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
                if ($reshook < 0)
                {
                    setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
                    $error++;
                }
                elseif ($reshook == 0 && !empty($hookmanager->resArray))
                {
                    $result = $hookmanager->resArray;
                    if (isset($result['resources'])) $resources = $result['resources'];
                    if (isset($result['error'])) $error = $result['error'];
                    if (isset($result['return'])) $return = $result['return'];
                }

                // Switch taken resources
                if (!$error && !isset($return))
                {
                    foreach ($resources as $resource_id => $resource_type)
                    {
                        //Fetch resource
                        $resource = fetchObjectByElement($resource_id, $resource_type);
                        if (!is_object($resource) || $resource->id != $resource_id)
                        {
                            setEventMessages($langs->trans('ErrorResourceUnknown', $resource_id, $resource_type), null, 'errors');
                            $error++;
                            break;
                        }
                        
                        //Get resource status
                        $res_status = $resource->getStatus($object->date_start, $object->date_end, $booker_id, $booker_type);
                        if ($res_status < 0)
                        {
                            setEventMessages($resource->error, $resource->errors, 'errors');
                            $error++;
                            break;
                        }
                        
                        //Switch the resource if taken by the current booker
                        if ($res_status == ResourceStatus::TAKEN)
                        {
                            $result = $resource->switchResource($user, $object->date_start, $object->date_end, $status, $booker_id, $booker_type, $new_booker_id, $new_booker_type);
                            if ($result < 0)
                            {
                                setEventMessages($resource->error, $resource->errors, 'errors');
                                $error++;
                                break;
                            }
                        }
                    }
                }
            }
        }
    }

    // Commit or rollback
    if ($error)
    {
        $db->rollback();
        return -1*$error;
    }
    else
    {
        $db->commit();
        return isset($return) ? $return : 1;
    }
}

/**
 *  Frees all resources associated to this object
 *
 * @param   CommonObject    $object       Object
 * @param   int             $status       Status to restore from
 * @param   int             $booker_id    Booker id
 * @param   string          $booker_type  Booker type
 * @return  int                           <0 if KO, 0 > if OK
 */

function freeAllResources($object, $status, $booker_id=null, $booker_type=null)
{
    global $langs, $hookmanager, $user, $db;

    if (empty($booker_id)) $booker_id = $object->id;
    if (empty($booker_type)) $booker_type = $object->element;
    dol_syslog(__METHOD__." object_id=".$object->id." object_type=".$object->element, LOG_DEBUG);
    dol_syslog("status=".$status." booker_id=".$booker_id." booker_type=".$booker_type, LOG_DEBUG);

    $error = 0;
    $return = null;
    $db->begin();
    $link = new ResourceLink($db);

    if ($object->element == 'propal' || $object->element == 'facture') //Proposal/Bill
    {
        foreach ($object->lines as $line)
        {
            $result = freeAllResources($line, $status, $booker_id, $booker_type);
            if ($result < 0)
            {
                $error++;
                break;
            }
        }
    }
    else if ($object->element == 'propaldet' || $object->element == 'facturedet') //Proposal/Bill line
    {
        if ($object->fk_product && $object->product_type == Product::TYPE_SERVICE && $object->date_start && $object->date_end)
        {
            //Get all linked resources
            $resources = $link->getResourcesLinked($object->fk_product, 'service');
            if (is_int($resources) && $resources < 0)
            {
                setEventMessages($link->error, $link->errors, 'errors');
                $error++;
            }
            else
            {
                //Call hook
                $parameters=array(
                    'resources'=>$resources,
                    'status'=>$status,
                    'booker_id'=>$booker_id,
                    'booker_type'=>$booker_type,
                );
                $action='';
                $reshook=$hookmanager->executeHooks('freeLineResources', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
                if ($reshook < 0)
                {
                    setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
                    $error++;
                }
                elseif ($reshook == 0 && !empty($hookmanager->resArray))
                {
                    $result = $hookmanager->resArray;
                    if (isset($result['resources'])) $resources = $result['resources'];
                    if (isset($result['error'])) $error = $result['error'];
                    if (isset($result['return'])) $return = $result['return'];
                }

                // Free each resource
                if (!$error && !isset($return))
                {
                    foreach ($resources as $resource_id => $resource_type)
                    {
                        $resource = fetchObjectByElement($resource_id, $resource_type);
                        if (!is_object($resource) || $resource->id != $resource_id)
                        {
                            setEventMessages($langs->trans('ErrorResourceUnknown', $resource_id, $resource_type), null, 'errors');
                            $error++;
                            break;
                        }
                        $result = $resource->freeResource($user, $object->date_start, $object->date_end, $status, $booker_id, $booker_type);
                        if ($result < 0)
                        {
                            setEventMessages($resource->error, $resource->errors, 'errors');
                            $error++;
                            break;
                        }
                    }
                }
            }
        }
    }
    else if ($object->element == 'action') //Event
    {
        if ($object->datep && $object->datef)
        {
            //Get all linked resources
            $resources = $link->getResourcesLinked($object->id, $object->element);
            if (is_int($resources) && $resources < 0)
            {
                setEventMessages($link->error, $link->errors, 'errors');
                $error++;
            }
            else
            {
                // Free each resource
                if (!$error && !isset($return))
                {
                    foreach ($resources as $resource_id => $resource_type)
                    {
                        $resource = fetchObjectByElement($resource_id, $resource_type);
                        if (!is_object($resource) || $resource->id != $resource_id)
                        {
                            setEventMessages($langs->trans('ErrorResourceUnknown', $resource_id, $resource_type), null, 'errors');
                            $error++;
                            break;
                        }
                        $result = $resource->freeResource($user, $object->datep, $object->datef, $status, $booker_id, $booker_type);
                        if ($result < 0)
                        {
                            setEventMessages($resource->error, $resource->errors, 'errors');
                            $error++;
                            break;
                        }
                    }
                }
            }
        }
    }

    // Commit or rollback
    if ($error)
    {
        $db->rollback();
        return -1*$error;
    }
    else
    {
        $db->commit();
        return isset($return) ? $return : 1;
    }
}
