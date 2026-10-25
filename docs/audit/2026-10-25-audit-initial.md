# Audit initial du fork Dolibarr

Date : 2026-10-25  
Dépôt : `amipm178/dolibarr`  
Branche de base : `develop`  
Portée : première revue documentaire du README et des fichiers de configuration racine. Ce rapport ne constitue pas un audit exhaustif du code PHP.

## Résumé

Cette première passe a mis en évidence des points à examiner dans la traçabilité des dépendances et dans la validation CI. L'audit spécialisé des dépendances est consigné dans [2026-10-25-dependency-audit.md](2026-10-25-dependency-audit.md).

## Constats initiaux corrigés

Une version antérieure de ce rapport affirmait que `composer.json` actif déclarait PHP >= 5.5.0 et contenait PHPUnit 4, PHP_CodeSniffer 2 et PHPExcel 1.8.1. **Ces affirmations n'ont pas été confirmées sur la branche examinée et sont retirées.** Le fichier présent à la racine est `composer.json.disabled`, pas `composer.json`. Il déclare PHP >= 7.1.0 et PhpSpreadsheet >= 1.12 ; aucun `composer.lock` n'a été trouvé à la racine. Ne pas considérer le manifeste désactivé comme la source de vérité des dépendances effectivement déployées.

Le README affiche PHP >= 7.2. La configuration `.travis.yml` prévoit un job push sur PHP 7.2 et un job pull request sur PHP 8.5. Cela ne suffit pas à établir toute la matrice de support, ni à prouver que les builds ont réussi.

## Priorités

1. **P1 — Inventaire des dépendances effectives :** rapprocher les versions réellement embarquées sous `htdocs/includes` et `htdocs/public` du manifeste désactivé et du fichier `COPYRIGHT`.
2. **P1 — Reproductibilité :** décider si les dépendances doivent être maintenues manuellement ou gérées par Composer ; ne pas activer le manifeste sans version PHP cible, stratégie de verrouillage et tests.
3. **P1 — Revue de sécurité :** examiner les avis de sécurité des composants identifiés, sur les versions réellement livrées, sans conclure à une vulnérabilité sur la seule base de leur ancienneté.
4. **P2 — Validation :** confirmer les commandes de tests et leurs résultats dans CI.
5. **P2 — Corrections ciblées :** mettre à niveau les bibliothèques une par une avec tests de non-régression et plan de retour arrière.

## Vérifications effectuées

- Lecture de `README.md`, `composer.json.disabled`, `COPYRIGHT`, `.travis.yml`, `.pre-commit-config.yaml` et `htdocs/version.inc.php`.
- Vérification de l'absence de `composer.json` et `composer.lock` actifs à la racine.
- Ajout d'un rapport spécialisé sur les dépendances.
- Aucun test automatisé, audit SCA/CVE ou test d'intégration exécuté dans cette passe.
- Aucune dépendance ni aucun fichier applicatif modifié.

## Limites

L'analyse reste documentaire. Les versions de bibliothèques réellement déployées et les résultats CI doivent être vérifiés avant toute mise à niveau. L'absence de manifeste Composer actif ne signifie pas que le projet n'embarque pas de dépendances tierces.
