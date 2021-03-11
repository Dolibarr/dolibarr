# ChangeLog MULTICOMPANY

## 12.0.1

NEW add stock warehouse sharing list  
NEW add triggers create, update, delete  
FIX uniformize trigger  
FIX uniformize extrafields management

## 12.0.0

FIX compatibility with Dolibarr 12  
FIX look and feel V12

## 11.0.2

NEW add stock warehouse sharing list

## 11.0.1

FIX form is not printed to addgroups on user card in Dolibarr 11.0

## 11.0.0

FIX compatibility with Dolibarr 11  
FIX better warning for upgrade message  

## 10.0.3

NEW add templates of entities management  
NEW pre-format modules and shares of new entities with templates  
NEW possibility to duplicate usergroup rights by entities in transversal mode  
NEW add label of current entity in right top menu

## 10.0.1.3

FIX compatibility with Two-Factor Authentication module

## 10.0.1.2

FIX missing rename facture ref field (ref instead facnumber)

## 10.0.1.1

FIX wrong Font Awesome css class for check/uncheck button

## 10.0.1

FIX wrong sql request for show users in transversal mode

## 10.0.0

NEW add dropdown menu (look and feel v10)
NEW change logo and background of entity in login page
FIX compatibility with Dolibarr 10

## 9.0.4

FIX wrong sql request for show users in transversal mode

## 9.0.3

FIX display rights of allowed entity for user when it's not entity 1 on perms card

## 9.0.2

FIX better test for compatibility with multiple Dolibarr versions

## 9.0.1

FIX superadmin can always edit groups of another entities  
FIX need php 5.6 min  
FIX possibility to hide html title with constant MULTICOMPANY\_HIDE\_HTML\_TITLE
FIX translation

## 9.0.0

NEW add customer invoices sharing  
NEW add proposal numbering sharing  
NEW an external plugin can easily include its own sharing settings  
NEW add buttons to stay in entity card  
FIX compatibility between proposal sharing and proposalnumber sharing  
FIX compatibility between invoice sharing and invoicenumber sharing  
FIX possibility to use a different referrer for numbering sharing  
FIX compatibility with Dolibarr 9  

## 8.0.6

FIX backport from newest version for avoid error

## 8.0.5.4

FIX display rights of allowed entity for user when it's not entity 1 on perms card

## 8.0.5.3

FIX Make retrocompatibility for external modules using old method

## 8.0.5.2

FIX when using switchentityautoopen, this avoid to have the autopen always after first use

## 8.0.5.1

FIX missing cashdesk module authentication

## 8.0.5

FIX better check entities in user permissions tab

## 8.0.4

FIX autoopen of the "switch entity popup" was broken (used by DoliDroid)
FIX version checking refactorization

## 8.0.3

FIX missing "printUserPasswordField" hook for mc authentication

## 8.0.2

FIX use POST instead GET for avoid false positive error with firewall (datatables)  
FIX avoid error when SHMOP cache is used

## 8.0.1

FIX Look and feel v8

## 8.0.0

NEW can share leave requests - holidays  
NEW ad possibility to change third party entity 
NEW add customer proposals sharing with read/write permissions  
NEW add read/write permissions for products/services sharing  
NEW add entity field in products/services list  
NEW add entity field in third parties list  
NEW add entity field in customer proposals list  
NEW add entity information in banner of elements sharing
NEW add warning message when hide the entity in login page  
NEW add warning message if user not linked with group/entity (transversal mode)  
NEW change icons add specific icon by theme  
NEW Can set a parameter switchentityautoopen=1 on any urls to force open of the switch entity box, with no need to click:
- This is required to provide a compatibility with native android application when menu is not managed by Dolibarr but by the android application  

NEW change multiselect library 
NEW add product reseller prices sharing  
NEW add new parameters for enable and make visible an entity by default  

FIX compatibility with Dolibarr 8  
FIX better redirection when you switch to another entity  
FIX better user/group management in transversal mode
FIX many improvements and fixes for look and performance

## 7.0.4

FIX use POST instead GET for avoid false positive error with firewall (datatables)  
FIX avoid error when SHMOP cache is used

## 7.0.3

Fix: datatables error when "multicompany" directory is in root of dolibarr  
Fix: use REQUEST_URI by default when switch to another entity  
Fix: use dol_include_once() by default

## 7.0.2

Fix: broken feature when dropdown list in login page is hidden

## 7.0.1

Fix: hide dictionnaries sharings for the moment

## 7.0.0

Fix: compatibility with Dolibarr 7  
New: add cache system (memcached)  
New: use datatables for entities list  
New: add multiselect entities for rights management in transverse mode

## 6.0.1

Fix: compatibility with Dolibarr 7

## 6.0.0

Fix: compatibility with Dolibarr 6  
New: add members sharing  
New: add possibility to customizing dictionnaries by entity (llx\_c\_paiement and llc\_c\_payment\_term)  
New: getEntity function use true $shared value by default  
New: big refactorization of transverse mode  

Improvements to the transverse mode:
- A module activated in the main entity is no longer activated in the child entities, each entity can have its own modules activated.
- You can define different rights per entity in the same group.
- You can customize/supplement the rights of a user by entity

For developers: 
* You can remove $multicompany_transverse_mode in conf.php
* Use $conf->global->MULTICOMPANY\_TRANSVERSE\_MODE instead $conf->multicompany->transverse_mode
* Use getEntity('xxx') instead getEntity('xxx', 1) and use getEntity('xxx', 0) instead getEntity('xxx')
* Use getEntity('thirdparty') instead getEntity('societe')
* Use getEntity('member') instead getEntity('adherent')
* Use getEntity('bankaccount') instead getEntity('bank_account')

## 5.0.0

Fix: compatibility with Dolibarr 5  
New: add expense report sharing

## 4.0.0

Fix: compatibility with Dolibarr 4  
New: add project sharing

## 3.8.2

Fix: compatibility with transverse modes

## 3.8.1

Fix: folder sharing was not working when more than 2 entities

## 3.8.0

Fix: compatibility with Dolibarr 3.8.x

## 3.7.3

Fix: folder sharing was not working when more than 2 entities

## 3.7.2

New: add transifex management  
New: change the development platform

## 3.7.1

New: add extrafields  
Fix: more bugs

## 3.7.0

Fix: compatibility with Dolibarr 3.7.x  
New: add invoice number sharing  
New: add script to move master entity with another entity  
New: add about page

## 3.6.2

Fix: folder sharing was not working when more than 2 entities

## 3.6.1

Fix: add stock sharing parameters

## 3.6.0

Fix: compatibility with Dolibarr 3.6.x  
Fix: show entities combobox only in transverse mode or with admin users  
Fix: automatic connection to the entity of the user if the drop down list of entities is hidden (use different login strictly by entity)  
New: add multicompany function login

## 3.5.0

Fix: compatibility with Dolibarr 3.5.0

## 3.4.0

New: add bank sharing  
New: add product prices sharing  
New: add agenda sharing  
New: add stock sharing  
New: add Hungary translation  
Fix: compatibility with Dolibarr 3.4  
Fix: grant access for user card in transverse mode  
Fix: sharing services was not functional if the module product was not activated  
Fix: more bugs

## 3.2.2

New: add Netherland translation  
Fix: minor bugs and uniformize code  
Fix: add check method for login  
Fix: check permission in combobox  
Fix: remove constantes already defined by module  
Fix: compatibility with bureau2crea theme  
Fix: possibility to force entity un login page  
Fix: bad rights verification

## 3.2.1

no release

## 3.2.0

New: add sharing system for products/services and thirdparties/contacts between entities  
New: add category sharing between entities  
Fix: problem with user card access and wrong carriage return  
Fix: show login page options with hook manager  
Fix: update es\_ES and ca\_ES translations  
Fix: replace serialize by json  
Fix: security  
Fix: more bugs  

## 3.1.2

Fix: invalid path if you do not use the custom directory

## 3.1.1

Fix: convert current admin to superadmin  
The administrator of the primary entity was not converted into superadministrator when activating the module,  
this happened during an update of a version of Dolibarr < 3.1 to >= 3.1

## 3.1.0

New: change logo  
New: add italian translation  
New: stay connected for switch entity  
New: add options tab in module configuration  
New: possibility to hide combobox in login page  
New: add transverse mode
* Off mode: The rights and groups are managed in each entity: users belong to the entity for their rights
* One mode: Groups can only belong to the entity = 0 and that the user belongs to a particular entity
		
Fix: translation

## 3.0.1

New: add spanish translation  
Fix: minor bugfix

## 3.0.0

First release of this module
