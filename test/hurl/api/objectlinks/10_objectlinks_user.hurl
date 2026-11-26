# test/hurl/api/objectlinks/10_objectlinks_user.hurl
# GET-only matrix: sourcetype=user x ALL 50 object types (incl. itself).
# GENERATED from an observed probe run (2026-11-22) by run_objectlinks_matrix.py
# (type list from GET /setup/modules on this instance):
# every request below is pinned to the status OBSERVED on this instance —
# including the per-type gate denials (403). Regenerate after changing
# instance permissions; treat any manual edit as deliberate.
# Reference model from 10_objectlinks.hurl: required params -> format-only
# validation -> per-type read gate -> match semantics (404 on no-match).
# Ids are deliberately nonexistent (99999999); no real links are touched.
# Only GET requests — the suite is strictly read-only.

# -> accounting
GET http://{{hostnport}}/api/index.php/objectlinks?fk_source=99999999&sourcetype=user&fk_target=99999999&targettype=accounting
HTTP 403
[Asserts]
jsonpath "$.error.code" == 403

# -> adherent
GET http://{{hostnport}}/api/index.php/objectlinks?fk_source=99999999&sourcetype=user&fk_target=99999999&targettype=adherent
HTTP 403
[Asserts]
jsonpath "$.error.code" == 403

# -> agenda
GET http://{{hostnport}}/api/index.php/objectlinks?fk_source=99999999&sourcetype=user&fk_target=99999999&targettype=agenda
HTTP 403
[Asserts]
jsonpath "$.error.code" == 403

# -> api
GET http://{{hostnport}}/api/index.php/objectlinks?fk_source=99999999&sourcetype=user&fk_target=99999999&targettype=api
HTTP 403
[Asserts]
jsonpath "$.error.code" == 403

# -> banque
GET http://{{hostnport}}/api/index.php/objectlinks?fk_source=99999999&sourcetype=user&fk_target=99999999&targettype=banque
HTTP 403
[Asserts]
jsonpath "$.error.code" == 403

# -> barcode
GET http://{{hostnport}}/api/index.php/objectlinks?fk_source=99999999&sourcetype=user&fk_target=99999999&targettype=barcode
HTTP 403
[Asserts]
jsonpath "$.error.code" == 403

# -> blockedlog
GET http://{{hostnport}}/api/index.php/objectlinks?fk_source=99999999&sourcetype=user&fk_target=99999999&targettype=blockedlog
HTTP 403
[Asserts]
jsonpath "$.error.code" == 403

# -> bookmark
GET http://{{hostnport}}/api/index.php/objectlinks?fk_source=99999999&sourcetype=user&fk_target=99999999&targettype=bookmark
HTTP 403
[Asserts]
jsonpath "$.error.code" == 403

# -> categorie
GET http://{{hostnport}}/api/index.php/objectlinks?fk_source=99999999&sourcetype=user&fk_target=99999999&targettype=categorie
HTTP 403
[Asserts]
jsonpath "$.error.code" == 403

# -> commande
GET http://{{hostnport}}/api/index.php/objectlinks?fk_source=99999999&sourcetype=user&fk_target=99999999&targettype=commande
HTTP 403
[Asserts]
jsonpath "$.error.code" == 403

# -> cron
GET http://{{hostnport}}/api/index.php/objectlinks?fk_source=99999999&sourcetype=user&fk_target=99999999&targettype=cron
HTTP 403
[Asserts]
jsonpath "$.error.code" == 403

# -> ecm
GET http://{{hostnport}}/api/index.php/objectlinks?fk_source=99999999&sourcetype=user&fk_target=99999999&targettype=ecm
HTTP 403
[Asserts]
jsonpath "$.error.code" == 403

# -> emailcollector
GET http://{{hostnport}}/api/index.php/objectlinks?fk_source=99999999&sourcetype=user&fk_target=99999999&targettype=emailcollector
HTTP 403
[Asserts]
jsonpath "$.error.code" == 403

# -> eventorganization
GET http://{{hostnport}}/api/index.php/objectlinks?fk_source=99999999&sourcetype=user&fk_target=99999999&targettype=eventorganization
HTTP 403
[Asserts]
jsonpath "$.error.code" == 403

# -> expedition
GET http://{{hostnport}}/api/index.php/objectlinks?fk_source=99999999&sourcetype=user&fk_target=99999999&targettype=expedition
HTTP 403
[Asserts]
jsonpath "$.error.code" == 403

# -> expensereport
GET http://{{hostnport}}/api/index.php/objectlinks?fk_source=99999999&sourcetype=user&fk_target=99999999&targettype=expensereport
HTTP 403
[Asserts]
jsonpath "$.error.code" == 403

# -> export
GET http://{{hostnport}}/api/index.php/objectlinks?fk_source=99999999&sourcetype=user&fk_target=99999999&targettype=export
HTTP 403
[Asserts]
jsonpath "$.error.code" == 403

# -> facture
GET http://{{hostnport}}/api/index.php/objectlinks?fk_source=99999999&sourcetype=user&fk_target=99999999&targettype=facture
HTTP 403
[Asserts]
jsonpath "$.error.code" == 403

# -> fckeditor
GET http://{{hostnport}}/api/index.php/objectlinks?fk_source=99999999&sourcetype=user&fk_target=99999999&targettype=fckeditor
HTTP 403
[Asserts]
jsonpath "$.error.code" == 403

# -> ficheinter
GET http://{{hostnport}}/api/index.php/objectlinks?fk_source=99999999&sourcetype=user&fk_target=99999999&targettype=ficheinter
HTTP 403
[Asserts]
jsonpath "$.error.code" == 403

# -> fournisseur
GET http://{{hostnport}}/api/index.php/objectlinks?fk_source=99999999&sourcetype=user&fk_target=99999999&targettype=fournisseur
HTTP 403
[Asserts]
jsonpath "$.error.code" == 403

# -> gravatar
GET http://{{hostnport}}/api/index.php/objectlinks?fk_source=99999999&sourcetype=user&fk_target=99999999&targettype=gravatar
HTTP 403
[Asserts]
jsonpath "$.error.code" == 403

# -> hrm
GET http://{{hostnport}}/api/index.php/objectlinks?fk_source=99999999&sourcetype=user&fk_target=99999999&targettype=hrm
HTTP 403
[Asserts]
jsonpath "$.error.code" == 403

# -> import
GET http://{{hostnport}}/api/index.php/objectlinks?fk_source=99999999&sourcetype=user&fk_target=99999999&targettype=import
HTTP 403
[Asserts]
jsonpath "$.error.code" == 403

# -> knowledgemanagement
GET http://{{hostnport}}/api/index.php/objectlinks?fk_source=99999999&sourcetype=user&fk_target=99999999&targettype=knowledgemanagement
HTTP 403
[Asserts]
jsonpath "$.error.code" == 403

# -> mailing
GET http://{{hostnport}}/api/index.php/objectlinks?fk_source=99999999&sourcetype=user&fk_target=99999999&targettype=mailing
HTTP 403
[Asserts]
jsonpath "$.error.code" == 403

# -> modulebuilder
GET http://{{hostnport}}/api/index.php/objectlinks?fk_source=99999999&sourcetype=user&fk_target=99999999&targettype=modulebuilder
HTTP 403
[Asserts]
jsonpath "$.error.code" == 403

# -> multicurrency
GET http://{{hostnport}}/api/index.php/objectlinks?fk_source=99999999&sourcetype=user&fk_target=99999999&targettype=multicurrency
HTTP 403
[Asserts]
jsonpath "$.error.code" == 403

# -> notification
GET http://{{hostnport}}/api/index.php/objectlinks?fk_source=99999999&sourcetype=user&fk_target=99999999&targettype=notification
HTTP 403
[Asserts]
jsonpath "$.error.code" == 403

# -> opensurvey
GET http://{{hostnport}}/api/index.php/objectlinks?fk_source=99999999&sourcetype=user&fk_target=99999999&targettype=opensurvey
HTTP 403
[Asserts]
jsonpath "$.error.code" == 403

# -> product
GET http://{{hostnport}}/api/index.php/objectlinks?fk_source=99999999&sourcetype=user&fk_target=99999999&targettype=product
HTTP 403
[Asserts]
jsonpath "$.error.code" == 403

# -> projet
GET http://{{hostnport}}/api/index.php/objectlinks?fk_source=99999999&sourcetype=user&fk_target=99999999&targettype=projet
HTTP 403
[Asserts]
jsonpath "$.error.code" == 403

# -> propal
GET http://{{hostnport}}/api/index.php/objectlinks?fk_source=99999999&sourcetype=user&fk_target=99999999&targettype=propal
HTTP 403
[Asserts]
jsonpath "$.error.code" == 403

# -> reception
GET http://{{hostnport}}/api/index.php/objectlinks?fk_source=99999999&sourcetype=user&fk_target=99999999&targettype=reception
HTTP 403
[Asserts]
jsonpath "$.error.code" == 403

# -> resource
GET http://{{hostnport}}/api/index.php/objectlinks?fk_source=99999999&sourcetype=user&fk_target=99999999&targettype=resource
HTTP 403
[Asserts]
jsonpath "$.error.code" == 403

# -> service
GET http://{{hostnport}}/api/index.php/objectlinks?fk_source=99999999&sourcetype=user&fk_target=99999999&targettype=service
HTTP 403
[Asserts]
jsonpath "$.error.code" == 403

# -> socialnetworks
GET http://{{hostnport}}/api/index.php/objectlinks?fk_source=99999999&sourcetype=user&fk_target=99999999&targettype=socialnetworks
HTTP 403
[Asserts]
jsonpath "$.error.code" == 403

# -> societe
GET http://{{hostnport}}/api/index.php/objectlinks?fk_source=99999999&sourcetype=user&fk_target=99999999&targettype=societe
HTTP 403
[Asserts]
jsonpath "$.error.code" == 403

# -> stock
GET http://{{hostnport}}/api/index.php/objectlinks?fk_source=99999999&sourcetype=user&fk_target=99999999&targettype=stock
HTTP 403
[Asserts]
jsonpath "$.error.code" == 403

# -> stripe
GET http://{{hostnport}}/api/index.php/objectlinks?fk_source=99999999&sourcetype=user&fk_target=99999999&targettype=stripe
HTTP 403
[Asserts]
jsonpath "$.error.code" == 403

# -> supplier_proposal
GET http://{{hostnport}}/api/index.php/objectlinks?fk_source=99999999&sourcetype=user&fk_target=99999999&targettype=supplier_proposal
HTTP 403
[Asserts]
jsonpath "$.error.code" == 403

# -> syslog
GET http://{{hostnport}}/api/index.php/objectlinks?fk_source=99999999&sourcetype=user&fk_target=99999999&targettype=syslog
HTTP 403
[Asserts]
jsonpath "$.error.code" == 403

# -> takepos
GET http://{{hostnport}}/api/index.php/objectlinks?fk_source=99999999&sourcetype=user&fk_target=99999999&targettype=takepos
HTTP 403
[Asserts]
jsonpath "$.error.code" == 403

# -> tax
GET http://{{hostnport}}/api/index.php/objectlinks?fk_source=99999999&sourcetype=user&fk_target=99999999&targettype=tax
HTTP 403
[Asserts]
jsonpath "$.error.code" == 403

# -> ticket
GET http://{{hostnport}}/api/index.php/objectlinks?fk_source=99999999&sourcetype=user&fk_target=99999999&targettype=ticket
HTTP 403
[Asserts]
jsonpath "$.error.code" == 403

# -> user (self)
GET http://{{hostnport}}/api/index.php/objectlinks?fk_source=99999999&sourcetype=user&fk_target=99999999&targettype=user
HTTP 403
[Asserts]
jsonpath "$.error.code" == 403

# -> variants
GET http://{{hostnport}}/api/index.php/objectlinks?fk_source=99999999&sourcetype=user&fk_target=99999999&targettype=variants
HTTP 403
[Asserts]
jsonpath "$.error.code" == 403

# -> webhook
GET http://{{hostnport}}/api/index.php/objectlinks?fk_source=99999999&sourcetype=user&fk_target=99999999&targettype=webhook
HTTP 403
[Asserts]
jsonpath "$.error.code" == 403

# -> website
GET http://{{hostnport}}/api/index.php/objectlinks?fk_source=99999999&sourcetype=user&fk_target=99999999&targettype=website
HTTP 403
[Asserts]
jsonpath "$.error.code" == 403

# -> workflow
GET http://{{hostnport}}/api/index.php/objectlinks?fk_source=99999999&sourcetype=user&fk_target=99999999&targettype=workflow
HTTP 403
[Asserts]
jsonpath "$.error.code" == 403
