# Buttons

The Buttons extension for DataTables provides a common set of options, API methods and styling to display buttons on a page that will interact with a DataTable. Modules are also provided for data export, printing and column visibility control.


# Installation

To use Buttons the primary way to obtain the software is to use the [DataTables downloader](//datatables.net/download). You can also include the individual files from the [DataTables CDN](//cdn.datatables.net). See the [documentation](http://datatables.net/extensions/buttons/) for full details.

## NPM and Bower

If you prefer to use a package manager such as NPM or Bower, distribution repositories are available with software built from this repository under the name `datatables.net-buttons`. Styling packages for Bootstrap, Foundation and other styling libraries are also available by adding a suffix to the package name.

Please see the DataTables [NPM](//datatables.net/download/npm) and [Bower](//datatables.net/download/bower) installation pages for further information. The [DataTables installation manual](//datatables.net/manual/installation) also has details on how to use package managers with DataTables.


# Basic usage

Buttons is initialised using the `buttons` option in the DataTables constructor, giving an array of the buttons that should be shown. Further options can be specified using this option as an object - see the documentation for details. For example:

```js
$(document).ready( function () {
    $('#example').DataTable( {
    	buttons: [ 'csv', 'excel', 'pdf', 'print' ]
    } );
} );
```


# Documentation / support

* [Documentation](https://datatables.net/extensions/buttons/)
* [DataTables support forums](http://datatables.net/forums)


# GitHub

If you fancy getting involved with the development of Buttons and help make it better, please refer to its [GitHub repo](https://github.com/DataTables/Buttons)

