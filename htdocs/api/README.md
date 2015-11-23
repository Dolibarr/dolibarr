API REST howto
==============

This directory contains files to make Dolibarr a server of REST Web Services.
It depends on external library Restler.


Explore the api
---------------

You can explore API method by using web interface : https://**yourdolibarr.tld**/mydolibarr/api/admin/explorer.php (replace **yourdolibarr.tld** by real hostname of your Dolibarr installation)

Access to the API
-----------------

> **Warning : access to the API should (or better : must!) be secured with SSL connection**

To access to the API you need a token to identify. When you access the API for the first time, you need to log in with user name and password to get a token. **Only**  this token will allow to access API with.

To log in with the API, use this uri : https://**yourdolibarr.tld**/mydolibarr/api/index.php/login?login=**username**&password=**password** (replace bold strings with real values)

The token will be saved by Dolibarr for next user accesses to the API and it **must** be put into request uri as **api_key** parameter. 

Then call other services with

https://**yourdolibarr.tld**/mydolibarr/api/index.php/otherservice?api_key=**api_key**


Develop the API
---------------

The API uses Lucarast Restler framework. Please check documentation https://www.luracast.com/products/restler and examples http://help.luracast.com/restler/examples/ 
Github contains also usefull informations : https://github.com/Luracast/Restler

To implement it into Dolibarr, we need to create a specific class for object we want to use. A skeleton file is available into /dev directory : *skeleton_api_class.class.php* 
The API class file must be put into object class directory, with specific file name. By example, API class file for '*myobject*' must be put as : /htdocs/*myobject*/class/api_*myobject*.class.php. Class must be named  **MyobjectApi**.

If a module provide several object, use a different name for '*myobject*' and put the file into the same directory. 

**Define url for methods**

It is possible to specify url for API methods by simply use the PHPDoc tag **@url**. See examples :

    /**
    * List contacts
    * 
    * Get a list of contacts
    *
    * @url	GET /contact/list
    * @url	GET /contact/list/{socid}
    * @url	GET	/thirdparty/{socid}/contacts
    * [...]

**Other Annotations**
Other annotations are used, you are encouraged to read them : https://github.com/Luracast/Restler/blob/master/ANNOTATIONS.md

PHPDoc tags can also be used to specify variables informations for API. Again, rtfm : https://github.com/Luracast/Restler/blob/master/PARAM.md 


