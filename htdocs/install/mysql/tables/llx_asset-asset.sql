-- ========================================================================
-- Copyright (C) 2018-2022  OpenDSI             <support@open-dsi.fr>
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program.  If not, see https://www.gnu.org/licenses/.
-- ========================================================================
--
-- Table for fixed asset
--
-- Data example:
-- INSERT INTO llx_asset (ref, entity, label, fk_asset_model, reversal_amount_ht, acquisition_value_ht, recovered_vat, reversal_date, date_acquisition, date_start, qty, acquisition_type, asset_type, not_depreciated, disposal_date, disposal_amount_ht, fk_disposal_type, disposal_depreciated, disposal_subject_to_vat, note_public, note_private, date_creation, tms, fk_user_creat, fk_user_modif, last_main_doc, import_key, model_pdf, status) VALUES
-- ('LAPTOP', 1, 'LAPTOP xxx for accountancy department', 1, NULL, 1000.00000000, NULL, NULL, '2022-01-18', '2022-01-20', 0, 0, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2022-01-18 14:31:21', '2022-03-09 14:09:46', 1, 1, NULL, NULL, NULL, 0);

CREATE TABLE llx_asset(
    rowid                   integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
    ref                     varchar(128)    NOT NULL,
    entity                  integer         DEFAULT 1 NOT NULL,
    label                   varchar(255),

    fk_asset_model          integer,

    reversal_amount_ht      double(24,8),
    acquisition_value_ht    double(24,8)    DEFAULT NULL,
    recovered_vat           double(24,8),

    reversal_date           date,

    date_acquisition        date            NOT NULL,
    date_start              date            NOT NULL,

    qty                     real            DEFAULT 1 NOT NULL,

    acquisition_type        smallint        DEFAULT 0 NOT NULL,
    asset_type              smallint        DEFAULT 0 NOT NULL,

    not_depreciated         boolean         DEFAULT false,

    disposal_date           date,
    disposal_amount_ht      double(24,8),
    fk_disposal_type        integer,
    disposal_depreciated    boolean         DEFAULT false,
    disposal_subject_to_vat boolean         DEFAULT false,

    note_public             text,
    note_private            text,

    date_creation           datetime        NOT NULL,
    tms                     timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    fk_user_creat           integer         NOT NULL,
    fk_user_modif           integer,
    last_main_doc           varchar(255),
    import_key              varchar(14),
    model_pdf               varchar(255),
    status                  integer         NOT NULL
) ENGINE=innodb;
