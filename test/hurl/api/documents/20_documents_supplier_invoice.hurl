# List the documents of a supplier invoice, which are stored under a hashed subdir (fournisseur/facture/X/Y/REF)

# Create a supplier and a draft supplier invoice
POST http://{{hostnport}}/api/index.php/thirdparties
{ "name": "Hurl documents supplier {{newUuid}}", "client": "0", "fournisseur": "1", "code_fournisseur": "auto" }
HTTP 200
[Captures]
socid: body

POST http://{{hostnport}}/api/index.php/supplierinvoices
{ "socid": "{{socid}}", "ref_supplier": "HURL-DOC-{{newUuid}}", "date": 1767225600 }
HTTP 200
[Captures]
invoiceid: body

GET http://{{hostnport}}/api/index.php/supplierinvoices/{{invoiceid}}
HTTP 200
[Captures]
invoiceref: jsonpath "$.ref"

# Upload a file on it
POST http://{{hostnport}}/api/index.php/documents/upload
{ "modulepart": "supplier_invoice", "ref": "{{invoiceref}}", "filename": "hurl-supplier-invoice.txt", "filecontent": "hurl test", "fileencoding": "", "overwriteifexists": "1" }
HTTP 200

# The file is listed, by id and by ref
GET http://{{hostnport}}/api/index.php/documents
[QueryStringParams]
modulepart: supplier_invoice
id: {{invoiceid}}
HTTP 200
[Asserts]
jsonpath "$[0].name" == "hurl-supplier-invoice.txt"

GET http://{{hostnport}}/api/index.php/documents
[QueryStringParams]
modulepart: supplier_invoice
ref: {{invoiceref}}
HTTP 200
[Captures]
reldir: jsonpath "$[0].filepath" regex /^fournisseur\/facture\/(.*)$/
[Asserts]
jsonpath "$[0].name" == "hurl-supplier-invoice.txt"

# Also with the alias facture_fournisseur
GET http://{{hostnport}}/api/index.php/documents
[QueryStringParams]
modulepart: facture_fournisseur
id: {{invoiceid}}
HTTP 200
[Asserts]
jsonpath "$[0].name" == "hurl-supplier-invoice.txt"

# Delete the file, then there is nothing left to list
DELETE http://{{hostnport}}/api/index.php/documents
[QueryStringParams]
modulepart: facture_fournisseur
original_file: {{reldir}}/hurl-supplier-invoice.txt
HTTP 200

GET http://{{hostnport}}/api/index.php/documents
[QueryStringParams]
modulepart: supplier_invoice
id: {{invoiceid}}
HTTP 404

# Clean
DELETE http://{{hostnport}}/api/index.php/supplierinvoices/{{invoiceid}}
HTTP 200

DELETE http://{{hostnport}}/api/index.php/thirdparties/{{socid}}
HTTP 200
