# Audit des dépendances — état réel et plan de mise à niveau

Date : 2026-10-25  
Dépôt : `amipm178/dolibarr`  
Branche : `audit/general-2026-10`  
Périmètre : manifeste Composer désactivé, inventaire de licences et configuration CI. Analyse documentaire, sans installation des dépendances.

## Résumé exécutif

**Ne pas activer `composer.json.disabled` tel quel et ne pas mettre à niveau les bibliothèques en bloc.** Le manifeste n'est pas actif (le fichier s'appelle `composer.json.disabled`), aucun `composer.lock` n'a été trouvé à la racine et plusieurs versions du manifeste diffèrent de l'inventaire `COPYRIGHT`. L'inventaire de licences et le manifeste ne constituent pas à eux seuls une preuve des versions effectivement déployées.

## Éléments observés

| Élément | `composer.json.disabled` | `COPYRIGHT` | Évaluation |
|---|---|---|---|
| PHP minimum | `>=7.1.0` | Sans objet | Le README affiche PHP >= 7.2 et Travis teste PHP 7.2 sur un job push. Confirmer la matrice officielle de support avant toute modification. |
| CKEditor | `4.12.1` | `4.22.1` | Écart manifeste/inventaire. Vérifier les fichiers embarqués et la provenance. CKEditor 4 est une branche ancienne ; vérifier le support et la politique de licence de l'édition utilisée. |
| PhpSpreadsheet | `>=1.12` | `1.12.0` | La contrainte sans borne supérieure autorise potentiellement une version incompatible avec l'application et la version PHP visée. Une résolution reproductible exige un lockfile ou une autre procédure de pinning. |
| Restler | `3.0.0-RC6` | `3.1.1` | Écart et dépendance pré-release dans le manifeste. Vérifier les API utilisées et les tests REST avant migration. |
| TCPDF | `6.3.2` | `6.11.4` | Écart important entre la version déclarée et l'inventaire ; confirmer la copie réellement embarquée. |
| Stripe PHP | `10.7.0` | `10.7.0` | Versions concordantes dans ces deux documents ; reste à confirmer la version embarquée et les avis de sécurité applicables. |
| MobileDetect | `2.8.41` | `2.8.41` | Versions concordantes dans ces documents. |
| PHPUnit / PHP_CodeSniffer | Non déclarés dans ce manifeste | Non répertoriés comme dépendances runtime dans l'extrait vérifié | Ne pas reprendre l'affirmation de leur présence sans trouver leurs manifests ou copies effectives. |

Autres observations :
- Le README annonce PHP >= 7.2.
- `.travis.yml` contient un job push sur PHP 7.2 et un job pull request sur PHP 8.5, avec PHPCS et scan PHPUnit activés dans ce dernier. Cela décrit la configuration, pas le résultat des builds.
- `.pre-commit-config.yaml` configure plusieurs contrôles génériques, mais sa présence ne prouve pas qu'ils ont été exécutés.
- `COPYRIGHT` inventorie également plusieurs composants JavaScript et PHP embarqués. Les licences, versions et notices doivent être conservées lors des mises à niveau.
- Aucun `composer.lock` ni `composer.json` actif n'a été trouvé à la racine lors de cette vérification.

## Risques prioritaires

### P1 — Traçabilité des versions insuffisante

Le manifeste Composer est désactivé et n'est pas synchronisé avec plusieurs versions indiquées dans `COPYRIGHT`. Les outils ne doivent pas traiter ce manifeste comme une source fiable des versions de production. Établir une source de vérité pour les versions réellement livrées.

### P1 — Dépendances sans résolution reproductible

En l'absence de `composer.lock` actif, la contrainte `phpoffice/phpspreadsheet >=1.12` ne borne pas la résolution vers le haut. Activer le manifeste sans stratégie de compatibilité et sans lockfile pourrait produire des installations différentes dans le temps.

### P1 — CKEditor 4

La version déclarée dans le manifeste diffère de l'inventaire. Déterminer la version effectivement livrée, l'édition (open source ou commerciale) et le statut de support. Ne pas remplacer le code de l'éditeur sans vérifier les plugins, intégrations et obligations de licence.

### P2 — Divergence du manifeste et des copies embarquées

TCPDF et Restler présentent également des versions différentes entre les documents. Vérifier les en-têtes de version, les métadonnées de paquet et les tests fonctionnels avant de choisir une version cible.

## Plan de mise à niveau recommandé

1. **Inventaire effectif (sans modification)**  
   Relever les versions des fichiers réellement présents sous `htdocs/includes` et des bibliothèques JS sous `htdocs/public`, en inspectant les en-têtes de version et les métadonnées. Confirmer si les dépendances sont copiées manuellement, générées depuis Composer, ou les deux.

2. **Décider de la stratégie de gestion**  
   Choisir explicitement entre dépendances embarquées maintenues manuellement et installation Composer reproductible. Ne pas réactiver le manifeste désactivé avant cette décision. Si Composer est retenu, définir la version PHP cible, verrouiller les versions, documenter la commande de build et exclure les fichiers générés qui ne doivent pas être commités.

3. **Valider les compatibilités**  
   Construire une matrice PHP/base de données alignée sur les versions officiellement prises en charge. Tester en priorité les modules qui utilisent les bibliothèques concernées : génération PDF, import/export tableur, API REST, paiements Stripe et édition WYSIWYG.

4. **Mettre à niveau par composant**  
   Traiter une bibliothèque à la fois, avec analyse des avis de sécurité des sources officielles, changelog, comparaison API, tests ciblés et rollback documenté. Éviter les changements de versions majeures simultanés.

5. **Ajouter un contrôle automatisé**  
   À chaque release, vérifier la cohérence entre versions embarquées et `COPYRIGHT`. Si Composer est retenu, exécuter dans CI une validation du manifeste et du lockfile, un audit des dépendances avec une base de vulnérabilités à jour, puis les tests de compatibilité.

6. **Livrer via pull request**  
   Fournir la liste des versions avant/après, les avis de sécurité pertinents, les licences, les résultats des tests et les éventuelles régressions connues.

## Tests et limites de cette passe

**Effectué :**
- Lecture de `composer.json.disabled`, `COPYRIGHT`, `README.md`, `.travis.yml`, `.pre-commit-config.yaml` et `htdocs/version.inc.php`.
- Vérification que `composer.json` et `composer.lock` ne sont pas présents à la racine sous ces noms.
- Comparaison documentaire de plusieurs versions.
- Aucun fichier applicatif ou dépendance embarquée modifié.

**Non exécuté :**
- `composer validate`, `composer install`, `composer audit` (pas de manifeste actif/lockfile et pas d'environnement d'installation ici).
- PHPUnit, PHPCS, lint PHP, tests d'intégration REST, PDF, tableurs, Stripe ou CKEditor.
- Scan CVE/SCA sur les paquets réellement livrés.
- Installation ou test en base de données.

Cette passe ne permet pas de déclarer l'application vulnérable ou exempte de vulnérabilités. Elle identifie d'abord une lacune de traçabilité et de reproductibilité à résoudre avant de faire des mises à niveau de dépendances.


## Point d'attention confirmé par la documentation éditeur : CKEditor 4

CKEditor 4 a atteint sa fin de vie publique le 30 juin 2023. Selon l'éditeur, la version open source ne reçoit plus de correctifs de sécurité ; une offre LTS commerciale est distincte. Il faut donc traiter la version réellement embarquée comme une priorité de revue et planifier soit une migration vers CKEditor 5, soit une solution LTS contractuellement prise en charge. La migration vers CKEditor 5 est un changement fonctionnel majeur, pas une simple substitution de fichiers.

Références éditeur : [support CKEditor 4](https://ckeditor.com/docs/ckeditor4/latest/support/index.html), [page CKEditor 4 et options après EOL](https://ckeditor.com/ckeditor-4/).

Pour Composer, la commande `composer audit` est prévue pour examiner les paquets installés ou verrouillés, mais elle ne peut pas être considérée comme exécutée dans cette passe faute de manifeste actif/lockfile et d'environnement d'installation. Voir la [documentation officielle de la commande audit](https://getcomposer.org/doc/03-cli.md#audit).
