
mysql dolidemo -e 'delete from llx_facture';
mysql dolidemo -e 'delete from llx_facture_det';
mysql dolidemo -e 'delete from llx_paiements';

rm -fr ../htdocs/document/facture/*
rm -fr ../htdocs/document/propale/*