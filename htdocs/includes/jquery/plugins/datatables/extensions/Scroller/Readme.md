# Scroller

Scroller is a virtual rendering plug-in for DataTables which allows large datasets to be drawn on screen every quickly. What the virtual rendering means is that only the visible portion of the table (and a bit to either side to make the scrolling smooth) is drawn, while the scrolling container gives the visual impression that the whole table is visible. This is done by making use of the pagination abilities of DataTables and moving the table around in the scrolling container DataTables adds to the page. The scrolling container is forced to the height it would be for the full table display using an extra element.

Key features include:

* Speed! The aim of Scroller for DataTables is to make rendering large data sets fast
* Full compatibility with DataTables' deferred rendering for maximum speed
* Integration with state saving in DataTables (scrolling position is saved)
* Support for scrolling with millions of rows
* Easy to use


# Installation

To use Scroller the primary way to obtain the software is to use the [DataTables downloader](//datatables.net/download). You can also include the individual files from the [DataTables CDN](//cdn.datatables.net). See the [documentation](http://datatables.net/extensions/scroller/) for full details.

## NPM and Bower

If you prefer to use a package manager such as NPM or Bower, distribution repositories are available with software built from this repository under the name `datatables.net-scroller`. Styling packages for Bootstrap, Foundation and other styling libraries are also available by adding a suffix to the package name.

Please see the DataTables [NPM](//datatables.net/download/npm) and [Bower](//datatables.net/download/bower) installation pages for further information. The [DataTables installation manual](//datatables.net/manual/installation) also has details on how to use package managers with DataTables.


# Basic usage

Scroller is initialised using the `scroller` option in the DataTables constructor - a simple boolean `true` will enable the feature. Further options can be specified using this option as an object - see the documentation for details.

```js
$(document).ready( function () {
	$('#example').DataTable( {
		scroller: true
	} );
} );
```

Note that rows in the table must all be the same height. Information in a cell which expands on to multiple lines will cause some odd behaviour in the scrolling. Additionally, the table's `cellspacing` parameter must be set to 0, again to ensure the information display is correct.


# Documentation / support

* [Documentation](https://datatables.net/extensions/scroller/)
* [DataTables support forums](http://datatables.net/forums)


# GitHub

If you fancy getting involved with the development of Scroller and help make it better, please refer to its [GitHub repo](https://github.com/DataTables/Scroller)

