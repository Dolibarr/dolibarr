#
# Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
# Copyright (C) 2002-2003 Éric Seigne <erics@rycks.com>
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
SQL=psql
BASE=dolibarr
OPTIONS=-U postgres

all:	show

create:
	$(SQL) $(OPTIONS) $(BASE) < actioncomm.sql
	$(SQL) $(OPTIONS) $(BASE) < c_actioncomm.sql
	$(SQL) $(OPTIONS) $(BASE) < c_chargesociales.sql
	$(SQL) $(OPTIONS) $(BASE) < c_effectif.sql
	$(SQL) $(OPTIONS) $(BASE) < c_paiement.sql
	$(SQL) $(OPTIONS) $(BASE) < c_pays.sql
	$(SQL) $(OPTIONS) $(BASE) < c_propalst.sql
	$(SQL) $(OPTIONS) $(BASE) < c_stcomm.sql
	$(SQL) $(OPTIONS) $(BASE) < c_typent.sql
	$(SQL) $(OPTIONS) $(BASE) < llx_adherent.sql
	$(SQL) $(OPTIONS) $(BASE) < llx_bank.sql
	$(SQL) $(OPTIONS) $(BASE) < llx_bank_account.sql
	$(SQL) $(OPTIONS) $(BASE) < llx_bank_categ.sql
	$(SQL) $(OPTIONS) $(BASE) < llx_bank_class.sql
	$(SQL) $(OPTIONS) $(BASE) < llx_bookmark.sql
	$(SQL) $(OPTIONS) $(BASE) < llx_chargesociales.sql
	$(SQL) $(OPTIONS) $(BASE) < llx_compta.sql
	$(SQL) $(OPTIONS) $(BASE) < llx_compta_account.sql
	$(SQL) $(OPTIONS) $(BASE) < llx_cotisation.sql
	$(SQL) $(OPTIONS) $(BASE) < llx_don.sql
	$(SQL) $(OPTIONS) $(BASE) < llx_don_projet.sql
	$(SQL) $(OPTIONS) $(BASE) < llx_fa_pr.sql
	$(SQL) $(OPTIONS) $(BASE) < llx_facture.sql
	$(SQL) $(OPTIONS) $(BASE) < llx_facture_fourn.sql
	$(SQL) $(OPTIONS) $(BASE) < llx_fichinter.sql
	$(SQL) $(OPTIONS) $(BASE) < llx_paiement.sql
	$(SQL) $(OPTIONS) $(BASE) < llx_pointmort.sql
	$(SQL) $(OPTIONS) $(BASE) < llx_product.sql
	$(SQL) $(OPTIONS) $(BASE) < llx_projet.sql
	$(SQL) $(OPTIONS) $(BASE) < llx_propal.sql
	$(SQL) $(OPTIONS) $(BASE) < llx_propaldet.sql
	$(SQL) $(OPTIONS) $(BASE) < llx_service.sql
	$(SQL) $(OPTIONS) $(BASE) < llx_soc_recontact.sql
	$(SQL) $(OPTIONS) $(BASE) < llx_tva.sql
	$(SQL) $(OPTIONS) $(BASE) < llx_user.sql
	$(SQL) $(OPTIONS) $(BASE) < llx_ventes.sql
	$(SQL) $(OPTIONS) $(BASE) < llx_voyage.sql
	$(SQL) $(OPTIONS) $(BASE) < llx_voyage_reduc.sql
	$(SQL) $(OPTIONS) $(BASE) < societe.sql
	$(SQL) $(OPTIONS) $(BASE) < socpeople.sql
	$(SQL) $(OPTIONS) $(BASE) < socstatutlog.sql


drop:
	$(SQL) $(OPTIONS) $(BASE) < drop.sql

show:
	$(SQL) $(OPTIONS) $(BASE) -c '\d'
