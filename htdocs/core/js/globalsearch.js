/* Copyright (C) 2026		Michael Wallis			<mh.wallis@gmail.com>
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
 * Global search box showing the matching records while typing.
 * Used when MAIN_SEARCHBOX_SHOW_RECORDS is on. Settings and texts are read from the data-* attributes of the input
 * (see function globalSearchInput() in main.inc.php).
 *
 * @param {HTMLInputElement} input	The search input
 * @return {void}
 */
function initGlobalSearch(input)
{
	if (!input || input.dataset.globalsearchDone) {
		return;
	}
	input.dataset.globalsearchDone = '1';

	var panel = document.getElementById(input.getAttribute('aria-controls'));
	var container = input.dataset.container ? document.querySelector(input.dataset.container) : null;
	if (!container) {
		/* Move the panel out of the left menu, so it is not cut or moved by the menu (overflow, transform...) */
		document.body.appendChild(panel);
	}
	var minchars = parseInt(input.dataset.minchars, 10) || 3;
	var sections = [];
	try {
		sections = JSON.parse(input.dataset.sections || '[]');
	} catch (e) {
		sections = [];
	}
	var timer = null;
	var controller = null;
	var lastquery = null;
	var itemcount = 0;
	var openfirst = false;

	/* Remove accents and case so "e" matches "é" */
	function normalize(s)
	{
		return s.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
	}

	function escapeHtml(s)
	{
		return String(s).replace(/[&<>"']/g, function (c) {
			return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c];
		});
	}

	/* Words searched, same split as the server search (space means AND, | means OR) */
	function searchWords(q)
	{
		return q.split(/[\s|]+/).map(function (w) {
			return normalize(w.replace(/^[-!=]+|["%*]/g, ''));
		}).filter(function (w) {
			return w.length > 0;
		});
	}

	/* Escape a text and put the parts matching the words into <mark> */
	function highlight(text, words)
	{
		var chars = Array.from(String(text || ''));
		var normtext = '';
		var map = [];
		chars.forEach(function (c, i) {
			var n = normalize(c);
			for (var k = 0; k < n.length; k++) {
				map.push(i);
			}
			normtext += n;
		});
		var marked = new Array(chars.length).fill(false);
		words.forEach(function (w) {
			var pos = normtext.indexOf(w);
			while (pos >= 0) {
				for (var k = pos; k < pos + w.length; k++) {
					marked[map[k]] = true;
				}
				pos = normtext.indexOf(w, pos + w.length);
			}
		});
		var out = '';
		var open = false;
		chars.forEach(function (c, i) {
			if (marked[i] && !open) {
				out += '<mark>';
				open = true;
			} else if (!marked[i] && open) {
				out += '</mark>';
				open = false;
			}
			out += escapeHtml(c);
		});
		return out + (open ? '</mark>' : '');
	}

	function withSearch(url, q)
	{
		/* Section urls of the page are built without the search string */
		if (q === '' || url.indexOf('search_all=') >= 0) {
			return url;
		}
		return url + (url.indexOf('?') >= 0 ? '&' : '?') + 'search_all=' + encodeURIComponent(q.replace(/"/g, ''));
	}

	function item(url, html, cssclass)
	{
		itemcount++;
		return '<a class="globalsearch-item' + (cssclass ? ' ' + cssclass : '') + '" role="option" id="' + panel.id + '-item' + itemcount + '" href="' + escapeHtml(url) + '">' + html + '</a>';
	}

	/* Put the panel below the input (the panel is position fixed, except when inside the top menu dropdown) */
	function place()
	{
		if (panel.hidden || window.getComputedStyle(panel).position !== 'fixed') {
			return;
		}
		var r = input.getBoundingClientRect();
		panel.style.top = (r.bottom + 2) + 'px';
		panel.style.left = Math.max(10, Math.min(r.left, window.innerWidth - panel.offsetWidth - 10)) + 'px';
		panel.style.maxHeight = Math.max(150, window.innerHeight - r.bottom - 12) + 'px';
	}

	function show(html)
	{
		panel.innerHTML = html;
		panel.hidden = (html === '');
		place();
		input.setAttribute('aria-expanded', panel.hidden ? 'false' : 'true');
		if (container) {
			container.classList.toggle('globalsearch-active', !panel.hidden && input.value.trim().length >= minchars);
		}
		setActive(null);
	}

	function hide()
	{
		panel.hidden = true;
		input.setAttribute('aria-expanded', 'false');
		setActive(null);
	}

	/* Before the minimum number of characters, show the list of sections like the classic search box */
	function showSections(q)
	{
		itemcount = 0;
		if (q === '') {
			show('');
			return;
		}
		var html = '<div class="globalsearch-hint opacitymedium">' + escapeHtml(input.dataset.textTypemore) + '</div>';
		sections.forEach(function (s) {
			html += item(withSearch(s.url, q), s.text, 'globalsearch-section');
		});
		show(html);
		/* Preselect the first section so Enter searches into it, like the classic search box */
		setActive(panel.querySelector('.globalsearch-item'));
	}

	function showResults(q, data)
	{
		itemcount = 0;
		var words = searchWords(q);
		var html = '';
		var nbrecords = 0;
		(data.sections || []).forEach(function (s) {
			nbrecords += s.records.length;
			/* Sections without record keep their place, with only a link to search into their list */
			html += '<div class="globalsearch-group' + (s.records.length ? '' : ' globalsearch-empty') + '" role="group" aria-label="' + escapeHtml(s.label) + '">';
			html += '<div class="globalsearch-grouptitle">' + s.picto + '<span class="globalsearch-grouplabel">' + escapeHtml(s.label) + '</span>';
			if (s.records.length) {
				html += ' <span class="badge badge-secondary">' + s.total + '</span>';
				html += item(s.url, escapeHtml(input.dataset.textSeeall) + ' <span class="fa fa-arrow-right"></span>', 'globalsearch-seeall');
			} else {
				html += item(s.url, escapeHtml(input.dataset.textSearchinlist) + ' <span class="fa fa-arrow-right"></span>', 'globalsearch-seeall');
			}
			html += '</div>';
			s.records.forEach(function (r) {
				var line = r.picto + '<span class="globalsearch-text"><span class="globalsearch-ref">' + highlight(r.ref, words) + '</span>';
				if (r.label) {
					line += ' <span class="globalsearch-label">' + highlight(r.label, words) + '</span>';
				}
				if (r.sub) {
					line += ' <span class="globalsearch-sub opacitymedium">' + highlight(r.sub, words) + '</span>';
				}
				line += '</span>';
				line += '<span class="globalsearch-info">' + (r.amount ? '<span class="amount">' + escapeHtml(r.amount) + '</span>' : '') + (r.status || '') + '</span>';
				html += item(r.url, line, 'globalsearch-record');
			});
			html += '</div>';
		});
		if (!nbrecords) {
			html = '<div class="globalsearch-hint opacitymedium">' + escapeHtml(input.dataset.textNoresult) + '</div>' + html;
		}
		show(html);
		/* Preselect the first record so Enter opens it */
		var first = panel.querySelector('.globalsearch-record') || panel.querySelector('.globalsearch-item');
		setActive(first);
		if (openfirst && first) {
			window.location.href = first.href;
		}
		openfirst = false;
	}

	function search(q)
	{
		if (controller) {
			controller.abort();
		}
		controller = new AbortController();
		lastquery = q;
		panel.classList.add('globalsearch-loading');
		fetch(input.dataset.url + '?q=' + encodeURIComponent(q), {credentials: 'same-origin', signal: controller.signal})
			.then(function (response) {
				return response.json();
			})
			.then(function (data) {
				panel.classList.remove('globalsearch-loading');
				if (data.error === 'ERROR_NOT_LOGGED') {
					window.location.href = data.url;
					return;
				}
				if (q === lastquery && input.value.trim() === q) {
					showResults(q, data);
				}
			})
			.catch(function (e) {
				if (e.name !== 'AbortError') {
					panel.classList.remove('globalsearch-loading');
					console.log('globalsearch error', e);
					showSections(q);
				}
			});
	}

	function onInput()
	{
		var q = input.value.trim();
		clearTimeout(timer);
		if (q.length < minchars) {
			if (controller) {
				controller.abort();
			}
			lastquery = null;
			showSections(q);
			return;
		}
		if (q === lastquery && !panel.hidden) {
			return;
		}
		timer = setTimeout(function () {
			search(q);
		}, 250);
	}

	function items()
	{
		return Array.prototype.slice.call(panel.querySelectorAll('.globalsearch-item'));
	}

	function setActive(el)
	{
		items().forEach(function (i) {
			i.classList.remove('globalsearch-current');
			i.setAttribute('aria-selected', 'false');
		});
		if (el) {
			el.classList.add('globalsearch-current');
			el.setAttribute('aria-selected', 'true');
			input.setAttribute('aria-activedescendant', el.id);
			el.scrollIntoView({block: 'nearest'});
		} else {
			input.removeAttribute('aria-activedescendant');
		}
	}

	function move(step)
	{
		var list = items();
		if (!list.length) {
			return;
		}
		var pos = list.indexOf(panel.querySelector('.globalsearch-current'));
		pos = (pos < 0) ? (step > 0 ? 0 : list.length - 1) : (pos + step + list.length) % list.length;
		setActive(list[pos]);
	}

	input.addEventListener('input', onInput);
	input.addEventListener('focus', function () {
		if (panel.innerHTML !== '' && input.value.trim() !== '') {
			panel.hidden = false;
			input.setAttribute('aria-expanded', 'true');
			place();
		} else {
			onInput();
		}
	});
	input.addEventListener('keydown', function (e) {
		var q = input.value.trim();
		if (e.key === 'Escape') {
			if (!panel.hidden) {
				/* First Escape only closes the results (the browser would also clear the input) */
				e.preventDefault();
				hide();
			} else {
				input.value = '';
				show('');
			}
			return;
		}
		if (e.key !== 'ArrowDown' && e.key !== 'ArrowUp' && e.key !== 'Enter') {
			return;
		}
		if (q.length < minchars && (panel.hidden || !items().length)) {
			/* Nothing to choose here: keep the default behaviour of the search box */
			return;
		}
		e.preventDefault();
		e.stopPropagation();
		if (e.key !== 'Enter') {
			if (panel.hidden) {
				onInput();
			} else {
				move(e.key === 'ArrowDown' ? 1 : -1);
			}
			return;
		}
		var current = panel.hidden ? null : panel.querySelector('.globalsearch-current');
		if (!current && q.length >= minchars) {
			/* Results not yet received: search now and open the first result */
			clearTimeout(timer);
			openfirst = true;
			search(q);
		} else if (current) {
			if (e.ctrlKey || e.metaKey) {
				window.open(current.href, '_blank');
			} else {
				window.location.href = current.href;
			}
		}
	});
	panel.addEventListener('mousemove', function (e) {
		var el = e.target.closest('.globalsearch-item');
		if (el && !el.classList.contains('globalsearch-current')) {
			setActive(el);
		}
	});
	window.addEventListener('resize', place);
	window.addEventListener('scroll', place, true);
	document.addEventListener('click', function (e) {
		if (!panel.contains(e.target) && e.target !== input) {
			hide();
		}
	});
}

document.addEventListener('DOMContentLoaded', function () {
	document.querySelectorAll('input.globalsearch-input').forEach(initGlobalSearch);
});
