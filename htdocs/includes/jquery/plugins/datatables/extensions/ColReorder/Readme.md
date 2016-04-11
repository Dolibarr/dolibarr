# ColReorder

ColReorder adds the ability for the end user to click and drag column headers to reorder a table as they see fit, to DataTables. See the [documentation](http://datatables.net/extensions/colreorder/) for full details.


# Installation

To use ColReorder the primary way to obtain the software is to use the [DataTables downloader](//datatables.net/download). You can also include the individual files from the [DataTables CDN](//cdn.datatables.net). See the [documentation](http://datatables.net/extensions/colreorder/) for full details.

## NPM and Bower

If you prefer to use a package manager such as NPM or Bower, distribution repositories are available with software built from this repository under the name `datatables.net-colreorder`. Styling packages for Bootstrap, Foundation and other styling libraries are also available by adding a suffix to the package name.

Please see the DataTables [NPM](//datatables.net/download/npm) and [Bower](//datatables.net/download/bower) installation pages for further information. The [DataTables installation manual](//datatables.net/manual/installation) also has details on how to use package managers with DataTables.


# Basic usage

ColReorder is initialised using the `colReorder` option in the DataTables constructor - a simple boolean `true` will enable the feature. Further options can be specified using this option as an object - see the documentation for details.

Example:

```js
$(document).ready( function () {
    $('#myTable').DataTable( {
    	colReorder: true
    } );
} );
```


# Documentation / support

* [Documentation](https://datatables.net/extensions/colreorder/)
* [DataTables support forums](http://datatables.net/forums)
