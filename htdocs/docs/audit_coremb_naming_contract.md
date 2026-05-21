# Audit — [COREMB] Replace myobject occurrences in generated code

**Date :** 2026-05-21  
**Branche :** `NEW/COREMBReplaceMyObjectOccurences`  
**Périmètre :** NamingContract, NamingContractValidator, index.php, modulebuilder.lib.php

---

## Résumé exécutif

Architecture solide (NamingContract/Validator, applyTo, carte ordonnée, 24 tests verts).
**4 défauts identifiés** : 3 régressions dans le mapping $filetodelete, 1 XSS dans le helper
de validation, 2 exceptions non gérées.

---

## Défauts identifiés

### DÉFAUT 1 — CRITIQUE : 3 régressions dans $filetodelete (confirm_deleteobject)

Fichier : htdocs/modulebuilder/index.php ~l.2112
Gravité : Régression fonctionnelle — les fichiers ciblés ne sont pas supprimés

Le foreach + applyToFilename ne traite que mymodule/myobject (minuscules).
Trois entrées avaient des transformations non-symétriques :

| Template key                      | Attendu                          | Obtenu (erroné)                     |
|-----------------------------------|----------------------------------|-------------------------------------|
| ajax/myobject.lib.php             | ajax/{objectname}.php            | ajax/{objectname}.lib.php           |
| test/phpunit/MyObjectTest.php     | test/phpunit/{objectname}Test.php| test/phpunit/MyObjectTest.php (inchangé) |
| class/api_myobject.class.php      | class/api_{module}.class.php     | class/api_{objectname}.class.php    |

Correction : Retirer ces 3 entrées du foreach, les ajouter explicitement après.

### DÉFAUT 2 — HAUT : XSS dans modulebuilderValidateGeneratedFile (l.302)

setEventMessages(implode('<br>', array_slice($errors, 0, 5)), ...) — le chemin $filePath
n'est pas échappé avant injection HTML.

Correction : dol_escape_htmltag() sur chaque message avant implode.

### DÉFAUT 3 — HAUT : Exception non interceptée dans initobject (l.1113)

$ncObj = new NamingContract($module, $objectname) est hors de tout if (!$error).
Si strtolower($module) === strtolower($objectname), InvalidArgumentException → fatal error.

Correction : try-catch autour du constructeur, $error++, setEventMessages.

### DÉFAUT 4 — HAUT : Exception non interceptée dans confirm_deleteobject (l.2110)

Même problème pour $ncObjDel — modules legacy pourraient avoir objet = module en nom.

Correction : même traitement que défaut 3.

### DÉFAUT 5 — BAS : ChangeLog non mis à jour

Règle CLAUDE.md : toute feature doit mettre à jour le ChangeLog.
Correction : à compléter manuellement.

---

## Points conformes

- Aucune requête SQL introduite — N/A SQLi/free
- GETPOST : 2ème paramètre conservé partout
- PHPDoc complète, typage strict, visibilité explicite
- dol_syslog() présent dans le helper (non redondant)
- Bug MYOBJECT corrigé dans initobject (clé réintroduite dans getSubstitutionMap)
- applyTo() utilise str_replace — pas make_substitutions — correct
- 24 tests PHPUnit verts (52 assertions)

---

## Corrections appliquées

- [x] Défaut 1 — $filetodelete : 3 entrées exceptions traitées explicitement
- [x] Défaut 2 — XSS : dol_escape_htmltag() ajouté
- [x] Défaut 3 — try-catch initobject
- [x] Défaut 4 — try-catch confirm_deleteobject
- [ ] Défaut 5 — ChangeLog : à compléter manuellement
