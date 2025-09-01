-- Copyright (C) 2024 Johnson
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


CREATE TABLE `llx_c_preopportunity_source` (
  `rowid` int NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `label` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `position` int DEFAULT NULL,
  `use_default` varchar(255) COLLATE utf8mb4_general_ci DEFAULT '1',
  `active` int DEFAULT NULL
) ENGINE=InnoDB;

--
-- Dumping data for table `llx_c_preopportunity_source`
--

INSERT INTO `llx_c_preopportunity_source` (`rowid`, `code`, `label`, `position`, `use_default`, `active`) VALUES
(1, '1', 'Facebook', NULL, '1', 1),
(2, '2', 'Whatsapp', NULL, '1', 1),
(3, '-1', '--', NULL, '1', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `llx_c_preopportunity_source`
--
ALTER TABLE `llx_c_preopportunity_source`
  ADD PRIMARY KEY (`rowid`);
COMMIT;