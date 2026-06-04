/**
 * QuickMemo Class
 * Manages interactive sticky notes (memos) with drag-and-drop, resizing,
 * Markdown support, and server-side persistence.
 */
class QuickMemo {

	/**
	 * @constructor
	 * @param {Object} param - Configuration parameters for the instance.
	 */
	constructor(param = {}) {

		/**
		 * @type {Object} defaultParams - Default configuration values
		 */
		const defaultParams = {
			interfaceUrl: '',
			archivesUrl: false,
			userWriteRight: false,
			userDeleteRight: false,
			elementId: 0,
			autoResizeFontSize: true,
			elementType: '',
			context: '',
			token: '',
			shareBtnStatus: 0,
			colors: ['#fff8a6', '#ffd6d6', '#d6ffd9', '#d6e6ff', '#f3d6ff', '#ffffff', '#f5f5f5'],
			publicFontAIcon: 'far fa-eye',
			privateFontAIcon: 'far fa-eye-slash',
			archiveFontAIcon: 'far fa-trash-alt',
			sharedFontAIcon: 'far fa-copy',
			notSharedFontAIcon: 'far fa-file',
			modelFontAIcon: 'far fa-share-square',
			menuDropDownId: 'quickmemo-create-dropdown',
			menuDropDownModelListContainerId: 'quickmemo-model-list',
			menuDropDownPresetListContainerId: 'quickmemo-preset-list'
		};

		// Merge default parameters with user-provided parameters
		this.param = {...defaultParams, ...param};

		/** @type {number} countedArchivedMemo - Local counter for archived notes */
		this.countedArchivedMemo = 0;

		// Validation of required parameters
		if (!this.param.interfaceUrl || (!this.param.elementId && this.param.elementType !== '')) {
			console.warn('QuickMemo: missing required parameters');
			return;
		}

		/** @type {HTMLElement} container - Global container (body) */
		this.container = document.body;

		/** @type {number} currentZ - Initial z-index for notes */
		this.currentZ = 200;

		/** @type {Array} memos - Stores objects containing {memoData, domElement} */
		this.memos = [];

		this.init();
	}

	/**
	 * Initializes the component by loading languages, fetching data, and setting up events.
	 * @async
	 */
	async init() {
		// Load translation files via Dolibarr tools
		await Dolibarr.tools.langs.load('quickmemo');
		await Dolibarr.tools.langs.load('main');
		await Dolibarr.tools.langs.load('other');

		// Load existing memos from server
		this.loadMemos();

		// Set initial z-indexes based on stored position
		this.memos.forEach((m, i) => {
			// Default pos_z if it does not exist
			if (!('pos_z' in m.memo) || m.memo.pos_z < 1) m.memo.pos_z = i + 1;
			// z-index CSS
			m.el.style.zIndex = this.currentZ + m.memo.pos_z;
		});

		// Ensure notes stay within viewport on window resize
		window.addEventListener('resize', () => this.ensureAllVisible());

		// Initialize top-bar menu if user has write permissions
		if (this.param.userWriteRight) {
			this.initMenuMemoDropdown();
		}

		// Setup global shortcut behaviors (Escape key)
		this.escapeKeyBehavior();
	}

	/**
	 * Manages the Escape key behavior to hide/show notes with animations.
	 */
	escapeKeyBehavior() {
		let escTimer = null;
		let escActivated = false;

		/** Hides all notes by setting display to none */
		function applyDisplayNone() {
			document.querySelectorAll('.quickmemo-note').forEach(el => {
				el.style.display = 'none';
			});
		}

		/** Restores notes visibility */
		function removeDisplayNone() {
			document.querySelectorAll('.quickmemo-note').forEach(el => {
				el.style.display = '';
			});
		}

		// Keydown handler: triggers after a delay to hide notes
		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape' && !escTimer) {
				escTimer = setTimeout(function () {
					document.body.classList.add('hide-quickmemo');
					escActivated = true;

					// Single global listener
					document.addEventListener('transitionend', function handler(ev) {
						if (ev.target.classList.contains('quickmemo-note')) {
							applyDisplayNone();
							document.removeEventListener('transitionend', handler);
						}
					});

				}, 300);
			}
		});

		// Keyup handler: restores notes when Escape is released
		document.addEventListener('keyup', function (e) {
			if (e.key === 'Escape') {
				clearTimeout(escTimer);
				escTimer = null;

				if (escActivated) {
					removeDisplayNone();

					requestAnimationFrame(() => {
						document.body.classList.remove('hide-quickmemo');
					});

					escActivated = false;
				}
			}
		});
	}

	/* ===============================
	   LOAD
	================================= */

	/**
	 * Fetches memos from the server and renders them.
	 * @async
	 */
	async loadMemos() {
		try {

			const res = await fetch(this.param.interfaceUrl + '?action=list', {
				method: 'POST',
				headers: {'Content-Type': 'application/x-www-form-urlencoded'},
				body: new URLSearchParams({
					element_id: this.param.elementId,
					element_type: this.param.elementType,
					context: this.param.context,
					token: this.param.token
				})
			});

			const json = await res.json();

			if (!json.result) {
				throw new Error(json.msg);
			}

			// Render each fetched memo
			json.data.memos.forEach(memo => this.renderMemo(memo));
			// Update the UI with the archive count
			this.setArchivesNb(json.data.nbArchives);

		} catch (err) {
			Dolibarr.tools.setEventMessage(Dolibarr.tools.langs.trans('QuickMemoLoadMemosError') + ' : ' + err, 'errors');
			console.error('QuickMemo load error', err);
		}
	}

	/**
	 * Updates the internal counter and the DOM badge for archived memos.
	 * @param {number|string} nb - Number of archived items.
	 */
	setArchivesNb(nb) {
		this.countedArchivedMemo = parseInt(nb);

		// Update badge
		if (document.getElementById('quick-note-archives-link-mumber')) {
			const nbArchiveBadge = document.getElementById('quick-note-archives-link-mumber');
			nbArchiveBadge.textContent = this.countedArchivedMemo;
		}
	}

	/**
	 * Finds the memo data object associated with a DOM element.
	 * @param {HTMLElement} el
	 * @returns {Object|boolean}
	 */
	getMemoFromElement(el) {
		const m = this.memos.find(x => x.el === el);
		if (!m) return false;
		return m;
	}

	/**
	 * Places the selected note at the end of the stack and re-indexes all notes from 1 to N.
	 * @param {HTMLElement} el
	 */
	bringToTop(el) {
		const m = this.getMemoFromElement(el);
		if (!m) return;

		// 1. Sort all notes by their current z-index
		const sortedMemos = [...this.memos].sort((a, b) =>
			(parseInt(a.memo.pos_z) || 0) - (parseInt(b.memo.pos_z) || 0)
		);

		// 2. Remove the current note from its position and push it to the end (the top)
		const others = sortedMemos.filter(x => x !== m);
		others.push(m);

		// 3. Re-assign clean indexes (1 to N) to everyone
		others.forEach((x, i) => {
			const newZ = i + 1;
			x.memo.pos_z = newZ;
			x.el.style.zIndex = this.currentZ + newZ;
		});

	}


	/* ===============================
	   RENDER
	================================= */

	/**
	 * Creates and appends the DOM structure for a memo.
	 * @async
	 * @param {Object} memo - The data object for the note.
	 * @returns {HTMLElement} The created note element.
	 */
	async renderMemo(memo) {

		const note = document.createElement('div');
		this.memos.push({memo, el: note});

		note.className = 'quickmemo-note';
		note.dataset.id = memo.id;


		let posX = parseInt(memo.pos_x);
		let posY = parseInt(memo.pos_y);
		let posW = parseInt(memo.pos_w);
		let posH = parseInt(memo.pos_h);

		const containerWidth = this.container.clientWidth;

		// Prevent note from appearing outside of the viewport
		if (posX + posW > containerWidth) {
			posX = Math.max(32, containerWidth - posW - 32);
		}

		note.style.position = 'absolute';
		note.style.left = posX + 'px';
		note.style.top = posY + 'px';
		note.style.width = posW + 'px';
		note.style.height = posH + 'px';
		// note.style.background = memo.color || '#fff8a6';
		// note.style.setProperty('--memo-color', memo.color || '#fff8a6');
		this.setMemoColor(note, memo.color);
		if (parseInt(memo.id) === 0) { // Uniquement pour les nouvelles notes en cours de création
			const maxZ = this.memos.length > 0 ? Math.max(...this.memos.map(x => parseInt(x.memo.pos_z) || 0)) : 0;
			memo.pos_z = maxZ + 1;
		}
		note.style.zIndex = (this.currentZ || 0) + parseInt(memo.pos_z);


		const noteHeader = document.createElement('div');
		noteHeader.className = 'quickmemo-header';

		/* ================= MEMO FOOTER & ACTIONS ================= */

		const noteFooter = document.createElement('div');
		noteFooter.className = 'quickmemo-footer';

		const meta = document.createElement('div');
		meta.className = 'quickmemo-meta';
		const hasDateChange = memo.date_change && memo.date_change.trim() !== '' && memo.date_creation !== memo.date_change;
		const hasUserChange = memo.user_change_name && memo.user_change_name.trim() !== '' && memo.user_name !== memo.user_change_name;

		let updateBlock = '';

		// Template for "Modified on/by" information
		updateBlock = `
			<span class="quickmemo-info__date_update">
				${hasDateChange ? `
					<span class="quickmemo-info__date_update">
						${await Dolibarr.tools.langs.trans('QuickMemoModified')} : ${memo.date_change}
					</span>
				` : ''}
				${hasUserChange ? `
					<span class="quickmemo-info__user-change-name">
						${await Dolibarr.tools.langs.trans('QuickMemoBy')} ${memo.user_change_name}
					</span>
				` : ''}
			</span>
		`;


		meta.innerHTML = `
			<div class="quickmemo-info">
				<span class="quickmemo-info__create">
					<span class="quickmemo-info__user-create-name">${memo.user_name || ''}</span>
					<span class="quickmemo-info__date_create">${memo.date_creation || ''}</span>
				</span>
				${updateBlock}
			</div>
		`;

		const actions = document.createElement('div');
		actions.className = 'quickmemo-actions';

		/* ===== ARCHIVE ===== */

		const btnArchive = document.createElement('button');
		btnArchive.className = 'btn-low-emphasis --btn-icon quickmemo-btn-archive';
		btnArchive.innerHTML = `<i class="${this.param.archiveFontAIcon}"></i>`;
		btnArchive.title = await Dolibarr.tools.langs.transNoEntities('QuickMemoActionArchive');
		this.initTooltips(btnArchive);
		btnArchive.addEventListener('click', async (e) => {
			e.stopPropagation();
			this.removeTooltips(btnArchive); // JQUERY tooltip need to be destroy because it active at this moment

			try {
				// If empty, delete permanently, otherwise archive
				const textarea = note.querySelector('textarea');
				const actionDelete = textarea.value.length ? 'archive' : 'delete';

				if (actionDelete === 'delete') {
					this.deleteWithAnimation(note);
				} else {
					this.archiveWithAnimation(note);
				}

				const res = await fetch(this.param.interfaceUrl + '?action=' + actionDelete, {
					method: 'POST',
					headers: {'Content-Type': 'application/x-www-form-urlencoded'},
					body: new URLSearchParams({
						id: note.dataset.id,
						token: this.param.token
					})
				});

				const json = await res.json();

				if (!json.result) {
					throw new Error(json.msg);
				}

				this.memos = this.memos.filter(m => m.el !== note);
			} catch (err) {
				Dolibarr.tools.setEventMessage(Dolibarr.tools.langs.trans('QuickMemoArchiveError') + ' : ' + err, 'errors');
				console.error('QuickMemo archive error', err);
			}
		});

		/* ===== TOGGLE SHARING ===== */

		const btnShare = document.createElement('button');
		btnShare.className = 'btn-low-emphasis --btn-icon quickmemo-btn-share';
		btnShare.title = await Dolibarr.tools.langs.transNoEntities('QuickMemoActionShareMode');
		this.initTooltips(btnShare);


		/** Updates icon based on sharing status */
		const updateShareIcon = async () => {
			note.setAttribute('data-shared', parseInt(memo.shared_on_element));
			btnShare.title = await Dolibarr.tools.langs.transNoEntities(memo.shared_on_element == 1 ? 'QuickMemoActionShareModeActive' : 'QuickMemoActionShareModeNotActive');
			btnShare.innerHTML = `<i class="${
				memo.shared_on_element == 1
					? this.param.sharedFontAIcon
					: this.param.notSharedFontAIcon
			}"></i>`;

			this.initTooltips(btnShare);
		};

		updateShareIcon();

		btnShare.addEventListener('click', async (e) => {
			e.stopPropagation();

			memo.shared_on_element = memo.shared_on_element == 1 ? 0 : 1;

			updateShareIcon();

			await fetch(this.param.interfaceUrl + '?action=update-shared-on-element', {
				method: 'POST',
				headers: {'Content-Type': 'application/x-www-form-urlencoded'},
				body: new URLSearchParams({
					id: note.dataset.id,
					shared_on_element: memo.shared_on_element,
					token: this.param.token
				})
			});
		});


		/* ===== SET AS TEMPLATE ===== */
		const btnCreateModel = document.createElement('button');
		btnCreateModel.className = 'btn-low-emphasis --btn-icon';
		btnCreateModel.innerHTML = `<i class="${this.param.modelFontAIcon}"></i>`;
		btnCreateModel.title = await Dolibarr.tools.langs.transNoEntities('QuickMemoCreateAModelFromThisMemo');
		this.initTooltips(btnCreateModel);

		btnCreateModel.addEventListener('click', () => this.openCreateModelDialog(memo, note));

		/* ===== TOGGLE PRIVATE ===== */

		const btnPrivate = document.createElement('button');
		btnPrivate.className = 'btn-low-emphasis --btn-icon quickmemo-btn-private';
		btnPrivate.title = await Dolibarr.tools.langs.transNoEntities('QuickMemoActionPrivateMode');
		this.initTooltips(btnPrivate);

		/** Updates icon based on privacy status */
		const updatePrivateIcon = async () => {
			note.setAttribute('data-private', parseInt(memo.private));

			btnPrivate.title = await Dolibarr.tools.langs.transNoEntities(memo.private == 1 ? 'QuickMemoActionPrivateModeActive' : 'QuickMemoActionPrivateModeNotActive');
			btnPrivate.innerHTML = `<i class="${
				parseInt(memo.private) === 1
					? this.param.privateFontAIcon
					: this.param.publicFontAIcon
			}"></i>`;
			this.initTooltips(btnPrivate);
		};

		updatePrivateIcon();

		btnPrivate.addEventListener('click', async (e) => {
			e.stopPropagation();

			memo.private = parseInt(memo.private) === 1 ? 0 : 1; // Toggle private status

			updatePrivateIcon();

			await fetch(this.param.interfaceUrl + '?action=update-private', {
				method: 'POST',
				headers: {'Content-Type': 'application/x-www-form-urlencoded'},
				body: new URLSearchParams({
					id: note.dataset.id,
					private: memo.private,
					token: this.param.token
				})
			});
		});

		/* ===== COLOR PICKER ===== */

		const colorListId = 'quickmemo-color-list';

		// Create a datalist for predefined colors if not already existing
		if (!document.getElementById(colorListId)) {
			const datalist = document.createElement('datalist');
			datalist.id = colorListId;

			this.param.colors.forEach(c => {
				const option = document.createElement('option');
				option.value = c;
				datalist.appendChild(option);
			});

			document.body.appendChild(datalist);
		}

		const colorInput = document.createElement('input');
		colorInput.type = 'color';
		colorInput.className = 'quickmemo-color-input';
		colorInput.value = memo.color || '#fff8a6';
		colorInput.setAttribute('list', colorListId);
		colorInput.title = await Dolibarr.tools.langs.trans('QuickMemoActionChangeColor');
		this.initTooltips(colorInput);

		colorInput.addEventListener('click', e => {
			e.stopPropagation(); // prevent triggering drag
		});

		colorInput.addEventListener('input', async (e) => {
			e.stopPropagation();

			const newColor = e.target.value;
			// note.style.background = newColor;
			// note.style.setProperty('--memo-color', newColor);
			this.setMemoColor(note, newColor);
			memo.color = newColor;

			await fetch(this.param.interfaceUrl + '?action=update-color', {
				method: 'POST',
				headers: {'Content-Type': 'application/x-www-form-urlencoded'},
				body: new URLSearchParams({
					id: note.dataset.id,
					color: memo.color,
					token: this.param.token
				})
			});
		});

		// Append buttons based on permissions
		if (this.param.userWriteRight) {
			actions.appendChild(btnPrivate);
			if(this.param.shareBtnStatus > 0) {
				actions.appendChild(btnShare);
			}
			actions.appendChild(colorInput);
			actions.appendChild(btnCreateModel);
		}

		if (this.param.userDeleteRight) {
			actions.appendChild(btnArchive);
		}

		noteHeader.appendChild(actions);
		note.appendChild(noteHeader);

		noteFooter.appendChild(meta);

		/* ================= TEXTAREA ================= */

		const textarea = document.createElement('textarea');
		textarea.value = memo.note || '';
		if (!this.param.userWriteRight) {
			textarea.readOnly = true;
		}

		textarea.placeholder = await Dolibarr.tools.langs.transNoEntities('QuickMemoEnterNote');
		textarea.addEventListener('input', () => this.adjustFontSize(note));
		note.appendChild(textarea);


		// MARKDOWN PREVIEW
		const preview = document.createElement('div');
		preview.className = 'quickmemo-markdown-preview';
		note.appendChild(preview);
		if (!textarea.value) {
			// Dynamically retrieve the placeholder
			const ph = textarea.getAttribute('placeholder') || '';
			await this.renderPreview(preview, `<span class="quickmemo-placeholder">${ph}</span>`, false);
		} else {
			await this.renderPreview(preview, textarea.value);
		}


		/** Toggles between edit mode (textarea) and view mode (markdown) */
		const setEditMode = (edit) => {
			note.classList.toggle('--edit-mode', edit);
			// set focus on textarea on edit mode
			if (edit) textarea.focus();
		};

		textarea.addEventListener('focus', () => setEditMode(true));
		textarea.addEventListener('blur', async (e) => {

			// Check if the focus is moving to a toolbar button
			if (e.relatedTarget && e.relatedTarget.closest('.quickmemo-editor-toolbar')) {
				return; // Do nothing, keep --edit-mode active
			}

			// Otherwise, close edit mode
			setTimeout(async () => {
				// ... votre logique de preview ...
				setEditMode(false); // This will automatically hide the toolbar via CSS
			}, 150);

			if (!textarea.value) {
				// Dynamically retrieve the placeholder
				const ph = textarea.getAttribute('placeholder') || '';
				await this.renderPreview(preview, `<span class="quickmemo-placeholder">${ph}</span>`, false);
			} else {
				await this.renderPreview(preview, textarea.value);
			}

		});

		// Interaction with the preview (checkboxes and links)
		preview.addEventListener('click', async (e) => {
			const target = e.target;

			// If a link is clicked or inside a link
			if (target.closest('a')) {
				e.stopPropagation(); // Prevent setEditMode
				return;
			} else if (target.classList.contains('markdown-checkbox')) {
				const lineNumber = target.closest('[data-line]').dataset.line;
				const lines = textarea.value.split('\n');
				const line = lines[lineNumber];
				const todoMatch = line.match(/^\[([ xX])\] (.*)$/);
				if (!todoMatch) return;

				const checked = todoMatch[1].toLowerCase() === 'x';
				lines[lineNumber] = `[${checked ? ' ' : 'x'}] ${todoMatch[2]}`;
				textarea.value = lines.join('\n');

				// Re-render preview
				await this.renderPreview(preview, textarea.value);

				// trigger envent input for bindSave
				const inputEvent = new Event('input', {bubbles: true});
				textarea.dispatchEvent(inputEvent);

				e.stopPropagation(); // Prevent setEditMode
			} else {
				setEditMode(true)
			}
		});


		const editorToolbar = document.createElement('div');
		editorToolbar.className = 'quickmemo-editor-toolbar';


		const btnBold = this.createToolbarButton(textarea,editorToolbar,'bold', 'fa-bold', Dolibarr.tools.langs.transNoEntities('Bold'));
		const btnItalic = this.createToolbarButton(textarea,editorToolbar,'italic', 'fa-italic', Dolibarr.tools.langs.transNoEntities('Italic'));
		const btnUnderline = this.createToolbarButton(textarea,editorToolbar,'underline', 'fa-underline', Dolibarr.tools.langs.transNoEntities('Underline'));
		const btnH1 = this.createToolbarButton(textarea,editorToolbar,'h1', 'fa-heading', Dolibarr.tools.langs.transNoEntities('H1'), '1');
		const btnH2 = this.createToolbarButton(textarea,editorToolbar,'h2', 'fa-heading', Dolibarr.tools.langs.transNoEntities('H2'), '1');
		const btnH3 = this.createToolbarButton(textarea,editorToolbar,'h3', 'fa-heading', Dolibarr.tools.langs.transNoEntities('H3'), '1');
		const btnCheck = this.createToolbarButton(textarea,editorToolbar,'task', 'fa-check-square', Dolibarr.tools.langs.transNoEntities('Checkbox'));

		this.createToolbarSeparator(editorToolbar)

		const btnEditorHelp = document.createElement('button');
		btnEditorHelp.type = 'button';
		btnEditorHelp.className = 'quickmemo-toolbar-btn';
		btnEditorHelp.innerHTML = `<i class="fa fa-question-circle" style="pointer-events: none;"></i>`;
		btnEditorHelp.title = Dolibarr.tools.langs.transNoEntities('Help');
		//<div class="data-tooltip" >${this.getMarkDownHelp()}</div>
		editorToolbar.appendChild(btnEditorHelp);

		btnEditorHelp.addEventListener('click', () => {
			const confirmOptions = {
				title: Dolibarr.tools.langs.trans('QuickMemoEnterNoteMdCompabible'),
				confirmLabel: Dolibarr.tools.langs.trans('CloseDialog'),
				customClass: 'dialog-lg',
			};
			this.openSimpleDialog(this.getMarkDownHelp(), confirmOptions);
		});


		note.appendChild(editorToolbar);


		textarea.addEventListener('keyup', () => this.updateToolbarState(textarea, editorToolbar));
		textarea.addEventListener('click', () => this.updateToolbarState(textarea, editorToolbar));
		textarea.addEventListener('select', () => this.updateToolbarState(textarea, editorToolbar));
		textarea.addEventListener('focus', () => this.updateToolbarState(textarea, editorToolbar));

		// END MARKDOWN


		note.appendChild(noteFooter);

		this.container.appendChild(note);
		this.adjustFontSize(note);

		const state = {isResizing: false};

		// Enable drag, resize and auto-save
		this.makeDraggable(note, state);
		this.makeResizable(note, memo, state);
		this.bindSave(textarea, note);


		return note;
	}

	createToolbarSeparator(toolbar, autoAppend = true) {
		const btn = document.createElement('hr');
		btn.className = 'quickmemo-toolbar-separator';

		if(autoAppend) {
			toolbar.appendChild(btn);
		}

		return btn;
	}

	createToolbarButton(textarea,toolbar, type, iconClass, title, autoAppend = true) {
		const btn = document.createElement('button');
		btn.type = 'button';
		btn.className = 'quickmemo-toolbar-btn';
		btn.dataset.type = type;
		let sub = '';
		if(type === 'h1') {
			sub = '<sub style="pointer-events: none;" >1</sub>';
		}else if(type === 'h2'){
			sub = '<sub style="pointer-events: none;">2</sub>';
		}else if(type === 'h3'){
			sub = '<sub style="pointer-events: none;">3</sub>';
		}

		btn.innerHTML = `<i class="fa ${iconClass}" style="pointer-events: none;"></i>${sub}`;
		btn.title = title;

		// On empêche le bouton de voler le focus définitivement,
		// mais on le laisse être le "relatedTarget"
		btn.addEventListener('mousedown', (e) => {
			e.preventDefault(); // Empêche le curseur de quitter le textarea
			this.applyMarkdown(textarea, type, toolbar);
		});

		if(autoAppend) {
			toolbar.appendChild(btn);
		}

		return btn;

	}

	/**
	 * Converts raw text into Markdown HTML and updates the preview area.
	 * @async
	 * @param {HTMLElement} preview - The preview container.
	 * @param {string} txt - The source text.
	 * @param {boolean} convertMd - Whether to apply markdown conversion.
	 */
	async renderPreview(preview, txt, convertMd = true) {
		// Re-render preview
		preview.innerHTML = convertMd ? await this.simpleMarkdown(txt) : txt;

		const badge = document.createElement('span');
		badge.classList.add('quickmemo-markdown-preview__badge');
		const checkboxes = preview.querySelectorAll('input[type="checkbox"]');

		const total = checkboxes.length;

		if (total === 0) {
			return;
		}

		const checked = preview.querySelectorAll('input[type="checkbox"]:checked').length;

		badge.textContent = checked + " / " + total;

		if (checked === total) {
			badge.classList.add("success");
		} else {
			badge.classList.remove("success");
		}

		preview.prepend(badge);
	}

	/**
	 * Visual effect: note flies towards the archive button and disappears.
	 * @param {HTMLElement} note
	 */
	archiveWithAnimation(note) {

		const target = document.getElementById(this.param.menuDropDownId);
		if (!target) {
			note.remove();
			return;
		}

		const noteRect = note.getBoundingClientRect();
		const targetRect = target.getBoundingClientRect();

		const deltaX = targetRect.left - noteRect.left;
		const deltaY = targetRect.top - noteRect.top;

		// Set current size to prevent jumps effect
		note.style.width = noteRect.width + 'px';
		note.style.height = noteRect.height + 'px';

		note.style.position = 'fixed';
		note.style.left = noteRect.left + 'px';
		note.style.top = noteRect.top + 'px';
		note.style.margin = 0;
		note.style.zIndex = 99999;

		note.style.transition = 'transform 400ms ease, opacity 400ms ease';
		note.style.transformOrigin = 'top left';

		requestAnimationFrame(() => {
			note.style.transform = `
            translate(${deltaX}px, ${deltaY}px)
            scale(0.2)
        `;
			note.style.opacity = '0';
		});

		note.addEventListener('transitionend', () => {
			note.remove();
			this.setArchivesNb(this.countedArchivedMemo + 1);
		}, {once: true});
	}


	/**
	 * Visual effect: note flies towards the template list when a model is created.
	 * @param {Object} memo
	 * @param {HTMLElement} note
	 */
	convertToModelWithAnimation(memo, note) {

		const dropDown = document.getElementById(this.param.menuDropDownId);
		if (!dropDown) {
			note.remove();
			return;
		}


		const modelListDiv = document.getElementById(this.param.menuDropDownModelListContainerId);
		if (!modelListDiv) {
			note.remove();
			return;
		}

		if (!memo) {
			note.remove();
			return;
		}


		setTimeout(async () => {
			await this.fetchMenuDropDown();
			dropDown.classList.add('open');

			const target = document.createElement('div');
			target.className = 'quickmemo-model-placeholder';
			modelListDiv.appendChild(target);


			const noteRect = note.getBoundingClientRect();
			const targetRect = target.getBoundingClientRect();

			const deltaX = targetRect.left - noteRect.left;
			const deltaY = targetRect.top - noteRect.top;

			// Set current size to prevent jumps effect
			note.style.width = noteRect.width + 'px';
			note.style.height = noteRect.height + 'px';

			note.style.position = 'fixed';
			note.style.left = noteRect.left + 'px';
			note.style.top = noteRect.top + 'px';
			note.style.margin = 0;
			note.style.zIndex = 99999;

			note.style.transition = 'transform 400ms ease, opacity 400ms ease';
			note.style.transformOrigin = 'top left';

			requestAnimationFrame(() => {
				note.style.transform = `
				translate(${deltaX}px, ${deltaY}px)
				scale(0.2)
       		`;
				note.style.opacity = '0';
			});

			note.addEventListener('transitionend', () => {
				note.remove();
				target.remove();// remove placeholder

				// Check if same btn already exists caused by menu open loading new model before animation ends
				const existingBtn = modelListDiv.querySelector(`.quickmemo-model-btn[data-model-id="${memo.id}"]`);
				if (!existingBtn) {
					const newBtn = this.generateAddMemoBtn(memo);
					this.attachDragEventsForModelReorder(newBtn);
					modelListDiv.appendChild(newBtn);
				}

			}, {once: true});
		}, 10); // hack to avoid close dropdown on click event
	}

	/**
	 * Triggers the deletion animation.
	 * @param {HTMLElement} note
	 */
	deleteWithAnimation(note) {
		note.classList.add('--note-delete-animation');
		note.addEventListener('animationend', () => {
			note.remove();
		}, {once: true});
	}

	/**
	 * Applies background color and calculates brightness to set a dark/light font class.
	 * @param {HTMLElement} note
	 * @param {string} color - Hex color string.
	 */
	setMemoColor(note, color) {
		// Default color if not valide
		if (!color || typeof color !== 'string') color = '#fff8a6';

		note.style.setProperty('--memo-color', color);

		// Check that it is a 6-character hex
		let c = color.startsWith('#') ? color.substring(1) : color;
		if (c.length !== 6 || !/^[0-9a-fA-F]{6}$/.test(c)) {
			// invalide → considère clair par défaut
			note.classList.remove('--memo-is-dark');
			return;
		}

		// Conversion hex -> RGB
		const r = parseInt(c.substr(0, 2), 16);
		const g = parseInt(c.substr(2, 2), 16);
		const b = parseInt(c.substr(4, 2), 16);

		// Calculate brightness
		const brightness = (r * 299 + g * 587 + b * 114) / 1000;

		if (brightness < 128) {
			note.classList.add('--memo-is-dark');
		} else {
			note.classList.remove('--memo-is-dark');
		}
	}

	/**
	 * Dynamically resizes the font based on the content density within the note's dimensions.
	 * @param {HTMLElement} noteEl
	 */
	async adjustFontSize(noteEl) {
		if (!this.param.autoResizeFontSize) {
			return;
		}

		const textarea = noteEl.querySelector('textarea');
		const preview = noteEl.querySelector('.quickmemo-markdown-preview');

		const footerHeight = noteEl.querySelector('.quickmemo-footer')?.offsetHeight || 0;
		const headerHeight = noteEl.querySelector('.quickmemo-header')?.offsetHeight || 0;

		const contentHeight = noteEl.clientHeight - headerHeight - footerHeight;
		const contentWidth = noteEl.clientWidth;

		const textLength = textarea.value.length;

		const btnArchive = noteEl.querySelector('.quickmemo-btn-archive');
		if (btnArchive) {
			btnArchive.title = await Dolibarr.tools.langs.transNoEntities(textLength > 0 ? 'QuickMemoActionArchive' : 'QuickMemoActionDeleteEmpty');
		}

		// Calculate density
		const density = textLength / (contentWidth * contentHeight);

		// Linearly map density to font-size
		const densityMin = 0.0002;
		const densityMax = 0.005;
		const fontMin = 0.8;
		const fontMax = 1.2;

		// Clamp density between min & max
		const clampedDensity = Math.max(densityMin, Math.min(densityMax, density));

		// Linear interpolation
		let fontSize = fontMax - ((clampedDensity - densityMin) / (densityMax - densityMin)) * (fontMax - fontMin);
		// Round to the nearest 0.1 em
		fontSize = Math.round(fontSize * 10) / 10;

		textarea.style.fontSize = fontSize + 'em';
		preview.style.fontSize = fontSize + 'em';
	}


	// /**
	//  * Brings the element to the foreground on mouse click.
	//  * @param {HTMLElement} el
	//  */
	// bindFocusZ(el) {
	// 	el.addEventListener('mousedown', () => {
	// 		el.style.zIndex = ++this.currentZ;
	// 	});
	// }

	/* ===============================
	   DRAG
	================================= */

	/**
	 * Makes a note element draggable. Handles selection vs drag logic on textareas.
	 * @param {HTMLElement} el
	 * @param {Object} state - Reference to shared resizing state.
	 */
	makeDraggable(el, state) {

		let offsetX, offsetY, dragging = false;
		let hasMoved = false;


		let startX, startY;
		let dragTimeout = null;
		const startDragDelay = 300; // ms : temps d'attente au click avant de passer en mode drag
		const selectionThreshold = 5; // px : distance en pixels qui désactive le drag pour passer en mode selection
		const textarea = el.querySelector('textarea');

		// Auto-scroll configuration
		const scrollSpeed = 15;
		const scrollThreshold = 50;
		let scrollInterval = null;

		const stopAutoScroll = () => {
			if (scrollInterval) {
				clearInterval(scrollInterval);
				scrollInterval = null;
			}
		};

		const onMouseMove = (ev) => {
			if (!dragging) return;

			// 1. Calculate new positions
			let newX = ev.clientX + window.scrollX - offsetX;
			let newY = ev.clientY + window.scrollY - offsetY;

			// 2. Boundaries logic
			const boundaryW = Math.max(document.documentElement.scrollWidth, window.innerWidth);
			const boundaryH = Math.max(document.documentElement.scrollHeight, window.innerHeight);

			const maxX = boundaryW - el.offsetWidth;
			const maxY = boundaryH - el.offsetHeight;

			newX = Math.max(0, Math.min(newX, maxX));
			newY = Math.max(0, Math.min(newY, maxY));

			el.style.left = newX + 'px';
			el.style.top = newY + 'px';

			hasMoved = true;

			// 3. Auto-scroll logic
			const viewportY = ev.clientY;
			const viewportHeight = window.innerHeight;

			stopAutoScroll();

			// Scroll down if mouse is near bottom edge
			if (viewportY > viewportHeight - scrollThreshold) {
				scrollInterval = setInterval(() => {
					window.scrollBy(0, scrollSpeed);
					// Update position while scrolling even if mouse doesn't move
					let currentY = parseInt(el.style.top) + scrollSpeed;
					const currentMaxY = document.documentElement.scrollHeight - el.offsetHeight;
					el.style.top = Math.min(currentY, currentMaxY) + 'px';
				}, 16);
			}
			// Scroll up if mouse is near top edge
			else if (viewportY < scrollThreshold) {
				scrollInterval = setInterval(() => {
					window.scrollBy(0, -scrollSpeed);
					let currentY = parseInt(el.style.top) - scrollSpeed;
					el.style.top = Math.max(0, currentY) + 'px';
				}, 16);
			}
		};

		const cancelDrag = () => {
			if (dragTimeout) {
				clearTimeout(dragTimeout);
				dragTimeout = null;
			}
			dragging = false;
			el.classList.remove('--dragging');
		};

		const onMouseUp = () => {
			cancelDrag();

			document.removeEventListener('mousemove', onMouseMove);
			document.removeEventListener('mouseup', onMouseUp);

			if (hasMoved) this.saveAllPositions()
			hasMoved = false;
		};

		el.addEventListener('mousedown', e => {

			if (e.button !== 0) return; // Left click only
			if (state.isResizing) return;
			// if (el.style.cursor && el.style.cursor.includes('resize')) return;

			// DO NOT DRAG if mouse hover .quickmemo-actions
			if (e.target.closest('.quickmemo-actions')) return;

			//if (e.target.tagName === 'TEXTAREA') return;
			const isOnTextarea = e.target === textarea;

			this.bringToTop(el);

			hasMoved = false;

			const rect = el.getBoundingClientRect();
			startX = e.clientX + window.scrollX;
			startY = e.clientY + window.scrollY;
			offsetX = startX - (rect.left + window.scrollX);
			offsetY = startY - (rect.top + window.scrollY);

			if (isOnTextarea && !textarea.disabled && !textarea.readOnly) {

				// Delayed drag to allow text selection
				dragTimeout = setTimeout(() => {
					// Check if there is no selection
					const sel = window.getSelection();
					if (sel && sel.toString().length === 0) {
						dragging = true;
						el.classList.add('--dragging');

						// Cancel selection when dragging
						if (sel.rangeCount > 0) sel.removeAllRanges();
						// Remove focus so the cursor stops moving
						if (document.activeElement === textarea) textarea.blur();
					}
				}, startDragDelay);

				// Detect movement for text selection
				const onTempMove = (ev) => {
					const dx = ev.clientX - e.clientX;
					const dy = ev.clientY - e.clientY;
					if (Math.sqrt(dx * dx + dy * dy) > selectionThreshold) {
						cancelDrag(); // Cancel selection when selected
						cleanupTempMove();
					}
				};

				const cleanupTempMove = () => {
					document.removeEventListener('mousemove', onTempMove);
					dragTimeout = null;
				};
				document.addEventListener('mousemove', onTempMove, {once: true});

			} else {
				dragging = true;
				el.classList.add('--dragging');
				e.preventDefault();
			}

			document.addEventListener('mousemove', onMouseMove);
			document.addEventListener('mouseup', onMouseUp);

		});
	}

	/* ===============================
	   RESIZE
	================================= */

	/**
	 * Makes a note resizable from all edges and corners.
	 * @param {HTMLElement} el
	 * @param {Object} memo - Data object to update.
	 * @param {Object} state - Shared resizing state.
	 */
	makeResizable(el, memo, state) {

		const margin = 10;
		let direction = null;
		let startX, startY, startW, startH, startLeft, startTop;

		const onMouseMove = (e) => {
			if (!state.isResizing) return;

			let dx = e.clientX - startX;
			let dy = e.clientY - startY;

			let newW = startW;
			let newH = startH;
			let newLeft = startLeft;
			let newTop = startTop;

			// Handle different directions
			if (direction.includes('e')) {
				newW = startW + dx;
				newW = Math.min(newW, document.documentElement.scrollWidth - startLeft);
			}
			if (direction.includes('s')) {
				newH = startH + dy;
				newH = Math.min(newH, document.documentElement.scrollHeight - startTop);
			}
			if (direction.includes('w')) {
				newW = startW - dx;
				newLeft = startLeft + dx;
				if (newLeft < 0) {
					newW += newLeft; // Reduce width to avoid overflow
					newLeft = 0;
				}
			}
			if (direction.includes('n')) {
				newH = startH - dy;
				newTop = startTop + dy;
				if (newTop < 0) {
					newH += newTop; // Reduce height to avoid overflow
					newTop = 0;
				}
			}

			// Minimum dimensions constraint
			newW = Math.max(250, newW);
			newH = Math.max(100, newH);

			el.style.width = newW + 'px';
			el.style.height = newH + 'px';
			el.style.left = newLeft + 'px';
			el.style.top = newTop + 'px';

			this.adjustFontSize(el);

			memo.pos_w = newW;
			memo.pos_h = newH;
			memo.pos_x = newLeft;
			memo.pos_y = newTop;
		};

		const onMouseUp = () => {
			if (!state.isResizing) return;

			state.isResizing = false;
			document.body.style.userSelect = '';

			document.removeEventListener('mousemove', onMouseMove);
			document.removeEventListener('mouseup', onMouseUp);

			this.savePosition(el);
		};

		// Mouse movement detection for cursor change
		el.addEventListener('mousemove', (e) => {

			if (state.isResizing) return;

			const rect = el.getBoundingClientRect();
			const x = e.clientX;
			const y = e.clientY;

			const onLeft = x >= rect.left - margin && x <= rect.left + margin;
			const onRight = x >= rect.right - margin && x <= rect.right + margin;
			const onTop = y >= rect.top - margin && y <= rect.top + margin;
			const onBottom = y >= rect.bottom - margin && y <= rect.bottom + margin;

			direction = null;

			if (onRight && onBottom) direction = 'se';
			else if (onLeft && onBottom) direction = 'sw';
			else if (onRight && onTop) direction = 'ne';
			else if (onLeft && onTop) direction = 'nw';
			else if (onRight) direction = 'e';
			else if (onLeft) direction = 'w';
			else if (onBottom) direction = 's';
			else if (onTop) direction = 'n';

			el.style.cursor = getCursor(direction);
		});

		el.addEventListener('mousedown', (e) => {
			if (!direction) return;

			// DO NOT RESIZE if mouse hover .quickmemo-actions
			if (e.target.closest('.quickmemo-actions')) return;

			this.bringToTop(el);

			state.isResizing = true;

			const rect = el.getBoundingClientRect();
			startX = e.clientX;
			startY = e.clientY;
			startW = rect.width;
			startH = rect.height;
			startLeft = rect.left + window.scrollX;
			startTop = rect.top + window.scrollY;

			document.body.style.userSelect = 'none';

			document.addEventListener('mousemove', onMouseMove);
			document.addEventListener('mouseup', onMouseUp);

			e.preventDefault();
		});

		/** Helper to return CSS cursor string */
		function getCursor(dir) {
			switch (dir) {
				case 'se':
				case 'nw':
					return 'nwse-resize';
				case 'ne':
				case 'sw':
					return 'nesw-resize';
				case 'e':
				case 'w':
					return 'ew-resize';
				case 'n':
				case 's':
					return 'ns-resize';
				default:
					return 'default';
			}
		}
	}

	/* ===============================
	   SAVE
	================================= */

	/**
	 * Binds auto-save with debounce logic on textarea input.
	 * @param {HTMLTextAreaElement} textarea
	 * @param {HTMLElement} noteEl
	 */
	bindSave(textarea, noteEl) {

		let timeout;

		let m = this.getMemoFromElement(noteEl);

		textarea.addEventListener('input', () => {

			clearTimeout(timeout);

			timeout = setTimeout(() => {
				const id = noteEl.dataset.id;

				if (!id || id == 0) return; // Note not yet created in DB
				m.memo.note = textarea.value;
				this.saveNote(id, m.memo.note, noteEl);

			}, 600);
		});
	}

	/**
	 * Persists note position and dimensions to the server.
	 * @async
	 * @param {HTMLElement} el
	 */
	async savePosition(el) {
		const m = this.memos.find(x => x.el === el);
		if (!m) return;

		if (!el.dataset.id || parseInt(el.dataset.id) === 0) return; // not created yet in database

		try {

			// TODO send all others memo pos_z for update

			const res = await fetch(this.param.interfaceUrl + '?action=update_position', {
				method: 'POST',
				headers: {'Content-Type': 'application/x-www-form-urlencoded'},
				body: new URLSearchParams({
					id: el.dataset.id,
					x: parseInt(el.style.left),
					y: parseInt(el.style.top),
					w: parseInt(el.style.width),
					h: parseInt(el.style.height),
					z: parseInt(m.memo.pos_z) || 1,
					token: this.param.token
				})
			});

			const json = await res.json();

			if (!json.result) {
				throw new Error(json.msg);
			}

			Dolibarr.executeHook('QuickMemo:savePosition:saved', {el, json});

		} catch (err) {
			console.error('QuickMemo save position error', err);
			Dolibarr.tools.setEventMessage(Dolibarr.tools.langs.trans('QuickMemoSavePositionError') + ' : ' + err, 'errors');
		}
	}

	/**
	 * Saves the coordinates and Z-index of ALL memos on the page.
	 */
	saveAllPositions() {
		const memosData = this.memos.map(m => ({
			id: m.el.dataset.id,
			x: parseInt(m.el.style.left) || 0,
			y: parseInt(m.el.style.top) || 0,
			w: parseInt(m.el.style.width) || 0,
			h: parseInt(m.el.style.height) || 0,
			z: parseInt(m.memo.pos_z) || 1
		}));

		fetch(this.param.interfaceUrl + '?action=update_all_positions&token=' + this.param.token, {
			method: 'POST',
			headers: {'Content-Type': 'application/json'},
			body: JSON.stringify({
				memos: memosData
			})
		})
			.then(response => response.json())
			.then(data => {
				if (!data.result) console.error("Error saving all positions:", data.msg);
			})
			.catch(err => console.error("Fetch error:", err));
	}

	/**
	 * Persists note content to the server.
	 * @async
	 * @param {number|string} id
	 * @param {string} note
	 * @param {HTMLElement} noteEl
	 */
	async saveNote(id, note, noteEl) {

		let hookRes = await Dolibarr.executeHookAwait('QuickMemo:saveNote', {id, note});
		if (hookRes > 0) {
			return;
		}

		try {
			const res = await fetch(this.param.interfaceUrl + '?action=update_note', {
				method: 'POST',
				headers: {'Content-Type': 'application/x-www-form-urlencoded'},
				body: new URLSearchParams({
					id: id,
					note: note,
					token: this.param.token
				})
			});

			const json = await res.json();

			if (!json.result) {
				throw new Error(json.msg);
			}

			if (json.data && json.data.memo) {
				await this.updateMemoTxt(json.data.memo, noteEl);
			}

			Dolibarr.executeHook('QuickMemo:saveNote:saved', {id, note, json});

		} catch (err) {
			console.error('QuickMemo save note error', err);
			Dolibarr.tools.setEventMessage(Dolibarr.tools.langs.trans('QuickMemoSaveError') + ' : ' + err, 'errors');
		}
	}

	/* ===============================
	   VISIBILITY
	================================= */

	/**
	 * Checks all notes and ensures they are visible in the current container.
	 */
	ensureAllVisible() {
		this.memos.forEach(({el}) => this.ensureVisible(el));
	}

	/**
	 * Clamps a note's position to keep it within the horizontal boundaries.
	 * Vertical clamping is disabled to allow notes to be placed anywhere
	 * in long documents, as vertical scrolling is standard.
	 * @param {HTMLElement} el - The memo DOM element to check.
	 */
	ensureVisible(el) {
		// Get current CSS positions and actual element dimensions
		const posX = parseInt(el.style.left) || 0;
		const posW = el.offsetWidth;

		/**
		 * We use the maximum between the container width and the window width
		 * to determine the right boundary.
		 */
		const boundaryW = Math.max(this.container.scrollWidth, window.innerWidth);

		// 1. Horizontal constraint (Right border)
		// We only force the note back if it exceeds the right edge.
		if (posX + posW > boundaryW) {
			// Calculate a new position with a 32px safety margin
			let newLeft = boundaryW - posW - 32;

			// Ensure we don't push the note into negative space on the left
			el.style.left = Math.max(32, newLeft) + 'px';
		}

		// 2. Vertical constraint
		// Disabled: Let the note stay where it is, even if it's below the current body height.
		// The browser will naturally handle the scroll if needed.
	}

	/**
	 * Updates the metadata text (User/Date) in the memo DOM element.
	 * @async
	 * @param {Object} memo
	 * @param {HTMLElement} note
	 */
	async updateMemoTxt(memo, note) {
		// Sélectionne les éléments existants
		const userCreateEl = note.querySelector('.quickmemo-info__user-create-name');
		const dateCreateEl = note.querySelector('.quickmemo-info__date_create');
		const dateUpdateEl = note.querySelector('.quickmemo-info__date_update');
		const userChangeEl = note.querySelector('.quickmemo-info__user-change-name');

		// Mets à jour la création
		if (userCreateEl) userCreateEl.textContent = memo.user_name || '';
		if (dateCreateEl) dateCreateEl.textContent = memo.date_creation || '';

		// Détermine si des changements existent
		const hasDateChange = memo.date_change && memo.date_change.trim() !== '' && memo.date_creation !== memo.date_change;
		const hasUserChange = memo.user_change_name && memo.user_change_name.trim() !== '' && memo.user_name !== memo.user_change_name;

		// Mets à jour ou vide les infos de mise à jour
		if (dateUpdateEl) {
			dateUpdateEl.textContent = hasDateChange ? `${await Dolibarr.tools.langs.transNoEntities('QuickMemoModified')} : ${memo.date_change}` : '';
		}
		if (userChangeEl) {
			userChangeEl.textContent = hasUserChange ? `${await Dolibarr.tools.langs.transNoEntities('QuickMemoBy')} ${memo.user_change_name}` : '';
		}
	}

	/**
	 * Generates a button for the template list based on a model object.
	 * @param {Object} model
	 * @returns {HTMLElement} The created button.
	 */
	generateAddMemoBtn(model) {
		const dropdown = document.getElementById(this.param.menuDropDownId);
		const defaultModel = {
			id: false,
			name: "",
			color: null,
			note: "",
			pos_x: null,
			pos_y: null,
			pos_w: null,
			pos_h: null,
			pos_z: null,
			rank_tpl: 0,
			shared_on_element: "0",
			private: 0,
			date_creation: "",
			fk_user_creat: "",
			user_name: "",
			date_change: "",
			user_change_name: ""
		};

		model = {...defaultModel, ...model};

		const btn = document.createElement('button');
		btn.className = 'quickmemo-model-btn';
		btn.style.background = model.color || '#fff8a6';
		btn.dataset.modelId = model.id;
		btn.title = Dolibarr.tools.langs.transNoEntities('QuickMemoAdd')
			+ (model.name ? ` : ${model.name}` : '');

		// if(model.name) {
		// 	btn.title = `${model.name}`
		// 	this.initTooltips(btn);
		// }

		// ===== création du bouton delete =====
		const deleteBtn = document.createElement('span');
		deleteBtn.className = 'quickmemo-model-delete';
		deleteBtn.title = Dolibarr.tools.langs.transNoEntities('QuickMemoDeleteModel')
		btn.dataset.rank = model.rank_tpl || 0;

		btn.appendChild(deleteBtn);

		// DELETE
		if (this.param.userDeleteRight && model.id) {
			// Hover delay
			let hoverTimer = null;

			btn.addEventListener('mouseenter', (e) => {
				// If shiftKey is pressed, direct show
				if (e.shiftKey) {
					clearTimeout(hoverTimer); // In case of a timer already active
					deleteBtn.classList.add('--show');
				} else {
					// instead run timer
					hoverTimer = setTimeout(() => {
						deleteBtn.classList.add('--show');
					}, 800);
				}
			});

			btn.addEventListener('mouseleave', () => {
				clearTimeout(hoverTimer);
				deleteBtn.classList.remove('--show');
			});

			// Delete model
			deleteBtn.addEventListener('click', async (e) => {
				e.stopPropagation();
				e.preventDefault();

				// Fast delete if shiftKey is pressed, otherwise ask for confirmation
				if (!e.shiftKey) {
					const confirm = await this.openConfirmDialog(
						await Dolibarr.tools.langs.trans('QuickMemoConfirmDeleteModel') + (model.name ? ' <br/> <strong>' + this.escapeHTML(model.name) + '</strong>' : ''),
						{
							dialogClass: '--dialog-danger',
							btnConfirmClass: 'dialog-btn-destructive'
						}
					);

					if (!confirm) return;
				}

				try {
					const res = await fetch(this.param.interfaceUrl + '?action=delete_model', {
						method: 'POST',
						headers: {'Content-Type': 'application/x-www-form-urlencoded'},
						body: new URLSearchParams({
							id: model.id,
							token: this.param.token
						})
					});

					const json = await res.json();
					if (!json.result) throw new Error(json.msg);

					btn.remove();
				} catch (err) {
					Dolibarr.tools.setEventMessage(
						Dolibarr.tools.langs.trans('QuickMemoDeleteModelError') + ' : ' + err,
						'errors'
					);
				}
			});
		}


		// Create memo on template click
		btn.addEventListener('click', e => {
			e.preventDefault();
			this.createMemoFromModel(model);
			dropdown.classList.remove('open');
		});

		return btn;
	}

	/**
	 * Initialize standard tooltips.
	 *
	 * @param {HTMLElement|jQuery} root
	 */
	initTooltips(root) {

		if (!root) return;

		root.classList.add('dol-tooltip', '--dark-mode', '--tooltip-top');

		// Convert title -> data-title au moment de l'init
		const title = root.getAttribute('title');
		if (title) {
			root.setAttribute('data-title', title);
			root.setAttribute('aria-label', title);
			root.removeAttribute('title');
		}

		// Mise à jour à chaque survol (si le title a été modifié dynamiquement)
		const syncTitle = () => {
			const currentTitle = root.getAttribute('title');
			if (currentTitle) {
				root.setAttribute('data-title', currentTitle);
				root.setAttribute('aria-label', currentTitle);
				root.removeAttribute('title');
			}
		};

		root.addEventListener('mouseenter', syncTitle);
		root.addEventListener('focus', syncTitle);

		// Stocker les handlers pour pouvoir les retirer proprement
		root._quickmemoTooltipSync = syncTitle;

		// JQUERY STYLE
		// const tooltipClass = 'quick-memo-tooltip'
		// const $el = jQuery(root);
		// if ($el.data("ui-tooltip")) {
		// 	$el.tooltip("destroy");
		// }
		//
		// $el.tooltip({
		// 	tooltipClass: tooltipClass,
		// 	show: { collision: "flipfit", effect: "toggle", delay: 50, duration: 20 },
		// 	hide: { delay: 250, duration: 20 },
		// 	content: function () {
		// 		return $el.prop("title");
		// 	}
		// });
	}


	/**
	 * remove tooltips.
	 *
	 * @param {HTMLElement|jQuery} root
	 */
	removeTooltips(root) {

		if (!root) return;

		root.classList.remove('dol-tooltip', '--dark-mode', '--tooltip-top');

		// Restaurer title si data-title existe
		const dataTitle = root.getAttribute('data-title');
		if (dataTitle) {
			root.setAttribute('title', dataTitle);
		}

		root.removeAttribute('data-title');

		// Remove listeners
		if (root._quickmemoTooltipSync) {
			root.removeEventListener('mouseenter', root._quickmemoTooltipSync);
			root.removeEventListener('focus', root._quickmemoTooltipSync);
			delete root._quickmemoTooltipSync;
		}

		// JQUERY STYLE
		// const $el = jQuery(root);
		// if ($el.data("ui-tooltip")) {
		// 	$el.tooltip("destroy");
		// }
	}

	/**
	 * Initializes drag-and-drop reordering for the template list.
	 */
	initModelReorder() {
		if (!this.param.userWriteRight) return;

		const container = document.getElementById(this.param.menuDropDownModelListContainerId);
		if (!container) return;

		this._modelReorderContainer = container;

		// Placeholder unique pour tout le container
		if (!this._modelReorderPlaceholder) {
			this._modelReorderPlaceholder = document.createElement('div');
			this._modelReorderPlaceholder.className = 'quickmemo-model-placeholder';
		}

		container.querySelectorAll('.quickmemo-model-btn')
			.forEach(btn => this.attachDragEventsForModelReorder(btn));
	}

	/**
	 * Attaches native drag events to a template button for sorting.
	 * @param {HTMLElement} btn
	 */
	attachDragEventsForModelReorder(btn) {

		if (btn.dataset.reorderInit) return; // évite double bind
		btn.dataset.reorderInit = '1';

		const container = this._modelReorderContainer;
		const placeholder = this._modelReorderPlaceholder; // utiliser le placeholder unique

		btn.setAttribute('draggable', true);

		let dragged = null;

		btn.addEventListener('dragstart', (e) => {
			dragged = btn;
			btn.classList.add('--dragging');
			placeholder.style.height = btn.offsetHeight + 'px';
			container.insertBefore(placeholder, btn.nextSibling);
			setTimeout(() => btn.style.display = 'none', 0);
		});

		btn.addEventListener('dragend', () => {
			btn.classList.remove('--dragging');
			btn.style.display = '';

			if (!placeholder.parentNode) return;

			container.insertBefore(dragged, placeholder);
			placeholder.remove();

			this.saveModelOrder(dragged.dataset.modelId);
		});

		btn.addEventListener('dragover', (e) => {
			e.preventDefault();
			const rect = btn.getBoundingClientRect();
			const offset = e.clientY - rect.top;

			if (offset < rect.height / 2) {
				container.insertBefore(placeholder, btn);
			} else {
				container.insertBefore(placeholder, btn.nextSibling);
			}
		});
	}

	/**
	 * Persists the new sorting order of templates to the server.
	 * @async
	 * @param {number|string|null} movedId - ID of the element that was just moved.
	 */
	async saveModelOrder(movedId = null) {

		const container = document.getElementById(this.param.menuDropDownModelListContainerId);
		const buttons = [...container.querySelectorAll('.quickmemo-model-btn')];

		// recalcul complet des positions à partir du DOM
		const orderData = buttons.map((btn, index) => {

			const newRank = buttons.length - index; // cohérent avec ton ORDER BY rank DESC

			// on met à jour le dataset pour rester cohérent en mémoire
			btn.dataset.rank = newRank;

			return {
				id: btn.dataset.modelId,
				rank: newRank
			};
		});

		const payload = {order: orderData};

		// si on veut envoyer moved, on calcule sa vraie position
		if (movedId !== null) {

			const newIndex = buttons.findIndex(
				btn => btn.dataset.modelId == movedId
			);

			if (newIndex !== -1) {
				payload.moved = {
					id: movedId,
					newPos: newIndex + 1 // position visuelle 1 based
				};
			}
		}

		try {

			const res = await fetch(
				this.param.interfaceUrl + '?action=update_model_rank&token=' + this.param.token,
				{
					method: 'POST',
					headers: {'Content-Type': 'application/json'},
					body: JSON.stringify(payload)
				}
			);

			const json = await res.json();
			if (!json.result) throw new Error(json.msg);

		} catch (err) {

			console.error('QuickMemo save model order error', err);

			Dolibarr.tools.setEventMessage(
				Dolibarr.tools.langs.trans('QuickMemoSaveModelOrderError') + ' : ' + err,
				'errors'
			);
		}
	}

	/**
	 * Builds and injects the top menu dropdown into the Dolibarr interface.
	 * @async
	 */
	async initMenuMemoDropdown() {
		const DOL_VERSION = parseInt(Dolibarr.getContextVar('DOL_VERSION', false));
		const menuContainer = document.querySelector(DOL_VERSION > 20 || !DOL_VERSION ? '#id-top .login_block_tools .inline-block' : '#id-top .inline-block .login_block_elem');
		if (!menuContainer) return;

		const dropdown = document.createElement('div');
		dropdown.className = 'dropdown inline-block mod-quick-memo-menu';
		dropdown.id = 'quickmemo-create-dropdown'

		let textLoading = await Dolibarr.tools.langs.trans('Loading');
		let textNewNote = await Dolibarr.tools.langs.trans('NewNote');
		dropdown.innerHTML = `
		<a class="dropdown-toggle login-dropdown-a nofocusvisible" data-toggle="dropdown" href="#" title="${textNewNote}" >
			<i class="fa fa-sticky-note" aria-hidden="true"></i>
		</a>
		<div class="dropdown-menu dropdown-quickmemo-create">
			<div class="dropdown-header">
				<span class="dropdown-header-title">${await Dolibarr.tools.langs.trans('QuickMemoDropDownTitle')}</span>
				<span class="dropdown-header-actions"></span>
			</div>
			<div class="dropdown-body ">
				<div class="quickmemo-tpl-title">
					${await Dolibarr.tools.langs.trans('QuickMemoColorsPresets')}
				</div>
				<div id="${this.param.menuDropDownPresetListContainerId}" class="quickmemo-tpl-list">
					<div class="loading">${textLoading}...</div>
				</div>
				<div class="quickmemo-tpl-title">
					${await Dolibarr.tools.langs.trans('QuickMemoSavedTemplate')}
				</div>
				<div id="${this.param.menuDropDownModelListContainerId}"  class="quickmemo-tpl-list">
					<div class="loading">${textLoading}...</div>
				</div>
				${this.param.archivesUrl ? `
					<div class="quickmemo-archive-list">
						<a id="quick-note-archives-link" class="top-menu-dropdown-quicknote-link" href="${this.param.archivesUrl}" >${await Dolibarr.tools.langs.trans('QuickMemoShowArchives')}</a>
						<span id="quick-note-archives-link-mumber" class="badge badge-pill badge-light" >${this.countedArchivedMemo}</span>
					</div>
				` : ''}
			</div>

			<div class="dropdown-footer">

			</div>
		</div>
	`;

		menuContainer.prepend(dropdown);

		const dropDownMenuToggleBtn = dropdown.querySelector('.dropdown-toggle');
		const presetListDiv = document.getElementById(this.param.menuDropDownPresetListContainerId);
		presetListDiv.innerHTML = '';

		const modelListDiv = document.getElementById(this.param.menuDropDownModelListContainerId);
		modelListDiv.innerHTML = '';


		dropdown.setAttribute('data-loaded', 0);

		// Toggle ouverture / fermeture
		dropDownMenuToggleBtn.addEventListener('click', e => {
			e.preventDefault();
			e.stopPropagation();

			// LOAD DATA IN DROPDOWN MENU
			this.fetchMenuDropDown();

			// OPEN DROP DOWN
			dropdown.classList.toggle('open');
		});

		document.addEventListener('click', e => {
			if (!dropdown.contains(e.target)) {
				dropdown.classList.remove('open');
			}
		});


		const btnHelp = document.createElement('button');
		btnHelp.type = 'button';
		btnHelp.className = 'quickmemo-dropdown-btn btn-low-emphasis --btn-icon';
		btnHelp.innerHTML = `<i class="fa fa-question-circle" ></i>`;
		btnHelp.title = Dolibarr.tools.langs.transNoEntities('Help');

		const dropDownMenuHeader = dropdown.querySelector('.dropdown-quickmemo-create .dropdown-header .dropdown-header-actions');
		dropDownMenuHeader.appendChild(btnHelp);

		btnHelp.addEventListener('click', () => {
			const confirmOptions = {
				title: Dolibarr.tools.langs.trans('QuickMemoHelp_Title'),
				confirmLabel: Dolibarr.tools.langs.trans('CloseDialog'),
				customClass: 'dialog-lg',
			};
			this.openSimpleDialog(this.getDropDownHelp(), confirmOptions);
		});


		Dolibarr.executeHook('QuickMemo:initMenuMemoDropdown', {dropdown});
	}

	/**
	 * Fetches available presets and user-created models from the server for the dropdown menu.
	 * @async
	 * @param {boolean} force - Force refresh even if already loaded.
	 */
	async fetchMenuDropDown(force = false) {

		const dropDown = document.getElementById(this.param.menuDropDownId);
		const dropDownMenuToggleBtn = dropDown.querySelector('.dropdown-toggle');
		const presetListDiv = document.getElementById(this.param.menuDropDownPresetListContainerId);
		const modelListDiv = document.getElementById(this.param.menuDropDownModelListContainerId);

		// Check attribute flag, used to check if data already loaded ONCE
		if (dropDown.getAttribute('data-loaded') === "1" && !force) {
			return;
		}

		// Fetch models
		try {
			const res = await fetch(this.param.interfaceUrl + '?action=list_models', {
				method: 'POST',
				headers: {'Content-Type': 'application/x-www-form-urlencoded'},
				body: new URLSearchParams({
					element_type: this.param.elementType,
					context: this.param.context,
					token: this.param.token
				})
			});
			const json = await res.json();

			if (!json.result) {
				throw new Error(json.msg);
			}

			if (!json.data) {
				throw new Error('QuickMemo list models get empty data');
			}


			let models = json.data.modelTemplate.length ? json.data.modelTemplate : [];
			let presets = json.data.presetTemplate.length ? json.data.presetTemplate : [];

			// CREATE BTN TEMPLATE LIST
			if (!models.length) {
				modelListDiv.innerHTML = '<div class="no-models">' + await Dolibarr.tools.langs.trans('NoTemplatesFound') + '</div>';
			} else {
				models.forEach(model => {
					const btn = this.generateAddMemoBtn(model);
					modelListDiv.appendChild(btn);
				});
			}

			this.initModelReorder();

			// CREATE BTN FOR COLOR PRESETS LIST
			if (!presets.length) {
				modelListDiv.innerHTML = '<div class="no-models">' + await Dolibarr.tools.langs.trans('NoPresetsFound') + '</div>';
			} else {

				presets.forEach(preset => {
					const btn = this.generateAddMemoBtn(preset);
					presetListDiv.appendChild(btn);
				});

				// Quick add default note on double click
				dropDownMenuToggleBtn.addEventListener('dblclick', e => {
					e.preventDefault();
					this.createMemoFromModel(presets[0]);
					dropdown.classList.remove('open');
				});
			}

			// Set loaded data flag
			dropDown.setAttribute('data-loaded', 1);
		} catch (err) {
			Dolibarr.tools.setEventMessage(Dolibarr.tools.langs.trans('QuickMemoLoadModelError') + ' : ' + err, 'errors');
			console.error('QuickMemo load models error', err);
		}
	}

	/* ===============================
	   CREATION MEMO DEPUIS MODELE
	================================= */
	/**
	 * Creates a new note based on a specific template (model).
	 * @async
	 * @param {Object} model - The template data.
	 */
	async createMemoFromModel(model) {
		// 1. Trouver le z-index maximum actuel parmi les notes affichées
		const allMemos = this.container.querySelectorAll('.quick-memo-note'); // Adaptez le sélecteur si besoin
		let maxZ = 0;
		allMemos.forEach(el => {
			const z = parseInt(el.style.zIndex) || 0;
			if (z > maxZ) maxZ = z;
		});

		// La nouvelle note doit être au-dessus de tout le monde
		const newZ = maxZ + 1;


		// Crée un objet memo minimal à partir du modèle
		const memo = {
			id: 0, // rowid temporaire avant sauvegarde
			deleteBtn: false,
			note: model.note || '',
			pos_w: model.pos_w || 250,   // largeur par défaut
			pos_h: model.pos_h || 250,  // hauteur par défaut
			pos_x: model.pos_x || 0,
			pos_y: model.pos_y || 0,
			pos_z: newZ || 0,
			color: model.color || '#fff8a6',
			shared_on_element: model.shared_on_element !== undefined ? model.shared_on_element : 0,
			private: model.private !== undefined ? parseInt(model.private) : 0
		};

		// Center the new note in the current viewport
		const containerWidth = this.container.clientWidth;
		const containerHeight = this.container.clientHeight;
		const viewportHeight = window.innerHeight;
		const scrollY = window.scrollY || window.pageYOffset;

		memo.pos_x = Math.max(32, (containerWidth - memo.pos_w) / 2);
		memo.pos_y = scrollY + Math.max(32, (viewportHeight - memo.pos_h) / 2);

		// Render le memo
		const noteEl = await this.renderMemo(memo);
		this.bringToTop(noteEl);

		// Focus sur le textarea
		const textarea = noteEl.querySelector('textarea');
		if (textarea) {
			textarea.focus();
			textarea.setSelectionRange(textarea.value.length, textarea.value.length);
		}

		try {
			const res = await fetch(this.param.interfaceUrl + '?action=create', {
				method: 'POST',
				headers: {'Content-Type': 'application/x-www-form-urlencoded'},
				body: new URLSearchParams({
					element_id: this.param.elementId,
					element_type: this.param.elementType,
					context: this.param.context,
					note: memo.note,
					color: memo.color,
					private: memo.private,
					shared_on_element: memo.shared_on_element,
					token: this.param.token,
					x: parseInt(noteEl.style.left),
					y: parseInt(noteEl.style.top),
					w: parseInt(noteEl.style.width),
					h: parseInt(noteEl.style.height),
					z: parseInt(memo.pos_z)
				})
			});

			const json = await res.json();

			if (!json.result) {
				throw new Error(json.msg);
				return;
			}

			if (json.data && json.data.id) {
				memo.id = json.data.id;
				noteEl.dataset.id = json.data.id;
			}

			if (json.data && json.data.memo) {
				await this.updateMemoTxt(json.data.memo, noteEl);
			}

			Dolibarr.executeHook('QuickMemo:createMemoFromModel', {memo, noteEl, json});

		} catch (err) {
			Dolibarr.tools.setEventMessage(Dolibarr.tools.langs.trans('QuickMemoCreateError') + ' : ' + err, 'errors');
			console.error('QuickMemo create error', err);
		}
	}

	/**
	 * Escapes special HTML characters for safe rendering.
	 * @param {string} str
	 * @returns {string}
	 */
	escapeHTML(str) {
		return str.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#39;');
	}


	/**
	 * A basic markdown parser that handles titles, checkboxes, links, bold and italic.
	 * @async
	 * @param {string} md - The raw markdown text.
	 * @returns {string} The resulting HTML string.
	 */
	async simpleMarkdown(md) {

		/**
		 * Handles inline styles like Bold, Italic, Underline, and Links.
		 * Can be used inside headers and checkboxes.
		 */
		const parseInline = (text) => {
			if (!text) return '';
			return this.escapeHTML(text)
				.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')    // **Bold**
				.replace(/__(.*?)__/g, '<u>$1</u>')                 // __Underline__
				.replace(/\*(.*?)\*/g, '<em>$1</em>')               // *Italique*
				.replace(/\[(.*?)\]\((.*?)\)/g, (full, txt, url) => { // [Link](url)
					const safeUrl = /^(https?:\/\/|#|\/)/.test(url) ? url : '#';
					return `<a href="${this.escapeHTML(safeUrl)}" target="_blank" rel="noopener">${this.escapeHTML(txt)}</a>`;
				});
		};

		const lines = md.split('\n');

		// Use .map to ensure every input line creates exactly one .markdown-line output
		let res = lines.map((line, idx) => {
			let html = '';
			let isEmpty = false;
			let match;

			const nextLine = (lines[idx + 1] || "").trim();
			const prevLine = (lines[idx - 1] || "").trim();
			const trimmedLine = line.trim();

			// CASE 1: Setext H1 - Current line is text followed by "==="
			if (trimmedLine !== "" && nextLine.match(/^={3,}$/)) {
				html = `<h1>${parseInline(line)}</h1>`;
			}
			// CASE 2: Setext H2 - Current line is text followed by "---"
			else if (trimmedLine !== "" && nextLine.match(/^-{3,}$/)) {
				html = `<h2>${parseInline(line)}</h2>`;
			}
				// CASE 3: The Underline line itself (=== or ---)
			// We check if the line above was a title to decide if we hide this line
			else if (trimmedLine.match(/^={3,}$/) && prevLine !== "") {
				html = ''; // Hide the syntax line
				isEmpty = true;
			} else if (trimmedLine.match(/^-{3,}$/) && prevLine !== "") {
				html = ''; // Hide the syntax line
				isEmpty = true;
			}
			// CASE 4: Standard ATX Titles (#, ##, ###)
			else if (match = line.match(/^(#{1,3})\s+(.*)$/)) {
				const level = match[1].length;
				html = `<h${level}>${parseInline(match[2])}</h${level}>`;
			}
			// CASE 5: Checkboxes [ ] or [x]
			else if (match = line.match(/^\[([ xX])\]\s+(.*)$/)) {
				const checked = match[1].toLowerCase() === 'x' ? 'checked' : '';
				html = `<input class="markdown-checkbox" type="checkbox" ${checked}> ${parseInline(match[2])}`;
			}
			// CASE 6: Regular Text or actually empty line
			else {
				if (trimmedLine === "") {
					html = '&nbsp;';
					isEmpty = true;
				} else {
					html = parseInline(line);
				}
			}

			// Maintain the div structure for scrolling/indexing consistency
			const classes = `markdown-line ${isEmpty ? 'empty-line' : ''}`;
			return `<div class="${classes}" data-line="${idx}">${html}</div>`;
		}).join('');

		// Hook for external extensions
		if (Dolibarr && Dolibarr.executeHookAwait) {
			await Dolibarr.executeHookAwait('QuickMemo:simpleMarkdown', {md, res});
		}

		return res;
	}

	/**
	 * Handles Toggle for Bold and Italic without infinite accumulation.
	 * @param {string} text - The selected text
	 * @param {string} type - 'bold' or 'italic'
	 * @returns {string} Processed text
	 */
	/**
	 * Toggles inline markdown with support for overlapping styles (***bold italic***)
	 */
	toggleInlineMarkdown(text, type) {
		const patterns = {
			bold: { tag: '**', regex: /^\*\*(.*)\*\*$/ },
			italic: { tag: '*', regex: /^(\*\*\*|(?<!\*)\*(?!\*))(.*)(\*\*\*|(?<!\*)\*(?!\*))$/ },
			underline: { tag: '__', regex: /^__(.*)__$/ }
		};

		const { tag, regex } = patterns[type];

		// Special case for Italic to not conflict with Bold
		if (type === 'italic') {
			// If it starts with *** (Bold+Italic) or exactly * (Italic only)
			if ((text.startsWith('***') && text.endsWith('***')) ||
				(text.startsWith('*') && !text.startsWith('**') && text.endsWith('*') && !text.endsWith('**'))) {
				return text.substring(1, text.length - 1);
			}
			return `*${text}*`;
		}

		// Standard case for Bold and Underline
		if (text.startsWith(tag) && text.endsWith(tag)) {
			return text.substring(tag.length, text.length - tag.length);
		}

		return tag + text + tag;
	}

	/**
	 * Inserts text at cursor position while trying to preserve Undo/Redo history.
	 * Falls back to setRangeText if execCommand is not supported.
	 * * @param {HTMLTextAreaElement} textarea
	 * @param {string} text - The text to insert
	 * @param {number} selectionMode - 'select', 'start', 'end', or 'preserve'
	 */
	/**
	 * Future-proof text insertion that respects the Undo stack.
	 * @param {HTMLTextAreaElement} textarea
	 * @param {string} text - The text to insert
	 * @param {string} selectionMode - 'select', 'start', 'end', or 'preserve' (for setRangeText)
	 * @param {boolean} forceReselect - Manual override to keep selection after execCommand
	 */
	safeInsertText(textarea, text, selectionMode = 'select', forceReselect = false) {
		textarea.focus();
		const start = textarea.selectionStart;
		let success = false;

		// 1. Try native command for Undo/Redo support
		if (document.queryCommandSupported && document.queryCommandSupported('insertText')) {
			success = document.execCommand('insertText', false, text);
		}

		// 2. Fallback to modern API (SelectionMode is used here)
		if (!success) {
			// selectionMode 'select' keeps the new text highlighted
			textarea.setRangeText(text, start, textarea.selectionEnd, selectionMode);
			// We must manually trigger input for the preview to update
			textarea.dispatchEvent(new Event('input', { bubbles: true }));
		}

		// 3. Manual override for execCommand
		// Because execCommand usually moves the cursor to the end,
		// we force a re-selection if requested (very useful for Bold/Italic)
		if (forceReselect !== false) {
			if (forceReselect === 'select') {
				textarea.setSelectionRange(start, start + text.length);
				return;
			}
			else if (forceReselect === 'start') {
				textarea.setSelectionRange(start, start);
				return;
			}
			else if (forceReselect === 'end') {
				textarea.setSelectionRange(start + text.length, start + text.length);
				return;
			}
		}

		if (forceReselect === 'select' || (success && selectionMode === 'select')) {
			textarea.setSelectionRange(start, start + text.length);
		}
	}

	/**
	 * Applies Markdown formatting while preserving Undo history.
	 * Supports inline toggling (bold, italic, underline) and block toggling (h1, h2, h3, task).
	 * * @param {HTMLTextAreaElement} textarea - The source textarea
	 * @param {string} type - Formatting type (bold, italic, underline, h1, h2, h3, task)
	 * @param {HTMLElement} toolbar - Toolbar element for state updates
	 */
	applyMarkdown(textarea, type, toolbar) {
		const start = textarea.selectionStart;
		const end = textarea.selectionEnd;
		const val = textarea.value;
		const selectedText = val.substring(start, end);

		const inlineTags = { bold: '**', italic: '*', underline: '__' };

		// --- 1. Inline Formatting Logic ---
		if (inlineTags[type]) {
			const tag = inlineTags[type];
			let replacementText = "";
			let newCursorPos = start;

			if (selectedText.length > 0) {
				// Toggle existing formatting on selected text
				replacementText = this.toggleInlineMarkdown(selectedText, type);
			} else {
				// No selection: create a pair of tags and place cursor inside
				replacementText = tag + tag;
				newCursorPos = start + tag.length;
			}

			this.safeInsertText(textarea, replacementText, 'select');

			// Adjust cursor position if we just inserted empty tags (e.g., **|**)
			if (selectedText.length === 0) {
				textarea.setSelectionRange(newCursorPos, newCursorPos);
			}
		}
		// --- 2. Block/Line-Based Formatting Logic ---
		else {
			// Find line boundaries
			const lineStart = val.lastIndexOf('\n', start - 1) + 1;
			let lineEnd = val.indexOf('\n', end);
			if (lineEnd === -1) lineEnd = val.length;

			const lineText = val.substring(lineStart, lineEnd);
			const prefixes = { h1: '# ', h2: '## ', h3: '### ', task: '[ ] ' };
			const currentPrefix = prefixes[type];

			// Regex to match existing headers or checkboxes to allow "swapping" styles
			const prefixRegex = /^(\s*#{1,3}\s+|\[[ xX]\]\s+)/;

			let newLineText;
			if (lineText.startsWith(currentPrefix)) {
				// Toggle OFF: Remove the prefix
				newLineText = lineText.replace(currentPrefix, '');
			} else {
				// Toggle ON: Strip any existing block prefix and add the new one
				const cleanLine = lineText.replace(prefixRegex, '');
				newLineText = currentPrefix + cleanLine;
			}

			// Select the whole line and replace it via safeInsert to keep Undo history
			textarea.setSelectionRange(lineStart, lineEnd);
			this.safeInsertText(textarea, newLineText, 'end', (lineStart === lineEnd ? 'end' : 'start'));

			// Special UX: If it's a new empty task, put cursor after the checkbox
			if (type === 'task' && lineText.trim() === "") {
				const newCursorPos = lineStart + currentPrefix.length;
				textarea.setSelectionRange(newCursorPos, newCursorPos);
			}
		}

		// Update Toolbar & UI
		if (this.updateToolbarState) {
			this.updateToolbarState(textarea, toolbar);
		}
	}

	/**
	 * Updates toolbar buttons' active state based on the current selection or cursor position.
	 * Optimized for Post-it notes with support for Setext and Underline.
	 */
	updateToolbarState(textarea, toolbar) {
		const start = textarea.selectionStart;
		const end = textarea.selectionEnd;
		const text = textarea.value;
		const selectedText = text.substring(start, end);

		/**
		 * Helper to detect if the current context is formatted.
		 * It checks both the selection and the immediate surrounding characters.
		 */
		const isFormatActive = (type) => {
			// 1. Check if selection is wrapped
			if (selectedText.length > 0) {
				if (type === 'bold') return selectedText.startsWith('**') && selectedText.endsWith('**');
				if (type === 'underline') return selectedText.startsWith('__') && selectedText.endsWith('__');
				if (type === 'italic') {
					return (selectedText.startsWith('***') && selectedText.endsWith('***')) ||
						(selectedText.startsWith('*') && !selectedText.startsWith('**'));
				}
			}

			// 2. Check cursor context (peek 3 chars around)
			const before = text.substring(start - 3, start);
			const after = text.substring(end, end + 3);
			const context = before + "|" + after;

			if (type === 'bold') return context.includes('**|**') || context.includes('***|***');
			if (type === 'italic') return context.includes('*|*') || context.includes('***|***');
			if (type === 'underline') return context.includes('__|__');

			return false;
		};

		// Update UI
		const states = {
			bold: isFormatActive('bold'),
			italic: isFormatActive('italic'),
			underline: isFormatActive('underline')
		};

		Object.keys(states).forEach(type => {
			toolbar.querySelector(`[data-type="${type}"]`)?.classList.toggle('--active', states[type]);
		});

		// --- Line-based logic (H1, H2, Tasks) remains the same ---
		const lineStart = text.substring(0, start).lastIndexOf('\n') + 1;
		const currentLine = text.substring(lineStart, text.indexOf('\n', start) === -1 ? text.length : text.indexOf('\n', start)).trim();

		toolbar.querySelector('[data-type="h1"]')?.classList.toggle('--active', currentLine.startsWith('# '));
		toolbar.querySelector('[data-type="h2"]')?.classList.toggle('--active', currentLine.startsWith('## '));
		toolbar.querySelector('[data-type="h3"]')?.classList.toggle('--active', currentLine.startsWith('### '));
		toolbar.querySelector('[data-type="task"]')?.classList.toggle('--active', /^\[[ xX]\] /.test(currentLine));
	}

	getDropDownHelp() {
		return `
      <div class="quickmemo-help-content">

         <div class="help-section">
            <h4><i class="fa fa-eye-slash"></i> ${Dolibarr.tools.langs.trans('QuickMemoHelp_Visibility')}</h4>
            <p>${Dolibarr.tools.langs.trans('QuickMemoHelp_EscKey')}</p>
         </div>

         <div class="help-section">
            <h4><i class="far fa-copy"></i> ${Dolibarr.tools.langs.trans('QuickMemoHelp_Management')}</h4>
            <ul>
               <li>${Dolibarr.tools.langs.trans('QuickMemoHelp_DeleteHover')}</li>
               <li>${Dolibarr.tools.langs.trans('QuickMemoHelp_ShiftKeyDelete')}</li>
            </ul>
         </div>

         <div class="help-section">
            <h4><i class="fa fa-sort-numeric-down"></i> ${Dolibarr.tools.langs.trans('QuickMemoHelp_Organization')}</h4>
            <p>${Dolibarr.tools.langs.trans('QuickMemoHelp_DragDrop')}</p>
         </div>

         <p class="help-footer"><em>${Dolibarr.tools.langs.trans('QuickMemoHelp_Footer')}</em></p>
      </div>
   `;
	}

	/**
	 * Returns a help string describing the supported markdown syntax.
	 * @returns {string}
	 */
	getMarkDownHelp() {
		return `
      <div class="quickmemo-help-content">

         <div class="help-section universal-logic">
            <h4><i class="fa fa-globe"></i> ${Dolibarr.tools.langs.trans('QuickMemoMd_SectionUniversal')}</h4>
            <p>${Dolibarr.tools.langs.trans('QuickMemoMd_UniversalDesc')}</p>
         	<p class="help-intro"><strong>${Dolibarr.tools.langs.trans('QuickMemoMd_HelpIntro')}</strong></p>
         </div>


         <div class="help-section">
            <h4><i class="fa fa-text-height"></i> ${Dolibarr.tools.langs.trans('QuickMemoMd_SectionStyle')}</h4>
            <code>*${Dolibarr.tools.langs.trans('QuickMemoMd_Italic')}*</code> => <em>${Dolibarr.tools.langs.trans('QuickMemoMd_Italic')}</em><br/>
            <code>**${Dolibarr.tools.langs.trans('QuickMemoMd_Bold')}**</code> => <strong>${Dolibarr.tools.langs.trans('QuickMemoMd_Bold')}</strong>
         </div>

         <div class="help-section">
            <h4><i class="fa fa-heading"></i> ${Dolibarr.tools.langs.trans('QuickMemoMd_SectionTitles')}</h4>
            <code># ${Dolibarr.tools.langs.trans('QuickMemoMd_Title')} 1</code><br/>
            <code>## ${Dolibarr.tools.langs.trans('QuickMemoMd_Title')} 2</code><br/>
            <code>### ${Dolibarr.tools.langs.trans('QuickMemoMd_Title')} 3</code>
         </div>

         <div class="help-section">
            <h4><i class="fa fa-check-square"></i> ${Dolibarr.tools.langs.trans('QuickMemoMd_SectionTasks')}</h4>
            <code>[ ] ${Dolibarr.tools.langs.trans('QuickMemoMd_TaskNotDone')}</code> => <input type="checkbox" disabled> ...<br/>
            <code>[x] ${Dolibarr.tools.langs.trans('QuickMemoMd_TaskDone')}</code> => <input type="checkbox" checked disabled> ...
         </div>

         <div class="help-section">
            <h4><i class="fa fa-link"></i> ${Dolibarr.tools.langs.trans('QuickMemoMd_SectionLinks')}</h4>
            <code>[${Dolibarr.tools.langs.trans('QuickMemoMd_LinkText')}](url)</code> => <a href="#" onclick="return false;">${Dolibarr.tools.langs.trans('QuickMemoMd_LinkText')}</a>
         </div>

         <p class="help-footer"><em>${Dolibarr.tools.langs.trans('QuickMemoMd_HelpFooter')}</em></p>
      </div>
   `;
	}

	/**
	 * Opens a modal dialog to define parameters before creating a new template from a note.
	 * @param {Object} memo
	 * @param {HTMLElement} note
	 */
	openCreateModelDialog(memo, note) {

		const dialog = document.createElement('dialog');
		dialog.className = 'quickmemo-modal noselect';

		dialog.innerHTML = `
		<form>
			<header>
				<h3 class="quickmemo-modal-title">${Dolibarr.tools.langs.trans('QuickMemoAskCreateAModelFromThisMemo')}</h3>
			</header>

			<section>
				<div class="quickmemo-modal-input-group">
					<label class="quickmemo-modal-label">
						${Dolibarr.tools.langs.trans('QuickMemoModelName')}
					</label>
					<input
						class="quickmemo-modal-text-input"
						type="text"
						name="modelName"
						required
						placeholder="${Dolibarr.tools.langs.transNoEntities('QuickMemoModelNamePlaceHolder')}"
					/>
				</div>

				${this.param.elementId > 0 ? `
				<div class="quickmemo-modal-input-group">
					<label class="quickmemo-modal-label">
						<input type="checkbox" name="commonElementType" value="1"  />
						${Dolibarr.tools.langs.trans('QuickMemoAskLabelModelMemoIsCommon')}
					</label>
				</div>
				` : `<input type="hidden" name="commonElementType" value="0" />` }

				<div class="quickmemo-modal-input-group">
					<label class="quickmemo-modal-label">
						<input type="checkbox" name="privateModel" checked />
						${Dolibarr.tools.langs.trans('QuickMemoAskLabelModelMemoIsPrivate')}
					</label>
				</div>
			</section>

			<footer class="quickmemo-modal-actions">
				<button type="button" class="dialog-btn dialog-btn-secondary quickmemo-modal-actions-cancel-btn">
					${Dolibarr.tools.langs.trans('Cancel')}
				</button>
				<button type="button" class="dialog-btn dialog-btn-primary quickmemo-modal-actions-confirm-btn">
					${Dolibarr.tools.langs.trans('Confirm')}
				</button>
			</footer>
        </form>
    	`;

		document.body.appendChild(dialog);

		// Quand le dialog se ferme (Esc, cancel, close()), on le détruit
		dialog.addEventListener('close', () => {
			dialog.remove();
		});

		// Empêche le close automatique
		dialog.addEventListener('cancel', (e) => {
			e.preventDefault(); // optionnel
			dialog.close();
		});

		// Bouton Annuler
		dialog.querySelector('.quickmemo-modal-actions-cancel-btn')
			.addEventListener('click', () => {
				dialog.close();
				dialog.remove();
			});

		// Confirmer
		dialog.querySelector('.quickmemo-modal-actions-confirm-btn')
			.addEventListener('click', async () => {

				const commonInput = dialog.querySelector('[name=commonElementType]');
				const TplCommon = commonInput
					? (commonInput.type === 'checkbox' ? commonInput.checked : commonInput.value === '1')
					: false;
				const TplPrivate = dialog.querySelector('[name=privateModel]').checked;
				const TplModelName = dialog.querySelector('[name=modelName]').value || '';
				const elementType = TplCommon ? '' : this.param.elementType || '';

				let hookRes = await Dolibarr.executeHookAwait('QuickMemo:openCreateModelDialog:confirm', {
					dialog,
					memo,
					note,
					TplCommon,
					TplPrivate,
					TplModelName,
					elementType
				});
				if (hookRes > 0) {
					return;
				}

				try {
					const res = await fetch(this.param.interfaceUrl + '?action=create_model', {
						method: 'POST',
						headers: {'Content-Type': 'application/x-www-form-urlencoded'},
						body: new URLSearchParams({
							id: memo.id,
							element_type: elementType,
							token: this.param.token,
							tpl_private: TplPrivate,
							tpl_name: TplModelName
						})
					});

					const json = await res.json();

					if (!json.result) {
						throw new Error(json.msg);
					}

					Dolibarr.executeHook('QuickMemo:openCreateModelDialog:ModelCreated', {
						dialog,
						memo,
						note,
						TplCommon,
						TplPrivate,
						TplModelName,
						elementType,
						json
					});
				} catch (err) {

					Dolibarr.tools.setEventMessage(
						Dolibarr.tools.langs.trans('QuickMemoCreateError') + ' : ' + err,
						'errors'
					);

					console.error('Erreur fetch création modèle', err);

				} finally {
					dialog.close();
					this.convertToModelWithAnimation(memo, note);
				}
			});


		Dolibarr.executeHook('QuickMemo:openCreateModelDialog', {dialog, memo, note});

		dialog.showModal();
	}

	/**
	 * Specific confirmation dialog wrapper.
	 * @param {string} message - HTML content.
	 * @param {Object} param - Overrides for the confirmation UI.
	 * @returns {Promise<boolean>}
	 */
	openConfirmDialog(message, param = {}) {
		// Map old param names to the new config names if necessary,
		// or just set confirmation defaults.
		const confirmOptions = {
			title: param.dialogTitle || Dolibarr.tools.langs.trans('DialogAskConfirm'),
			customClass: 'noselect' + ( param.dialogClass || ''),
			confirmLabel: param.btnConfirmTxt || Dolibarr.tools.langs.trans('Confirm'),
			confirmClass: param.btnConfirmClass || 'btn-primary',
			btn: {
				confirm: true,
				cancel: true,
				close: true
			},
		};

		return this.openSimpleDialog(message, confirmOptions);
	}



	/**
	 * Generic dialog engine based on HTML5 <dialog>.
	 * @param {string} message - HTML content to display.
	 * @param {Object} options - UI parameters.
	 * @returns {Promise<boolean>} Resolves to true if confirmed, false otherwise.
	 */
	async openSimpleDialog(message, options = {}) {
		const config = {
			title: '',
			customClass: '',
			confirmLabel: Dolibarr.tools.langs.trans('Confirm'),
			confirmClass: 'btn-primary',
			cancelLabel: Dolibarr.tools.langs.trans('Cancel'),
			...options,
			btn: {
				close: true,
				confirm: true,
				cancel: false,
				...(options.btn || {}) // Merge seulement les boutons précisés dans options
			}
		};

		return new Promise((resolve) => {
			const dialog = document.createElement('dialog');
			dialog.className = `quickmemo-modal fade-dialog ${config.customClass}`;

			dialog.innerHTML = `
            <div class="dialog-content">
                <header >
                    <h3 class="quickmemo-modal-title">${config.title}</h3>
                    <div class="header-actions noselect">
                     ${config.btn.close ? `
                    	<button type="button" value="cancel" class="dialog-close-icon">
                    		&times;
                    	</button>` : ''}
                    </div>
                </header>
                <section class="dialog-body">
                    ${message}
                </section>
                <footer class="quickmemo-modal-actions noselect">
                    ${config.btn.cancel ? `
                        <button type="button" value="cancel" class="dialog-btn btn-secondary">
                            ${config.cancelLabel}
                        </button>` : ''}

                     ${config.btn.confirm ? `
                    <button type="button" value="confirm" class="dialog-btn ${config.confirmClass}">
                        ${config.confirmLabel}
                    </button>` : ''}
                </footer>
            </div>
        `;

			document.body.appendChild(dialog);
			dialog.showModal();

			const handleClose = (result) => {
				dialog.close();
				resolve(result);
			};

			// Unified event listener
			dialog.addEventListener('click', (e) => {
				const btn = e.target.closest('button[value]');
				if (btn) return handleClose(btn.value === 'confirm');
				if (e.target === dialog) handleClose(false); // Backdrop click
			});

			dialog.addEventListener('cancel', (e) => {
				e.preventDefault();
				handleClose(false);
			});

			dialog.addEventListener('close', () => dialog.remove());
		});
	}
}
