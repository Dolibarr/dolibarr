-- ===========================================================================
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program. If not, see <https://www.gnu.org/licenses/>.
--
-- ===========================================================================

create table llx_facture_fourn_rec
(
    rowid                       integer AUTO_INCREMENT PRIMARY KEY,
    titre                       varchar(200)        NOT NULL,
    ref_supplier			    varchar(180) NOT NULL,
    entity                      integer   DEFAULT 1 NOT NULL,                                    -- multi company id
    fk_soc                      integer             NOT NULL,

    datec                       datetime,                                                        -- date de creation
    tms                         timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, -- last modification date

    suspended                   integer   DEFAULT 0,    -- 1=suspended

    libelle				        varchar(255),
    amount                      double(24, 8) DEFAULT 0 NOT NULL,
    remise                      real     DEFAULT 0,

    vat_src_code		        varchar(10)  DEFAULT '',			-- Vat code used as source of vat fields. Not strict foreign key here.
    localtax1				    double(24,8)     DEFAULT 0,
    localtax2				    double(24,8)     DEFAULT 0,
    total_ht				    double(24,8)     DEFAULT 0,
    total_tva				    double(24,8)     DEFAULT 0,
    total_ttc				    double(24,8)     DEFAULT 0,

    fk_user_author              integer,             -- user creating
    fk_user_modif               integer,             -- user making last change

    fk_projet                   integer,             -- projet auquel est associe la facture

    fk_account                  integer,             -- bank account
    fk_cond_reglement		    integer,   	         -- condition de reglement (30 jours, fin de mois ...)
    fk_mode_reglement		    integer,             -- mode de reglement (CHQ, VIR, ...)
    date_lim_reglement 	        date,                -- date limite de reglement

    note_private                text,
    note_public                 text,
    modelpdf                    varchar(255),

    fk_multicurrency            integer,
    multicurrency_code          varchar(3),
    multicurrency_tx            double(24,8) DEFAULT 1,
    multicurrency_total_ht      double(24,8) DEFAULT 0,
    multicurrency_total_tva     double(24,8) DEFAULT 0,
    multicurrency_total_ttc     double(24,8) DEFAULT 0,


    -- Fields linked to the recurring behavior

    usenewprice                 integer DEFAULT 0,			-- update invoice with current price of product instead of recorded price
    frequency                   integer,					-- frequency (for example: 3 for every 3 month)
    unit_frequency              varchar(2) DEFAULT 'm',		-- 'm' for month (date_when must be a day <= 28), 'y' for year, ...

    date_when                   datetime DEFAULT NULL,		-- date for next gen (when an invoice is generated, this field must be updated with next date)
    date_last_gen               datetime DEFAULT NULL,		-- date for last gen (date with last successfull generation of invoice)
    nb_gen_done                 integer DEFAULT NULL,		-- nb of generation done (when an invoice is generated, this field must incremented)
    nb_gen_max                  integer DEFAULT NULL,		-- maximum number of generation
    auto_validate               integer DEFAULT 0,		    -- 0 to create in draft, 1 to create and validate the new invoice
    generate_pdf                integer DEFAULT 1           -- 0 disable pdf, 1 to generate pdf
    
)ENGINE=innodb;
