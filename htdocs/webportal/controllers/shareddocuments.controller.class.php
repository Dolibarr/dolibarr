<?php
/**
 * \file        htdocs/webportal/controllers/documentutile.controller.class.php
 * \ingroup     webportal
 * \brief       This file is a controller for the shared documents list
 */

/**
 * Class for DocumentUtileController
 */
class SharedDocumentsController extends Controller 
{
    public function checkAccess()
    {
        $this->accessRight = getDolGlobalInt('WEBPORTAL_SHARED_DOCUMENT_ACCESS');
        return parent::checkAccess();
    }

    public function action()
    {
        global $langs;
        $context = Context::getInstance();
        if (!$context->controllerInstance->checkAccess()) {
            return -1;
        }

        $langs->loadLangs(array('other'));
        $context->title = 'Documents Utiles';
        $context->desc = 'Documents utiles partagés avec tous nos clients';
        $context->menu_active[] = 'shared_documents';

        return 1;
    }

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

    echo '<h2>Documents Utiles</h2>';
    echo '<p>Vous trouverez ici une liste de documents utiles (brochures, conditions générales, etc.) mis à votre disposition.</p>';

    $shared_dir_name = 'Documentscomptes';
    $dir_ged_partage = $conf->ecm->dir_output . '/' . $shared_dir_name;
    $shared_dir_relative_path = 'ecm/' . $shared_dir_name;

    require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
    $liste_fichiers = dol_dir_list($dir_ged_partage, 'files', 0, '', '', 'date', SORT_DESC);

    if (is_array($liste_fichiers) && count($liste_fichiers) > 0) {
        echo '<table class="table" width="100%">';
        echo '<thead><tr><th>Fichier</th><th style="text-align: right; white-space: nowrap;">Taille</th><th style="text-align: right; white-space: nowrap;">Date de dépôt</th></tr></thead>';
        echo '<tbody>';

        foreach ($liste_fichiers as $fichier) {
            $lien_telechargement = DOL_URL_ROOT . '/document.php?modulepart=ecm&file=' . urlencode($shared_dir_relative_path . '/' . $fichier['name']);

            echo '<tr>';
            echo '<td><a href="' . $lien_telechargement . '" target="_blank">' . htmlspecialchars($fichier['name']) . '</a></td>';

            // --- DEBUT DE LA CORRECTION ---
            // On récupère la taille du fichier manuellement pour plus de fiabilité
            $taille_fichier = filesize($dir_ged_partage . '/' . $fichier['name']);
            echo '<td style="text-align: right;">' . dol_print_size($taille_fichier) . '</td>';
            // --- FIN DE LA CORRECTION ---

            echo '<td style="text-align: right;">' . dol_print_date($fichier['date'], 'dayhour') . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    } else {
        echo '<p>Aucun document utile n\'est disponible pour le moment.</p>';
    }

    echo '<br>';

    print '</main>';

    $this->loadTemplate('footer');
}
}