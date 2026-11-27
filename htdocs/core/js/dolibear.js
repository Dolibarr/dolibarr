// Copyright (C) 2026		iProspective			<contact@iprospective.fr>
//
// This program is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program. If not, see <https://www.gnu.org/licenses/>.

//
// \file       htdocs/core/js/dolibear.js
// \brief      Easter egg: the DoliBear, official mascot of Dolibarr, walks on the page
//             and makes elements vanish with its magic wand.
//             Loaded by core/tpl/easteregg_dolibear.tpl.php when "dolibear" is searched.
//             Purely visual: nothing is removed from the DOM, reloading the page restores it.
//             Click on the bear or press Escape to stop it (elements come back).
//             Also available from the console: Dolibear.start() / Dolibear.stop()
//

(function () {
	'use strict';

	if (window.Dolibear) {
		return;
	}

	var BEAR_W = 140;
	var BEAR_H = 168;
	var SPEED = 280;		// px per second
	var MAX_TARGETS = 7;
	var SELECTORS = [
		'tr.oddeven',
		'.butAction, .butActionRefused, .butActionDelete, .butActionNew',
		'.btnTitle',
		'li.tmenu',
		'div.menu_titre, div.menu_contenu',
		'.liste_titre th, .liste_titre td',
		'.pagination li',
		'.titre, .titre_right',
		'img.photo, img.photoref',
		'.badge',
		'.login_block_elem, .login_block_other',
		'.select2-container',
		'input.button, button.button'
	];
	var SPELLS = ['Abracadabra!', 'Hocus pocus!', 'Poof!', '\u2726 \u2726 \u2726', 'Bibbidi-bobbidi-boo!'];

	// The DoliBear: plush bear with its Dolibarr T-shirt (after the official mascot), plus a magic wand.
	var SVG = ''
		+ '<svg viewBox="0 0 200 240" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">'
		+ '<defs>'
		+ '<radialGradient id="dolibear-fur" cx="40%" cy="30%" r="80%"><stop offset="0" stop-color="#f7e4c0"/><stop offset="1" stop-color="#dcb985"/></radialGradient>'
		+ '<radialGradient id="dolibear-cream" cx="45%" cy="35%" r="75%"><stop offset="0" stop-color="#fffaf0"/><stop offset="1" stop-color="#efdcb8"/></radialGradient>'
		+ '<linearGradient id="dolibear-shirt" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#ffffff"/><stop offset="1" stop-color="#eef1f5"/></linearGradient>'
		+ '</defs>'
		+ '<ellipse cx="100" cy="233" rx="46" ry="6" fill="#000" fill-opacity=".14"/>'
		+ '<g class="db-body">'
		// legs
		+ '<g class="db-leg-l"><ellipse cx="80" cy="204" rx="21" ry="25" fill="url(#dolibear-fur)"/><ellipse cx="80" cy="223" rx="16" ry="9" fill="url(#dolibear-cream)"/></g>'
		+ '<g class="db-leg-r"><ellipse cx="120" cy="204" rx="21" ry="25" fill="url(#dolibear-fur)"/><ellipse cx="120" cy="223" rx="16" ry="9" fill="url(#dolibear-cream)"/></g>'
		// left arm
		+ '<g class="db-arm-l"><ellipse cx="54" cy="160" rx="14" ry="27" transform="rotate(22 54 160)" fill="url(#dolibear-fur)"/><ellipse cx="45" cy="181" rx="11" ry="10" fill="url(#dolibear-cream)"/></g>'
		// belly and T-shirt
		+ '<ellipse cx="100" cy="168" rx="47" ry="45" fill="url(#dolibear-fur)"/>'
		+ '<path d="M60 131 Q100 119 140 131 L157 149 Q151 160 141 157 L143 192 Q100 205 57 192 L59 157 Q49 160 43 149 Z" fill="url(#dolibear-shirt)" stroke="#dfe3e8" stroke-width="1.5"/>'
		+ '<path d="M83 127 Q100 140 117 127" fill="none" stroke="#dfe3e8" stroke-width="2"/>'
		+ '<g class="db-text"><text x="127" y="160" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-size="6.5" fill="#263c5c">ERP/CRM</text>'
		+ '<text x="100" y="179" text-anchor="middle" font-family="\'Arial Rounded MT Bold\', Nunito, \'Trebuchet MS\', Arial, sans-serif" font-weight="bold" font-size="19" textLength="76" lengthAdjust="spacingAndGlyphs" fill="#263c5c">Dolibarr</text></g>'
		// head
		+ '<g class="db-head">'
		+ '<circle cx="58" cy="60" r="20" fill="url(#dolibear-fur)"/><circle cx="58" cy="60" r="11" fill="#f2d3a6"/>'
		+ '<circle cx="142" cy="60" r="20" fill="url(#dolibear-fur)"/><circle cx="142" cy="60" r="11" fill="#f2d3a6"/>'
		+ '<ellipse cx="100" cy="90" rx="51" ry="46" fill="url(#dolibear-fur)"/>'
		+ '<ellipse cx="69" cy="106" rx="8" ry="5" fill="#ff8f8f" fill-opacity=".3"/><ellipse cx="131" cy="106" rx="8" ry="5" fill="#ff8f8f" fill-opacity=".3"/>'
		+ '<ellipse cx="100" cy="108" rx="25" ry="19" fill="url(#dolibear-cream)"/>'
		+ '<g class="db-eyes"><circle cx="81" cy="83" r="5.5" fill="#141414"/><circle cx="119" cy="83" r="5.5" fill="#141414"/>'
		+ '<circle cx="83" cy="81" r="1.8" fill="#fff"/><circle cx="121" cy="81" r="1.8" fill="#fff"/></g>'
		+ '<path d="M88 96 Q100 87 112 96 Q110 107 100 109 Q90 107 88 96 Z" fill="#5a2e1a"/>'
		+ '<ellipse cx="96" cy="94" rx="3.5" ry="1.8" fill="#fff" fill-opacity=".35"/>'
		+ '<path d="M100 109 L100 115 M89 113 Q100 124 111 113" fill="none" stroke="#3a2416" stroke-width="2.2" stroke-linecap="round"/>'
		+ '</g>'
		// right arm with the magic wand
		+ '<g class="db-arm-r">'
		+ '<line x1="152" y1="178" x2="180" y2="104" stroke="#4b2e5e" stroke-width="5" stroke-linecap="round"/>'
		+ '<line x1="175" y1="117" x2="180" y2="104" stroke="#f3f0ff" stroke-width="5" stroke-linecap="round"/>'
		+ '<g class="db-star"><path d="M181 91 L183.65 97.36 L190.51 97.91 L185.28 102.39 L186.88 109.09 L181 105.5 L175.12 109.09 L176.72 102.39 L171.49 97.91 L178.35 97.36 Z" fill="#ffc83d" stroke="#f0a000" stroke-width="1" stroke-linejoin="round"/></g>'
		+ '<ellipse cx="146" cy="160" rx="14" ry="27" transform="rotate(-22 146 160)" fill="url(#dolibear-fur)"/><ellipse cx="155" cy="181" rx="11" ry="10" fill="url(#dolibear-cream)"/>'
		+ '</g>'
		+ '</g>'
		+ '</svg>';

	var state = null;

	function reducedMotion() {
		return !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
	}

	function clamp(v, min, max) {
		return Math.max(min, Math.min(max, v));
	}

	function rand(min, max) {
		return min + Math.random() * (max - min);
	}

	function shuffle(list) {
		for (var i = list.length - 1; i > 0; i--) {
			var j = Math.floor(Math.random() * (i + 1));
			var t = list[i];
			list[i] = list[j];
			list[j] = t;
		}
		return list;
	}

	function aborted() {
		return !state || state.aborted;
	}

	function sleep(ms) {
		return new Promise(function (resolve) {
			setTimeout(resolve, ms);
		});
	}

	function center(rect) {
		return {x: rect.left + rect.width / 2, y: rect.top + rect.height / 2};
	}

	function inViewport(rect) {
		var c = center(rect);
		return c.x > 0 && c.y > 0 && c.x < window.innerWidth && c.y < window.innerHeight;
	}

	/**
	 * Is the element a good target for the wand: visible, in the viewport, neither tiny nor huge
	 */
	function isCandidate(el) {
		if (el.closest('#dolibear-stage')) {
			return false;
		}
		var r = el.getBoundingClientRect();
		if (r.width < 12 || r.height < 8 || r.width > window.innerWidth * 0.85 || r.height > window.innerHeight * 0.4) {
			return false;
		}
		if (!inViewport(r)) {
			return false;
		}
		var cs = window.getComputedStyle(el);
		return cs.visibility !== 'hidden' && cs.display !== 'none' && parseFloat(cs.opacity) > 0.1;
	}

	/**
	 * Choose a varied set of targets (round robin on kinds of elements), ordered as a walk
	 */
	function pickTargets(start) {
		var groups = [];
		var seen = new Set();
		SELECTORS.forEach(function (sel) {
			var list = [];
			document.querySelectorAll(sel).forEach(function (el) {
				if (!seen.has(el) && isCandidate(el)) {
					seen.add(el);
					list.push(el);
				}
			});
			if (list.length) {
				groups.push(shuffle(list));
			}
		});
		shuffle(groups);

		var chosen = [];
		var progress = true;
		while (chosen.length < MAX_TARGETS && progress) {
			progress = false;
			for (var g = 0; g < groups.length && chosen.length < MAX_TARGETS; g++) {
				var el = groups[g].shift();
				if (!el) {
					continue;
				}
				progress = true;
				var nested = chosen.some(function (c) {
					return c.contains(el) || el.contains(c);
				});
				if (!nested) {
					chosen.push(el);
				}
			}
		}

		// Nearest neighbour walk from the starting point
		var route = [];
		var here = start;
		while (chosen.length) {
			var best = 0;
			var bestDist = Infinity;
			chosen.forEach(function (el, i) {
				var c = center(el.getBoundingClientRect());
				var d = Math.hypot(c.x - here.x, c.y - here.y);
				if (d < bestDist) {
					bestDist = d;
					best = i;
				}
			});
			var next = chosen.splice(best, 1)[0];
			route.push(next);
			here = center(next.getBoundingClientRect());
		}
		return route;
	}

	function place(pos) {
		state.pos = pos;
		state.bear.style.transform = 'translate(' + Math.round(pos.x) + 'px,' + Math.round(pos.y) + 'px)';
	}

	function face(dx) {
		if (Math.abs(dx) > 2) {
			state.bear.classList.toggle('db-left', dx < 0);
		}
	}

	function say(text, duration) {
		var bubble = state.bubble;
		bubble.textContent = text;
		bubble.classList.add('db-show');
		clearTimeout(state.bubbleTimer);
		state.bubbleTimer = setTimeout(function () {
			bubble.classList.remove('db-show');
		}, duration || 1300);
	}

	function walkTo(pos) {
		var from = state.pos;
		var dx = pos.x - from.x;
		var dy = pos.y - from.y;
		var dist = Math.hypot(dx, dy);
		if (dist < 4 || aborted()) {
			return Promise.resolve();
		}
		face(dx);
		state.bear.classList.add('db-walking');
		var anim = state.bear.animate([
			{transform: 'translate(' + from.x + 'px,' + from.y + 'px)'},
			{transform: 'translate(' + pos.x + 'px,' + pos.y + 'px)'}
		], {duration: Math.max(350, dist / SPEED * 1000), easing: 'ease-in-out', fill: 'forwards'});
		state.anim = anim;
		return anim.finished.then(function () {
			if (state && !state.aborted) {
				place(pos);
				anim.cancel();
				state.bear.classList.remove('db-walking');
			}
		}, function () {});
	}

	/**
	 * Where the bear stands to cast a spell on an element: beside it, else below / above it
	 */
	function spotFor(el) {
		var r = el.getBoundingClientRect();
		var vw = window.innerWidth;
		var vh = window.innerHeight;
		var y = clamp(r.top + r.height / 2 - BEAR_H * 0.45, 0, vh - BEAR_H);
		if (r.left - BEAR_W - 30 >= 0) {
			return {x: r.left - BEAR_W - 30, y: y};
		}
		if (r.right + 30 + BEAR_W <= vw) {
			return {x: r.right + 30, y: y};
		}
		var x = clamp(r.left + Math.min(r.width / 2, 160) - BEAR_W / 2, 0, vw - BEAR_W);
		if (r.bottom + 10 + BEAR_H <= vh) {
			return {x: x, y: r.bottom + 10};
		}
		return {x: x, y: clamp(r.top - BEAR_H - 10, 0, vh - BEAR_H)};
	}

	function particle(cls, text) {
		var p = document.createElement('div');
		p.className = 'dolibear-spark' + (cls ? ' ' + cls : '');
		if (text) {
			p.textContent = text;
		}
		state.stage.appendChild(p);
		return p;
	}

	function removeAfter(anim, el) {
		anim.finished.then(function () {
			el.remove();
		}, function () {
			el.remove();
		});
	}

	/**
	 * A trail of stars flying in an arc from the wand to the target
	 */
	function magicTrail(from, to) {
		var kinds = [['db-star-spark', '✦'], ['', ''], ['db-star-spark db-pink', '✧'], ['db-star-spark db-blue', '★'], ['', '']];
		var lift = Math.min(120, Math.hypot(to.x - from.x, to.y - from.y) * 0.35);
		var last = null;
		for (var i = 0; i < 9; i++) {
			var k = kinds[i % kinds.length];
			var p = particle(k[0], k[1]);
			var wobble = rand(-18, 18);
			var frames = [];
			for (var s = 0; s <= 6; s++) {
				var t = s / 6;
				var x = (1 - t) * (1 - t) * from.x + 2 * (1 - t) * t * ((from.x + to.x) / 2 + wobble) + t * t * to.x;
				var y = (1 - t) * (1 - t) * from.y + 2 * (1 - t) * t * (Math.min(from.y, to.y) - lift) + t * t * to.y;
				frames.push({transform: 'translate(' + x + 'px,' + y + 'px) scale(' + (0.6 + Math.sin(t * Math.PI)) + ') rotate(' + (t * 360) + 'deg)', opacity: s === 6 ? 0.2 : 1});
			}
			var anim = p.animate(frames, {duration: 520, delay: i * 40, easing: 'ease-in', fill: 'both'});
			removeAfter(anim, p);
			last = anim;
		}
		return last.finished.catch(function () {});
	}

	/**
	 * The element vanishes in a puff of smoke and a burst of sparkles
	 */
	function vanish(el) {
		var r = el.getBoundingClientRect();
		var c = center(r);
		var size = clamp(Math.max(r.width, r.height) * 0.7, 50, 180);

		var puff = document.createElement('div');
		puff.className = 'dolibear-puff';
		puff.style.width = size + 'px';
		puff.style.height = size + 'px';
		state.stage.appendChild(puff);
		var pa = puff.animate([
			{transform: 'translate(' + (c.x - size / 2) + 'px,' + (c.y - size / 2) + 'px) scale(.2)', opacity: 1},
			{transform: 'translate(' + (c.x - size / 2) + 'px,' + (c.y - size / 2) + 'px) scale(1.5)', opacity: 0}
		], {duration: 700, easing: 'ease-out', fill: 'forwards'});
		removeAfter(pa, puff);

		var chars = ['✦', '✧', '★', '', ''];
		var colors = ['', 'db-pink', 'db-blue'];
		for (var i = 0; i < 16; i++) {
			var ch = chars[i % chars.length];
			var p = particle(ch ? 'db-star-spark ' + colors[i % colors.length] : '', ch);
			var angle = rand(0, Math.PI * 2);
			var dist = rand(30, 50 + size / 2);
			var sa = p.animate([
				{transform: 'translate(' + c.x + 'px,' + c.y + 'px) scale(.4)', opacity: 1},
				{transform: 'translate(' + (c.x + Math.cos(angle) * dist) + 'px,' + (c.y + Math.sin(angle) * dist) + 'px) scale(1.2) rotate(' + rand(-180, 180) + 'deg)', opacity: 0}
			], {duration: rand(500, 900), easing: 'cubic-bezier(.2,.8,.4,1)', fill: 'forwards'});
			removeAfter(sa, p);
		}

		var record = {el: el, visibility: el.style.visibility};
		record.anim = el.animate([
			{transform: 'none', opacity: 1, filter: 'none'},
			{transform: 'scale(1.06) rotate(-2deg)', opacity: 1, filter: 'brightness(1.6)', offset: 0.25},
			{transform: 'scale(.1) rotate(10deg)', opacity: 0, filter: 'blur(3px)'}
		], {duration: 650, easing: 'cubic-bezier(.55,-0.35,.75,.95)', fill: 'forwards'});
		record.anim.finished.then(function () {
			el.style.visibility = 'hidden';
		}, function () {});
		state.vanished.push(record);
	}

	async function cast(el) {
		var bear = state.bear;
		var b = center(bear.getBoundingClientRect());
		face(center(el.getBoundingClientRect()).x - b.x);
		bear.classList.add('db-casting');
		if (Math.random() < 0.6 || state.casts === 0) {
			say(state.casts === 0 ? SPELLS[0] : SPELLS[Math.floor(Math.random() * SPELLS.length)]);
		}
		state.casts++;
		await sleep(420);
		if (aborted()) {
			return;
		}
		var from = center(bear.querySelector('.db-star').getBoundingClientRect());
		await magicTrail(from, center(el.getBoundingClientRect()));
		if (aborted()) {
			return;
		}
		vanish(el);
		await sleep(450);
		bear.classList.remove('db-casting');
		if (Math.random() < 0.5) {
			bear.classList.add('db-hop');
			await sleep(500);
			bear.classList.remove('db-hop');
		}
	}

	function buildStage() {
		var stage = document.createElement('div');
		stage.id = 'dolibear-stage';
		var bear = document.createElement('div');
		bear.className = 'dolibear';
		bear.title = 'DoliBear';
		var flip = document.createElement('div');
		flip.className = 'dolibear-flip';
		flip.innerHTML = SVG;
		var bubble = document.createElement('div');
		bubble.className = 'dolibear-bubble';
		bear.appendChild(bubble);
		bear.appendChild(flip);
		stage.appendChild(bear);
		document.body.appendChild(stage);
		return {stage: stage, bear: bear, bubble: bubble};
	}

	function onKey(e) {
		if (e.key === 'Escape') {
			stop();
		}
	}

	function cleanup() {
		document.removeEventListener('keydown', onKey);
		if (state) {
			clearTimeout(state.bubbleTimer);
			state.stage.remove();
		}
		state = null;
	}

	async function run() {
		var vh = window.innerHeight;
		var vw = window.innerWidth;
		var bear = state.bear;

		if (state.reduce) {
			// No walk, no flight: the bear appears, one spell, everything fades
			place({x: vw - BEAR_W - 30, y: vh - BEAR_H - 20});
			face(-1);
			await bear.animate([{opacity: 0}, {opacity: 1}], {duration: 300}).finished;
			bear.classList.add('db-casting');
			say(SPELLS[0], 2500);
			state.targets.forEach(function (el) {
				var record = {el: el, visibility: el.style.visibility};
				record.anim = el.animate([{opacity: 1}, {opacity: 0}], {duration: 800, fill: 'forwards'});
				state.vanished.push(record);
			});
			await sleep(2800);
			if (!aborted()) {
				await bear.animate([{opacity: 1}, {opacity: 0}], {duration: 300, fill: 'forwards'}).finished;
				cleanup();
			}
			return;
		}

		// Enter from the bottom left corner, say hello
		place({x: -BEAR_W - 10, y: vh - BEAR_H - 15});
		await walkTo({x: 30, y: vh - BEAR_H - 15});
		if (aborted()) {
			return;
		}
		say('Hello!', 1200);
		bear.classList.add('db-hop');
		await sleep(900);
		bear.classList.remove('db-hop');

		for (var i = 0; i < state.targets.length; i++) {
			var el = state.targets[i];
			if (aborted()) {
				return;
			}
			if (!el.isConnected || !inViewport(el.getBoundingClientRect())) {
				continue;
			}
			await walkTo(spotFor(el));
			if (aborted()) {
				return;
			}
			await cast(el);
			await sleep(250);
		}
		if (aborted()) {
			return;
		}

		// Goodbye: wave, then leave by the nearest side
		bear.classList.add('db-waving');
		say('Bye! \u2605', 1400);
		await sleep(1300);
		bear.classList.remove('db-waving');
		var exitX = state.pos.x + BEAR_W / 2 < vw / 2 ? -BEAR_W - 20 : vw + 20;
		await walkTo({x: exitX, y: state.pos.y});
		if (!aborted()) {
			cleanup();
		}
	}

	/**
	 * Start the show
	 *
	 * @return	void
	 */
	function start() {
		if (state || !document.body || !document.body.animate) {
			return;
		}
		var parts = buildStage();
		state = {
			stage: parts.stage,
			bear: parts.bear,
			bubble: parts.bubble,
			pos: {x: 0, y: 0},
			anim: null,
			vanished: [],
			casts: 0,
			aborted: false,
			reduce: reducedMotion(),
			bubbleTimer: null
		};
		state.targets = pickTargets({x: 0, y: window.innerHeight});
		state.bear.addEventListener('click', stop);
		document.addEventListener('keydown', onKey);
		run().catch(function (e) {
			console.error('DoliBear', e);
			cleanup();
		});
	}

	/**
	 * Stop the show: vanished elements come back, the bear leaves
	 *
	 * @return	void
	 */
	function stop() {
		if (!state || state.aborted) {
			return;
		}
		var current = state;
		current.aborted = true;
		if (current.anim) {
			current.anim.cancel();
		}
		current.vanished.forEach(function (record) {
			record.anim.cancel();
			record.el.style.visibility = record.visibility;
			record.el.animate([{opacity: 0, transform: 'scale(.6)'}, {opacity: 1, transform: 'none'}], {duration: 400, easing: 'ease-out'});
		});
		current.bear.classList.remove('db-walking', 'db-casting');
		current.bubble.textContent = 'Oops!';
		current.bubble.classList.add('db-show');
		current.bear.animate([{opacity: 1}, {opacity: 0, transform: current.bear.style.transform + ' scale(.3)'}], {duration: 600, delay: 500, fill: 'forwards'})
			.finished.then(cleanup, cleanup);
	}

	window.Dolibear = {start: start, stop: stop};

	var script = document.currentScript;
	if (script && script.getAttribute('data-dolibear-autostart') === '1') {
		var launch = function () {
			setTimeout(start, 600);
		};
		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', launch);
		} else {
			launch();
		}
	}
})();
