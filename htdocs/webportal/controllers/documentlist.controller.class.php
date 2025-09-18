<?php
/*
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file        htdocs/webportal/controllers/documentlist.controller.class.php
 * \ingroup     webportal
 * \brief       This file is a controller for thirdparty's documents list (GED)
 */

/**
 * Class for DocumentListController
 */
class DocumentListController extends Controller
{
    /**
     * Check current access to controller
     *
     * @return  bool
     */
    public function checkAccess()
    {
        $this->accessRight = getDolGlobalInt('WEBPORTAL_DOCUMENT_LIST_ACCESS');
        return parent::checkAccess();
    }

    /**
     * Action method is called before html output
     * can be used to manage security and change context
     *
     * @return  int     Return integer < 0 on error, > 0 on success
     */
    public function action()
    {
        global $langs;

        $context = Context::getInstance();
        if (!$context->controllerInstance->checkAccess()) {
            return -1;
        }

        // Load translation files required by the page
        $langs->loadLangs(array('other'));

        $context->title = $langs->trans('WebPortalDocumentListTitle');
        $context->desc = $langs->trans('WebPortalDocumentListDesc');
        $context->menu_active[] = 'document_list';

        return 1;
    }

    /**
     * Display
     *
     * @return  void
     */
    public function display()
    {
        global $conf;
        $context = Context::getInstance();
        if (!$context->controllerInstance->checkAccess()) {
            $this->display404();
            return;
        }

        $this->loadTemplate('header');
        $this->loadTemplate('menu');
        $this->loadTemplate('hero-header-banner');

        print '<main class="container">';

       // We retrieve the connected third party from the context
        $thirdparty = $context->logged_thirdparty;

        if (!empty($thirdparty) && $thirdparty->id) {

            echo '<h2>Documents Partagés</h2>';

            // We use the client ID to find the folder name
            $client_dir_name = $thirdparty->id;
            
            // We retrieve the ABSOLUTE path of the third party's GED directory
            $dir_ged_tiers = $conf->societe->dir_output . '/' . $client_dir_name;

            // We retrieve the list of files in this directory
            require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
            $liste_fichiers = dol_dir_list($dir_ged_tiers, 'files', 0, '', '', 'date', SORT_DESC);

            // We check if there are files and display them
            if (is_array($liste_fichiers) && count($liste_fichiers) > 0) {
                echo '<table class="table" width="100%">';
                echo '<thead>';
                echo '<tr>';
                echo '<th>Fichier</th>';
                echo '<th style="text-align: right; white-space: nowrap;">Taille</th>';
                echo '<th style="text-align: right; white-space: nowrap;">Date Modification</th>';
                echo '</tr>';
                echo '</thead>';
                echo '<tbody>';

                foreach ($liste_fichiers as $fichier) {
                    // We also use the ID to build the download link
                    $lien_telechargement = DOL_URL_ROOT . '/document.php?modulepart=societe&attachment=1&file=' . urlencode($client_dir_name . '/' . $fichier['name']);

                    echo '<tr>';
                    echo '<td><a href="' . $lien_telechargement . '" target="_blank">' . htmlspecialchars($fichier['name']) . '</a></td>';
                    echo '<td style="text-align: right;">' . dol_print_size($fichier['size']) . '</td>';
                    echo '<td style="text-align: right;">' . dol_print_date($fichier['date'], 'dayhour') . '</td>';
                    echo '</tr>';
                }

                echo '</tbody>';
                echo '</table>';
            } else {
                echo '<p>Aucun document partagé n\'est disponible pour le moment.</p>';
            }

            echo '<br>';
        }

        print '</main>';

        $this->loadTemplate('footer');
    }
}
