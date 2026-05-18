-- ========================================================================
-- Copyright (C) 2026 	Jon Bendtsen        <jon.bendtsen.github@jonb.dk>
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
-- ========================================================================

-- Default roles for Event Organization
INSERT INTO llx_c_actioncomm_role (code, label, active, position, picto) VALUES
('ADMIN', 'Admin', 1, 1, 'admin'),
('MANAGER', 'Manager', 1, 2, 'user'),
('LEADER', 'Leader', 1, 3, 'star'),
('ORGANIZER', 'Organizer', 1, 4, 'calendar'),
('STAFF', 'Staff', 1, 5, 'user'),
('SECURITY', 'Security', 1, 6, 'shield'),
('SPEAKER', 'Speaker', 1, 7, 'user'),
('VOLUNTEER', 'Volunteer', 1, 8, 'group'),
('TEACHER', 'Teacher', 1, 9, 'chalkboard'),
('DJ', 'DJ', 1, 10, 'music'),
('BAND', 'Band', 1, 11, 'music'),
('MC', 'MC/Host', 1, 12, 'microphone'),
('PHOTOGRAPHER', 'Photographer', 1, 13, 'camera'),
('MEDIC', 'Medic', 1, 14, 'medical');
