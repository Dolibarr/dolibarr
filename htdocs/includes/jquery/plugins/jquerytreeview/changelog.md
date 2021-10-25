1.4.1
-----
* Fix for #2360
* Added option cookieOptions: Passed through to $.cookie to set path, domain etc.
* Tested with jQuery 1.2.x and 1.4.3
* Fixed combination of persist: "location" and prerendered: true

1.4
---

* Added changelog (this file)
* Fixed tree control to search only for anchors, allowing images or other elements inside the controls, while keeping the control usable with the keyboard
* Restructured folder layout: root contains plugin resources, lib contains script dependencies, demo contains demos and related files
* Added prerendered option: If set to true, assumes all hitarea divs and classes already rendered, speeding up initialization for big trees, but more obtrusive
* Added jquery.treeview.async.js for ajax-lazy-loading trees, see async.html demo
* Exposed $.fn.treeview.classes for custom classes if necessary
* Show treecontrol only when JavaScript is enabled
* Completely reworked themeing via CSS sprites, resulting in only two files per theme
  * updated dotted, black, gray and red theme
  * added famfamfam theme (no lines)
* Improved cookie persistence to allow multiple persisted trees per page via cookieId option
* Improved location persistence by making it case-insensitive
* Improved swapClass and replaceClass plugin implementations
* Added folder-closed.gif to filetree example

1.3
---

* Fixes for all outstanding bugs
* Added persistence features
      * location based: click on a link in the treeview and reopen that link after the page loaded
      * cookie based: save the state of the tree in a cookie on each click and load that on reload
* smoothed animations, fixing flickering in both IE and Opera
* Tested in Firefox 2, IE 6 & 7, Opera 9, Safari 3
* Moved documentation to jQuery wiki
* Requires jQuery 1.2+
