API REST
========

## Integrate your ERP with any other applications using Dolibarr APIs
 
This module provides the service to make Dolibarr a server of REST Web Services. It depends on external library Restler.

Extract any data or push insert, update or delete record using our new REST APIs. Using standard HTTP and Json format, it is compatible with any language (PHP, Java, Ruby, Python, C#, C++, JavaScript, JQuery, Basic, ...). Use the embedded APIs explorer tool to test APIs or get generated URLs to use in your own code.


<div align="center">
  <img class="imgdoc" src="https://www.dolibarr.org//images/doc_apirest.png" alt="Dolibarr API explorer"/>
</div>



Explore the APIs
----------------

You can explore all available APIs by using the API explorer : [**yourdolibarr.tld**/api/index.php/explorer](../api/index.php/explorer) (replace **yourdolibarr.tld** by real hostname of your Dolibarr installation)


Access to an API
-----------------

> **Warning : access to any API should (or better : must!) be secured with SSL connection**

To access to the API you need a token to identify. **Only**  this token will allow to access API with.
The token is dedicated to a user and it **must** be put into requests as **DOLAPIKEY** parameter in HTTP header (or among URL parameters, but this is less secured). 

To get a token you can:

* Edit the user card to set the value of token. Each user can have a different token.
* or Call the *login* API with login and password. This will return the value of token for the user used to login.

Then call other services with

https://**yourdolibarr.tld**/mydolibarr/api/index.php/otherservice?DOLAPIKEY=**api_key**


Develop an API
--------------

The API uses Lucarast Restler framework. Please check documentation https://www.luracast.com/products/restler and examples https://restler3.luracast.com/examples/index.html  

Github contains also useful information : https://github.com/Luracast/Restler

To implement it into Dolibarr, you need to create a specific class for object we want to use. A skeleton file is available into /modulebuilder/class directory : *api_mymodule_class.class.php* 
The API class file must be put into object class directory, with specific file name. By example, API class file for '*myobject*' must be put as : /htdocs/*myobject*/class/api_*myobject*.class.php. Class must be named  **MyobjectApi**.

If a module provide several object, use a different name for *'myobject'* and put the file into the same directory. 

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

PHPDoc tags can also be used to specify variables information for API. Again, rtfm : https://github.com/Luracast/Restler/blob/master/PARAM.md 


