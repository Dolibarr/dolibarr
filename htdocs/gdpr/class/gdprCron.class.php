<?php

class gdprCron {

    public function exec() {
        global $conf, $db, $langs, $user;

        $langs->load('gdpr@gdpr');

        $arrayofparameters=array(
            'RGPD_TIERS_CLIENT' => array(
                'sql' => "
                    SELECT s.rowid FROM ".MAIN_DB_PREFIX."societe as s
                    WHERE (s.fk_forme_juridique = 19 OR s.fk_forme_juridique = 11 OR s.fk_forme_juridique = 12 OR s.fk_forme_juridique = 13 OR s.fk_forme_juridique = 15 OR s.fk_forme_juridique = 17 OR s.fk_forme_juridique = 18 OR s.fk_forme_juridique = 35 OR s.fk_forme_juridique = 60 OR s.fk_forme_juridique = 312 OR s.fk_forme_juridique = 316 OR s.fk_forme_juridique = 401 OR s.fk_forme_juridique = 600 OR s.fk_forme_juridique = 700 OR s.fk_forme_juridique = 1005 OR s.fk_typent = 8)
                    AND s.entity = %d
                    AND s.client = 1
                    AND s.fournisseur = 0
                    AND s.tms < DATE_SUB(NOW(), INTERVAL %d MONTH)
                    AND s.rowid NOT IN (
                        SELECT DISTINCT a.fk_soc
                        FROM ".MAIN_DB_PREFIX."actioncomm as a
                        WHERE a.tms > DATE_SUB(NOW(), INTERVAL %d MONTH)
                        AND a.fk_soc IS NOT NULL
                    )
                ",
                "class" => "Societe",
                "file" => DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php',
                'fields_anonym' => array(
                    'name' => $langs->trans('ANONYME'),
                    'name_bis' => '',
                    'name_alias' => '',
                    'address' => '',
                    'town' => '',
                    'zip' => '',
                    'phone' => '',
                    'email' => '',
                    'url' => '',
                    'fax' => '',
                    'state' => '',
                    'country' => '',
                    'state_id' => '',
                    'skype' => '',
                    'country_id' => '',
                )
            ),
            'RGPD_TIERS_PROSPECT' => array(
                'sql' => "
                    SELECT s.rowid FROM ".MAIN_DB_PREFIX."societe as s
                    WHERE (s.fk_forme_juridique = 19 OR s.fk_forme_juridique = 11 OR s.fk_forme_juridique = 12 OR s.fk_forme_juridique = 13 OR s.fk_forme_juridique = 15 OR s.fk_forme_juridique = 17 OR s.fk_forme_juridique = 18 OR s.fk_forme_juridique = 35 OR s.fk_forme_juridique = 60 OR s.fk_forme_juridique = 312 OR s.fk_forme_juridique = 316 OR s.fk_forme_juridique = 401 OR s.fk_forme_juridique = 600 OR s.fk_forme_juridique = 700 OR s.fk_forme_juridique = 1005 OR s.fk_typent = 8)
                    AND s.entity = %d
                    AND s.client = 2
                    AND s.fournisseur = 0
                    AND s.tms < DATE_SUB(NOW(), INTERVAL %d MONTH)
                    AND s.rowid NOT IN (
                        SELECT DISTINCT a.fk_soc
                        FROM ".MAIN_DB_PREFIX."actioncomm as a
                        WHERE a.tms > DATE_SUB(NOW(), INTERVAL %d MONTH)
                        AND a.fk_soc IS NOT NULL
                    )
                ",
                "class" => "Societe",
                "file" => DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php',
                'fields_anonym' => array(
                    'name' => $langs->trans('ANONYME'),
                    'name_bis' => '',
                    'name_alias' => '',
                    'address' => '',
                    'town' => '',
                    'zip' => '',
                    'phone' => '',
                    'email' => '',
                    'url' => '',
                    'fax' => '',
                    'state' => '',
                    'country' => '',
                    'state_id' => '',
                    'skype' => '',
                    'country_id' => '',
                )
            ),
            'RGPD_TIERS_PROSPECT_CLIENT' => array(
                'sql' => "
                    SELECT s.rowid FROM ".MAIN_DB_PREFIX."societe as s
                    WHERE (s.fk_forme_juridique = 19 OR s.fk_forme_juridique = 11 OR s.fk_forme_juridique = 12 OR s.fk_forme_juridique = 13 OR s.fk_forme_juridique = 15 OR s.fk_forme_juridique = 17 OR s.fk_forme_juridique = 18 OR s.fk_forme_juridique = 35 OR s.fk_forme_juridique = 60 OR s.fk_forme_juridique = 312 OR s.fk_forme_juridique = 316 OR s.fk_forme_juridique = 401 OR s.fk_forme_juridique = 600 OR s.fk_forme_juridique = 700 OR s.fk_forme_juridique = 1005 OR s.fk_typent = 8)
                    AND s.entity = %d
                    AND s.client = 3
                    AND s.fournisseur = 0
                    AND s.tms < DATE_SUB(NOW(), INTERVAL %d MONTH)
                    AND s.rowid NOT IN (
                        SELECT DISTINCT a.fk_soc
                        FROM ".MAIN_DB_PREFIX."actioncomm as a
                        WHERE a.tms > DATE_SUB(NOW(), INTERVAL %d MONTH)
                        AND a.fk_soc IS NOT NULL
                    )
                ",
                "class" => "Societe",
                "file" => DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php',
                'fields_anonym' => array(
                    'name' => $langs->trans('ANONYME'),
                    'name_bis' => '',
                    'name_alias' => '',
                    'address' => '',
                    'town' => '',
                    'zip' => '',
                    'phone' => '',
                    'email' => '',
                    'url' => '',
                    'fax' => '',
                    'state' => '',
                    'country' => '',
                    'state_id' => '',
                    'skype' => '',
                    'country_id' => '',
                )
            ),
            'RGPD_TIERS_NIPROSPECT_NICLIENT' => array(
                'sql' => "
                    SELECT s.rowid FROM ".MAIN_DB_PREFIX."societe as s
                    WHERE (s.fk_forme_juridique = 19 OR s.fk_forme_juridique = 11 OR s.fk_forme_juridique = 12 OR s.fk_forme_juridique = 13 OR s.fk_forme_juridique = 15 OR s.fk_forme_juridique = 17 OR s.fk_forme_juridique = 18 OR s.fk_forme_juridique = 35 OR s.fk_forme_juridique = 60 OR s.fk_forme_juridique = 312 OR s.fk_forme_juridique = 316 OR s.fk_forme_juridique = 401 OR s.fk_forme_juridique = 600 OR s.fk_forme_juridique = 700 OR s.fk_forme_juridique = 1005 OR s.fk_typent = 8)
                    AND s.entity = %d
                    AND s.client = 0
                    AND s.fournisseur = 0
                    AND s.tms < DATE_SUB(NOW(), INTERVAL %d MONTH)
                    AND s.rowid NOT IN (
                        SELECT DISTINCT a.fk_soc
                        FROM ".MAIN_DB_PREFIX."actioncomm as a
                        WHERE a.tms > DATE_SUB(NOW(), INTERVAL %d MONTH)
                        AND a.fk_soc IS NOT NULL
                    )
                ",
                "class" => "Societe",
                "file" => DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php',
                'fields_anonym' => array(
                    'name' => $langs->trans('ANONYME'),
                    'name_bis' => '',
                    'name_alias' => '',
                    'address' => '',
                    'town' => '',
                    'zip' => '',
                    'phone' => '',
                    'email' => '',
                    'url' => '',
                    'fax' => '',
                    'state' => '',
                    'country' => '',
                    'state_id' => '',
                    'skype' => '',
                    'country_id' => '',
                )
            ),
            'RGPD_TIERS_FOURNISSEUR' => array(
                'sql' => "
                    SELECT s.rowid FROM ".MAIN_DB_PREFIX."societe as s
                    WHERE (s.fk_forme_juridique = 19 OR s.fk_forme_juridique = 11 OR s.fk_forme_juridique = 12 OR s.fk_forme_juridique = 13 OR s.fk_forme_juridique = 15 OR s.fk_forme_juridique = 17 OR s.fk_forme_juridique = 18 OR s.fk_forme_juridique = 35 OR s.fk_forme_juridique = 60 OR s.fk_forme_juridique = 312 OR s.fk_forme_juridique = 316 OR s.fk_forme_juridique = 401 OR s.fk_forme_juridique = 600 OR s.fk_forme_juridique = 700 OR s.fk_forme_juridique = 1005 OR s.fk_typent = 8)
                    AND s.entity = %d
                    AND s.fournisseur = 1
                    AND s.tms < DATE_SUB(NOW(), INTERVAL %d MONTH)
                    AND s.rowid NOT IN (
                        SELECT DISTINCT a.fk_soc
                        FROM ".MAIN_DB_PREFIX."actioncomm as a
                        WHERE a.tms > DATE_SUB(NOW(), INTERVAL %d MONTH)
                        AND a.fk_contact IS NOT NULL
                    )
                ",
                "class" => "Societe",
                "file" => DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php',
                'fields_anonym' => array(
                    'name' => $langs->trans('ANONYME'),
                    'name_bis' => '',
                    'name_alias' => '',
                    'address' => '',
                    'town' => '',
                    'zip' => '',
                    'phone' => '',
                    'email' => '',
                    'url' => '',
                    'fax' => '',
                    'state' => '',
                    'country' => '',
                    'state_id' => '',
                    'skype' => '',
                    'country_id' => '',
                )
            ),
            'RGPD_CONTACT_CLIENT' => array(
                'sql' => "
                    SELECT c.rowid FROM ".MAIN_DB_PREFIX."socpeople as c
                    INNER JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = c.fk_soc
                    WHERE c.entity = %d
                    AND c.tms < DATE_SUB(NOW(), INTERVAL %d MONTH)
                    AND s.client = 1
                    AND s.fournisseur = 0
                    AND c.rowid NOT IN (
                        SELECT DISTINCT a.fk_contact
                        FROM ".MAIN_DB_PREFIX."actioncomm as a
                        WHERE a.tms > DATE_SUB(NOW(), INTERVAL %d MONTH)
                        AND a.fk_contact IS NOT NULL
                    )
                ",
                "class" => "Contact",
                "file" => DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php',
                'fields_anonym' => array(
                    'lastname' => $langs->trans('ANONYME'),
                    'firstname' => '',
                    'civility_id' => '',
                    'poste' => '',
                    'address' => '',
                    'town' => '',
                    'zip' => '',
                    'phone_pro' => '',
                    'phone_perso' => '',
                    'phone_mobile' => '',
                    'email' => '',
                    'url' => '',
                    'fax' => '',
                    'state' => '',
                    'country' => '',
                    'state_id' => '',
                    'skype' => '',
                    'jabberid' => '',
                    'country_id' => '',
                )
            ),
            'RGPD_CONTACT_PROSPECT' => array(
                'sql' => "
                    SELECT c.rowid FROM ".MAIN_DB_PREFIX."socpeople as c
                    INNER JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = c.fk_soc
                    WHERE c.entity = %d
                    AND c.tms < DATE_SUB(NOW(), INTERVAL %d MONTH)
                    AND s.client = 2
                    AND s.fournisseur = 0
                    AND c.rowid NOT IN (
                        SELECT DISTINCT a.fk_contact
                        FROM ".MAIN_DB_PREFIX."actioncomm as a
                        WHERE a.tms > DATE_SUB(NOW(), INTERVAL %d MONTH)
                        AND a.fk_contact IS NOT NULL
                    )
                ",
                "class" => "Contact",
                "file" => DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php',
                'fields_anonym' => array(
                    'lastname' => $langs->trans('ANONYME'),
                    'firstname' => '',
                    'civility_id' => '',
                    'poste' => '',
                    'address' => '',
                    'town' => '',
                    'zip' => '',
                    'phone_pro' => '',
                    'phone_perso' => '',
                    'phone_mobile' => '',
                    'email' => '',
                    'url' => '',
                    'fax' => '',
                    'state' => '',
                    'country' => '',
                    'state_id' => '',
                    'skype' => '',
                    'jabberid' => '',
                    'country_id' => '',
                )
            ),
            'RGPD_CONTACT_PROSPECT_CLIENT' => array(
                'sql' => "
                    SELECT c.rowid FROM ".MAIN_DB_PREFIX."socpeople as c
                    INNER JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = c.fk_soc
                    WHERE c.entity = %d
                    AND c.tms < DATE_SUB(NOW(), INTERVAL %d MONTH)
                    AND s.client = 3
                    AND s.fournisseur = 0
                    AND c.rowid NOT IN (
                        SELECT DISTINCT a.fk_contact
                        FROM ".MAIN_DB_PREFIX."actioncomm as a
                        WHERE a.tms > DATE_SUB(NOW(), INTERVAL %d MONTH)
                        AND a.fk_contact IS NOT NULL
                    )
                ",
                "class" => "Contact",
                "file" => DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php',
                'fields_anonym' => array(
                    'lastname' => $langs->trans('ANONYME'),
                    'firstname' => '',
                    'civility_id' => '',
                    'poste' => '',
                    'address' => '',
                    'town' => '',
                    'zip' => '',
                    'phone_pro' => '',
                    'phone_perso' => '',
                    'phone_mobile' => '',
                    'email' => '',
                    'url' => '',
                    'fax' => '',
                    'state' => '',
                    'country' => '',
                    'state_id' => '',
                    'skype' => '',
                    'jabberid' => '',
                    'country_id' => '',
                )
            ),
            'RGPD_CONTACT_NIPROSPECT_NICLIENT' => array(
                'sql' => "
                    SELECT c.rowid FROM ".MAIN_DB_PREFIX."socpeople as c
                    INNER JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = c.fk_soc
                    WHERE c.entity = %d
                    AND c.tms < DATE_SUB(NOW(), INTERVAL %d MONTH)
                    AND s.client = 0
                    AND s.fournisseur = 0
                    AND c.rowid NOT IN (
                        SELECT DISTINCT a.fk_contact
                        FROM ".MAIN_DB_PREFIX."actioncomm as a
                        WHERE a.tms > DATE_SUB(NOW(), INTERVAL %d MONTH)
                        AND a.fk_contact IS NOT NULL
                    )
                ",
                "class" => "Contact",
                "file" => DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php',
                'fields_anonym' => array(
                    'lastname' => $langs->trans('ANONYME'),
                    'firstname' => '',
                    'civility_id' => '',
                    'poste' => '',
                    'address' => '',
                    'town' => '',
                    'zip' => '',
                    'phone_pro' => '',
                    'phone_perso' => '',
                    'phone_mobile' => '',
                    'email' => '',
                    'url' => '',
                    'fax' => '',
                    'state' => '',
                    'country' => '',
                    'state_id' => '',
                    'skype' => '',
                    'jabberid' => '',
                    'country_id' => '',
                )
            ),
            'RGPD_CONTACT_FOURNISSEUR' => array(
                'sql' => "
                    SELECT c.rowid FROM ".MAIN_DB_PREFIX."socpeople as c
                    INNER JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = c.fk_soc
                    WHERE c.entity = %d
                    AND c.tms < DATE_SUB(NOW(), INTERVAL %d MONTH)
                    AND s.fournisseur = 1
                    AND c.rowid NOT IN (
                        SELECT DISTINCT a.fk_contact
                        FROM ".MAIN_DB_PREFIX."actioncomm as a
                        WHERE a.tms > DATE_SUB(NOW(), INTERVAL %d MONTH)
                        AND a.fk_contact IS NOT NULL
                    )
                ",
                "class" => "Contact",
                "file" => DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php',
                'fields_anonym' => array(
                    'lastname' => $langs->trans('ANONYME'),
                    'firstname' => '',
                    'civility_id' => '',
                    'poste' => '',
                    'address' => '',
                    'town' => '',
                    'zip' => '',
                    'phone_pro' => '',
                    'phone_perso' => '',
                    'phone_mobile' => '',
                    'email' => '',
                    'url' => '',
                    'fax' => '',
                    'state' => '',
                    'country' => '',
                    'state_id' => '',
                    'skype' => '',
                    'jabberid' => '',
                    'country_id' => '',
                )
            ),
            'RGPD_ADHERENT' => array(
                'sql' => "
                    SELECT a.rowid FROM ".MAIN_DB_PREFIX."adherent as a
                    WHERE a.entity = %d
                    AND a.tms < DATE_SUB(NOW(), INTERVAL %d MONTH)
                    AND a.rowid NOT IN (
                        SELECT DISTINCT a.fk_element
                        FROM ".MAIN_DB_PREFIX."actioncomm as a
                        WHERE a.tms > DATE_SUB(NOW(), INTERVAL %d MONTH)
                        AND a.elementtype LIKE 'member'
                        AND a.fk_element IS NOT NULL
                    )
                ",
                "class" => "Adherent",
                "file" => DOL_DOCUMENT_ROOT . '/adherents/class/adherent.class.php',
                'fields_anonym' => array(
                    'lastname' => $langs->trans('ANONYME'),
                    'firstname' => $langs->trans('ANONYME'),
                    'civility_id' => '',
                    'societe' => '',
                    'address' => '',
                    'town' => '',
                    'zip' => '',
                    'phone' => '',
                    'phone_perso' => '',
                    'phone_mobile' => '',
                    'email' => '',
                    'url' => '',
                    'fax' => '',
                    'state' => '',
                    'country' => '',
                    'state_id' => '',
                    'skype' => '',
                    'country_id' => '',
                )
            ),
        );

        foreach ($arrayofparameters as $key => $params) {
            if ($conf->global->$key != '' && is_numeric($conf->global->$key) && (int)$conf->global->$key > 0) {

                $sql = sprintf($params['sql'], (int)$conf->entity, (int)$conf->global->$key, (int)$conf->global->$key);

                $resql = $db->query($sql);

                if ($resql && $db->num_rows($resql) > 0) {

                    $num = $db->num_rows($resql);
                    $i = 0;

                    require_once $params['file'];
                    $object = new $params['class']($db);

                    while ($i < $num)
                    {
                        $obj = $db->fetch_object($resql);

                        $object->fetch($obj->rowid);
                        $object->id = $obj->rowid;

                        if ($object->isObjectUsed($obj->rowid) > 0) {
                            foreach ($params['fields_anonym'] as $fields => $val) {
                                $object->$fields = $val;
                            }
                            $object->update($obj->rowid, $user);
                            if ($params['class'] == 'Societe') {
                                // On supprime les contacts associé
                                $sql = "DELETE FROM ".MAIN_DB_PREFIX."socpeople WHERE fk_soc = " . $obj->rowid;
                                $db->query($sql);
                            }
                        } else {
                            if (DOL_VERSION < 8) {
                                $ret = $object->delete($obj->rowid, $user);
                            } else {
                                if ($object->element == 'adherent') {
                                    $ret = $object->delete($obj->rowid);
                                } else {
                                    $ret = $object->delete();
                                }
                            }

                        }

                        $i++;
                    }
                }
            }

        }
        return true;

    }
}