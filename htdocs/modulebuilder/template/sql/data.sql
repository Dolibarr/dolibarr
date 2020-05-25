-- Copyright (C) ---Put here your own copyright and developer email---
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
-- along with this program.  If not, see <https://www.gnu.org/licenses/>.


-- delete from llx_mymodule_myobject;
--INSERT INTO llx_mymodule_myobject VALUES (1, 1, 'mydata');


-- delete from llx_c_mydictionarytabme;
--INSERT INTO llx_c_mydictionarytabme (code,label,active) VALUES ('ABC', 'Label ABC',   1);
--INSERT INTO llx_c_mydictionarytabme (code,label,active) VALUES ('DEF', 'Label DEF', 1);


-- new types of automatic events to record in agenda
-- 'code' must be a value matching 'MYOBJECT_ACTION'
-- 'elementtype' must be value 'mymodule' ('myobject@mymodule' may be possible but should not be required)
--insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('MYOBJECT_VALIDATE','MyObject validated','Executed when myobject is validated', 'mymodule', 1000);
--insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('MYOBJECT_UNVALIDATE','MyObject unvalidated','Executed when myobject is unvalidated', 'mymodule', 1001);
--insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('MYOBJECT_DELETE','MyObject deleted','Executed when myobject deleted', 'mymodule', 1004);

