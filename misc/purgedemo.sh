
mysql dolidemo -e 'delete from llx_facture';
mysql dolidemo -e 'delete from llx_facturedet';
mysql dolidemo -e 'delete from llx_paiement';
mysql dolidemo -e 'delete from llx_propal';
mysql dolidemo -e 'delete from llx_propaldet';

rm -fr ../htdocs/document/facture/*
rm -fr ../htdocs/document/propale/*