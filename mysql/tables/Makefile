#
# Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
#
# $Id$
# $Source$
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
#
MYSQL=mysql
BASE=dolibarr
OPTIONS=-f

all:	show

create:
	$(MYSQL) $(OPTIONS) $(BASE) < actioncomm.sql
	$(MYSQL) $(OPTIONS) $(BASE) < c_actioncomm.sql
	$(MYSQL) $(OPTIONS) $(BASE) < c_chargesociales.sql
	$(MYSQL) $(OPTIONS) $(BASE) < c_effectif.sql
	$(MYSQL) $(OPTIONS) $(BASE) < c_paiement.sql
	$(MYSQL) $(OPTIONS) $(BASE) < c_pays.sql
	$(MYSQL) $(OPTIONS) $(BASE) < c_propalst.sql
	$(MYSQL) $(OPTIONS) $(BASE) < c_stcomm.sql
	$(MYSQL) $(OPTIONS) $(BASE) < c_typent.sql
	$(MYSQL) $(OPTIONS) $(BASE) < llx_adherent.sql
	$(MYSQL) $(OPTIONS) $(BASE) < llx_adherent_type.sql
	$(MYSQL) $(OPTIONS) $(BASE) < llx_adherent_options.sql
	$(MYSQL) $(OPTIONS) $(BASE) < llx_bank.sql
	$(MYSQL) $(OPTIONS) $(BASE) < llx_bank_account.sql
	$(MYSQL) $(OPTIONS) $(BASE) < llx_bank_categ.sql
	$(MYSQL) $(OPTIONS) $(BASE) < llx_bank_class.sql
	$(MYSQL) $(OPTIONS) $(BASE) < llx_bookmark.sql
	$(MYSQL) $(OPTIONS) $(BASE) < llx_chargesociales.sql
	$(MYSQL) $(OPTIONS) $(BASE) < llx_compta.sql
	$(MYSQL) $(OPTIONS) $(BASE) < llx_compta_account.sql
	$(MYSQL) $(OPTIONS) $(BASE) < llx_const.sql
	$(MYSQL) $(OPTIONS) $(BASE) < llx_cotisation.sql
	$(MYSQL) $(OPTIONS) $(BASE) < llx_don.sql
	$(MYSQL) $(OPTIONS) $(BASE) < llx_don_projet.sql
	$(MYSQL) $(OPTIONS) $(BASE) < llx_fa_pr.sql
	$(MYSQL) $(OPTIONS) $(BASE) < llx_facture.sql
	$(MYSQL) $(OPTIONS) $(BASE) < llx_facturedet.sql
	$(MYSQL) $(OPTIONS) $(BASE) < llx_facture_fourn.sql
	$(MYSQL) $(OPTIONS) $(BASE) < llx_fichinter.sql
	$(MYSQL) $(OPTIONS) $(BASE) < llx_paiement.sql
	$(MYSQL) $(OPTIONS) $(BASE) < llx_pointmort.sql
	$(MYSQL) $(OPTIONS) $(BASE) < llx_product.sql
	$(MYSQL) $(OPTIONS) $(BASE) < llx_projet.sql
	$(MYSQL) $(OPTIONS) $(BASE) < llx_propal.sql
	$(MYSQL) $(OPTIONS) $(BASE) < llx_propaldet.sql
	$(MYSQL) $(OPTIONS) $(BASE) < llx_service.sql
	$(MYSQL) $(OPTIONS) $(BASE) < llx_soc_recontact.sql
	$(MYSQL) $(OPTIONS) $(BASE) < llx_tva.sql
	$(MYSQL) $(OPTIONS) $(BASE) < llx_user.sql
	$(MYSQL) $(OPTIONS) $(BASE) < llx_ventes.sql
	$(MYSQL) $(OPTIONS) $(BASE) < llx_voyage.sql
	$(MYSQL) $(OPTIONS) $(BASE) < llx_voyage_reduc.sql
	$(MYSQL) $(OPTIONS) $(BASE) < societe.sql
	$(MYSQL) $(OPTIONS) $(BASE) < socpeople.sql
	$(MYSQL) $(OPTIONS) $(BASE) < socstatutlog.sql


drop:
	$(MYSQL) $(OPTIONS) $(BASE) < drop.sql

show:
	$(MYSQL) $(OPTIONS) $(BASE) -e 'show tables'
