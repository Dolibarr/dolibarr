<?php
/* Copyright (C) 2010 Regis Houssin  <regis.houssin@inodbox.com>
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
 *    \file       htdocs/core/modules/ticket/mod_ticket_universal.php
 *    \ingroup    ticket
 *    \brief      Fichier contenant la classe du modele de numerotation de reference de projet Universal
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/ticket/modules_ticket.php';

/**
 *     Classe du modele de numerotation de reference de projet Universal
 */
class mod_ticket_universal extends ModeleNumRefTicket
{
    /**
     * Dolibarr version of the loaded document
     * @public string
     */
    public $version = 'dolibarr'; // 'development', 'experimental', 'dolibarr'

    /**
     * @var string Error code (or message)
     */
    public $error = '';

    /**
	 * @var string Nom du modele
	 * @deprecated
	 * @see name
	 */
	public $nom='Universal';

	/**
	 * @var string model name
	 */
	public $name='Universal';

    /**
     *  Renvoi la description du modele de numerotation
     *
     *  @return string      Texte descripif
     */
    public function info()
    {
        global $db, $conf, $langs;

        // Load translation files required by the page
        $langs->loadLangs(array("ticket","admin"));

        $form = new Form($db);

        $texte = $langs->trans('GenericNumRefModelDesc') . "<br>\n";
        $texte .= '<form action="' . $_SERVER["PHP_SELF"] . '" method="POST">';
        $texte .= '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
        $texte .= '<input type="hidden" name="action" value="updateMask">';
        $texte .= '<input type="hidden" name="maskconstticket" value="TICKET_UNIVERSAL_MASK">';
        $texte .= '<table class="nobordernopadding" width="100%">';

        $tooltip = $langs->trans("GenericMaskCodes", $langs->transnoentities("Ticket"), $langs->transnoentities("Ticket"));
        $tooltip .= $langs->trans("GenericMaskCodes2");
        $tooltip .= $langs->trans("GenericMaskCodes3");
        $tooltip .= $langs->trans("GenericMaskCodes4a", $langs->transnoentities("Ticket"), $langs->transnoentities("Ticket"));
        $tooltip .= $langs->trans("GenericMaskCodes5");

        // Parametrage du prefix
        $texte .= '<tr><td>' . $langs->trans("Mask") . ':</td>';
        $texte .= '<td class="right">' . $form->textwithpicto('<input type="text" class="flat" size="24" name="maskticket" value="' . $conf->global->TICKET_UNIVERSAL_MASK . '">', $tooltip, 1, 1) . '</td>';

        $texte .= '<td class="left" rowspan="2">&nbsp; <input type="submit" class="button" value="' . $langs->trans("Modify") . '" name="Button"></td>';

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
     *  @param  Project $ticket Object ticket
     *  @return string                    Value if OK, 0 if KO
     */
    public function getNextValue($objsoc, $ticket)
    {
        global $db, $conf;

        include_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';

        // On defini critere recherche compteur
        $mask = $conf->global->TICKET_UNIVERSAL_MASK;

        if (!$mask) {
            $this->error = 'NotConfigured';
            return 0;
        }

        $date = empty($ticket->date_c) ? dol_now() : $ticket->datec;
        $numFinal = get_next_value($db, $mask, 'ticket', 'ref', '', $objsoc->code_client, $date);

        return $numFinal;
    }
}
