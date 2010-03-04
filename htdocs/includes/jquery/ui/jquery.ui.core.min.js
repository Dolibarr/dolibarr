/*!
 * jQuery UI 1.8rc3
 *
 * Copyright (c) 2010 AUTHORS.txt (http://jqueryui.com/about)
 * Dual licensed under the MIT (MIT-LICENSE.txt)
 * and GPL (GPL-LICENSE.txt) licenses.
 *
 * http://docs.jquery.com/UI
 *//*
 * jQuery UI 1.8rc3
 *
 * Copyright (c) 2010 AUTHORS.txt (http://jqueryui.com/about)
 * Dual licensed under the MIT (MIT-LICENSE.txt)
 * and GPL (GPL-LICENSE.txt) licenses.
 *
 * http://docs.jquery.com/UI
 */
jQuery.ui||(function(b){var a=b.browser.mozilla&&(parseFloat(b.browser.version)<1.9);b.ui={version:"1.8rc3",plugin:{add:function(d,e,g){var f=b.ui[d].prototype;for(var c in g){f.plugins[c]=f.plugins[c]||[];f.plugins[c].push([e,g[c]])}},call:function(c,e,d){var g=c.plugins[e];if(!g||!c.element[0].parentNode){return}for(var f=0;f<g.length;f++){if(c.options[g[f][0]]){g[f][1].apply(c.element,d)}}}},contains:function(d,c){return document.compareDocumentPosition?d.compareDocumentPosition(c)&16:d!==c&&d.contains(c)},hasScroll:function(f,d){if(b(f).css("overflow")=="hidden"){return false}var c=(d&&d=="left")?"scrollLeft":"scrollTop",e=false;if(f[c]>0){return true}f[c]=1;e=(f[c]>0);f[c]=0;return e},isOverAxis:function(d,c,e){return(d>c)&&(d<(c+e))},isOver:function(h,d,g,f,c,e){return b.ui.isOverAxis(h,g,c)&&b.ui.isOverAxis(d,f,e)},keyCode:{BACKSPACE:8,CAPS_LOCK:20,COMMA:188,CONTROL:17,DELETE:46,DOWN:40,END:35,ENTER:13,ESCAPE:27,HOME:36,INSERT:45,LEFT:37,NUMPAD_ADD:107,NUMPAD_DECIMAL:110,NUMPAD_DIVIDE:111,NUMPAD_ENTER:108,NUMPAD_MULTIPLY:106,NUMPAD_SUBTRACT:109,PAGE_DOWN:34,PAGE_UP:33,PERIOD:190,RIGHT:39,SHIFT:16,SPACE:32,TAB:9,UP:38}};b.fn.extend({_focus:b.fn.focus,focus:function(c,d){return typeof c==="number"?this.each(function(){var e=this;setTimeout(function(){b(e).focus();(d&&d.call(e))},c)}):this._focus.apply(this,arguments)},enableSelection:function(){return this.attr("unselectable","off").css("MozUserSelect","").unbind("selectstart.ui")},disableSelection:function(){return this.attr("unselectable","on").css("MozUserSelect","none").bind("selectstart.ui",function(){return false})},scrollParent:function(){var c;if((b.browser.msie&&(/(static|relative)/).test(this.css("position")))||(/absolute/).test(this.css("position"))){c=this.parents().filter(function(){return(/(relative|absolute|fixed)/).test(b.curCSS(this,"position",1))&&(/(auto|scroll)/).test(b.curCSS(this,"overflow",1)+b.curCSS(this,"overflow-y",1)+b.curCSS(this,"overflow-x",1))}).eq(0)}else{c=this.parents().filter(function(){return(/(auto|scroll)/).test(b.curCSS(this,"overflow",1)+b.curCSS(this,"overflow-y",1)+b.curCSS(this,"overflow-x",1))}).eq(0)}return(/fixed/).test(this.css("position"))||!c.length?b(document):c},zIndex:function(f){if(f!==undefined){return this.css("zIndex",f)}if(this.length){var d=b(this[0]),c,e;while(d.length&&d[0]!==document){c=d.css("position");if(c=="absolute"||c=="relative"||c=="fixed"){e=parseInt(d.css("zIndex"));if(!isNaN(e)&&e!=0){return e}}d=d.parent()}}return 0}});b.extend(b.expr[":"],{data:function(e,d,c){return !!b.data(e,c[3])},focusable:function(d){var e=d.nodeName.toLowerCase(),c=b.attr(d,"tabindex");return(/input|select|textarea|button|object/.test(e)?!d.disabled:"a"==e||"area"==e?d.href||!isNaN(c):!isNaN(c))&&!b(d)["area"==e?"parents":"closest"](":hidden").length},tabbable:function(d){var c=b.attr(d,"tabindex");return(isNaN(c)||c>=0)&&b(d).is(":focusable")}})})(jQuery);