/* Copyright (C) 2026 Eric Seigne <eric.seigne@cap-rel.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * TinyMCE <-> CKEditor 4 compatibility shim.
 *
 * Exposes a minimal subset of the CKEDITOR global so that legacy Dolibarr
 * pages can keep calling CKEDITOR.instances[name].getData()/setData()/
 * updateElement() while TinyMCE is the active WYSIWYG backend.
 *
 * Only the methods actually consumed by Dolibarr core are proxied.
 */
(function (global) {
	if (typeof global.tinymce === 'undefined') {
		return;
	}
	if (typeof global.CKEDITOR !== 'undefined') {
		return;
	}

	function wrap(editor) {
		return {
			_tiny: editor,
			getData: function () {
				return editor.getContent();
			},
			setData: function (value, options) {
				editor.setContent(value == null ? '' : String(value));
				if (options && typeof options.callback === 'function') {
					options.callback();
				}
			},
			updateElement: function () {
				editor.save();
			},
			insertHtml: function (html) {
				editor.insertContent(html);
			},
			insertText: function (text) {
				editor.insertContent(global.tinymce.DOM.encode(text));
			},
			focus: function () {
				editor.focus();
			},
			destroy: function () {
				editor.remove();
			},
			on: function (evt, cb) {
				var map = {
					'instanceReady': 'init',
					'change': 'change',
					'blur': 'blur',
					'focus': 'focus',
					'key': 'keyup'
				};
				editor.on(map[evt] || evt, cb);
			}
		};
	}

	var instancesProxy = new Proxy({}, {
		get: function (_obj, name) {
			var ed = global.tinymce.get(name);
			return ed ? wrap(ed) : undefined;
		},
		has: function (_obj, name) {
			return !!global.tinymce.get(name);
		},
		ownKeys: function () {
			return (global.tinymce.editors || []).map(function (e) {
				return e.id;
			});
		},
		getOwnPropertyDescriptor: function (_obj, name) {
			if (global.tinymce.get(name)) {
				return { enumerable: true, configurable: true };
			}
			return undefined;
		}
	});

	global.CKEDITOR = {
		_tinymceCompat: true,
		disableAutoInline: true,
		instances: instancesProxy,
		replace: function (name) {
			if (!global.tinymce.get(name)) {
				global.tinymce.init({ selector: '#' + name });
			}
			return instancesProxy[name];
		},
		on: function () { /* not supported in shim */ },
		dom: {
			element: {
				createFromHtml: function (html) {
					var div = document.createElement('div');
					div.innerHTML = html;
					return div.firstChild;
				}
			}
		}
	};
})(window);
