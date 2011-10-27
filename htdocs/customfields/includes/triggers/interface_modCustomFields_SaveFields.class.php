<?php
/* Copyright (C) 2011   Stephen Larroque <lrq3000@gmail.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 *      \file       htdocs/includes/triggers/interface_modCustomFields_SaveFields.class.php
 *      \ingroup    core
 *      \brief      Core triggers file for CustomFields module. Triggers actions for the customfields module. Necessary for actions to be comitted.
 *		\version	$Id: interface_modCustomFields_SaveFields.class.php, v1.2.2
 */


/**
 *      \class      InterfaceSaveFields
 *      \brief      Class of triggers for demo module
 */
class InterfaceSaveFields
{
    var $db;

    /**
     *   Constructor.
     *   @param      DB      Database handler
     */
    function InterfaceSaveFields($DB)
    {
        $this->db = $DB ;

        $this->name = preg_replace('/^Interface/i','',get_class($this));
        $this->family = "module";
        $this->description = "Triggers actions for the customfields module. Necessary for actions to be comitted.";
        $this->version = 'dolibarr';            // 'development', 'experimental', 'dolibarr' or version
        $this->picto = 'technic';
    }


    /**
     *   Return name of trigger file
     *   @return     string      Name of trigger file
     */
    function getName()
    {
        return $this->name;
    }

    /**
     *   Return description of trigger file
     *   @return     string      Description of trigger file
     */
    function getDesc()
    {
        return $this->description;
    }

    /**
     *   Return version of trigger file
     *   @return     string      Version of trigger file
     */
    function getVersion()
    {
        global $langs;
        $langs->load("admin");

        if ($this->version == 'development') return $langs->trans("Development");
        elseif ($this->version == 'experimental') return $langs->trans("Experimental");
        elseif ($this->version == 'dolibarr') return DOL_VERSION;
        elseif ($this->version) return $this->version;
        else return $langs->trans("Unknown");
    }

    /**
     *      Function called when a Dolibarrr business event is done.
     *      All functions "run_trigger" are triggered if file is inside directory htdocs/includes/triggers
     *      @param      action      Code de l'evenement
     *      @param      object      Objet concerne
     *      @param      user        Objet user
     *      @param      langs       Objet langs
     *      @param      conf        Objet conf
     *      @return     int         <0 if KO, 0 if no triggered ran, >0 if OK
     */
	function run_trigger($action,$object,$user,$langs,$conf)
    {
        // Put here code you want to execute when a Dolibarr business events occurs.
        // Data and type of action are stored into $object and $action

	foreach ($_POST as $key=>$value) { // Generic way to fill all the fields to the object (particularly useful for triggers and customfields) - NECESSARY to get the fields' values
	    $object->$key = $value;
	}

        // Products and services
        if($action == 'PRODUCT_CREATE') {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);

            $action = 'CUSTOMFIELDS_CREATE';
            $object->currentmodule = 'product';
            return $this->run_trigger($action,$object,$user,$langs,$conf);
        }
        elseif ($action == 'PRODUCT_CLONE') {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);

            $action = 'CUSTOMFIELDS_CLONE';
            $object->currentmodule = 'product';
            $object->origin_id = GETPOST('id'); // the clone functions do not store the origin_id in the standard dolibarr package (as of v3.1b)
            return $this->run_trigger($action,$object,$user,$langs,$conf);
        }

        // Proposals
        elseif ($action == 'PROPAL_CREATE') {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);

            $action = 'CUSTOMFIELDS_CREATE';
            $object->currentmodule = 'propal';
            return $this->run_trigger($action,$object,$user,$langs,$conf);
        }
        elseif ($action == 'PROPAL_CLONE') {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);

            $action = 'CUSTOMFIELDS_CLONE';
            $object->currentmodule = 'propal';
            $object->origin_id = GETPOST('id'); // the clone functions do not store the origin_id in the standard dolibarr package (as of v3.1b)
            return $this->run_trigger($action,$object,$user,$langs,$conf);
        }
        /* Managed by the customfields.lib.php for edition and by the SQL cascading for deletion
        elseif ($action == 'PROPAL_MODIFY') {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        }
        elseif ($action == 'PROPAL_DELETE') {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        } */
        elseif($action == 'PROPAL_PREBUILDDOC') {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);

            $action = 'CUSTOMFIELDS_PREBUILDDOC';
            $object->currentmodule = 'propal';
            return $this->run_trigger($action,$object,$user,$langs,$conf);
        }

        // Bills - Invoices
        elseif ($action == 'BILL_CREATE') {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);

            $action = 'CUSTOMFIELDS_CREATE';
            $object->currentmodule = 'facture';
            return $this->run_trigger($action,$object,$user,$langs,$conf);
        }
        elseif ($action == 'BILL_CLONE') {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);

            $action = 'CUSTOMFIELDS_CLONE';
            $object->currentmodule = 'facture';
            $object->origin_id = GETPOST('facid'); // the clone functions do not store the origin_id in the standard dolibarr package (as of v3.1b)
            return $this->run_trigger($action,$object,$user,$langs,$conf);
        }
        /* Managed by the customfields.lib.php for edition and by the SQL cascading for deletion
        elseif ($action == 'BILL_MODIFY') {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        }
        elseif ($action == 'BILL_DELETE') {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        }
        */
        elseif ($action == 'BILL_PREBUILDDOC') {
            $action = 'CUSTOMFIELDS_PREBUILDDOC';
            $object->currentmodule = 'facture';
            return $this->run_trigger($action,$object,$user,$langs,$conf);
        }


        /********************************** GENERIC CUSTOMFIELDS ACTION TRIGGERS **********************************/
        // Description: to avoid duplicating code in triggers, here are a few generic dummy customfields triggers, they are never triggered by any module but here you can use them recursively to activate them (eg: you get a BILL_CREATE trigger, just call run_trigger() with $action=CUSTOMFIELDS_CREATE and pass on the other arguments you received and you're done)

        elseif ($action == 'CUSTOMFIELDS_CREATE') { // Create a record
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);

            // Vars
            $currentmodule = $object->currentmodule;

            // Init and main vars
            include_once(DOL_DOCUMENT_ROOT.'/customfields/class/customfields.class.php');
            $customfields = new CustomFields($this->db, $currentmodule);

            // Saving the data (creating a record)
            $rtncode = $customfields->create($object);

            // Print errors (if there are)
            if (!empty($customfields->error) and strpos(strtolower($customfields->error), "Table '".$customfields->moduletable."' doesn't exist")) { // if the error is that the table doesn't exists, we ignore it because it is probably because the user does not use CustomFields for this module
                dol_print_error($this->db, $customfields->error);
            }

            return $rtncode;
        }
        /* DELETION is automatically managed by the SGBD (sql) thank's to the constraints
        elseif ($action == 'CUSTOMFIELDS_DELETE') {
        }*/
        elseif ($action == 'CUSTOMFIELDS_MODIFY') { // Modify a record (UNUSED because automatically managed by the customfields lib AND by the SQL constraints/triggers/check, this function here is just for example or possible future use, but now it's not used)
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);

            // Vars
            $currentmodule = $object->currentmodule;

            // Init and main vars
            include_once(DOL_DOCUMENT_ROOT.'/customfields/class/customfields.class.php');
            $customfields = new CustomFields($this->db, $currentmodule);

            // Saving the data (creating a record)
            $rtncode = $customfields->update($object);

            // Print errors (if there are)
            if (!empty($customfields->error) and strpos(strtolower($customfields->error), "Table '".$customfields->moduletable."' doesn't exist")) { // if the error is that the table doesn't exists, we ignore it because it is probably because the user does not use CustomFields for this module
                dol_print_error($this->db, $customfields->error);
            }

            return $rtncode;
        }
        elseif ($action == 'CUSTOMFIELDS_CLONE') { // Clone a record
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);

            // Vars
            $currentmodule = $object->currentmodule;

            // Init and main vars
            include_once(DOL_DOCUMENT_ROOT.'/customfields/class/customfields.class.php');
            $customfields = new CustomFields($this->db, $currentmodule);

            // Saving the data (creating a record)
            $rtncode = $customfields->createFromClone($object->origin_id, $object->id);

            // Print errors (if there are)
            if (!empty($customfields->error) and strpos(strtolower($customfields->error), "Table '".$customfields->moduletable."' doesn't exist")) { // if the error is that the table doesn't exists, we ignore it because it is probably because the user does not use CustomFields for this module
                dol_print_error($this->db, $customfields->error);
            }

            return $rtncode;
        }
        elseif ($action == 'CUSTOMFIELDS_PREBUILDDOC') {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);

            // Vars
            $currentmodule = $object->currentmodule;

            // Init and main vars
            include_once(DOL_DOCUMENT_ROOT.'/customfields/class/customfields.class.php');
            $customfields = new CustomFields($this->db, $currentmodule);

            // Fetching the list of fields columns
            $fields = $customfields->fetchAllCustomFields();

            // Fetching customfields data
            $record = $customfields->fetch($object->id);

            // Appending these properties to the $object which will be passed to the invoice's template
            foreach ($fields as $field) {
                $name = $field->column_name;
                $prefixedname = $customfields->varprefix.$field->column_name;
                $object->$prefixedname = $record->$name;
                $object->customfields->$name = $field; // we maintain a list of the customfields, so that later in the PDF or ODT we can easily list all the fields (we need the column_name and the column_type but we mirror the entire field)

                /** A little example of the resulting  $object :
                 *  you will get the usual $object with all the dolibar datas, then you add :
                 *  $object->customfieldname gives you the value of customfieldname (replace customfieldname by your custom field - and don't forget the prefix!)
                 *  $object->customfields->customfieldname gives you the sql parameter of this field (column_type, column_name, etc...). Eg:
                 *  $object->customfields->customfieldname->column_type gives you the type of the field (needed to use printFieldPDF efficiently)
                 *
                 *  For example, you can loop through each field this way :
                 *  foreach ($object->customfields as $field) {
			$name = $customfields->varprefix.$field->column_name; // name of the property (this is one customfield)
			$translatedname = $outputlangs->trans($field->column_name); // label of the customfield
			$value = $customfields->printFieldPDF($field, $object->$name, $outputlangs); // value (cleaned and properly formatted) of the customfield

                        $pdf->MultiCell(0,3, $translatedname.': '.$value, 0, 'L'); // printing the customfield
			$pdf->SetY($pdf->GetY()+1); // line return for the next printing
                    }

                    Exactly as if you'd have fetched the record from the database :
                    $fields = $customfields->fetchAllCustomFields();
                    foreach ($fields as $field) {
                        etc... same as above

                    }
                 */
            }

            return 1;
        }
	else { // Generic trigger
	    include(DOL_DOCUMENT_ROOT."/customfields/conf/conf_customfields.lib.php");

	    // Generic trigger based on the trigger array
	    if (preg_match('/^('.implode('|',array_keys($triggersarray)).')$/i', $action, $matches) ) { // if the current action is on a supported trigger action
		dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);

		$object->currentmodule = $triggersarray[strtolower($matches[1])]; // find the right module from the triggersarray (key_trigger=>value_module)

		preg_match('/^(.*)_((CREATE|PREBUILDDOC|CLONE).*)$/i', $action, $matches);
		$action = 'CUSTOMFIELDS_'.$matches[2]; // forge the right customfields trigger
		return $this->run_trigger($action,$object,$user,$langs,$conf);
	    }

	    // Generic trigger based on contexts and module's name
	    $patternsarray = array();
	    foreach ($modulesarray as $context => $module) { // we create a pattern for regexp with contexts and modules names mixed
		$patternsarray[] = addslashes($module);
		$patternsarray[] = $context;
	    }
	    $patterns_flattened = implode('|',$patternsarray); // we flatten the patterns array in a single regexp OR pattern
	    if (preg_match('/^('.$patterns_flattened.')_((CREATE|PREBUILDDOC|CLONE).*)$/i', $action, $matches) ) { // if the current action is on a supported module or context, and the action is supported (for the moment only CREATE, PREBUILDDOC and CLONE)
		$triggername = $matches[1]; // module's name
		$triggeraction = $matches[2]; // action name (create, modify, delete, clone, builddoc, prebuilddoc, etc.)
		dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);

		$action = 'CUSTOMFIELDS_'.$triggeraction;
		if ($this->in_arrayi($triggername, $modulesarray)) { // Either we have a value (module) that matched, or a key (context)
		    $object->currentmodule = strtolower($triggername); // value (module) matched
		} else {
		    $object->currentmodule = $modulesarray[strtolower($triggername)]; // key (context) matched
		}
		return $this->run_trigger($action,$object,$user,$langs,$conf);
	    }
	}

	return 0;
    }

}
?>
