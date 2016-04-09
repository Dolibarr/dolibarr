# FixedColumns

When making use of DataTables' x-axis scrolling feature (`scrollX`), you may wish to fix the left or right most columns in place. This extension for DataTables provides exactly this option (for non-scrolling tables, please use the FixedHeader extension, which can fix the header and footer).


# Installation

To use FixedColumns the primary way to obtain the software is to use the [DataTables downloader](//datatables.net/download). You can also include the individual files from the [DataTables CDN](//cdn.datatables.net). See the [documentation](http://datatables.net/extensions/fixedcolumns/) for full details.

## NPM and Bower

If you prefer to use a package manager such as NPM or Bower, distribution repositories are available with software built from this repository under the name `datatables.net-fixedcolumns`. Styling packages for Bootstrap, Foundation and other styling libraries are also available by adding a suffix to the package name.

Please see the DataTables [NPM](//datatables.net/download/npm) and [Bower](//datatables.net/download/bower) installation pages for further information. The [DataTables installation manual](//datatables.net/manual/installation) also has details on how to use package managers with DataTables.


# Basic usage

FixedColumns is initialised using the `fixedColumns` option in the DataTables constructor - a simple boolean `true` will enable the feature. Further options can be specified using this option as an object - see the documentation for details. DataTables' scrolling options should also be enabled to use this feature.

Example:

```js
$(document).ready(function() {
	var table = $('#example').DataTable( {
		scrollY:        "300px",
		scrollX:        true,
		scrollCollapse: true,
		paging:         false,
		fixedColumns:   true
	} );
} );
```


# Documentation / support

* [Documentation](https://datatables.net/extensions/fixedcolumns/)
* [DataTables support forums](http://datatables.net/forums)


# GitHub

If you fancy getting involved with the development of FixedColumns and help make it better, please refer to its [GitHub repo](https://github.com/DataTables/FixedColumns).
