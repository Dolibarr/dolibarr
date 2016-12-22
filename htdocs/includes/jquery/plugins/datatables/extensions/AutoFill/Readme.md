# AutoFill

AutoFill adds an Excel data fill like option to a DataTable to click and drag over multiple cells, filling in information over the selected cells and incrementing numbers as needed.


# Installation

To use AutoFill the best way to obtain the software is to use the [DataTables downloader](//datatables.net/download). You can also include the individual files from the [DataTables CDN](//cdn.datatables.net). See the [documentation](http://datatables.net/extensions/autofill/) for full details.

## NPM and Bower

If you prefer to use a package manager such as NPM or Bower, distribution repositories are available with software built from this repository under the name `datatables.net-autofill`. Styling packages for Bootstrap, Foundation and other styling libraries are also available by adding a suffix to the package name.

Please see the DataTables [NPM](//datatables.net/download/npm) and [Bower](//datatables.net/download/bower) installation pages for further information. The [DataTables installation manual](//datatables.net/manual/installation) also has details on how to use package managers with DataTables.


# Basic usage

AutoFill is initialised using the `autoFill` option in the DataTables constructor. Further options can be specified using this option as an object - see the documentation for details. For example:

```js
$(document).ready( function () {
    $('#example').DataTable( {
    	autoFill: true
    } );
} );
```


# Documentation / support

* [Documentation](https://datatables.net/extensions/autofill/)
* [DataTables support forums](http://datatables.net/forums)


# GitHub

If you fancy getting involved with the development of AutoFill and help make it better, please refer to its [GitHub repo](https://github.com/DataTables/AutoFill)

