#
# Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
MYSQL=mysql -f
BASE=dolibarr

all:	show

create:
	$(MYSQL) $(BASE) < actioncomm.sql
	$(MYSQL) $(BASE) < c_actioncomm.sql
	$(MYSQL) $(BASE) < c_effectif.sql
	$(MYSQL) $(BASE) < c_paiement.sql
	$(MYSQL) $(BASE) < c_pays.sql
	$(MYSQL) $(BASE) < c_propalst.sql
	$(MYSQL) $(BASE) < c_stcomm.sql
	$(MYSQL) $(BASE) < c_typent.sql
	$(MYSQL) $(BASE) < llx_bank.sql
	$(MYSQL) $(BASE) < llx_bank_account.sql
	$(MYSQL) $(BASE) < llx_bank_categ.sql
	$(MYSQL) $(BASE) < llx_bank_class.sql
	$(MYSQL) $(BASE) < llx_bookmark.sql
	$(MYSQL) $(BASE) < llx_fa_pr.sql
	$(MYSQL) $(BASE) < llx_facture.sql
	$(MYSQL) $(BASE) < llx_facture_fourn.sql
	$(MYSQL) $(BASE) < llx_fichinter.sql
	$(MYSQL) $(BASE) < llx_paiement.sql
	$(MYSQL) $(BASE) < llx_pointmort.sql
	$(MYSQL) $(BASE) < llx_product.sql
	$(MYSQL) $(BASE) < llx_projet.sql
	$(MYSQL) $(BASE) < llx_propal.sql
	$(MYSQL) $(BASE) < llx_propaldet.sql
	$(MYSQL) $(BASE) < llx_service.sql
	$(MYSQL) $(BASE) < llx_soc_recontact.sql
	$(MYSQL) $(BASE) < llx_user.sql
	$(MYSQL) $(BASE) < llx_ventes.sql
	$(MYSQL) $(BASE) < societe.sql
	$(MYSQL) $(BASE) < socpeople.sql
	$(MYSQL) $(BASE) < socstatutlog.sql

drop:
	$(MYSQL) $(BASE) < drop.sql

show:
	$(MYSQL) $(BASE) -e 'show tables'
