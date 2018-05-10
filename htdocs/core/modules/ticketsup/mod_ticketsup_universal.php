<?php
/* Copyright (C) 2010 Regis Houssin  <regis.houssin@capnetworks.com>
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
 * or see http://www.gnu.org/
 */

/**
 *    \file       ticketsup/core/modules/ticketsup/mod_ticketsup_universal.php
 *    \ingroup    ticketsup
 *    \brief      Fichier contenant la classe du modele de numerotation de reference de projet Universal
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/modules_ticketsup.php';

/**
 *     Classe du modele de numerotation de reference de projet Universal
 */
class mod_ticketsup_universal extends ModeleNumRefTicketsup
{
    public $version = 'dolibarr'; // 'development', 'experimental', 'dolibarr'
    public $error = '';
    public $nom = 'Universal';
    public $name = 'Universal';

    /**
     *  Renvoi la description du modele de numerotation
     *
     *  @return string      Texte descripif
     */
    public function info()
    {
        global $conf, $langs;

        $langs->load("ticketsup");
        $langs->load("admin");

        $form = new Form($this->db);

        $texte = $langs->trans('GenericNumRefModelDesc') . "<br>\n";
        $texte .= '<form action="' . $_SERVER["PHP_SELF"] . '" method="POST">';
        $texte .= '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
        $texte .= '<input type="hidden" name="action" value="updateMask">';
        $texte .= '<input type="hidden" name="maskconstticketsup" value="TICKETSUP_UNIVERSAL_MASK">';
        $texte .= '<table class="nobordernopadding" width="100%">';

        $tooltip = $langs->trans("GenericMaskCodes", $langs->transnoentities("Ticketsup"), $langs->transnoentities("Ticketsup"));
        $tooltip .= $langs->trans("GenericMaskCodes2");
        $tooltip .= $langs->trans("GenericMaskCodes3");
        $tooltip .= $langs->trans("GenericMaskCodes4a", $langs->transnoentities("Ticketsup"), $langs->transnoentities("Ticketsup"));
        $tooltip .= $langs->trans("GenericMaskCodes5");

        // Parametrage du prefix
        $texte .= '<tr><td>' . $langs->trans("Mask") . ':</td>';
        $texte .= '<td align="right">' . $form->textwithpicto('<input type="text" class="flat" size="24" name="maskticketsup" value="' . $conf->global->TICKETSUP_UNIVERSAL_MASK . '">', $tooltip, 1, 1) . '</td>';

        $texte .= '<td align="left" rowspan="2">&nbsp; <input type="submit" class="button" value="' . $langs->trans("Modify") . '" name="Button"></td>';

        $texte .= '</tr>';

        $texte .= '</table>';
        $texte .= '</form>';

        return $texte;
    }

    /**
     *  Renvoi un exemple de numerotation
     *
     *  @return string      Example
     */
    public function getExample()
    {
        global $conf, $langs, $mysoc;

        $old_code_client = $mysoc->code_client;
        $mysoc->code_client = 'CCCCCCCCCC';
        $numExample = $this->getNextValue($mysoc, '');
        $mysoc->code_client = $old_code_client;

        if (!$numExample) {
            $numExample = $langs->trans('NotConfigured');
        }
        return $numExample;
    }

    /**
     *  Return next value
     *
     *  @param  Societe $objsoc    Object third party
     *  @param  Project $ticketsup Object ticketsup
     *  @return string                    Value if OK, 0 if KO
     */
    public function getNextValue($objsoc, $ticketsup)
    {
        global $db, $conf;

        include_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';

        // On defini critere recherche compteur
        $mask = $conf->global->TICKETSUP_UNIVERSAL_MASK;

        if (!$mask) {
            $this->error = 'NotConfigured';
            return 0;
        }

        $date = empty($ticketsup->date_c) ? dol_now() : $ticketsup->datec;
        $numFinal = get_next_value($db, $mask, 'ticketsup', 'ref', '', $objsoc->code_client, $date);

        return $numFinal;
    }

}
