# RowReorder

RowReorder adds the ability for rows in a DataTable to be reordered through user interaction with the table (click and drag / touch and drag). Integration with Editor's multi-row editing feature is also available to update rows immediately. 


# Installation

To use RowReorder the primary way to obtain the software is to use the [DataTables downloader](//datatables.net/download). You can also include the individual files from the [DataTables CDN](//cdn.datatables.net). See the [documentation](http://datatables.net/extensions/rowreorder/) for full details.

## NPM and Bower

If you prefer to use a package manager such as NPM or Bower, distribution repositories are available with software built from this repository under the name `datatables.net-rowreorder`. Styling packages for Bootstrap, Foundation and other styling libraries are also available by adding a suffix to the package name.

Please see the DataTables [NPM](//datatables.net/download/npm) and [Bower](//datatables.net/download/bower) installation pages for further information. The [DataTables installation manual](//datatables.net/manual/installation) also has details on how to use package managers with DataTables.


# Basic usage

RowReorder is initialised using the `rowReorder` option in the DataTables constructor - a simple boolean `true` will enable the feature. Further options can be specified using this option as an object - see the documentation for details.

Example:

```js
$(document).ready( function () {
    $('#myTable').DataTable( {
    	rowReorder: true
    } );
} );
```


# Documentation / support

* [Documentation](https://datatables.net/extensions/rowreorder/)
* [DataTables support forums](http://datatables.net/forums)


# GitHub

If you fancy getting involved with the development of RowReorder and help make it better, please refer to its [GitHub repo](https://github.com/DataTables/RowReorder).

