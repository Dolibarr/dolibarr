# jQuery Treeview

Lightweight and flexible transformation of an unordered list into an expandable and collapsable tree, great for unobtrusive navigation enhancements. Supports both location and cookie based persistence.

Provides some options for customizing, an async-tree extension and an experimental sortable extension.

![screenshot](https://raw.github.com/jzaefferer/jquery-treeview/master/screenshot.png)

### Note that this project is not actively maintained anymore.  
Check out [jqTree](http://mbraak.github.com/jqTree/) for a more up to date plugin.

---

#### [Demo](http://jquery.bassistance.de/treeview/demo/)

#### [Download](https://github.com/jzaefferer/jquery-treeview/zipball/1.4.1)

#### [Changelog](https://raw.github.com/jzaefferer/jquery-treeview/master/changelog.md)


## Todo

### 1.5
- Add classes and rules for root items
- Lazy-loading: render the complete tree, but only apply hitzones and hiding of children to the first level on load
- Async treeview
  - Support animations
  - Support persist options


## Documentation

```javascript
.treeview( options )
```

Takes an unordered list and makes all branches collapsable. The "treeview" class is added if not already present. To hide branches on first display, mark their li elements with the class "closed". If the "collapsed" option is used, mark initially open branches with class "open".


## License

Copyright (c) 2007 Jörn Zaefferer

Dual licensed under the MIT and GPL licenses:

- http://www.opensource.org/licenses/mit-license.php
- https://www.gnu.org/licenses/gpl.html
