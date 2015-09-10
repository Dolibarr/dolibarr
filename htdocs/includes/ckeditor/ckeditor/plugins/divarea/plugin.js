/*
 Copyright (c) 2003-2014, CKSource - Frederico Knabben. All rights reserved.
 For licensing, see LICENSE.md or http://ckeditor.com/license
*/
CKEDITOR.plugins.add("divarea",{afterInit:function(a){a.addMode("wysiwyg",function(c){var b=CKEDITOR.dom.element.createFromHtml('<div class="cke_wysiwyg_div cke_reset" hidefocus="true"></div>');a.ui.space("contents").append(b);b=a.editable(b);b.detach=CKEDITOR.tools.override(b.detach,function(a){return function(){a.apply(this,arguments);this.remove()}});a.setData(a.getData(1),c);a.fire("contentDom")})}});