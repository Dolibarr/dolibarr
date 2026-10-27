/* Copyright (C) 2026	Jose Martinez			<jose.martinez@pichinov.com>
 * Copyright (C) 2026	Nick Fragoulis
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file htdocs/ai/js/ai_assistant.js
 * \brief Frontend logic for the AI Assistant
 * \ingroup ai
 */

/**
 * Initialize the AI Assistant chat on a given container (.ai-chat-container).
 * Called explicitly by the topbar popover bootstrap (main.inc.php) after AJAX
 * injection, or automatically for server-rendered containers carrying the
 * data-ai-autoinit attribute (standalone page ai/assistant/index.php).
 *
 * @param {HTMLElement} container The .ai-chat-container element
 */
export function initAiAssistant(container) {
    if (!container || container.dataset.aiInit) return;
    container.dataset.aiInit = '1';

    // =========================================================================
    // CONFIGURATION & INITIALIZATION
    // =========================================================================

    // 1. Load configuration emitted by PHP (getAiChatAssistantConfig) as a data
    // attribute: inline <script> config cannot travel inside an innerHTML fragment.
    let config = {};
    try {
        config = JSON.parse(container.dataset.aiConfig || '{}');
    } catch (e) {
        console.error('AI Assistant: invalid data-ai-config attribute', e);
    }
    if (!config.labels && window.AI_CONFIG) config = window.AI_CONFIG; // Legacy fallback
    const CONFIG_MODE = config.mode || 'text';
    const aiLabels = config.labels || {};

    // Helper for safe translation retrieval
    const t = (key) => aiLabels[key] || key;

    // Absolute endpoint URL: the chat may run injected into any Dolibarr page
    // (topbar popover), so relative URLs would resolve against the wrong path.
    // Resolved against location.origin (never contains user:pass@): fetch() rejects URLs
    // with credentials, which a page loaded as http://user:pass@host/ (e.g. DoliDroid) would give.
    const epUrl = (file) => new URL((config.baseUrl || '') + file + (config.token ? '?token=' + encodeURIComponent(config.token) : ''), window.location.origin).href;

    // 2. Select DOM Elements (scoped to the container so several chat instances
    // can coexist on the same page, e.g. popover opened over the standalone page)
    const micBtn = container.querySelector('#mic-btn');
    const micWrapper = container.querySelector('#mic-wrapper');

    const uploadBtn = container.querySelector('#upload-btn');
    const uploadWrapper = container.querySelector('#upload-wrapper');
    const fileInput = container.querySelector('#file-upload');
    const chipArea = container.querySelector('#file-chip-area');

    const clearBtn = container.querySelector('#clear-btn');
    const engineSelect = container.querySelector('#engine-select');
    const modelSelect = container.querySelector('#model-select');
    const input = container.querySelector('#user-input');
    const chat = container.querySelector('#chat-history');
    const statusBar = container.querySelector('#status-bar');
    const sendBtn = container.querySelector('#send-btn');
    const welcome = container.querySelector('.chat-welcome'); // empty-state screen (full page only)

    // 3. Application State
    let isRecording = false;
    let isProcessing = false;
    let whisperWorker = null;
    let whisperReady = false;
    let cloudRecognition = null;

    // Context for maintaining conversation flow
    let lastResult = { data: null, tool: '', query: '' };
    let pendingIntent = null;     // Stores action waiting for confirmation
    let clarificationContext = null;
    // Third parties the user picked when a name fitted several: phrase -> id,
    // 0 = none of them. They answer the request they were asked for only, the next
    // question asks again.
    let thirdpartyChoices = {};
    let lastSent = null;          // { sentQuery, query } of the last request, resent once a choice is made
    // Document attached via the paperclip: {name, payload}. Sent as context with
    // the NEXT message; only a small chip (icon + name) is shown in the UI.
    let attachedDocs = [];        // [{name, payload, error?}] — several documents can ride the next message

    // --- Conversation persistence (storage is separate from the model context:
    // reopening restores the bubbles, only PINNED messages are ever resent). ---
    let conversationId = 0;          // 0 = the next message starts a new conversation
    let restoringHistory = false;    // guard: reopening or restoring must not re-save messages
    let persistQueue = Promise.resolve();   // keeps message order server-side

    // Every change of the current conversation goes through here, so the
    // "open in full page" button always carries it along (popover -> page
    // used to start from a blank chat).
    function setConversationId(id) {
        const changed = (conversationId !== id);
        conversationId = id;
        if (changed) renderSidebar();
        // Remember the conversation in progress in this browser, so the popover
        // on the next page (or the full page) resumes it instead of a blank chat.
        try { if (id) localStorage.setItem('aiCurrentConversation', String(id)); else localStorage.removeItem('aiCurrentConversation'); } catch (e) { /* no storage: nothing to resume */ }
        const expand = container.querySelector('#ai-expand-btn');
        if (expand && (expand.dataset.fullscreenBase || expand.dataset.fullscreenUrl)) {
            if (!expand.dataset.fullscreenBase) expand.dataset.fullscreenBase = expand.dataset.fullscreenUrl;
            const base = expand.dataset.fullscreenBase;
            expand.dataset.fullscreenUrl = base + (id ? (base.indexOf('?') >= 0 ? '&' : '?') + 'conv=' + encodeURIComponent(id) : '');
        }
    }

    function chatHistoryApi(payload) {
        return fetch(epUrl('../ajax/chat_history.php'), {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        }).then((r) => r.json());
    }

    function persistMessage(div, type, rawText, html) {
        persistQueue = persistQueue.then(() => chatHistoryApi({
            action: 'save', id: conversationId,
            role: (type === 'bot') ? 'assistant' : 'user',
            raw: String(rawText).slice(0, 8000), html: String(html).slice(0, 200000),
            pinned: (div.dataset.ctx === 'on') ? 1 : 0,
            error: div.dataset.aiError ? 1 : 0
        }).then((res) => {
            if (res && res.id) { setConversationId(res.id); delete previewCache[res.id]; }
            if (res && res.message_id) div.dataset.msgId = String(res.message_id);
        })).catch(() => { /* history is best-effort, the chat must never break on it */ });
    }
    // Mirrors the server-side AI_ATTACHMENT_MAX_FILES guard (ai_validate_attachments);
    // the per-file/total size caps live server-side too.
    const MAX_ATTACHED_DOCS = (parseInt(config.maxAttachments, 10) > 0) ? parseInt(config.maxAttachments, 10) : 5;
    // sessionStorage key of the conversation (per user and entity), see saveChatHistory()
    const HISTORY_KEY = config.historyKey || '';
    const HISTORY_MAX_MSGS = 20;
    // "All" mode of the context bar: every exchange, including the next ones, travels as context
    let contextAllMode = false;

    // Audio Hardware Context
    let audioContext, mediaStream, audioProcessor, audioChunks = [];
    let recordedSampleRate = 0;

    // Confirmation Voice Loop
    let confirmationRecognition = null;
    let isConfirmationListening = false;

    // Cache for dynamically loaded libraries
    let cachedPdfLib = null;
    let cachedTransformers = null;

    let cachedPdfJsModule = null;
    let isPdfJsLoading = false;

    // -------------------------------------------------------------------------
    // BOOTSTRAP
    // -------------------------------------------------------------------------
    // Full page only: make the chat fill exactly from its top down to the
    // viewport bottom, whatever the real top menu bar height is (no magic
    // offset, so no scrollbar nor blank strip). The popover sizes itself.
    if (!container.classList.contains('ai-in-popover')) {
        const fitPageHeight = () => {
            const top = container.getBoundingClientRect().top;
            // clientHeight excludes any scrollbar, so the chat fits exactly
            // without re-triggering a page scrollbar.
            const vh = document.documentElement.clientHeight;
            container.style.height = Math.max(420, vh - top) + 'px';
        };
        fitPageHeight();
        window.addEventListener('resize', fitPageHeight);
    }

    input.focus();
    engineSelect.value = CONFIG_MODE;
    updateInterfaceMode(CONFIG_MODE);

    // Welcome screen quick cards: send the localized ready-made prompt on click
    container.querySelectorAll('.ai-quick-card').forEach((card) => {
        card.addEventListener('click', () => {
            input.value = card.dataset.prompt || '';
            handleQuery();
        });
    });

    // Initialize Doc Parsing UI Listeners
    initDocParsingUI();

    // Initialize the model picker (presets + dynamic provider model list)
    initModelPicker();

    // Apply the select2 look of the other Dolibarr combos on the model and mode lists
    initSelect2Combo(modelSelect, true);
    initSelect2Combo(engineSelect, false);

    // Listen for custom event to trigger PDF download from buttons
    // (scoped to the container: each chat instance reacts only to its own buttons)
    container.addEventListener('triggerPdf', () => {
        if (lastResult.data) {
            downloadPdf(lastResult);
        } else {
            appendMsg('system', t('NoDataAvailable'));
        }
    });

    // =========================================================================
    // AUTO-RESIZE INPUT
    // =========================================================================

    const autoResizeInput = () => {
        if (!input) return;

        // 1. Reset height to 'auto' to shrink the box if text is deleted
        input.style.height = 'auto';

        // 2. Set height to scrollHeight to expand to fit content
        // We use Math.min to respect the max-height set in CSS (150px)
        input.style.height = Math.min(input.scrollHeight, 150) + 'px';
    };

    // Attach the auto-resize listener to the input
    if (input) {
        input.addEventListener('input', autoResizeInput);
        // "/" at the start of the input opens the tool palette, filtered as the user types.
        input.addEventListener('input', () => {
            const v = input.value;
            const mention = mentionTarget();
            if (mention) {
                mentionSuggest(mention);    // "@" or "#" being typed, in a command or a question
            } else if (paletteWanted(v) || paletteLocalTyped(v)) {
                paletteOpen(v.slice(1));
            } else if (container.querySelector('.ai-palette-list')) {
                paletteClose();
            }
        });
    }

    // Handle Submit (Enter) vs New Line (Shift+Enter)
    input.addEventListener('keydown', (e) => {
        if (paletteKey(e)) return;
        if (e.key === 'Enter') {
            if (e.shiftKey) {
                // Shift+Enter: Allow new line (default behavior), just ensure resize happens
                setTimeout(autoResizeInput, 0);
            } else {
                // Enter: Send query
                e.preventDefault(); // Stop the default newline
                handleQuery();
            }
        }
    });

    // Handle Send Button Click
    if (sendBtn) {
        sendBtn.addEventListener('click', handleQuery);
    }

    // =========================================================================
    // UI LOGIC
    // =========================================================================

    /**
     * Update UI visibility based on selected engine.
     * The paperclip (uploadWrapper) is ALWAYS visible: documents can be attached
     * in any mode, like in modern chat UIs. The legacy 'local_docs'/'cloud_docs'
     * selector modes are gone — routing local/cloud is automatic on attach.
     * @param {string} mode - 'text', 'cloud', 'whisper'
     */
    function updateInterfaceMode(mode) {
        micWrapper.classList.add('ai-hidden');

        if (mode === 'text') {
            input.placeholder = t('TypeYourQuestion');
            statusBar.innerText = '';
        }
        else if (mode === 'cloud' || mode === 'whisper') {
            // Voice Modes
            micWrapper.classList.remove('ai-hidden');
            input.placeholder = 'Type or speak...';
            initEngine(mode);
        }
    }

    // Reset the chat display and state: used by startNewConversation() (Clear button, "/new" and "/clear" commands)
    function clearConversation(fromOtherInstance = false) {
        // Remove conversation messages; keep the welcome element so it can be
        // shown again on the full page (the popover has no welcome screen).
        chat.querySelectorAll('.msg').forEach((n) => n.remove());
        if (welcome) {
            welcome.style.display = '';
        } else {
            chat.innerHTML = `<div class="msg system">${t('HistoryCleared')}</div>`;
        }
        lastResult = { data: null, tool: '', query: '' };
        clarificationContext = null;
        thirdpartyChoices = {};
        contextAllMode = false;
        if (HISTORY_KEY) {
            try {
                sessionStorage.removeItem(HISTORY_KEY);
                sessionStorage.removeItem(HISTORY_KEY + '_all');
            } catch (e) { /* ignore */ }
        }
        updateContextBar();
        if (fromOtherInstance) return;
        // The other chat of the page (popover over the full page) shows the same conversation: clear it too.
        document.dispatchEvent(new CustomEvent('aiChatCleared', { detail: { key: HISTORY_KEY, source: container } }));
        input.focus();
    }

    document.addEventListener('aiChatCleared', (ev) => {
        if (ev.detail && ev.detail.source !== container && ev.detail.key === HISTORY_KEY) {
            clearConversation(true);
            setConversationId(0);
        }
    });

    // Clear Chat History
    // Leave the current conversation (it stays in the history) and start a
    // blank one: the Clear button and the sidebar's "New conversation" share it.
    // The bubbles, the copy kept by this tab and the pending states go through
    // the shared reset; the server-side conversation simply stays in the list.
    function startNewConversation() {
        clearConversation();
        setConversationId(0);
        refreshContext();
        input.focus();
    }
    // Non-destructive since the conversation stays in the history: no confirm.
    clearBtn.addEventListener('click', () => startNewConversation());

    // Handle Engine Switching
    engineSelect.addEventListener('change', () => {
        if (isRecording) stopRecording();
        if (isProcessing) cancelProcessing();

        // Cleanup Whisper worker if switching away to save memory
        if (engineSelect.value !== 'whisper' && whisperWorker) {
            whisperWorker.terminate();
            whisperWorker = null;
            whisperReady = false;
        }

        updateInterfaceMode(engineSelect.value);
    });

    // Microphone Toggle
    micBtn.addEventListener('click', () => {
        if (isProcessing) return; // Prevent clicks while processing
        if (isRecording) {
            stopRecording();
        } else {
            startRecording();
        }
    });

    // =========================================================================
    // DOCUMENT PARSING UI & LOGIC
    // =========================================================================

    function initDocParsingUI() {
        if (!uploadBtn || !fileInput) return;

        uploadBtn.addEventListener('click', () => fileInput.click());

        fileInput.addEventListener('change', async (e) => {
            const files = Array.from(e.target.files || []);
            if (!files.length) return;

            for (const file of files) {
                if (attachedDocs.filter((d) => !d.error).length >= MAX_ATTACHED_DOCS) {
                    statusBar.innerText = t('AIAttachmentTooMany').replace('%s', String(MAX_ATTACHED_DOCS));
                    break;
                }
                await attachOneFile(file);
            }

            fileInput.value = '';
        });
    }

    /** Process one selected file and add it to the attached-documents list. */
    async function attachOneFile(file) {
            renderChips({ name: file.name, loading: true });
            statusBar.innerText = t('ProcessingFile') + ` ${file.name}...`;

            try {
                // Automatic routing: try the in-browser extraction first, then
                // fall back to server-side cloud parsing (multimodal) when the
                // local result is empty or too short to be useful — typically a
                // photographed PDF with no text layer.
                //
                // Two guards keep the chip from spinning for minutes:
                // - a large image goes straight to cloud parsing (browser OCR on a
                //   multi-MB photo downloads Tesseract + a language model and can
                //   take minutes for a poor result);
                // - any local extraction is capped by a hard timeout, after which
                //   we fall back to cloud parsing (the orphan extraction result,
                //   if it ever completes, is simply ignored).
                const LOCAL_EXTRACT_TIMEOUT_MS = 20000;
                const LOCAL_IMAGE_MAX_BYTES = 1500000;
                const isImage = file.type.startsWith('image/') || /\.(png|jpe?g|gif|bmp|webp)$/i.test(file.name);

                // HEIC/HEIF (iPhone photos): no provider-agnostic path exists — the
                // in-browser OCR cannot decode it, Anthropic/OpenAI reject the MIME
                // and only Gemini takes it natively. Route: transcode to JPEG via
                // canvas where the browser can decode HEIC (Safari/iOS — precisely
                // where those photos come from); otherwise send natively when the
                // configured provider accepts it; otherwise explain clearly.
                if (isHeic(file)) {
                    let heicPayload = '';
                    try {
                        heicPayload = await transcodeImageToJpegMarker(file);
                    } catch (errHeic) {
                        if (parseInt(config.providerAcceptsHeic || 0, 10)) {
                            const b64 = String(await fileToBase64(file)).split(',').pop();
                            heicPayload = `__FILE_ATTACHMENT__[image/heic]::${b64}`;
                        } else {
                            throw new Error(t('AIAttachmentHeicUnsupported'));
                        }
                    }
                    attachedDocs.push({ name: file.name, payload: heicPayload });
                    renderChips();
                    statusBar.innerText = '';
                    input.focus();
                    return;
                }
                // Enforced privacy redaction: document contents cannot be masked,
                // so the cloud-attachment fallback is off the table. Local
                // extraction becomes the only route - attempt it even for large
                // images (slow OCR beats a policy bypass), and fail with the
                // policy message instead of silently shipping the file.
                const redactOnly = !!parseInt(config.privacyRedaction || 0, 10);

                let contentPayload = '';
                if (redactOnly || !(isImage && file.size > LOCAL_IMAGE_MAX_BYTES)) {
                    try {
                        contentPayload = await Promise.race([
                            processLocalFile(file),
                            new Promise((resolve) => setTimeout(() => resolve(''), LOCAL_EXTRACT_TIMEOUT_MS))
                        ]);
                    } catch (errLocal) {
                        contentPayload = '';
                    }
                }
                const usefulChars = (contentPayload || '').replace(/\s+/g, '').length;
                if (usefulChars < 120) {
                    if (redactOnly) {
                        throw new Error(t('AIAttachmentBlockedByPrivacy'));
                    }
                    statusBar.innerText = t('ProcessingFile') + ` ${file.name} (cloud)...`;
                    contentPayload = await processCloudFile(file);
                }

                if (!contentPayload || contentPayload.length < 5) {
                    throw new Error(t('UnsupportedFileType'));
                }

                // The content NEVER goes into the input nor the conversation:
                // it is kept aside and sent as context with the next message.
                attachedDocs.push({ name: file.name, payload: contentPayload });
                renderChips();
                statusBar.innerText = '';
                input.focus();
            } catch (err) {
                console.error(err);
                // Keep an error chip so the user sees WHICH file failed in a
                // multi-selection; it is excluded from sending and removable.
                attachedDocs.push({ name: file.name, payload: '', error: true });
                renderChips();
                statusBar.innerText = t('Error') + ": " + err.message;
            }
    }

    /** HEIC/HEIF detection: browsers often report an empty MIME for those. */
    function isHeic(file) {
        return file.type === 'image/heic' || file.type === 'image/heif' || /\.(heic|heif)$/i.test(file.name);
    }

    /**
     * Transcode an image file to a JPEG cloud-attachment marker via canvas.
     * Only works where the browser can decode the source format (HEIC: Safari).
     */
    async function transcodeImageToJpegMarker(file) {
        const bitmap = await createImageBitmap(file);	// throws where HEIC is not decodable
        const canvas = document.createElement('canvas');
        canvas.width = bitmap.width;
        canvas.height = bitmap.height;
        canvas.getContext('2d').drawImage(bitmap, 0, 0);
        const blob = await new Promise((resolve, reject) => {
            canvas.toBlob((b) => (b ? resolve(b) : reject(new Error('toBlob failed'))), 'image/jpeg', 0.85);
        });
        const base64 = String(await fileToBase64(blob)).split(',').pop();
        return `__FILE_ATTACHMENT__[image/jpeg]::${base64}`;
    }

    /** Pick a FontAwesome icon class from a file name extension. */
    function chipIcon(name) {
        const ext = (String(name).split('.').pop() || '').toLowerCase();
        if (ext === 'pdf') return 'fa-file-pdf';
        if (['png', 'jpg', 'jpeg', 'gif', 'bmp', 'webp'].indexOf(ext) !== -1) return 'fa-file-image';
        if (['xls', 'xlsx', 'ods'].indexOf(ext) !== -1) return 'fa-file-excel';
        if (['doc', 'docx', 'odt'].indexOf(ext) !== -1) return 'fa-file-word';
        return 'fa-file-alt';
    }

    /** Minimal HTML escaping (appendMsg uses innerHTML). */
    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, (c) => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c]));
    }

    /**
     * Render the attached-file chips above the input pill from attachedDocs,
     * optionally appending a transient spinner chip for a file being processed.
     * Each chip carries its own remove cross (by index).
     */
    function renderChips(processing) {
        if (!chipArea) return;
        if (!attachedDocs.length && !processing) {
            chipArea.innerHTML = '';
            chipArea.classList.add('ai-hidden');
            return;
        }
        chipArea.classList.remove('ai-hidden');
        let html = '';
        attachedDocs.forEach((doc, idx) => {
            html += '<span class="file-chip' + (doc.error ? ' chip-error' : '') + '">'
                + '<i class="fa ' + chipIcon(doc.name) + ' chip-icon"></i>'
                + '<span class="chip-name">' + escapeHtml(doc.name) + '</span>'
                + '<button type="button" class="chip-x" data-idx="' + idx + '" title="' + escapeHtml(t('Cancel')) + '">&times;</button>'
                + '</span>';
        });
        if (processing) {
            html += '<span class="file-chip">'
                + '<i class="fa fa-spinner fa-spin chip-icon"></i>'
                + '<span class="chip-name">' + escapeHtml(processing.name) + '</span>'
                + '</span>';
        }
        chipArea.innerHTML = html;
        chipArea.querySelectorAll('.chip-x').forEach((btn) => {
            btn.addEventListener('click', () => {
                attachedDocs.splice(parseInt(btn.getAttribute('data-idx'), 10), 1);
                renderChips();
            });
        });
    }

    /** Forget every attached document and hide the chip area. */
    function clearChip() {
        attachedDocs = [];
        renderChips();
    }

    /** Inline (read-only) chip markup shown inside a sent user message. */
    function chipHtmlFor(name) {
        return '<span class="file-chip chip-inline"><i class="fa ' + chipIcon(name) + ' chip-icon"></i>'
            + '<span class="chip-name">' + escapeHtml(name) + '</span></span>';
    }

    /**
     * Light markdown rendering for free-text LLM answers: the models reply with
     * markdown (bold, lists, line breaks) that innerHTML would otherwise flatten
     * into one unreadable block with literal asterisks. HTML is escaped FIRST,
     * so the LLM cannot inject markup.
     * @param {string} text Raw model answer
     * @return {string} Safe HTML
     */
    function renderMarkdownLite(text) {
        let s = escapeHtml(String(text));
        s = s.replace(/\*\*([^*\n]+)\*\*/g, '<strong>$1</strong>');
        s = s.replace(/(^|[\s(])\*([^*\n]+)\*(?=[\s).,;:!?]|$)/g, '$1<em>$2</em>');
        s = s.replace(/`([^`\n]+)`/g, '<code>$1</code>');
        // Numbered/bulleted list items get their own line even when the model
        // packed them into a single paragraph ("… 5,20 € 2. **Référence** …").
        // Conservative: only break before "N. **Header**" items (the pattern the
        // model actually emits); "\S" also matched prose like "version 2. is out".
        s = s.replace(/\s(\d{1,2}\.\s)(?=<strong>)/g, '<br>$1');
        s = s.replace(/(^|\n)[-•]\s/g, '$1• ');
        s = s.replace(/\n/g, '<br>');
        return s;
    }

    // =========================================================================
    // MODEL PICKER
    // =========================================================================

    let modelList = [];   // model ids fetched from the provider (via list_models.php)

    /** Populate the model pill: Auto + presets, then the provider's model list. */
    function initModelPicker() {
        if (!modelSelect) return;
        let saved = '';
        try { saved = localStorage.getItem('aiModelChoice') || ''; } catch (e) { /* private mode */ }

        // Presets rely on a name heuristic (see resolveModel): only offered when
        // AI_SUGGESTS_PRESETS_TO_TRY_TO_CHANGE_MODEL_DYNAMICALLY is on.
        const presets = parseInt(config.modelPresets || 0, 10) ? [
            ['preset:fast', '⚡ ' + t('AIModelFast')],
            ['preset:balanced', '⚖️ ' + t('AIModelBalanced')],
            ['preset:deep', '🧠 ' + t('AIModelDeep')]
        ] : [];
        presets.forEach(([val, label]) => {
            const o = document.createElement('option');
            o.value = val; o.textContent = label;
            modelSelect.appendChild(o);
        });

        const applySaved = () => {
            if (saved && Array.prototype.some.call(modelSelect.options, (o) => o.value === saved)) {
                modelSelect.value = saved;
            }
            refreshSelect2Combo(modelSelect);
        };
        applySaved();

        fetch(epUrl('../ajax/list_models.php'))
            .then((r) => r.json())
            .then((j) => {
                modelList = (j && j.models) || [];
                if (modelList.length) {
                    // Disabled separator line rather than an <optgroup>: browsers
                    // indent optgroup entries, keep models aligned with 'Auto'.
                    const sep = document.createElement('option');
                    sep.disabled = true; sep.textContent = '──────────';
                    modelSelect.appendChild(sep);
                    modelList.forEach((id) => {
                        const o = document.createElement('option');
                        o.value = id; o.textContent = id;
                        modelSelect.appendChild(o);
                    });
                    // Saved model no longer offered by the provider: fall back to
                    // Auto, forget the stale choice, and tell the user ONCE (so the
                    // picker never looks silently ignored, cf. review on #39878).
                    if (saved && saved.indexOf('preset:') !== 0 && modelList.indexOf(saved) < 0) {
                        appendMsg('system', escapeHtml(t('AIModelSavedGone').replace('%s', saved)));
                        try { localStorage.removeItem('aiModelChoice'); } catch (e) { /* ignore */ }
                        saved = '';
                    }
                }
                applySaved();
            })
            .catch(() => { /* provider unreachable: keep Auto + presets */ });

        modelSelect.addEventListener('change', () => {
            try { localStorage.setItem('aiModelChoice', modelSelect.value); } catch (e) { /* ignore */ }
        });
    }

    /**
     * Turn a native <select> into a select2 combo with the same options as ajax_combobox().
     * Does nothing when select2 is not loaded (the native select keeps its own pill style).
     *
     * @param {HTMLSelectElement} sel        Select element
     * @param {boolean}           withSearch Show the search field in the dropdown
     */
    function initSelect2Combo(sel, withSearch) {
        if (!sel || !window.jQuery || !window.jQuery.fn.select2) return;
        const $sel = window.jQuery(sel);
        // Show the picto declared into the data-picto attribute of the option before its label
        const withPicto = (data) => {
            const picto = data.element ? data.element.getAttribute('data-picto') : '';
            const experimental = data.element ? data.element.getAttribute('data-experimental') : '';
            if (!picto) return data.text;
            const span = window.jQuery('<span></span>').text(data.text).prepend(window.jQuery('<span class="pictofixedwidth"></span>').addClass(picto));
            if (experimental) {
                span.append(window.jQuery('<span class="opacitymedium paddingleft"></span>').text('(' + t('VersionExperimental') + ')'));
            }
            return span;
        };
        $sel.select2({
            dir: 'ltr',
            width: 'resolve',
            minimumResultsForSearch: withSearch ? 0 : Infinity,
            language: (typeof select2arrayoflanguage === 'undefined') ? 'en' : select2arrayoflanguage,
            theme: 'default',
            dropdownCssClass: 'dol-dropdown-dialog dol-dropdown-dialogmono',
            templateResult: withPicto,
            templateSelection: withPicto,
            // Keep the dropdown inside the chat, so a click into it is not seen as a click outside the popover
            dropdownParent: window.jQuery(container)
        });
        // select2 fires a jQuery 'change' only, relay it to the native listeners of the select
        $sel.on('select2:select', () => sel.dispatchEvent(new Event('change')));
    }

    /**
     * Refresh the displayed value of a select2 combo after a programmatic change of the select.
     *
     * @param {HTMLSelectElement} sel Select element
     */
    function refreshSelect2Combo(sel) {
        if (sel && window.jQuery && window.jQuery(sel).data('select2')) {
            window.jQuery(sel).trigger('change.select2');
        }
    }

    /**
     * Resolve the picker value to a concrete model id ('' = provider default).
     * Presets map onto the dynamic list with a heuristic regex on the id.
     */
    function resolveModel() {
        if (!modelSelect) return '';
        const v = modelSelect.value;
        if (!v) return '';
        if (v.indexOf('preset:') === 0) {
            const kind = v.substring(7);
            const re = (kind === 'fast') ? /haiku|mini|flash|lite|instant/i
                : ((kind === 'balanced') ? /sonnet|4o|medium|small/i : /opus|o1|pro|large/i);
            const hit = modelList.find((id) => re.test(id));
            return hit || '';
        }
        return v;
    }

    // =========================================================================
    // LIBRARY LOADERS & EXTRACTORS
    // =========================================================================

    /**
     * Loads Mammoth.js (for .docx) via script tag.
     */
    async function getMammothLib() {
        if (window.mammoth) return window.mammoth;
        const scriptUrl = 'https://cdnjs.cloudflare.com/ajax/libs/mammoth/1.11.0/mammoth.browser.min.js';
        return loadScript(scriptUrl, 'mammoth');
    }

    /**
     * Loads SheetJS (for .xls/.xlsx) via script tag.
     */
    async function getXlsxLib() {
        if (window.XLSX) return window.XLSX;
        const scriptUrl = 'https://cdn.sheetjs.com/xlsx-0.20.1/package/dist/xlsx.full.min.js';
        return loadScript(scriptUrl, 'XLSX');
    }

    /**
     * Loads JSZip (for .odt/.ods) via script tag.
     */
    async function getJszipLib() {
        if (window.JSZip) return window.JSZip;
        const scriptUrl = 'https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js';
        return loadScript(scriptUrl, 'JSZip');
    }

    /**
     * Generic Helper to load a script and check for the global variable.
     */
    function loadScript(url, globalVarName) {
        if (document.querySelector(`script[src="${url}"]`)) {
            // If script exists but var isn't loaded yet, poll briefly
            return new Promise((resolve) => {
                const check = setInterval(() => {
                    if (window[globalVarName]) {
                        clearInterval(check);
                        resolve(window[globalVarName]);
                    }
                }, 50);
            });
        }

        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = url;
            script.async = true;
            script.onload = () => resolve(window[globalVarName]);
            script.onerror = () => reject(new Error(`${t('NetworkError')}: ${url}`));
            document.head.appendChild(script);
        });
    }


    // =========================================================================
    // PDF.js v5 LOADER
    // =========================================================================
    /**
     * Loads PDF.js v5.x ES Module dynamically and caches it.
     */
    async function getPdfLib() {
        // 1. Return cached module immediately if already loaded
        if (cachedPdfJsModule) {
            return cachedPdfJsModule;
        }

        // 2. Prevent multiple simultaneous downloads if called twice quickly
        if (isPdfJsLoading) {
            // Wait in a loop until the first call finishes downloading
            while (isPdfJsLoading) {
                await new Promise(resolve => setTimeout(resolve, 100));
            }
            // By now it should be loaded, return it
            if (cachedPdfJsModule) return cachedPdfJsModule;
            else throw new Error("PDF.js failed to load in background.");
        }

        isPdfJsLoading = true;

        try {
            // 3. Dynamically import the v5 .mjs file from CDN
            const pdfjsModule = await import('https://cdnjs.cloudflare.com/ajax/libs/pdf.js/5.4.149/pdf.min.mjs');

            // 4. Crucial for v5: Tell PDF.js where to find the worker script on the CDN
            pdfjsModule.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/5.4.149/pdf.worker.min.mjs';

            // 5. Cache it for future use so we don't download it again
            cachedPdfJsModule = pdfjsModule;

            return cachedPdfJsModule;
        } catch (error) {
            console.error("Failed to load PDF.js v5 module:", error);
            throw new Error(t('NetworkError') + ": PDF.js v5 failed to load.");
        } finally {
            // 6. Always release the loading lock when done (success or error)
            isPdfJsLoading = false;
        }
    }

    async function extractTextFromImage(file) {
        try {
            statusBar.innerText = t('TryingOCR');

            const module = await import('https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.esm.min.js');
            const Tesseract = module.default || window.Tesseract;

            if (Tesseract) {
                const langList =
                    'afr+amh+ara+asm+aze+aze_cyrl+bel+ben+bod+bos+bre+bul+cat+ceb+ces+' +
                    'chi_sim+chi_tra+chr+cos+cym+dan+deu+dzo+ell+eng+enm+' +
                    'epo+est+eus+fao+fas+fil+fin+fra+frk+frm+fry+gla+gle+glg+' +
                    'grc+guj+hat+heb+hin+hrv+hun+hye+iku+ind+isl+ita+ita_old+jav+jpn+' +
                    'kan+kat+kat_old+kaz+khm+kir+kmr+kor+kor_vert+lao+lat+lav+lit+' +
                    'ltz+mal+mar+mkd+mlt+mon+mri+msa+mya+nep+nld+nor+oci+ori+osd+' +
                    'pan+pol+por+pus+que+ron+rus+san+sin+slk+slv+snd+spa+spa_old+' +
                    'sqi+srp+srp_latn+sun+swa+swe+syr+tam+tat+tel+tgk+tha+tir+ton+' +
                    'tur+uig+ukr+urd+uzb+uzb_cyrl+vie+yid+yor';

                const { data: { text } } = await Tesseract.recognize(
                    file,
                    langList,
                    {
                        logger: m => {
                            if (m.status === 'recognizing text') {
                                statusBar.innerText = `${t('OcrProgress')}: ${Math.round(m.progress * 100)}%`;
                            }
                        }
                    }
                );

                if (text && text.trim().length > 2) return text;
            }
        } catch (e) {
            console.warn("Tesseract failed. Trying Fallback...", e);
        }

        console.warn("Falling back to Transformers.js...");
        statusBar.innerText = t('SwitchingAIModel');

        try {
            if (!cachedTransformers) {
                const module = await import('https://cdn.jsdelivr.net/npm/@xenova/transformers@2.17.2');
                cachedTransformers = module;
            }

            const pipeline = cachedTransformers.pipeline;
            cachedTransformers.env.useBrowserCache = false;
            cachedTransformers.env.allowLocalModels = false;
            cachedTransformers.env.backends.onnx.wasm.numThreads = 1;

            const ocr = await pipeline('image-to-text', 'Xenova/trocr-base-multilingual-cased', {
                quantized: true
            });

            const imageUrl = URL.createObjectURL(file);
            const result = await ocr(imageUrl);

            return result[0].generated_text;

        } catch (e) {
            console.error("All OCR Failed:", e);
            throw new Error(t('OcrFailed'));
        }
    }

    async function extractTextFromXML(file) {
        const text = await file.text();
        const parser = new DOMParser();
        const xmlDoc = parser.parseFromString(text, "text/xml");

        // Convert XML to JSON for better AI understanding
        const jsonObject = xmlToJson(xmlDoc.documentElement);
        return JSON.stringify(jsonObject, null, 2);
    }

    //Extractors for Office/ODF Files ---
    async function extractTextFromWord(file) {
        statusBar.innerText = t('ReadingWord');
        try {
            const mammoth = await getMammothLib();
            statusBar.innerText = t('ReadingWord');
            const arrayBuffer = await file.arrayBuffer();
            const result = await mammoth.extractRawText({ arrayBuffer: arrayBuffer });
            return result.value;
        } catch (e) {
            throw new Error(t('Error') + ": " + e.message);
        }
    }

    async function extractTextFromExcel(file) {
        statusBar.innerText = t('ReadingExcel');
        try {
            const XLSX = await getXlsxLib();
            statusBar.innerText = t('ReadingExcel');
            const arrayBuffer = await file.arrayBuffer();
            const workbook = XLSX.read(arrayBuffer, { type: 'array' });

            // Grab the first sheet name
            const firstSheetName = workbook.SheetNames[0];

            // Convert sheet to CSV (good format for AI to understand data)
            const csvData = XLSX.utils.sheet_to_csv(workbook.Sheets[firstSheetName], { header: 1 });

            return `--- Excel Sheet: ${firstSheetName} ---\n${csvData}`;
        } catch (e) {
            throw new Error(t('Error') + ": " + e.message);
        }
    }

    async function extractTextFromOdf(file) {
        statusBar.innerText = t('ReadingOdf');
        try {
            const JSZip = await getJszipLib();
            statusBar.innerText = t('ReadingOdf');
            const zip = await JSZip.loadAsync(file);

            // For ODT, the text is usually in 'content.xml'. For ODS, also 'content.xml'
            let contentXml = null;
            if (zip.file("content.xml")) {
                contentXml = await zip.file("content.xml").async("string");
            } else {
                throw new Error("Invalid ODF file (content.xml missing).");
            }

            const textContent = contentXml.replace(/<[^>]+>/g, '\n')
                .replace(/\s+/g, ' ')
                .replace(/&nbsp;/g, ' ')
                .replace(/&lt;/g, '<')
                .replace(/&gt;/g, '>')
                .replace(/&amp;/g, '&')
                .trim();
            return `--- ODF Content ---\n${textContent}`;
        } catch (e) {
            throw new Error(t('Error') + ": " + e.message);
        }
    }

    function fileToBase64(file) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.readAsDataURL(file);
            reader.onload = () => resolve(reader.result);
            reader.onerror = error => reject(error);
        });
    }

    function xmlToJson(xml) {
        let obj = {};
        if (xml.nodeType === 1) {
            if (xml.attributes.length > 0) {
                obj["@attributes"] = {};
                for (let j = 0; j < xml.attributes.length; j++) {
                    const attribute = xml.attributes.item(j);
                    obj["@attributes"][attribute.nodeName] = attribute.nodeValue;
                }
            }
        } else if (xml.nodeType === 3) {
            obj = xml.nodeValue.trim();
        }
        if (xml.hasChildNodes()) {
            for (let i = 0; i < xml.childNodes.length; i++) {
                const item = xml.childNodes.item(i);
                const nodeName = item.nodeName;
                if (item.nodeType === 3 && !item.nodeValue.trim()) continue;
                if (typeof (obj[nodeName]) === "undefined") {
                    obj[nodeName] = xmlToJson(item);
                } else {
                    if (typeof (obj[nodeName].push) === "undefined") {
                        const old = obj[nodeName];
                        obj[nodeName] = [old];
                    }
                    obj[nodeName].push(xmlToJson(item));
                }
            }
        }
        return obj;
    }

    async function processLocalFile(file) {
        // PDF files
        if (file.type === 'application/pdf') {
            return await extractTextFromPDF(file);
        }

        // Word documents (.docx)
        if (file.type === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            || file.name.endsWith('.docx')) {
            return await extractTextFromWord(file);
        }

        // Excel files (.xlsx)
        if (file.type === 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            || file.name.endsWith('.xlsx')) {
            return await extractTextFromExcel(file);
        }

        // Legacy Excel files (.xls)
        if (file.type === 'application/vnd.ms-excel'
            || file.name.endsWith('.xls')) {
            return await extractTextFromExcel(file);
        }

        // OpenDocument Text (.odt)
        if (file.type === 'application/vnd.oasis.opendocument.text'
            || file.name.endsWith('.odt')) {
            return await extractTextFromOdf(file);
        }

        // OpenDocument Spreadsheet (.ods)
        if (file.type === 'application/vnd.oasis.opendocument.spreadsheet'
            || file.name.endsWith('.ods')) {
            return await extractTextFromOdf(file);
        }

        // Image files
        if (file.type.startsWith('image/')
            || file.name.match(/\.(png|jpg|jpeg|gif|bmp|webp)$/i)) {
            return await extractTextFromImage(file);
        }

        // XML files
        if (file.type === 'text/xml'
            || file.type === 'application/xml'
            || file.name.endsWith('.xml')) {
            return await extractTextFromXML(file);
        }

        // Plain text files
        if (file.type === 'text/plain') {
            return await file.text();
        }

        // Unsupported file type
        if (typeof dolibarr !== 'undefined' && typeof dolibarr.langs !== 'undefined') {
            throw new Error(dolibarr.langs.trans('UnsupportedFileType'));
        }
        throw new Error('UnsupportedFileType');
    }

    async function extractTextFromPDF(file) {
        statusBar.innerText = t('ReadingPdf');
        try {
            const { getDocument } = await getPdfLib();
            const arrayBuffer = await file.arrayBuffer();
            // cMapUrl + standardFontDataUrl are REQUIRED for PDFs that ship with
            // custom CID fonts referencing ToUnicode CMaps (extremely common with
            // generators like TCPDF, wkhtmltopdf, iText, etc.). Without these,
            // getTextContent() returns raw glyph codes that look like garbled
            // base64. jsdelivr is used here because cdnjs (used elsewhere for the
            // pdf.js bundle) does not distribute the cmaps/ and standard_fonts/
            // resource directories.
            const PDFJS_RES = 'https://cdn.jsdelivr.net/npm/pdfjs-dist@5.4.149';
            const pdf = await getDocument({
                data: arrayBuffer,
                cMapUrl: PDFJS_RES + '/cmaps/',
                cMapPacked: true,
                standardFontDataUrl: PDFJS_RES + '/standard_fonts/',
            }).promise;

            let fullText = "";
            const totalPages = pdf.numPages; // Get total pages

            for (let i = 1; i <= totalPages; i++) {
                statusBar.innerText = `${t('ReadingPdf')} (${i}/${totalPages})...`;
                const page = await pdf.getPage(i);
                const textContent = await page.getTextContent();
                const pageText = textContent.items.map(item => item.str).join(' ');
                fullText += `\n--- Page ${i} ---\n${pageText}\n`;

                // Prevent browser freeze on huge documents
                if (i % 10 === 0) await new Promise(r => setTimeout(r, 0));
            }
            return fullText;
        } catch (e) {
            throw new Error(t('PdfError') + ": " + e.message);
        }
    }

    async function processCloudFile(file) {
        // fileToBase64 resolves the FULL data URL ("data:image/png;base64,AAAA…").
        // Strip the prefix: the server-side extractor (parse_intent.php) expects
        // pure base64 after '::' and hands it to the LLM as a native multimodal
        // part — with the prefix left in, the marker is never recognized.
        const base64 = String(await fileToBase64(file)).split(',').pop();
        return `__FILE_ATTACHMENT__[${file.type}]::${base64}`;
    }

    // =========================================================================
    // ENGINE INITIALIZATION (VOICE)
    // =========================================================================

    function initEngine(mode) {
        statusBar.innerText = "";
        if (mode === 'cloud') {
            if ('SpeechRecognition' in window || 'webkitSpeechRecognition' in window) {
                initcloud();
                statusBar.innerText = t('CloudSpeechReady');
            } else {
                alert(t('BrowserNotSupported'));
                engineSelect.value = 'text';
                refreshSelect2Combo(engineSelect);
                updateInterfaceMode('text');
            }
        } else if (mode === 'whisper') {
            initWhisper();
        }
    }

    // -------------------------------------------------------------------------
    // 1. CLOUD ENGINE (Google Web Speech API)
    // -------------------------------------------------------------------------

    function initcloud() {
        if (cloudRecognition) return;
        try {
            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            cloudRecognition = new SpeechRecognition();
            cloudRecognition.continuous = false;
            cloudRecognition.interimResults = false;
            cloudRecognition.lang = navigator.language || 'en-US';

            cloudRecognition.onstart = () => { setMicState('listening'); statusBar.innerText = t('Listening'); };
            // When recognition ends without a result (user stopped, or silence
            // timeout), return to idle instead of looping on stopRecording (which
            // left the button stuck in the orange 'processing' state).
            cloudRecognition.onend = () => { if (engineSelect.value === 'cloud' && isRecording) { resetUI(); } };
            cloudRecognition.onerror = (event) => {
                console.error('Speech recognition error:', event.error);
                let errorMsg = t('Error') + ": " + event.error;
                if (event.error === 'network') {
                    errorMsg = t('ConnectionBlocked');
                    appendMsg('system', `<strong>${t('ConnectionBlocked')}:</strong> ${t('CloudVoiceRequiresSecureContext')}`);
                }
                statusBar.innerText = errorMsg;
                setMicState('idle');
                isRecording = false;
            };
            cloudRecognition.onresult = (event) => {
                const transcript = event.results[0][0].transcript;
                input.value = transcript;
                resetUI(); // clears isRecording/isProcessing so onend won't re-fire a reset
                statusBar.innerText = t('Transcribed');
                handleQuery();
            };
        } catch (e) {
            console.error("Cloud Init Error", e);
            statusBar.innerText = t('Error');
        }
    }

    /**
     * Initializes the Web Worker for client-side AI processing
     */
    function initWhisper() {
        if (whisperWorker) return;
        statusBar.innerText = t('ModelLoading');
        try {
            const workerCode = `
                import { pipeline, env } from 'https://cdn.jsdelivr.net/npm/@xenova/transformers@2.17.2';
                env.allowLocalModels = false;
                env.useBrowserCache = true;

                class SpeechPipeline {
                    static task = 'automatic-speech-recognition';
                    static model = 'Xenova/whisper-small.en';
                    static instance = null;

                    static async getInstance(progress_callback = null) {
                        if (this.instance === null) {
                            this.instance = await pipeline(this.task, this.model, {
                                quantized: true,
                                progress_callback
                            });
                        }
                        return this.instance;
                    }
                }

                self.addEventListener('message', async (event) => {
                    const message = event.data;
                    if (message.type === 'load') {
                        try {
                            self.postMessage({ type: 'status', data: 'Loading Model...' });
                            await SpeechPipeline.getInstance(x => { self.postMessage({ type: 'download', data: x }); });
                            self.postMessage({ type: 'ready' });
                        } catch (error) { self.postMessage({ type: 'error', data: error.message }); }
                    }
                    else if (message.type === 'generate') {
                        try {
                            if (!message.audio) throw new Error('No audio data');
                            self.postMessage({ type: 'status', data: 'Transcribing...' });
                            const transcriber = await SpeechPipeline.getInstance();
                            const output = await transcriber(message.audio, {
                                chunk_length_s: 30, stride_length_s: 5,
                                language: 'en', task: 'transcribe',
                            });
                            let textResult = (typeof output === 'string') ? output : (output && output.text ? output.text : "");
                            self.postMessage({ type: 'result', data: textResult });
                        } catch (error) { self.postMessage({ type: 'error', data: error.message }); }
                    }
                });
            `;
            const blob = new Blob([workerCode], { type: 'application/javascript' });
            whisperWorker = new Worker(URL.createObjectURL(blob), { type: 'module' });

            whisperWorker.onmessage = (e) => {
                const msg = e.data;
                switch (msg.type) {
                    case 'download':
                        if (msg.data.status === 'progress') {
                            statusBar.innerText = `${t('DownloadingModel')}: ${Math.round(msg.data.progress)}%`;
                        }
                        break;
                    case 'ready':
                        whisperReady = true;
                        statusBar.innerText = t('WhisperReady');
                        break;
                    case 'result':
                        let rawText = (typeof msg.data === 'string') ? msg.data : "";
                        let text = rawText.trim();
                        if (text.includes('Subtitle') || text.includes('Amara') || text.length < 2) text = '';
                        if (isConfirmationListening) { processConfirmationCommand(text); }
                        else {
                            setMicState('idle'); isProcessing = false;
                            if (text) { input.value = text; statusBar.innerText = t('Transcribed'); handleQuery(); }
                            else { statusBar.innerText = t('NoSpeech'); }
                        }
                        break;
                    case 'error':
                        console.error(msg.data);
                        statusBar.innerText = t('Error') + ": " + msg.data;
                        resetUI();
                        break;
                    case 'status':
                        statusBar.innerText = msg.data;
                        break;
                }
            };
            whisperWorker.postMessage({ type: 'load' });
        } catch (error) {
            console.error('Error creating worker:', error);
            statusBar.innerText = t('WorkerInitFailed');
            setMicState('idle');
        }
    }

    // =========================================================================
    // AUDIO RECORDING LOGIC
    // =========================================================================

    function startRecording() {
        if (isRecording) return;
        isRecording = true;
        if (engineSelect.value === 'cloud') {
            cloudRecognition.start();
            setMicState('listening');
        } else if (engineSelect.value === 'whisper') {
            if (!whisperReady) { statusBar.innerText = t('ModelLoading'); isRecording = false; return; }
            startWhisperRecording();
        }
    }

    async function startWhisperRecording() {
        try {
            mediaStream = await navigator.mediaDevices.getUserMedia({ audio: { echoCancellation: true, noiseSuppression: true, autoGainControl: true } });
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            audioContext = new AudioContext();
            if (audioContext.state === 'suspended') await audioContext.resume();
            recordedSampleRate = audioContext.sampleRate;
            const source = audioContext.createMediaStreamSource(mediaStream);
            const bufferSize = 4096;
            audioProcessor = audioContext.createScriptProcessor(bufferSize, 1, 1);
            audioChunks = [];
            audioProcessor.onaudioprocess = (e) => {
                if (!isRecording) return;
                audioChunks.push(new Float32Array(e.inputBuffer.getChannelData(0)));
            };
            source.connect(audioProcessor);
            audioProcessor.connect(audioContext.destination);
            setMicState('listening');
            statusBar.innerText = t('Listening');
        } catch (err) {
            console.error('Microphone error:', err);
            statusBar.innerText = t('MicError') + ": " + err.message;
            isRecording = false;
            setMicState('idle');
        }
    }

    function stopRecording() {
        if (engineSelect.value === 'cloud') {
            cloudRecognition.stop();
            setMicState('processing');
            isProcessing = true;
        } else { stopWhisperRecording(); }
    }

    function stopWhisperRecording() {
        if (!isRecording) return;
        isRecording = false;
        setMicState('processing');
        isProcessing = true;
        statusBar.innerText = t('ProcessingAudio');
        setTimeout(() => { if (isProcessing) { console.warn("Watchdog: Processing timed out."); statusBar.innerText = t('Timeout'); resetUI(); } }, 12000);
        if (mediaStream) mediaStream.getTracks().forEach(track => track.stop());
        if (audioProcessor) { audioProcessor.disconnect(); audioProcessor = null; }
        processAudioData();
        if (audioContext) { audioContext.close().catch(e => console.warn(e)); audioContext = null; }
    }

    function processAudioData() {
        if (!audioChunks || audioChunks.length === 0) { statusBar.innerText = t('NoSpeech'); resetUI(); return; }
        const totalLength = audioChunks.reduce((acc, chunk) => acc + chunk.length, 0);
        const rawAudio = new Float32Array(totalLength);
        let offset = 0;
        for (const chunk of audioChunks) { rawAudio.set(chunk, offset); offset += chunk.length; }
        let sum = 0; for (let i = 0; i < rawAudio.length; i++) sum += rawAudio[i] * rawAudio[i];
        const rms = Math.sqrt(sum / rawAudio.length);
        if (rms < 0.002) {
            console.warn("Audio too quiet:", rms);
            if (isConfirmationListening) { showVoiceFeedback(t('VoiceQuiet')); isConfirmationListening = false; resetUI(); }
            else { statusBar.innerText = t('MicTooQuiet'); resetUI(); }
            return;
        }
        const targetRate = 16000; const sourceRate = recordedSampleRate || 48000;
        let finalAudio = rawAudio;
        if (sourceRate !== targetRate) { console.log(`Resampling ${sourceRate}Hz -> ${targetRate}Hz`); finalAudio = downsampleBuffer(rawAudio, sourceRate, targetRate); }
        whisperWorker.postMessage({ type: 'generate', audio: finalAudio });
    }

    function downsampleBuffer(buffer, sampleRate, outSampleRate) {
        if (outSampleRate >= sampleRate) return buffer;
        const ratio = sampleRate / outSampleRate;
        const newLength = Math.round(buffer.length / ratio);
        const result = new Float32Array(newLength);
        for (let i = 0; i < newLength; i++) { const offset = Math.round(i * ratio); if (offset < buffer.length) result[i] = buffer[offset]; }
        return result;
    }

    function cancelProcessing() { resetUI(); if (whisperWorker) whisperWorker.terminate(); whisperWorker = null; initWhisper(); statusBar.innerText = t('Cancelled'); }
    function resetUI() { setMicState('idle'); isRecording = false; isProcessing = false; }

    function setMicState(state) {
        micBtn.classList.remove('listening', 'processing', 'cancelling');
        micBtn.disabled = false;
        if (state === 'listening') { micBtn.classList.add('listening'); micBtn.innerHTML = '<span class="fa fa-microphone"></span>'; }
        else if (state === 'processing') { micBtn.classList.add('processing'); micBtn.innerHTML = '<span class="fa fa-circle-notch fa-spin"></span>'; }
        else { micBtn.innerHTML = '<span class="fa fa-microphone"></span>'; micBtn.title = "Start Recording"; }
    }

    // =========================================================================
    // CHAT UI RENDERING
    // =========================================================================

    // Build the contextual action buttons row (e.g. "Download PDF", "Open record")
    function buildActions(actions) {
        const actionsDiv = document.createElement('div');
        actionsDiv.className = 'msg-actions';
        actions.forEach(action => {
            const btn = document.createElement('button');
            btn.className = `msg-action-btn ${action.class || ''}`;
            btn.innerHTML = action.icon ? `<span class="fa ${action.icon}"></span> ${action.text}` : action.text;
            btn.onclick = action.onclick;
            actionsDiv.appendChild(btn);
        });
        return actionsDiv;
    }

    // Build the avatar element shown next to user/bot messages
    function buildAvatar(type) {
        const avatar = document.createElement('div');
        avatar.className = `msg-avatar ${type}`;
        avatar.innerHTML = (type === 'bot')
            ? '<span class="fa fa-robot"></span>'
            : (config.userInitial || '<span class="fa fa-user"></span>');
        return avatar;
    }

    // A server failure (HTTP 5xx) may be transient: offer a button that runs the same request again.
    function retryActionsFor(result, onRetry) {
        if (!result || !result.error || !(parseInt(result.http_status, 10) >= 500)) return null;
        return [{ text: t('AIRetry'), icon: 'fa-redo', onclick: (ev) => { ev.currentTarget.closest('.msg-actions').remove(); onRetry(); } }];
    }

    function appendMsg(type, html, actions = null, rawText = null, opts = {}) {
        const div = document.createElement('div');
        div.className = `msg ${type}`;
        // A provider failure ("service overloaded, retry") is noise as context:
        // such a bubble starts, and stays, out of the window (still pinnable).
        if (opts && opts.error) div.dataset.aiError = '1';

        if (type === 'user' || type === 'bot') {
            // Row layout: avatar + bubble (CSS reverses the row for the user)
            const bubble = document.createElement('div');
            bubble.className = 'msg-bubble';
            bubble.innerHTML = html;
            // A table does not size its bubble (it scrolls inside): such a bubble takes the whole row
            if (bubble.querySelector('.chat-table-wrap')) bubble.classList.add('msg-bubble-wide');
            if (actions) bubble.appendChild(buildActions(actions));
            // Context pin: the last AUTO_CONTEXT exchanges follow the model by
            // default (sliding window), the user pins older ones explicitly or
            // excludes recent ones - the token cost stays visible in the bar.
            if (rawText) {
                div.dataset.aiRaw = String(rawText).slice(0, 4000);
                div.dataset.aiRole = (type === 'bot') ? 'assistant' : 'user';
                if (contextAllMode && !restoringHistory && !div.dataset.aiError) div.dataset.ctx = 'on';
                const pin = document.createElement('button');
                pin.type = 'button';
                pin.className = 'ctx-pin';
                pin.title = t('AIContextPinOff');
                pin.innerHTML = '<span class="fas fa-thumbtack"></span>';
                pin.onclick = (ev) => {
                    ev.stopPropagation();
                    toggleContextPin(div);
                    persistPinState(div);
                };
                bubble.appendChild(pin);
                if (!restoringHistory) {
                    persistMessage(div, type, rawText, html);
                }
            }
            div.appendChild(buildAvatar(type));
            div.appendChild(bubble);
        } else {
            // system / error / clarification / confirmation: flat, centered, no avatar
            div.innerHTML = html;
            if (actions) div.appendChild(buildActions(actions));
        }

        chat.appendChild(div);
        chat.scrollTop = chat.scrollHeight;
        if (div.dataset.aiRaw) refreshContext();
        else saveChatHistory();
    }

    // Keep the user/bot bubbles in sessionStorage so the conversation survives a
    // page change (popover -> full page, or any navigation) in the same tab.
    // Action buttons and pending confirmations are not kept: they cannot be replayed.
    function saveChatHistory() {
        if (!HISTORY_KEY || restoringHistory) return;
        const msgs = Array.from(chat.querySelectorAll('.msg.user, .msg.bot'))
            .filter((m) => !m.classList.contains('typing-indicator'))
            .slice(-HISTORY_MAX_MSGS)
            .map((m) => {
                const bubble = m.querySelector('.msg-bubble');
                if (!bubble) return null;
                const clone = bubble.cloneNode(true);
                // Pins are rebuilt on restore; charts are canvases that cannot be serialized.
                clone.querySelectorAll('.ctx-pin, .msg-actions, .chat-chart-wrap').forEach((n) => n.remove());
                return {
                    type: m.classList.contains('user') ? 'user' : 'bot',
                    html: clone.innerHTML,
                    raw: m.dataset.aiRaw || '',
                    ctx: m.dataset.ctx || '',
                    error: m.dataset.aiError ? 1 : 0
                };
            })
            .filter(Boolean);
        try {
            if (msgs.length) sessionStorage.setItem(HISTORY_KEY, JSON.stringify(msgs));
            else sessionStorage.removeItem(HISTORY_KEY);
            if (contextAllMode) sessionStorage.setItem(HISTORY_KEY + '_all', '1');
            else sessionStorage.removeItem(HISTORY_KEY + '_all');
        } catch (e) { /* private mode or quota exceeded: the chat just won't survive a page change */ }
    }

    function restoreChatHistory() {
        if (!HISTORY_KEY) return;
        let msgs = [];
        try { msgs = JSON.parse(sessionStorage.getItem(HISTORY_KEY) || '[]'); } catch (e) { msgs = []; }
        if (!Array.isArray(msgs)) return;
        // A question left without answer (page changed while waiting) is dropped.
        while (msgs.length && msgs[msgs.length - 1].type === 'user') msgs.pop();
        if (!msgs.length) return;
        try { contextAllMode = (sessionStorage.getItem(HISTORY_KEY + '_all') === '1'); } catch (e) { contextAllMode = false; }
        if (welcome) welcome.style.display = 'none';
        restoringHistory = true;
        msgs.forEach((m) => {
            if (!m || (m.type !== 'user' && m.type !== 'bot')) return;
            appendMsg(m.type, String(m.html || ''), null, m.raw || null, { error: !!m.error });
            const div = chat.lastElementChild;
            if (div && m.ctx) div.dataset.ctx = m.ctx;
        });
        restoringHistory = false;
        refreshContext();
    }

    // Animated three-dot "typing" bubble (avatar + dots) shown while waiting
    function appendTyping() {
        const div = document.createElement('div');
        div.className = 'msg bot typing-indicator';
        div.appendChild(buildAvatar('bot'));
        const bubble = document.createElement('div');
        bubble.className = 'msg-bubble';
        bubble.innerHTML = '<span></span><span></span><span></span>';
        div.appendChild(bubble);
        chat.appendChild(div);
        chat.scrollTop = chat.scrollHeight;
        return div;
    }

    function handleClarification(question, context) {
        clarificationContext = context;
        // No outer <strong>: the question may itself contain **bold**, which
        // renderMarkdownLite turns into <strong> — nesting produces invalid HTML.
        let html = `<div style="font-weight:600">${renderMarkdownLite(question)}</div><input type="text" id="clarification-input" placeholder="${t('TypeResponse')}" style="width:100%; margin-top:10px; padding:8px; border:1px solid #ccc; border-radius:4px;">`;
        const actions = [
            {
                text: t('Submit'), class: 'primary', icon: 'fa-check', onclick: () => {
                    const response = container.querySelector('#clarification-input').value;
                    if (response.trim()) {
                        const msg = chat.lastElementChild;
                        if (msg && msg.classList.contains('clarification')) msg.remove();
                        input.value = `${context}. ${response}`;
                        handleQuery();
                    }
                }
            },
            {
                text: t('Cancel'), icon: 'fa-times', onclick: () => {
                    const msg = chat.lastElementChild;
                    if (msg && msg.classList.contains('clarification')) msg.remove();
                    clarificationContext = null;
                }
            }
        ];
        appendMsg('clarification', html, actions);
        const clarInput = container.querySelector('#clarification-input');
        if (clarInput) clarInput.focus();
    }

    // Several third parties fit a name the user wrote, and the privacy guard
    // keeps names away from the provider: the server asked before sending
    // anything. The same request goes again with the choice.
    function handleThirdpartyChoice(question, choice, onPick) {
        const pick = (id) => {
            const msg = chat.lastElementChild;
            if (msg && msg.classList.contains('clarification')) msg.remove();
            if (onPick) {
                onPick(choice.phrase, id);   // a palette command runs again with the choice
                return;
            }
            thirdpartyChoices[choice.phrase] = id;
            if (lastSent) handleQuery(lastSent);
        };
        const actions = (choice.options || []).map((o) => ({ text: escapeHtml(o.label), icon: 'fa-building', onclick: () => pick(o.id) }));
        actions.push({ text: t('AINoneOfThese'), icon: 'fa-times', onclick: () => pick(0) });
        appendMsg('clarification', `<div style="font-weight:600">${escapeHtml(question)}</div>`, actions);
    }

    function handleResponse(message, isError = false) {
        if (!message) message = t('EmptyAIResponse');
        appendMsg('bot', renderMarkdownLite(message), null, message, { error: isError });
    }

    // =========================================================================
    // TOOL PALETTE ("/")
    // =========================================================================
    // "/" at the start of the input lists the tools this user may run (the same
    // list the model gets, from list_tools.php), filtered as the user types.
    // Picking one opens a form built from the tool's own schema, filled in from
    // the page being viewed. Run calls the tool directly: no AI provider is
    // involved. A write still stops at the confirmation, as in the chat.

    // Tool family (api_<family>_...) and documents module part of each page element.
    const PALETTE_FAMILY = {
        facture: 'invoices', societe: 'thirdparties', product: 'products', propal: 'proposals', commande: 'orders',
        project: 'projects', contrat: 'contracts', ticket: 'tickets', invoice_supplier: 'supplier_invoices',
        order_supplier: 'supplier_orders', supplier_proposal: 'supplier_proposals', shipping: 'shipments',
        reception: 'receptions', fichinter: 'interventions', expensereport: 'expensereports', stock: 'warehouses',
        categorie: 'categories', action: 'agendaevents'
    };
    const PALETTE_MODULEPART = {
        facture: 'invoice', invoice_supplier: 'supplier_invoice', commande: 'order', order_supplier: 'supplier_order',
        propal: 'propal', supplier_proposal: 'supplier_proposal', shipping: 'shipment', societe: 'societe',
        product: 'product', contrat: 'contract', fichinter: 'ficheinter', project: 'project', ticket: 'ticket'
    };
    // A module dashboard (index page, see actions_ai printCommonFooter): the
    // API families of that module.
    const PALETTE_DASHBOARD = {
        'thirdparties': ['thirdparties', 'contacts'], 'customer invoices': ['invoices'], 'supplier invoices': ['supplier_invoices'],
        'sales orders': ['orders'], 'supplier orders': ['supplier_orders'], 'commercial proposals': ['proposals'],
        'supplier proposals': ['supplier_proposals'], 'products and services': ['products'],
        'stock and warehouses': ['warehouses', 'stockmovements'], 'projects': ['projects', 'tasks'], 'contracts': ['contracts'],
        'interventions': ['interventions'], 'tickets': ['tickets'], 'shipments': ['shipments'], 'receptions': ['receptions'],
        'members': ['members', 'subscriptions'], 'donations': ['donations'], 'expense reports': ['expensereports'],
        'agenda': ['agendaevents'], 'manufacturing': ['mos', 'boms']
    };
    let paletteTools = null;

    // "/tools" stays the debug command of the chat; any other "/..." line is the palette.
    // Commands the chat answers by itself (sendMessage): never caught by the palette.
    const CHAT_LOCAL_COMMANDS = ['/tools', '/new', '/clear', '/status', '/help'];

    // Commands of the chat shown in the palette (run by handleQuery()), with their label key.
    const PALETTE_LOCALS = [['help', 'AIHelpHelp'], ['new', 'AIHelpClear'], ['status', 'AIHelpStatus'], ['tools', 'AIHelpTools']];
    // The whole name of one of them typed: the palette stays open on it until Enter.
    function paletteLocalTyped(v) {
        return typeof v === 'string' && PALETTE_LOCALS.some((l) => v.trim().toLowerCase() === '/' + l[0]);
    }

    function paletteWanted(v) {
        if (!config.palette) return false;   // switched off in the setup: everything goes to the AI
        return typeof v === 'string' && v.indexOf('/') === 0 && v.indexOf('\n') === -1
            && CHAT_LOCAL_COMMANDS.indexOf(v.trim().toLowerCase()) === -1;
    }

    // The page being viewed, as the page footer emitted it (window.aiPageContext):
    // a card gives its record; a list gives its kind of record, and the row the
    // user ticked when exactly one is ticked (read now: ticks change after load).
    function paletteContext() {
        const c = window.aiPageContext;
        if (c && !c.element && PALETTE_DASHBOARD[c.dashboard]) {
            return { element: '', id: 0, ref: '', socid: 0, family: '', families: PALETTE_DASHBOARD[c.dashboard], dashboard: true };
        }
        if (!c || !c.element) return null;
        let id = parseInt(c.id, 10) || 0;
        let ref = String(c.ref || '');
        let socid = (c.element === 'societe') ? id : (parseInt(c.socid, 10) || 0);
        if (c.list) {
            const ticked = Array.from(document.querySelectorAll('.checkforselect:checked'))
                .map((cb) => parseInt(cb.value, 10)).filter((n) => n > 0);
            id = (ticked.length === 1) ? ticked[0] : 0;
            ref = '';
            socid = (c.element === 'societe') ? id : 0;   // a ticked invoice's customer is not on the page data
        }
        const family = PALETTE_FAMILY[c.element] || '';
        return { element: String(c.element), id: id, ref: ref, socid: socid, family: family, families: family ? [family] : [], dashboard: false };
    }

    // 0 = a tool of this card (or of this dashboard's module), 1 = its files,
    // 2 = takes this card's third party, 3 = anything else.
    function paletteRank(tool, ctx) {
        if (!ctx) return 3;
        if (ctx.families.some((f) => tool.name.indexOf('api_' + f + '_') === 0)) return 0;
        if (tool.name === 'api_documents_objects_list' && ctx.id && PALETTE_MODULEPART[ctx.element]) return 1;
        const p = tool.properties || {};
        if (ctx.socid && (p.socid || p.thirdparty_ids)) return 2;
        return 3;
    }

    // How well a tool fits the words typed: 0 = its plain name or API name starts
    // with them, 1 = they are inside its plain name or API name, 2 = only in its
    // description, 3 = not at all. Every word typed must be found.
    function paletteMatch(tool, f) {
        const title = (tool.title || '').toLowerCase();
        const name = tool.name.toLowerCase();
        const short = name.replace(/^api_/, '');
        const desc = (tool.description || '').toLowerCase();
        let worst = 0;
        for (const w of f.split(/\s+/).filter(Boolean)) {
            let m = 3;
            if (title.indexOf(w) === 0 || short.indexOf(w) === 0 || name.indexOf(w) === 0) m = 0;
            else if (title.indexOf(w) !== -1 || name.indexOf(w) !== -1) m = 1;
            else if (desc.indexOf(w) !== -1) m = 2;
            worst = Math.max(worst, m);
        }
        return worst;
    }

    // Reading one record first, then other reads, writes last.
    // On a dashboard there is no record yet: the module's main family first, and
    // in each family creating a record, then the list of them, then the other
    // lists and reads, the other writes last.
    function paletteKind(tool, ctx) {
        if (ctx && ctx.dashboard) {
            const fi = ctx.families.findIndex((f) => tool.name.indexOf('api_' + f + '_') === 0);
            if (fi === -1) return 100;
            const fam = ctx.families[fi];
            let k = 3;
            if (tool.name === 'api_' + fam + '_create') k = 0;
            else if (tool.name === 'api_' + fam + '_list') k = 1;
            else if (/_list$/.test(tool.name)) k = 2;
            else if (/_(create|update|delete|add|remove|validate|pay|send)(_|$)/.test(tool.name)) k = 4;
            return fi * 10 + k;
        }
        if (/_(create|update|delete|add|remove|validate|pay|send)(_|$)/.test(tool.name)) return 2;
        return /_get$/.test(tool.name) ? 0 : 1;
    }

    let paletteCommands = null;

    // The commands this user may run (command.php), then the raw API tools only
    // when the administrator shows them (AI_PALETTE_API_TOOLS: testing, advanced use).
    let paletteLoading = null;
    function paletteLoad() {
        // One load for every caller: typing, Enter and Send may all ask at once.
        if (!paletteLoading) {
            paletteLoading = (async () => {
                let cmds = [];
                let tools = [];
                try {
                    const r = await fetch(epUrl('command.php') + '&action=list', { credentials: 'same-origin' });
                    const j = await aiJson(r);
                    cmds = (j && Array.isArray(j.commands)) ? j.commands : [];
                } catch (e) { /* no commands: the list stays empty */ }
                if (config.paletteApiTools) {
                    try {
                        const r = await fetch(epUrl('list_tools.php'), { credentials: 'same-origin' });
                        const j = await aiJson(r);
                        tools = (j && Array.isArray(j.tools)) ? j.tools : [];
                    } catch (e) { /* idem */ }
                }
                paletteCommands = cmds;
                paletteTools = tools;
                return tools;
            })();
        }
        return paletteLoading;
    }

    // "/invoice AI SOLUTION 4 popularpt 10" -> { cmd, text } when "invoice" is a command.
    function paletteParseCommand(v) {
        const m = /^\/(\S+)(?:\s+([\s\S]*))?$/.exec(String(v || '').trim());
        if (!m || !paletteCommands) return null;
        const cmd = paletteCommands.find((c) => c.name === m[1].toLowerCase());
        return cmd ? { cmd: cmd, text: (m[2] || '').trim(), typedSpace: /\s/.test(String(v).replace(/^\s+/, '')) } : null;
    }

    // What the page says, with the rows ticked right now.
    function paletteCommandContext() {
        const c = window.aiPageContext || {};
        const out = { element: c.element || '', id: parseInt(c.id, 10) || 0, socid: parseInt(c.socid, 10) || 0 };
        if (c.list) {
            out.list = 1;
            out.ids = Array.isArray(c.ids) ? c.ids : [];
            out.selected = Array.from(document.querySelectorAll('.checkforselect:checked')).map((cb) => parseInt(cb.value, 10)).filter((n) => n > 0);
        }
        return out;
    }

    // Run a command: no AI provider. A draft stops at the same confirmation as
    // the chat; a name that fits several third parties brings the choice card.
    async function runCommand(cmd, text, choices, productChoices) {
        paletteClose();
        input.value = '';
        autoResizeInput();
        if (welcome) welcome.style.display = 'none';
        if (!choices && !productChoices) appendMsg('user', escapeHtml('/' + cmd + (text ? ' ' + text : '')), null, '/' + cmd + (text ? ' ' + text : ''));
        appendMsg('system', `<span class="fa fa-circle-notch fa-spin"></span> ${t('ExecutingTool')} /${escapeHtml(cmd)}...`);
        const loadingMsg = chat.lastElementChild;
        input.disabled = true;
        try {
            const res = await fetch(epUrl('command.php'), {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ command: cmd, text: text, context: paletteCommandContext(), thirdparty_choices: choices || {}, product_choices: productChoices || {} })
            });
            const r = await aiJson(res);
            loadingMsg.remove();
            if (r && r.arguments && r.arguments.thirdparty_choice) {
                handleThirdpartyChoice(r.arguments.question || '', r.arguments.thirdparty_choice, (phrase, id) => {
                    if (!id) { appendMsg('system', t('ActionCancelled')); return; }
                    runCommand(cmd, text, Object.assign({}, choices || {}, { [phrase]: id }), productChoices);
                });
            } else if (r && r.arguments && r.arguments.product_choice) {
                // A product the line does not name uniquely: pick it, or keep the words as free text.
                const pc = r.arguments.product_choice;
                const again = (id) => {
                    const msg = chat.lastElementChild;
                    if (msg && msg.classList.contains('clarification')) msg.remove();
                    runCommand(cmd, text, choices || {}, Object.assign({}, productChoices || {}, { [pc.key]: id }));
                };
                const actions = (pc.options || []).map((o) => ({ text: escapeHtml(o.label), icon: 'fa-cube', onclick: () => again(o.id) }));
                actions.push({ text: escapeHtml(t('AICmdFreeText')), icon: 'fa-font', onclick: () => again(0) });
                actions.push({ text: escapeHtml(t('Cancel')), icon: 'fa-times', onclick: () => {
                    const msg = chat.lastElementChild;
                    if (msg && msg.classList.contains('clarification')) msg.remove();
                    appendMsg('system', t('ActionCancelled'));
                } });
                appendMsg('clarification', `<div style="font-weight:600">${escapeHtml(r.arguments.question || '')}</div>`, actions);
            } else if (r && r.error) {
                appendMsg('error', escapeHtml(String(r.error)));
            } else if (r && r.totals) {
                const rows = (r.totals.rows || []).map((row) => `<tr><td>${escapeHtml(row[0])}</td><td class="right"><strong>${escapeHtml(row[1])}</strong></td></tr>`).join('');
                appendMsg('bot', `<div style="font-weight:600">${escapeHtml(r.totals.title || '')}</div><table class="ai-cmd-totals">${rows}</table>`, null, (r.totals.title || '') + ': ' + (r.totals.rows || []).map((row) => row[0] + ' ' + row[1]).join(', '));
            } else if (r && r.result && r.result.resultType === 'input_required' && r.result.requestState) {
                const prompt = (r.result.inputRequests && r.result.inputRequests[0] && r.result.inputRequests[0].prompt) || '';
                const held = { tool: r.tool, arguments: Object.assign({}, r.arguments || {}, { requestState: r.result.requestState }) };
                handleConfirmation(prompt, '', { tool: 'ask_for_confirmation', arguments: { action: prompt, original_intent: held } });
            } else if (r && r.result) {
                appendMsg('bot', formatResult(r.result, false, r.tool || ''), null, contextSnippetOf(r.result, r.tool || ''));
            }
        } catch (e) {
            loadingMsg.remove();
            appendMsg('error', t('NetworkError') + ': ' + e.message);
        }
        input.disabled = false;
        input.focus();
    }

    function paletteClose() {
        container.querySelectorAll('.ai-palette').forEach((el) => el.remove());
    }

    function paletteMount(box) {
        paletteClose();
        const pill = container.querySelector('.chat-input-pill');
        if (pill && pill.parentNode) pill.parentNode.insertBefore(box, pill);
    }

    async function paletteOpen(filter) {
        const tools = await paletteLoad();
        if (!paletteWanted(input.value) && !paletteLocalTyped(input.value) && input.value !== '') return;   // the user moved on meanwhile
        const typed = paletteParseCommand(input.value);
        if (typed && typed.typedSpace) {
            // A command and its words: how to write it, and what fits the word
            // being typed (the third party, then each product). Enter runs it.
            paletteSuggest(typed);
            return;
        }
        const ctx = paletteContext();
        const f = (filter || '').toLowerCase().trim();
        // Commands of the chat itself, run by handleQuery(). Nothing typed: only them,
        // plus a line pointing to /help for the commands and tools that need no AI.
        const locals = PALETTE_LOCALS.filter((l) => !f || l[0].indexOf(f) === 0);
        const cmds = f ? (paletteCommands || []).filter((c) => c.name.indexOf(f) === 0) : [];
        // Typed: only the tools whose name starts with what is typed.
        const list = tools
            .filter((tl) => f && paletteMatch(tl, f) === 0)
            .sort((a, b) => {
                const ra = paletteRank(a, ctx);
                const rb = paletteRank(b, ctx);
                // Reads before writes only among the page's own tools; the rest stays alphabetical.
                return (ra - rb) || (f ? paletteMatch(a, f) - paletteMatch(b, f) : 0) || (ra <= 1 ? paletteKind(a, ctx) - paletteKind(b, ctx) : 0) || a.name.localeCompare(b.name);
            })
            .slice(0, 12);
        const box = document.createElement('div');
        box.className = 'ai-palette ai-palette-list';
        let html = `<div class="ai-palette-hint opacitymedium">${escapeHtml(t('AIPaletteHint'))}</div>`;
        const items = locals.map((l) => `<div class="ai-palette-item" data-local="/${escapeHtml(l[0])}"><strong>/${escapeHtml(l[0])}</strong> <span class="opacitymedium">${escapeHtml(t(l[1]))}</span></div>`)
            .concat(cmds.map((c) => `<div class="ai-palette-item" data-cmd="${escapeHtml(c.name)}"><strong>/${escapeHtml(c.name)}</strong> <span class="opacitymedium">${escapeHtml(c.label)}</span></div>`)
            .concat(list.map((tl) => `<div class="ai-palette-item ai-palette-api" data-tool="${escapeHtml(tl.name)}"><strong>${escapeHtml(tl.title || tl.name)}</strong> <span class="ai-palette-apiname">${escapeHtml(tl.name)}</span> <span class="opacitymedium">${escapeHtml(tl.description || '')}</span></div>`)));
        html += items.length ? items.join('').replace('class="ai-palette-item', 'class="ai-palette-item selected') : `<div class="ai-palette-empty opacitymedium">${escapeHtml(t('NoResults'))}</div>`;
        if (!f) html += `<div class="ai-palette-hint opacitymedium">${escapeHtml(t('AIPaletteMore'))}</div>`;
        box.innerHTML = html;
        paletteMount(box);
        // mousedown, not click: the input keeps its focus.
        box.querySelectorAll('.ai-palette-item').forEach((el) => el.addEventListener('mousedown', (ev) => { ev.preventDefault(); palettePick(el); }));
    }

    // A command: write its name in the input (and run it when it needs no words);
    // an API tool: its form.
    function palettePick(el) {
        if (el.dataset.local) {
            paletteClose();
            input.value = el.dataset.local;
            handleQuery();
            return;
        }
        if (el.dataset.cmd) {
            if (el.dataset.cmd === 'total') {
                runCommand('total', '');
                return;
            }
            input.value = '/' + el.dataset.cmd + ' ';
            input.focus();
            paletteOpen(el.dataset.cmd);
            return;
        }
        paletteForm(el.dataset.tool);
    }

    // "@" then a name suggests third parties (customers and suppliers), "#" then a
    // ref or a label suggests products, in a command or in a question to the AI.
    // Only at the start of a word, so an email address (nick@example.com) or a
    // number sign inside a word never opens it.
    function mentionTarget() {
        if (!input) return null;   // also with the palette off: suggestions only, nothing runs
        const pos = input.selectionStart;
        if (pos !== input.selectionEnd) return null;
        const before = String(input.value).slice(0, pos);
        const m = /(^|\s)([@#])([^@#\n]*)$/.exec(before);
        if (!m) return null;
        const q = m[3];
        if (/\s{2,}$/.test(q) || q.split(/\s+/).length > 6) return null;   // the user went on writing
        return { kind: m[2] === '@' ? 'thirdparty' : 'product', start: pos - q.length - 1, end: pos, q: q };
    }

    let mentionTimer = null;
    let mentionState = null;
    function mentionSuggest(target) {
        const typed = paletteWanted(input.value) ? paletteParseCommand(input.value) : null;
        const hint = t(target.kind === 'thirdparty' ? 'AIMentionThirdpartyHint' : 'AIMentionProductHint');
        const render = (items) => {
            const box = document.createElement('div');
            box.className = 'ai-palette ai-palette-list ai-palette-mention';
            let html = `<div class="ai-palette-hint opacitymedium">${escapeHtml(hint)}</div>`;
            html += (items || []).map((it, i) => `<div class="ai-palette-item ai-palette-sugg${i === 0 ? ' selected' : ''}" data-value="${escapeHtml(it.value)}" data-ref="${escapeHtml(it.ref || it.value)}"><strong>${escapeHtml(it.label)}</strong> <span class="opacitymedium">${escapeHtml(it.detail || '')}</span></div>`).join('');
            box.innerHTML = html;
            mentionState = { target: target, inCommand: !!typed, count: (items || []).length };
            paletteMount(box);
            box.querySelectorAll('.ai-palette-sugg').forEach((el) => el.addEventListener('mousedown', (ev) => { ev.preventDefault(); mentionPick(el); }));
        };
        clearTimeout(mentionTimer);   // a pending request must never draw over a newer state
        if (target.q.trim().length < 2) {
            render([]);
            return;
        }
        mentionTimer = setTimeout(async () => {
            const url = epUrl('command.php') + '&action=suggest&kind=' + target.kind + '&q=' + encodeURIComponent(target.q.trim())
                + '&cmd=' + encodeURIComponent(typed ? typed.cmd.name : '') + '&name=&socid=0';
            const asked = input.value;
            try {
                const r = await aiJson(await fetch(url, { credentials: 'same-origin' }));
                if (input.value !== asked) return;   // the user kept typing: a newer request follows
                render(r.items || []);
            } catch (e) {
                render([]);
            }
        }, 180);
    }

    // The picked name or product in place of "@..." / "#...". A command line
    // takes what its parser reads (a product with a numeric ref as #id); a
    // question takes the ref as the user would write it.
    function mentionPick(el) {
        const st = mentionState;
        if (!st) return;
        const value = st.inCommand ? el.dataset.value : el.dataset.ref;
        const v = input.value;
        const after = v.slice(st.target.end).replace(/^\s+/, '');
        const head = v.slice(0, st.target.start) + value + ' ';
        input.value = head + after;
        input.focus();
        input.setSelectionRange(head.length, head.length);
        mentionState = null;
        paletteClose();
        if (paletteWanted(input.value)) paletteOpen(input.value.slice(1));
        autoResizeInput();
    }

    // Keys while suggestions for "@" or "#" are shown. Returns true when handled.
    function mentionKey(e) {
        const box = container.querySelector('.ai-palette-mention');
        if (!box) return false;
        if (e.key === 'Escape') {
            e.preventDefault();
            mentionState = null;
            paletteClose();
            return true;
        }
        const items = Array.from(box.querySelectorAll('.ai-palette-sugg'));
        const idx = items.findIndex((el) => el.classList.contains('selected'));
        if ((e.key === 'Enter' || e.key === 'Tab') && !e.shiftKey && idx >= 0) {
            e.preventDefault();
            mentionPick(items[idx]);
            return true;
        }
        if ((e.key === 'ArrowDown' || e.key === 'ArrowUp') && items.length) {
            e.preventDefault();
            if (idx >= 0) items[idx].classList.remove('selected');
            const next = idx < 0 ? 0 : (idx + (e.key === 'ArrowDown' ? 1 : items.length - 1)) % items.length;
            items[next].classList.add('selected');
            items[next].scrollIntoView({ block: 'nearest' });
            return true;
        }
        if (e.key === 'Enter' && !e.shiftKey) paletteClose();   // nothing to pick: Enter sends as usual
        return false;
    }

    // Where the cursor is in a document command, and what to suggest there:
    // before the first number the third party, after a quantity the product.
    function paletteSuggestTarget(typed) {
        if (typed.cmd.name === 'total') return null;
        const text = String(input.value).replace(/^\s*\/\S+\s+/, '');
        if (input.selectionStart !== input.value.length) return null;   // only while typing at the end
        const firstNum = /(^|\s)\d+(?:[.,]\d+)?(?=\s|$)/.exec(text);
        if (!firstNum) {
            return text.trim().length >= 2 ? { kind: 'thirdparty', q: text.trim(), name: '' } : null;
        }
        const name = text.slice(0, firstNum.index).trim();
        const groups = text.slice(firstNum.index).split(/\s*;\s*|,\s+(?=\d)/);
        const group = groups[groups.length - 1];
        const m = /^\s*\d+(?:[.,]\d+)?(\s+[\s\S]*)?$/.exec(group);
        if (!m) return null;
        const rest = m[1] || '';
        if (rest === '') return null;                                  // the quantity is still being typed
        if (/^\s*#?"[^"]+"\s/.test(rest)) return null;                // a product already picked, in quotes
        const words = rest.trim().split(/\s+/).filter((w) => w !== '');
        if (words.length && /^\d+(?:[.,]\d+)?$/.test(words[words.length - 1])) return null;   // the price is being typed
        return { kind: 'product', q: rest.replace(/^\s+/, ''), name: name };
    }

    let paletteSuggestTimer = null;
    let paletteSuggestState = null;
    function paletteSuggest(typed) {
        const target = paletteSuggestTarget(typed);
        const render = (items, consumed) => {
            const box = document.createElement('div');
            box.className = 'ai-palette ai-palette-list ai-palette-cmdhint';
            let html = `<div class="ai-palette-hint opacitymedium"><strong>${escapeHtml(typed.cmd.syntax)}</strong> - ${escapeHtml(t('AIPaletteRunHint'))}</div>`;
            html += (items || []).map((it, i) => `<div class="ai-palette-item ai-palette-sugg${i === 0 ? ' selected' : ''}" data-value="${escapeHtml(it.value)}"><strong>${escapeHtml(it.label)}</strong> <span class="opacitymedium">${escapeHtml(it.detail || '')}</span></div>`).join('');
            box.innerHTML = html;
            paletteSuggestState = { kind: target ? target.kind : '', consumed: consumed || '', count: (items || []).length };
            paletteMount(box);
            box.querySelectorAll('.ai-palette-sugg').forEach((el) => el.addEventListener('mousedown', (ev) => { ev.preventDefault(); paletteSuggestPick(el.dataset.value); }));
        };
        clearTimeout(paletteSuggestTimer);   // a pending request must never draw over a newer state
        if (!target) {
            render([], '');
            return;
        }
        paletteSuggestTimer = setTimeout(async () => {
            const ctx = paletteCommandContext();
            const socid = (ctx.element === 'societe') ? ctx.id : ctx.socid;
            const url = epUrl('command.php') + '&action=suggest&cmd=' + encodeURIComponent(typed.cmd.name) + '&kind=' + target.kind
                + '&q=' + encodeURIComponent(target.q) + '&name=' + encodeURIComponent(target.name) + '&socid=' + (target.name ? 0 : (socid || 0));
            const asked = input.value;
            try {
                const r = await aiJson(await fetch(url, { credentials: 'same-origin' }));
                if (input.value !== asked) return;   // the user kept typing: a newer request follows
                render(r.items || [], r.consumed || '');
            } catch (e) {
                render([], '');
            }
        }, 180);
    }

    // Put the picked suggestion in place of the words it stands for.
    function paletteSuggestPick(value) {
        const st = paletteSuggestState || { kind: '', consumed: '' };
        let v = input.value;
        if (st.kind === 'thirdparty') {
            v = v.replace(/^(\s*\/\S+\s+)[\s\S]*$/, '$1' + value.replace(/\$/g, '$$$$') + ' ');
        } else {
            const tail = st.consumed;
            const trimmed = v.replace(/\s+$/, '');
            v = (tail && trimmed.toLowerCase().endsWith(tail.toLowerCase()))
                ? trimmed.slice(0, trimmed.length - tail.length) + value + ' '
                : trimmed + ' ' + value + ' ';
        }
        input.value = v;
        input.focus();
        input.setSelectionRange(v.length, v.length);
        paletteOpen(v.slice(1));
    }

    // Keys while the palette is open. Returns true when the key was handled.
    function paletteKey(e) {
        if (mentionKey(e)) return true;
        const list = container.querySelector('.ai-palette-list');
        const form = container.querySelector('.ai-palette-form');
        if (e.key === 'Escape' && (list || form)) {
            e.preventDefault();
            paletteClose();
            return true;
        }
        if (e.key === 'Enter' && !e.shiftKey && paletteWanted(input.value)) {
            if (paletteCommands === null) {
                // Typed faster than the command list arrived: decide once it is here.
                e.preventDefault();
                paletteLoad().then(() => {
                    const later = paletteParseCommand(input.value);
                    if (later && (later.text !== '' || later.cmd.name === 'total')) runCommand(later.cmd.name, later.text);
                    else paletteOpen(input.value.slice(1));
                });
                return true;
            }
            const typed = paletteParseCommand(input.value);
            const sel = container.querySelector('.ai-palette-sugg.selected');
            if (sel && paletteSuggestState && sel.dataset.value.toLowerCase() !== String(paletteSuggestState.consumed).trim().toLowerCase()) {
                e.preventDefault();
                paletteSuggestPick(sel.dataset.value);
                return true;
            }
            if (typed && (typed.text !== '' || typed.cmd.name === 'total')) {
                e.preventDefault();
                runCommand(typed.cmd.name, typed.text);
                return true;
            }
        }
        if (e.key === 'Tab' && !e.shiftKey) {
            const sel = container.querySelector('.ai-palette-sugg.selected');
            if (sel) {
                e.preventDefault();
                paletteSuggestPick(sel.dataset.value);
                return true;
            }
        }
        if (!list) {
            if (e.key === 'Enter' && !e.shiftKey && paletteWanted(input.value)) {
                e.preventDefault();   // never send "/something" to the model
                paletteOpen(input.value.slice(1));
                return true;
            }
            return false;
        }
        const items = Array.from(list.querySelectorAll('.ai-palette-item'));
        let idx = items.findIndex((el) => el.classList.contains('selected'));
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            e.preventDefault();
            if (!items.length) return true;
            if (idx >= 0) items[idx].classList.remove('selected');
            idx = (idx + (e.key === 'ArrowDown' ? 1 : items.length - 1)) % items.length;
            items[idx].classList.add('selected');
            items[idx].scrollIntoView({ block: 'nearest' });
            return true;
        }
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            if (idx >= 0) palettePick(items[idx]);
            return true;
        }
        return false;
    }

    // The value a field starts with, read from the card being viewed. Only what
    // the page itself shows; the user can change it before running.
    function palettePrefill(tool, pname, ctx) {
        if (!ctx) return '';
        const own = paletteRank(tool, ctx) <= 1;   // the card's own tools and its files
        if (pname === 'id' && own && ctx.id) return String(ctx.id);
        if (pname === 'ref' && own && ctx.ref) return ctx.ref;
        if (pname === 'modulepart' && tool.name === 'api_documents_objects_list' && ctx.id) return PALETTE_MODULEPART[ctx.element] || '';
        if ((pname === 'socid' || pname === 'thirdparty_ids') && ctx.socid) return String(ctx.socid);
        return '';
    }

    function paletteForm(name) {
        const tool = (paletteTools || []).find((tl) => tl.name === name);
        if (!tool) return;
        const ctx = paletteContext();
        let rows = '';
        for (const pname in (tool.properties || {})) {
            const pdef = tool.properties[pname];
            const req = (tool.required || []).indexOf(pname) !== -1;
            const val = palettePrefill(tool, pname, ctx);
            const nm = escapeHtml(pname);
            const help = escapeHtml(pdef.description || '');
            let field;
            if (pdef.enum && pdef.enum.length) {
                field = `<select name="${nm}" data-type="string">${req ? '' : '<option value=""></option>'}${pdef.enum.map((o) => `<option${String(o) === val ? ' selected' : ''}>${escapeHtml(String(o))}</option>`).join('')}</select>`;
            } else if (pdef.type === 'boolean') {
                field = `<select name="${nm}" data-type="boolean"><option value=""></option><option value="true">true</option><option value="false">false</option></select>`;
            } else if (pdef.type === 'array' || pdef.type === 'object') {
                // A list of records (invoice lines): JSON, with the record's fields as the example.
                const example = (pdef.items && pdef.items.length) ? '[{' + pdef.items.map((k) => `"${k}": ""`).join(', ') + '}]' : (pdef.type === 'array' ? '[]' : '{}');
                field = `<textarea name="${nm}" data-type="json" rows="2" placeholder="${escapeHtml(example)}" title="${help}"></textarea>`;
            } else {
                const num = (pdef.type === 'integer' || pdef.type === 'number');
                field = `<input type="${num ? 'number' : 'text'}"${pdef.type === 'number' ? ' step="any"' : ''} name="${nm}" data-type="${escapeHtml(pdef.type || 'string')}" value="${escapeHtml(val)}" placeholder="${help}">`;
            }
            rows += `<label class="ai-palette-row" title="${help}"><span>${nm}${req ? ' *' : ''}</span>${field}</label>`;
        }
        const box = document.createElement('div');
        box.className = 'ai-palette ai-palette-form';
        box.innerHTML = `<div class="ai-palette-title"><strong>${escapeHtml(tool.title || tool.name)}</strong> <span class="ai-palette-apiname">${escapeHtml(tool.name)}</span> <span class="opacitymedium">${escapeHtml(tool.description || '')}</span></div>`
            + rows
            + `<div class="ai-palette-error ai-hidden"></div>`
            + `<div class="ai-palette-actions"><button type="button" class="msg-action-btn primary ai-palette-run"><span class="fa fa-play"></span> ${escapeHtml(t('Run'))}</button> <button type="button" class="msg-action-btn ai-palette-cancel"><span class="fa fa-times"></span> ${escapeHtml(t('Cancel'))}</button></div>`;
        paletteMount(box);
        input.value = '';
        autoResizeInput();
        const first = box.querySelector('[name]');
        if (first) first.focus();
        box.querySelector('.ai-palette-cancel').addEventListener('click', () => { paletteClose(); input.focus(); });
        box.addEventListener('keydown', (ev) => {
            if (ev.key === 'Escape') { ev.preventDefault(); paletteClose(); input.focus(); }
            if (ev.key === 'Enter' && ev.target.tagName !== 'TEXTAREA') { ev.preventDefault(); box.querySelector('.ai-palette-run').click(); }
        });
        box.querySelector('.ai-palette-run').addEventListener('click', () => {
            const errBox = box.querySelector('.ai-palette-error');
            const args = {};
            let problem = '';
            box.querySelectorAll('[name]').forEach((el) => {
                if (problem) return;
                const v = el.value.trim();
                const pname = el.getAttribute('name');
                if (v === '') {
                    if ((tool.required || []).indexOf(pname) !== -1) problem = t('ErrorFieldRequired').replace('%s', pname);
                    return;
                }
                const type = el.dataset.type;
                if (type === 'json') {
                    try { args[pname] = JSON.parse(v); } catch (e) { problem = t('AIPaletteBadJson').replace('%s', pname); }
                } else if (type === 'integer') {
                    args[pname] = parseInt(v, 10);
                } else if (type === 'number') {
                    args[pname] = parseFloat(v);
                } else if (type === 'boolean') {
                    args[pname] = (v === 'true');
                } else {
                    args[pname] = v;
                }
            });
            if (problem) {
                errBox.textContent = problem;
                errBox.classList.remove('ai-hidden');
                return;
            }
            paletteClose();
            runToolDirect(tool.name, args);
        });
    }

    // Run a tool picked in the palette, with no model in the loop. A write answers
    // input_required: the same confirmation as the chat's is shown, carrying the
    // state that completes it, so the gate is never bypassed.
    async function runToolDirect(name, args) {
        if (welcome) welcome.style.display = 'none';
        appendMsg('user', escapeHtml('/' + name), null, '/' + name + ' ' + JSON.stringify(args));
        appendMsg('system', `<span class="fa fa-circle-notch fa-spin"></span> ${t('ExecutingTool')} ${escapeHtml(name)}...`);
        const loadingMsg = chat.lastElementChild;
        input.disabled = true;
        try {
            const res = await fetch(epUrl('execute_tool.php'), {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ tool: name, arguments: args })
            });
            const result = await aiJson(res);
            loadingMsg.remove();
            if (result && result.resultType === 'input_required' && result.requestState) {
                const prompt = (result.inputRequests && result.inputRequests[0] && result.inputRequests[0].prompt) || '';
                const held = { tool: name, arguments: Object.assign({}, args, { requestState: result.requestState }) };
                handleConfirmation(prompt, '', { tool: 'ask_for_confirmation', arguments: { action: prompt, original_intent: held } });
            } else if (result && result.error) {
                appendMsg('error', escapeHtml(String(result.error)));
            } else {
                lastResult = { data: result, tool: name, query: '' };
                appendMsg('bot', formatResult(result, false, name), null, contextSnippetOf(result, name));
                resolveThirdpartyNames(chat.lastElementChild);
            }
        } catch (e) {
            loadingMsg.remove();
            appendMsg('error', t('NetworkError') + ': ' + e.message);
        }
        input.disabled = false;
        input.focus();
    }

    function handleConfirmation(action, details, originalIntent) {
		// original_intent must be provided by parse_intent.php.
		// If missing, treat as a malformed confirmation and abort.
		if (!originalIntent.arguments || !originalIntent.arguments.original_intent) {
			appendMsg('error', t('AIError') + ': malformed confirmation response (missing original_intent). Please try again.');
			input.disabled = false;
			input.focus();
			return;
		}
		pendingIntent = originalIntent.arguments.original_intent;
		const toolName = pendingIntent.tool || 'unknown tool';
        // A write tool sends a full sentence describing what it would write; it
        // is the question itself, not a verb to slot into another sentence. More
        // than 2 words is a sentence too: a label may follow its final dot.
        const isSentence = typeof action === 'string' && (/[.!?]\s*$/.test(action.trim()) || action.trim().split(/\s+/).length > 2);
        let messageHtml;
        if (isSentence) {
            messageHtml = `<strong>${action}</strong>`;
        } else {
            const template = t('ConfirmAiAction');
            messageHtml = template.replace('%1$s', `<strong>${action}</strong>`).replace('%2$s', `<strong>${toolName}</strong>`);
        }
        let html = `<div class="confirmation-dialog"><div class="confirmation-header"><i class="fas fa-question-circle"></i><strong>${t('confirmation')}</strong></div><div class="confirmation-body"><p>${messageHtml}</p>${details ? `<p class="confirmation-details">${details}</p>` : ''}</div></div>`;
        const actions = [
            { text: t('YesProceed'), class: 'danger', icon: 'fa-check', onclick: () => confirmAction() },
            { text: t('Cancel'), icon: 'fa-times', onclick: () => cancelAction() }
        ];
        appendMsg('confirmation', html, actions);
        initConfirmationVoiceRecognition();
    }

    // =========================================================================
    // CONFIRMATION VOICE LOGIC
    // =========================================================================

    function initConfirmationVoiceRecognition() {
        isConfirmationListening = true;
        if (engineSelect.value === 'whisper') {
            if (!whisperReady) { showVoiceFeedback(t('ModelLoading')); return; }
            startWhisperRecording();
            showVoiceFeedback(`<span class="fa fa-microphone"></span> ${t('Listening')} (${t('VoiceYesNo')})`);
            setTimeout(() => { if (isConfirmationListening && isRecording) stopRecording(); }, 4000);
            return;
        }
        if (!('webkitSpeechRecognition' in window)) { showVoiceFeedback(t('BrowserNotSupported')); return; }
        if (confirmationRecognition) confirmationRecognition.abort();
        const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
        confirmationRecognition = new SR();
        confirmationRecognition.lang = 'en-US';
        confirmationRecognition.onresult = (e) => { processConfirmationCommand(e.results[0][0].transcript); };
        confirmationRecognition.onerror = (e) => { if (e.error !== 'no-speech') showVoiceFeedback(t('Error')); isConfirmationListening = false; };
        try { confirmationRecognition.start(); showVoiceFeedback(`<span class="fa fa-microphone"></span> ${t('Listening')} (${t('VoiceYesNo')})`); } catch (e) { console.error(e); }
    }

    function processConfirmationCommand(rawText) {
        resetUI(); isConfirmationListening = false;
        if (!rawText) { showVoiceFeedback(t('PleaseRepeat')); return; }
        const cmd = rawText.toLowerCase(); console.log("Confirmation Cmd:", cmd);
        if (cmd.includes('yes') || cmd.includes('sure') || cmd.includes('confirm') || cmd.includes('proceed')) confirmAction();
        else if (cmd.includes('no') || cmd.includes('stop') || cmd.includes('cancel') || cmd.includes('don')) cancelAction();
        else showVoiceFeedback(`${t('HeardText')} "${rawText}".`);
    }

    function confirmAction() {
        if (confirmationRecognition) try { confirmationRecognition.stop(); } catch (e) { }
        const msg = chat.lastElementChild;
        if (msg && msg.classList.contains('confirmation')) msg.remove();
        executePendingIntent();
    }

    function cancelAction() {
        // The question behind a cancelled action must not travel as context:
        // left as an unanswered request in the window, the model re-proposes
        // it on the next question (field case: "set the phone of X" answered
        // by creating the thirdparty a cancelled request had named). Out of
        // the window by default, like a failed answer; still pinnable by hand.
        // (contextBubbles, not pastContextBubbles: at this point the question
        // is the trailing bubble, which the latter leaves out on purpose.)
        const asked = contextBubbles().filter((m) => m.dataset.aiRole === 'user').pop();
        if (asked) {
            asked.dataset.aiError = '1';
            // Persisted, so a reopened conversation keeps it out of the window too
            if (asked.dataset.msgId) chatHistoryApi({ action: 'pin', message_id: parseInt(asked.dataset.msgId, 10), pinned: (asked.dataset.ctx === 'on') ? 1 : 0, error: 1 }).catch(() => {});
        }
        if (confirmationRecognition) try { confirmationRecognition.stop(); } catch (e) { }
        const msg = chat.lastElementChild;
        if (msg && msg.classList.contains('confirmation')) msg.remove();
        appendMsg('system', t('ActionCancelled'));
        pendingIntent = null;
        refreshContext();
    }

    function showVoiceFeedback(message) {
        const dialog = container.querySelector('.confirmation-dialog .confirmation-body');
        if (dialog) {
            let fb = dialog.querySelector('.voice-feedback');
            if (!fb) { fb = document.createElement('div'); fb.className = 'voice-feedback'; dialog.appendChild(fb); }
            fb.innerHTML = `<i class="fas fa-comment-dots"></i> ${message}`;
        }
    }

    // =========================================================================
    // BACKEND COMMUNICATION
    // =========================================================================

    async function executePendingIntent() {
        if (!pendingIntent) return;
        appendMsg('system', `<span class="fa fa-circle-notch fa-spin"></span> ${t('ExecutingTool')} ${pendingIntent.tool || ''}...`);
        const loadingMsg = chat.lastElementChild;
        input.disabled = true;
		// PendingIntent must be a real action tool, never a system tool.
		// This catches the edge case where handleConfirmation stored the wrong intent.
		const systemTools = ['ask_for_confirmation', 'ask_for_clarification', 'respond_to_user', 'reject_general_question'];
		if (!pendingIntent || !pendingIntent.tool || systemTools.includes(pendingIntent.tool)) {
			loadingMsg.remove();
			appendMsg('error', t('AIError') + ': cannot execute system tool "' + (pendingIntent && pendingIntent.tool || 'unknown') + '" as an action. Please try again.');
			pendingIntent = null;
			input.disabled = false;
			input.focus();
			return;
		}
        try {
            const toolRes = await fetch(epUrl('execute_tool.php'), {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(pendingIntent)
            });
            let result = await aiJson(toolRes);
            // After the user confirms the preview send the state back to complete the write.
            if (result && result.resultType === 'input_required' && result.requestState) {
                const confirmed = Object.assign({}, pendingIntent, {
                    arguments: Object.assign({}, pendingIntent.arguments || {}, { requestState: result.requestState })
                });
                const secondRes = await fetch(epUrl('execute_tool.php'), {
                    method: 'POST', headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(confirmed)
                });
                result = await aiJson(secondRes);
            }
            loadingMsg.remove();
            lastResult = { data: result, tool: pendingIntent.tool, query: pendingIntent.query || '' };
            const failedIntent = pendingIntent;
            const retry = retryActionsFor(result, () => { pendingIntent = failedIntent; executePendingIntent(); });
            appendMsg('bot', formatResult(result, false, pendingIntent.tool), retry, contextSnippetOf(result, pendingIntent.tool));
            resolveThirdpartyNames(chat.lastElementChild);
            pendingIntent = null;
        } catch (e) { loadingMsg.remove(); appendMsg('error', t('NetworkError') + ': ' + e.message); }
        input.disabled = false;
        input.focus();
    }

    // Parse a fetch Response that must be JSON. When the Dolibarr session has
    // expired, the endpoints answer with the HTML login form (HTTP 200), which
    // used to surface as a cryptic "Unexpected token '<'" network error: detect
    // that case and tell the user to sign back in instead.
    // --- Conversation history panel (list / reopen / delete) ---
    const historyBtn = container.querySelector('#ai-history-btn');
    const pageSidebar = container.classList.contains('ai-in-popover') ? null : document.getElementById('ai-history-sidebar');
    if (historyBtn && pageSidebar) {
        // Full page: the header button shows / hides the conversations column.
        historyBtn.addEventListener('click', (ev) => { ev.stopPropagation(); setSidebarCollapsed(!container.classList.contains('sidebar-collapsed')); });
    } else if (historyBtn) {
        historyBtn.addEventListener('click', (ev) => {
            ev.stopPropagation();
            const existing = container.querySelector('#ai-history-panel');
            if (existing) { existing.remove(); return; }
            const panel = document.createElement('div');
            panel.id = 'ai-history-panel';
            panel.innerHTML = '<div class="opacitymedium">…</div>';
            historyBtn.insertAdjacentElement('afterend', panel);
            chatHistoryApi({ action: 'list' }).then((res) => {
                const items = (res && res.conversations) || [];
                if (!items.length) { panel.innerHTML = '<div class="opacitymedium">' + t('AIHistoryEmpty') + '</div>'; return; }
                panel.innerHTML = '';
                items.forEach((c) => {
                    const row = document.createElement('div');
                    row.className = 'ai-history-item';
                    row.innerHTML = '<span class="ai-history-title">' + escapeHtml(c.title || '…') + '</span>'
                        + '<span class="ai-history-meta">' + escapeHtml(c.date) + ' · ' + c.nb + '</span>'
                        + '<button type="button" class="ai-history-ren" title="' + escapeHtml(t('AIHistoryRename')) + '"><i class="fa fa-pen"></i></button>'
                        + '<button type="button" class="ai-history-del" title="' + escapeHtml(t('AIHistoryDelete')) + '">&times;</button>';
                    row.querySelector('.ai-history-ren').onclick = (e2) => {
                        e2.stopPropagation();
                        startRenameRow(row, c, '.ai-history-title', (saved) => {
                            // Keep the panel open: put the title span back with the new text
                            const inp = row.querySelector('input');
                            const span = document.createElement('span');
                            span.className = 'ai-history-title';
                            span.textContent = saved || c.title || '…';
                            if (inp) inp.replaceWith(span);
                        });
                    };
                    row.querySelector('.ai-history-del').onclick = (e2) => {
                        e2.stopPropagation();
                        chatHistoryApi({ action: 'delete', id: c.id }).then(() => { row.remove(); if (conversationId === c.id) setConversationId(0); });
                    };
                    row.onclick = () => { panel.remove(); loadConversation(c.id); };
                    attachConversationPreview(row, c);
                    panel.appendChild(row);
                });
            }).catch(() => { panel.innerHTML = '<div class="opacitymedium">' + t('AIError') + '</div>'; });
        });
        // Any click outside closes the panel
        document.addEventListener('click', (ev) => {
            const panel = container.querySelector('#ai-history-panel');
            if (panel && !panel.contains(ev.target) && ev.target !== historyBtn && !panel.querySelector('input')) panel.remove();
        });
    }

    // --- Hover preview of a past conversation (column and popover panel alike):
    // the whole thread, fetched once per conversation and cached, shown in a
    // floating card next to the hovered row. ---
    const previewCache = {};
    let previewEl = null;
    let previewTimer = null;
    function hideConversationPreview() {
        clearTimeout(previewTimer);
        if (previewEl) { previewEl.remove(); previewEl = null; }
    }
    function showConversationPreview(row, c, messages) {
        hideConversationPreview();
        const card = document.createElement('div');
        card.className = 'ai-conv-preview';
        let html = '<div class="ai-conv-preview-head">' + escapeHtml(c.date || '') + ' · ' + escapeHtml(String(c.nb || messages.length)) + '</div>';
        messages.forEach((m) => {
            const who = (m.role === 'assistant') ? t('AIAssistant') : t('AIYou');
            const text = String(m.raw || '').replace(/\s+/g, ' ').trim();
            html += '<div class="ai-conv-preview-msg ' + (m.role === 'assistant' ? 'bot' : 'user') + '"><b>' + escapeHtml(who) + '</b> ' + escapeHtml(text.length > 300 ? text.slice(0, 300) + '…' : text) + '</div>';
        });
        card.innerHTML = html;
        document.body.appendChild(card);
        // Next to the row: right of a column row, left of a popover panel row; clamped to the viewport.
        const r = row.getBoundingClientRect();
        const cw = card.offsetWidth, ch = card.offsetHeight;
        const vw = document.documentElement.clientWidth, vh = document.documentElement.clientHeight;
        let left = (row.closest('#ai-history-sidebar')) ? r.right + 8 : r.left - cw - 8;
        if (left + cw > vw - 8) left = vw - cw - 8;
        if (left < 8) left = 8;
        let top = r.top;
        if (top + ch > vh - 8) top = Math.max(8, vh - ch - 8);
        card.style.left = left + 'px';
        card.style.top = top + 'px';
        previewEl = card;
    }
    // Rename in place (column and popover panel): the title span turns into an
    // input; Enter / blur saves through the ownership-checked 'rename' action,
    // Esc cancels; onDone(savedTitle|null) lets the caller refresh its list.
    function startRenameRow(row, c, titleSelector, onDone) {
        hideConversationPreview();
        const span = row.querySelector(titleSelector);
        if (!span || row.querySelector('input')) return;
        const inp = document.createElement('input');
        inp.type = 'text'; inp.className = 'ai-sidebar-rename'; inp.value = c.title || ''; inp.maxLength = 250;
        span.replaceWith(inp);
        inp.focus(); inp.select();
        let done = false;
        const finish = (save) => {
            if (done) return; done = true;
            const v = inp.value.trim();
            if (save && v && v !== c.title) {
                chatHistoryApi({ action: 'rename', id: c.id, title: v }).then((r) => { c.title = (r && r.title) || v; onDone(c.title); }).catch(() => onDone(null));
            } else { onDone(null); }
        };
        inp.addEventListener('keydown', (ek) => { if (ek.key === 'Enter') { ek.preventDefault(); finish(true); } else if (ek.key === 'Escape') { finish(false); } });
        inp.addEventListener('blur', () => finish(true));
        inp.addEventListener('click', (ec) => ec.stopPropagation());
    }

    function attachConversationPreview(row, c) {
        row.addEventListener('mouseenter', () => {
            clearTimeout(previewTimer);
            previewTimer = setTimeout(() => {
                if (previewCache[c.id]) { showConversationPreview(row, c, previewCache[c.id]); return; }
                chatHistoryApi({ action: 'load', id: c.id }).then((res) => {
                    if (!res || !res.messages) return;
                    previewCache[c.id] = res.messages;
                    if (row.matches(':hover')) showConversationPreview(row, c, res.messages);
                }).catch(() => {});
            }, 350);
        });
        row.addEventListener('mouseleave', hideConversationPreview);
        row.addEventListener('click', hideConversationPreview);
    }

    // --- Full page: permanent conversations column (like the mainstream chat applications) ---
    // The popover has no room for it and keeps the button + panel above.
    const sidebar = container.classList.contains('ai-in-popover') ? null : document.getElementById('ai-history-sidebar');
    let sidebarTimer = null;
    function renderSidebar() {
        if (!sidebar) return;
        // Coalesce bursts (a save + an id change) into one listing call.
        clearTimeout(sidebarTimer);
        sidebarTimer = setTimeout(() => {
            const list = sidebar.querySelector('#ai-sidebar-list');
            if (!list) return;
            chatHistoryApi({ action: 'list' }).then((res) => {
                const items = (res && res.conversations) || [];
                list.innerHTML = '';
                if (!items.length) { list.innerHTML = '<div class="opacitymedium ai-sidebar-empty">' + t('AIHistoryEmpty') + '</div>'; return; }
                // Group by age, client-side from the raw timestamp
                const now = new Date(); const startOfToday = new Date(now.getFullYear(), now.getMonth(), now.getDate()).getTime() / 1000;
                const groupOf = (ts) => (ts >= startOfToday ? 'AIHistoryToday' : ts >= startOfToday - 86400 ? 'AIHistoryYesterday' : ts >= startOfToday - 7 * 86400 ? 'AIHistoryLast7Days' : 'AIHistoryOlder');
                let lastGroup = '';
                items.forEach((c) => {
                    const g = groupOf(c.ts || 0);
                    if (g !== lastGroup) {
                        const h = document.createElement('div');
                        h.className = 'ai-sidebar-group';
                        h.textContent = t(g);
                        list.appendChild(h);
                        lastGroup = g;
                    }
                    const row = document.createElement('div');
                    row.className = 'ai-sidebar-item' + (c.id === conversationId ? ' active' : '');
                    row.innerHTML = '<span class="ai-sidebar-title">' + escapeHtml(c.title || '…') + '</span>'
                        + '<button type="button" class="ai-sidebar-act ai-sidebar-ren" title="' + escapeHtml(t('AIHistoryRename')) + '"><i class="fa fa-pen"></i></button>'
                        + '<button type="button" class="ai-sidebar-act ai-sidebar-del" title="' + escapeHtml(t('AIHistoryDelete')) + '"><i class="fa fa-trash-alt"></i></button>';
                    const startRename = (e2) => { e2.stopPropagation(); startRenameRow(row, c, '.ai-sidebar-title', () => renderSidebar()); };
                    row.querySelector('.ai-sidebar-ren').onclick = startRename;
                    row.addEventListener('dblclick', startRename);
                    row.querySelector('.ai-sidebar-del').onclick = (e2) => {
                        e2.stopPropagation();
                        chatHistoryApi({ action: 'delete', id: c.id }).then(() => {
                            if (conversationId === c.id) startNewConversation(); else renderSidebar();
                        }).catch(() => {});
                    };
                    row.onclick = () => { if (c.id !== conversationId) loadConversation(c.id); };
                    attachConversationPreview(row, c);
                    list.appendChild(row);
                });
            }).catch(() => { list.innerHTML = '<div class="opacitymedium ai-sidebar-empty">' + t('AIError') + '</div>'; });
        }, 150);
    }
    // Collapsible: "<<" in the column hides it, the header history button
    // brings it back; the choice is remembered per browser. Narrow screens
    // start collapsed (the column would eat the chat).
    function setSidebarCollapsed(collapsed) {
        if (!sidebar) return;
        container.classList.toggle('sidebar-collapsed', collapsed);
        if (historyBtn) historyBtn.title = collapsed ? t('AIShowConversations') : t('AIHideConversations');
        try { localStorage.setItem('aiSidebarCollapsed', collapsed ? '1' : '0'); } catch (e) { /* private mode: no memory, no harm */ }
    }
    if (sidebar) {
        const newBtn = sidebar.querySelector('#ai-new-chat');
        if (newBtn) newBtn.addEventListener('click', () => startNewConversation());
        const collapseBtn = sidebar.querySelector('#ai-sidebar-collapse');
        if (collapseBtn) collapseBtn.addEventListener('click', () => setSidebarCollapsed(true));
        const expandBtn = container.querySelector('#ai-sidebar-expand');
        if (expandBtn) expandBtn.addEventListener('click', () => setSidebarCollapsed(false));
        let stored = null;
        try { stored = localStorage.getItem('aiSidebarCollapsed'); } catch (e) { stored = null; }
        setSidebarCollapsed(stored === null ? (window.innerWidth <= 900) : stored === '1');
        renderSidebar();
    }

    function loadConversation(id) {
        chatHistoryApi({ action: 'load', id: id }).then((res) => {
            if (!res || !res.messages) return;
            chat.querySelectorAll('.msg').forEach((n) => n.remove());
            if (welcome) welcome.style.display = 'none';
            restoringHistory = true;
            res.messages.forEach((m) => {
                const html = m.html || escapeHtml(m.raw).replace(/\n/g, '<br>');
                appendMsg(m.role === 'assistant' ? 'bot' : 'user', html, null, m.raw);
                const div = chat.lastElementChild;
                if (div) {
                    div.dataset.msgId = String(m.id);
                    if (m.pinned) div.dataset.ctx = 'on';
                    if (m.error) div.dataset.aiError = '1';   // stays out of the window on reopen too
                }
            });
            restoringHistory = false;
            setConversationId(res.id);
            refreshContext();
        }).catch(() => {});
    }

    // Compact, model-oriented snippet of a tool result for the pinned context:
    // the model needs the shape and the ids, not the full rendered table.
    function contextSnippetOf(result, toolName) {
        let s = '';
        try { s = JSON.stringify(result); } catch (e) { s = String(result); }
        if (s.length > 1500) s = s.slice(0, 1500) + '…';
        return '[' + (toolName || 'tool') + ' result] ' + s;
    }

    // Number of recent exchanges (question + answer) that follow the model by
    // default. 0 = fully manual: nothing goes back unless the user pins it.
    const AUTO_CONTEXT = Math.max(0, parseInt(config.autoContext, 10) || 0);

    // Bubbles that can be sent back as context (they carry a plain-text form).
    function contextBubbles() {
        return Array.from(chat.querySelectorAll('.msg')).filter((m) => m.dataset.aiRaw);
    }

    // Same, without the question currently being answered: that one IS the
    // query, it never travels as context.
    function pastContextBubbles() {
        const msgs = contextBubbles();
        if (msgs.length && msgs[msgs.length - 1].dataset.aiRole === 'user') msgs.pop();
        return msgs;
    }

    // Effective context = the sliding window of the last AUTO_CONTEXT exchanges
    // (counted from the questions) + explicit pins - explicit exclusions. Each
    // bubble holds its own decision in data-ctx: '' follows the window, 'on' is
    // pinned for good, 'off' is excluded. Recomputed after every change, so the
    // window slides as the conversation grows while the pins stay put.
    function refreshContext() {
        const all = contextBubbles();
        const msgs = pastContextBubbles();
        // The question being answered is the query itself, never context: it
        // waits outside the window until its answer arrives.
        const pending = (all.length > msgs.length) ? all[all.length - 1] : null;
        if (pending) { pending.classList.remove('ctx-pinned'); pending.dataset.ctxWindow = ''; }
        let windowStart = msgs.length;
        if (AUTO_CONTEXT > 0) {
            let questions = 0;
            for (let i = msgs.length - 1; i >= 0; i--) {
                if (msgs[i].dataset.aiRole === 'user' && ++questions === AUTO_CONTEXT) { windowStart = i; break; }
                if (i === 0) windowStart = 0;
            }
        }
        msgs.forEach((m, i) => {
            const inWindow = i >= windowStart;
            const state = m.dataset.ctx || '';
            const on = state === 'on' || (state === '' && inWindow && !m.dataset.aiError);
            m.dataset.ctxWindow = inWindow ? '1' : '';
            m.classList.toggle('ctx-pinned', on);
            const pin = m.querySelector('.ctx-pin');
            if (pin) {
                // Two glyphs: a planted blue pin (travels with the next question)
                // or a grey pin struck through (does not) - see .ctx-pin-off in CSS.
                pin.classList.toggle('ctx-pin-off', !on);
                pin.title = on ? t('AIContextPinOn') : t('AIContextPinOff');
            }
        });
        updateContextBar();
        saveChatHistory();
    }

    // Persist the explicit pin so it survives a reopen (the window itself is
    // recomputed on reopen, not stored). Best-effort, like the rest of history.
    function persistPinState(div) {
        if (!div.dataset.msgId) return;
        chatHistoryApi({ action: 'pin', message_id: parseInt(div.dataset.msgId, 10), pinned: (div.dataset.ctx === 'on') ? 1 : 0 }).catch(() => {});
    }

    // One click flips the bubble: inside the window it toggles between "follows
    // the window" and "excluded"; outside it toggles the explicit pin.
    function toggleContextPin(div) {
        const on = div.classList.contains('ctx-pinned');
        const inWindow = div.dataset.ctxWindow === '1';
        if (on) div.dataset.ctx = inWindow ? 'off' : '';
        else div.dataset.ctx = (inWindow && !div.dataset.aiError) ? '' : 'on';
        refreshContext();
    }

    function collectPinnedContext() {
        return contextBubbles().filter((m) => m.classList.contains('ctx-pinned'))
            .map((m) => ({ role: m.dataset.aiRole || 'user', text: m.dataset.aiRaw || '', id: parseInt(m.dataset.msgId, 10) || 0 }))
            .filter((p) => p.text);
    }

    // What the server receives for the pinned turns: ids only for persisted
    // messages (the text is rebuilt server-side from the stored conversation,
    // so the client is not trusted for what was said); the inline text is kept
    // only for a message that has no row yet (history disabled or save in flight).
    function pinnedContextPayload() {
        return collectPinnedContext().map((p) => (p.id ? { id: p.id } : { role: p.role, text: p.text }));
    }

    // Small bar above the input: how many exchanges are pinned and their rough
    // token weight (chars/4) - the cost of the selected context stays visible.
    function updateContextBar() {
        let bar = container.querySelector('#ai-ctx-bar');
        // Past exchanges the user can act on (the pending question is not one).
        const past = pastContextBubbles();
        const pinned = collectPinnedContext();
        if (!past.length) { if (bar) bar.remove(); return; }
        const tokens = Math.round(pinned.reduce((n, p) => n + p.text.length, 0) / 4);
        // The setting counts exchanges (question + answer), so the bar says
        // both: exchanges, then messages - one unit on its own misleads.
        const exchanges = pinned.filter((p) => p.role === 'user').length;
        if (!bar) {
            bar = document.createElement('div');
            bar.id = 'ai-ctx-bar';
            const pill = input.closest('.chat-input-pill') || input.parentElement;
            pill.insertAdjacentElement('beforebegin', bar);
        }
        // "Auto (3)" is lit as long as every bubble simply follows the window
        // (no manual pin, no exclusion): one glance says which mode is on.
        const isDefault = !contextAllMode && past.every((m) => !m.dataset.ctx);
        bar.innerHTML = '<span class="fas fa-thumbtack"></span> ' + escapeHtml(t('AIContextLabel')) +
            (AUTO_CONTEXT > 0 ? ' <a href="#" id="ai-ctx-auto" class="' + (isDefault ? 'ai-ctx-active' : '') + '" title="' + escapeHtml(t('AIContextAutoTitle').replace('%s', String(AUTO_CONTEXT))) + '"><span class="fa fa-history"></span> ' + t('AIContextAuto').replace('%s', String(AUTO_CONTEXT)) + '</a>' : '') +
            ' <a href="#" id="ai-ctx-all" class="' + (contextAllMode ? 'ai-ctx-active' : '') + '" title="' + escapeHtml(t('AIContextAllTitle')) + '"><span class="fa fa-check-double paddingright"></span>' + t('AIContextAll') + '</a>' +
            ' <a href="#" id="ai-ctx-clear" title="' + escapeHtml(t('AIContextClearTitle')) + '"><span class="fa fa-eraser paddingright"></span>' + t('None') + '</a>' +
            ' <span class="opacitymedium marginleftonly" title="' + escapeHtml(t('AIContextCounter').replace('%s', String(exchanges)).replace('%s', String(pinned.length)).replace('%s', String(tokens))) + '">(' + escapeHtml(t('AIContextTokens').replace('%s', String(tokens))) + ')</span>';
        const auto = bar.querySelector('#ai-ctx-auto');
        if (auto) {
            auto.onclick = (ev) => {
                ev.preventDefault();
                // Auto = back to the default: every bubble follows the window again.
                contextAllMode = false;
                pastContextBubbles().forEach((m) => { m.dataset.ctx = ''; persistPinState(m); });
                refreshContext();
            };
        }
        const all = bar.querySelector('#ai-ctx-all');
        if (all) {
            all.onclick = (ev) => {
                ev.preventDefault();
                // All = every past exchange pinned for good (the bar shows the price).
                // The next exchanges are pinned too, until Auto or Clear is chosen.
                contextAllMode = true;
                pastContextBubbles().forEach((m) => { m.dataset.ctx = 'on'; persistPinState(m); });
                refreshContext();
            };
        }
        const clear = bar.querySelector('#ai-ctx-clear');
        if (clear) {
            clear.onclick = (ev) => {
                ev.preventDefault();
                // Clear = nothing goes back with the NEXT question: recent bubbles
                // are excluded, older pins dropped. New exchanges re-enter the
                // window on their own afterwards.
                contextAllMode = false;
                pastContextBubbles().forEach((m) => { m.dataset.ctx = (m.dataset.ctxWindow === '1') ? 'off' : ''; persistPinState(m); });
                refreshContext();
            };
        }
    }

    async function aiJson(response) {
        const raw = await response.text();
        try {
            return JSON.parse(raw);
        } catch (e) {
            if (/<\s*(!doctype|html|form|body)[\s>]/i.test(raw)) {
                throw new Error(t('AISessionExpiredReload'));
            }
            throw e;
        }
    }

    async function handleQuery(resend) {
        // A resend (after the user picked a third party) posts the same request
        // again, without a second bubble.
        const isResend = !!(resend && typeof resend.sentQuery === 'string');
        const query = isResend ? resend.query : input.value.trim();
        if (!isResend && paletteWanted(query)) {
            // A tool or a command, not a question for the model.
            paletteLoad().then(() => {
                const typed = paletteParseCommand(query);
                if (typed && (typed.text !== '' || typed.cmd.name === 'total')) {
                    runCommand(typed.cmd.name, typed.text);
                } else {
                    paletteOpen(query.slice(1));
                }
            });
            return;
        }
        const readyDocs = isResend ? [] : attachedDocs.filter((d) => !d.error);
        if (!query && !readyDocs.length && !isResend) return;
        if (welcome) welcome.style.display = 'none'; // leave the empty-state once a message is sent

        // "/new" and "/clear" are handled locally (no AI call): same as the Clear button.
        if ((query.toLowerCase() === '/new' || query.toLowerCase() === '/clear') && !readyDocs.length) {
            input.value = '';
            input.style.height = '44px';
            startNewConversation();
            return;
        }

        // "/help" is answered locally (no AI call): the commands this user may run,
        // the internal ones and the "@" / "#" suggestions. No raw text: never sent as context.
        if (query.toLowerCase() === '/help' && !readyDocs.length) {
            input.value = '';
            input.style.height = '44px';
            appendMsg('user', escapeHtml(query));
            await paletteLoad();
            const line = (syntax, label, inline) => '<li><strong><code>' + escapeHtml(syntax) + '</code></strong>' + (inline ? ' : ' : '<br>') + escapeHtml(label) + '</li>';
            let html = '<div class="small"><b>' + escapeHtml(t('AIHelpInternal')) + '</b><ul>';
            html += line('/help', t('AIHelpHelp'), true);
            html += line('/new, /clear', t('AIHelpClear'), true);
            html += line('/status', t('AIHelpStatus'), true);
            html += '</ul><b>' + escapeHtml(t('AIHelpMentions')) + '</b><ul>';
            html += line('@', t('AIHelpMentionThirdparty'), true);
            html += line('#', t('AIHelpMentionProduct'), true);
            html += '</ul><b>' + escapeHtml(t('AIHelpCommands')) + '</b><ul>';
            (paletteCommands || []).forEach((c) => { html += line(c.syntax || ('/' + c.name), c.label || ''); });
            html += line('/...', t('AIHelpPalette'));
            html += '</ul>' + escapeHtml(t('AIHelpFreeText')) + '</div>';
            appendMsg('bot', html);
            input.focus();
            return;
        }

        // "/status" is answered locally (no AI call): statistics of the whole
        // conversation (the bar above the input already shows the selected
        // context). Both bubbles have no raw text so they never travel as context.
        if (query.toLowerCase() === '/status' && !readyDocs.length) {
            const whole = pastContextBubbles().map((m) => ({ role: m.dataset.aiRole || 'user', text: m.dataset.aiRaw || '' }));
            const stats = t('AIContextCounter')
                .replace('%s', String(whole.filter((p) => p.role === 'user').length))
                .replace('%s', String(whole.length))
                .replace('%s', String(Math.round(whole.reduce((n, p) => n + p.text.length, 0) / 4)));
            appendMsg('user', escapeHtml(query));
            appendMsg('bot', escapeHtml(stats));
            input.value = '';
            input.style.height = '44px';
            input.focus();
            return;
        }

        // What is SENT = document context + question; what is DISPLAYED = chip + question.
        let sentQuery = isResend ? resend.sentQuery : query;
        let displayHtml = escapeHtml(query);
        if (readyDocs.length) {
            // One wrapped context block per document, so each keeps its own
            // intro/outro delimiters whatever mix of text and markers is sent.
            // The trailing space after the payload matters: the server-side marker
            // regex consumes trailing newlines as part of the base64 run, which
            // used to glue '[attached document]' to the outro line in the logs.
            const docContext = readyDocs.map((d) => `${t('DocContextIntro')}\n\n${d.payload} \n\n--- ${t('DocContextOutro')} ---`).join('\n') + '\n';
            sentQuery = docContext + (query ? '\n' + query : '');
            displayHtml = readyDocs.map((d) => chipHtmlFor(d.name)).join(' ') + (query ? '<br>' + displayHtml : '');
        }

        if (!isResend) {
            appendMsg('user', displayHtml, null, query || t('AIContextAttachmentOnly'));
            clearChip();
        }
        lastSent = { sentQuery: sentQuery, query: query };
        input.value = '';
        input.style.height = '44px';
        input.disabled = true;
        appendTyping();
        let loadingMsg = chat.lastElementChild;
        try {
            const chosenModel = resolveModel();
            // Pinned exchanges only: context is opt-in, its cost visible in the bar.
            const pinned = pinnedContextPayload();
            // Result of the read flagged needs_followup, sent back on the second round.
            let followupSnippet = null;
            let followupResult = null;
            // The third parties the user picked, for every round of this request.
            const sentChoices = Object.assign({}, thirdpartyChoices);
            // Two rounds at most: the server never flags a followup round itself.
            for (let round = 0; round < 2; round++) {
                if (round > 0) { appendTyping(); loadingMsg = chat.lastElementChild; }
                const intentRes = await fetch(epUrl('parse_intent.php'), {
                    method: 'POST', headers: { 'Content-Type': 'application/json' },
                    // Page context (set by the printCommonFooter hook on card pages)
                    // lets the server resolve "this invoice" - it re-validates the
                    // ids against the user's rights before trusting them.
                    body: JSON.stringify(Object.assign(
                        chosenModel ? { query: sentQuery, model: chosenModel } : { query: sentQuery },
                        Object.keys(sentChoices).length ? { thirdparty_choices: sentChoices } : {},
                        (function () {
                            // followup_result: only to name the object in the confirmation, never sent to the model.
                            if (followupSnippet) return { history: pinned.concat([{ role: 'assistant', text: followupSnippet }]), followup: 1, followup_result: followupResult };
                            return pinned.length ? { history: pinned } : {};
                        })(),
                        (function () {
                            const ctx = window.aiPageContext;
                            if (!ctx || (!ctx.id && !ctx.list && !ctx.dashboard)) return {};
                            // On list pages, the mass-action checkboxes carry the row
                            // ids: checked ones are the user's live selection.
                            if (ctx.list) {
                                // The mass-action checkboxes carry rowids by core
                                // convention on every list - a uniform source that
                                // sidesteps the per-list SQL alias zoo server-side.
                                const all = Array.from(document.querySelectorAll('.checkforselect'))
                                    .map(cb => parseInt(cb.value, 10)).filter(n => n > 0);
                                if ((!ctx.ids || !ctx.ids.length) && all.length) ctx.ids = all.slice(0, 100);
                                const sel = Array.from(document.querySelectorAll('.checkforselect:checked'))
                                    .map(cb => parseInt(cb.value, 10)).filter(n => n > 0).slice(0, 25);
                                if (sel.length) ctx.selected = sel;
                            }
                            return { context: ctx };
                        })()
                    ))
                });
                const intent = await aiJson(intentRes);
                loadingMsg.remove();
                // Choices answer this request only: once it went through, the next question asks again.
                if (!(intent.tool === 'ask_for_clarification' && intent.arguments && intent.arguments.thirdparty_choice)) thirdpartyChoices = {};
                if (intent.error) { appendMsg('error', t('AIError') + ': ' + intent.error); input.disabled = false; input.focus(); return; }
                if (intent.tool === 'ask_for_clarification' && intent.arguments && intent.arguments.thirdparty_choice) { handleThirdpartyChoice(intent.arguments.question || '', intent.arguments.thirdparty_choice); input.disabled = false; input.focus(); return; }
                if (intent.tool === 'ask_for_clarification') { const a = intent.arguments || {}; handleClarification(a.question || a.reason || (a.missing_argument ? t('MissingInformation') + ': ' + a.missing_argument : t('CouldYouClarify')), query); input.disabled = false; input.focus(); return; }
                if (query === '/tools' && intent.tool === 'respond_to_user' && intent.arguments && typeof intent.arguments.html === 'string') {
                    // Debug output of /tools, built and escaped server side: shown as is,
                    // never sent back as context. Only for that query: an 'html' argument
                    // coming from the model is never trusted.
                    appendMsg('bot', intent.arguments.html); input.disabled = false; input.focus(); return;
                }
                if (intent.tool === 'respond_to_user' || intent.tool === 'reject_general_question') { const a = intent.arguments || {}; const msg = a.message || a.response || a.text || a.answer || a.content || a.reply || t('EmptyAIResponse'); handleResponse(msg, intent.status === 'error'); input.disabled = false; input.focus(); return; }
                if (intent.tool === 'ask_for_confirmation') { handleConfirmation(intent.arguments.action, intent.arguments.details, intent); input.disabled = false; input.focus(); return; }
                if (intent.tool === 'generate_navigation_url') {
                    appendMsg('system', t('GeneratingLink'));
                    const loadingNav = chat.lastElementChild;
                    const navRes = await fetch(epUrl('execute_tool.php'), {
                        method: 'POST', headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(intent)
                    });
                    const nav = await aiJson(navRes);
                    loadingNav.remove();
                    if (nav.error) { appendMsg('error', nav.error); }
                    else { const html = `${t('Found')}: <a href="${nav.url}" target="_blank" class="msg-action-btn primary"><span class="fas fa-external-link-alt"></span> ${t('Open')} ${nav.description}</a>`; appendMsg('bot', html); }
                    input.disabled = false; input.focus(); return;
                }

                appendMsg('system', t('FetchingData'));
                const loadingData = chat.lastElementChild;
                const toolRes = await fetch(epUrl('execute_tool.php'), {
                    method: 'POST', headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(intent)
                });
                const result = await aiJson(toolRes);
                loadingData.remove();
                // The read only fetched the ids the asked action needs: hand its result
                // back to the model instead of showing it, and let it perform the action.
                if (!round && intent.needs_followup && result && !result.error && result.success !== false && result.resultType !== 'input_required') {
                    appendMsg('system', t('AISearchDoneNowPreparingAction') + ' (' + escapeHtml(intent.tool || '') + ')...');
                    followupSnippet = contextSnippetOf(result, intent.tool);
                    followupResult = result;
                    continue;
                }
                // A write tool does not write on its first call: it answers with the
                // sentence to confirm and a requestState. Ask the user, the write runs
                // when executePendingIntent() sends the state back.
                if (result && result.resultType === 'input_required' && result.requestState) {
                    const req = (Array.isArray(result.inputRequests) && result.inputRequests[0]) || {};
                    const confirmed = Object.assign({}, intent, {
                        arguments: Object.assign({}, intent.arguments || {}, { requestState: result.requestState })
                    });
                    delete confirmed.needs_followup;
                    delete confirmed.resolved_label;
                    // The id came from a search the user never saw: say which object it is.
                    const action = (req.prompt || intent.tool) + (intent.resolved_label ? ' - ' + escapeHtml(intent.resolved_label) : '');
                    handleConfirmation(action, '', { arguments: { original_intent: confirmed } });
                    input.disabled = false; input.focus(); return;
                }
                lastResult = { data: result, tool: intent.tool, query: query };
                const failedRequest = lastSent;
                const retry = retryActionsFor(result, () => { if (failedRequest) handleQuery(failedRequest); });
                appendMsg('bot', formatResult(result, false, intent.tool), retry, contextSnippetOf(result, intent.tool));
                resolveThirdpartyNames(chat.lastElementChild);
                break;
            }
        } catch (e) { if (loadingMsg.parentNode) loadingMsg.remove(); appendMsg('error', t('NetworkError') + ': ' + e.message); }
        input.disabled = false;
        input.focus();
    }

    // =========================================================================
    // UTILITIES (PDF & FORMATTING)
    // =========================================================================

    function downloadPdf(resultObj) {
        let reportTitle = "AI Report";
        if (resultObj.query && resultObj.query.length > 2) {
            let temp = resultObj.query.replace(/\b\w/g, l => l.toUpperCase());
            temp = temp.replace(/^(Show Me|Find|Get|List|Search For)\s+/i, '');
            if (temp.length > 2) reportTitle = temp.charAt(0).toUpperCase() + temp.slice(1);
        } else { reportTitle = resultObj.tool.replace(/_/g, ' '); }
        let filename = reportTitle.replace(/[\/\\:*?"<>|]/g, '_').substring(0, 50) + '_' + new Date().toISOString().slice(0, 10) + '.pdf';
        const form = document.createElement('form');
        form.method = 'POST'; form.action = epUrl('download_pdf.php'); form.target = '_blank';
        const addField = (name, val) => {
            const i = document.createElement('input'); i.type = 'hidden'; i.name = name; i.value = val; form.appendChild(i);
        };
        addField('content', JSON.stringify(resultObj.data));	// field name must stay 'content': allowlisted for GETPOST 'none' server-side
        addField('title', reportTitle);
        addField('filename', filename);
        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    }

    // ---- Tool-result presentation: raw API data -> localized display ----

    // API field -> translation key for table headers; fallback prettifies the raw name.
    const FIELD_LABELS = {
        ref: 'Ref', label: 'Label', name: 'ThirdParty', socid: 'Customer', fk_soc: 'Customer',
        paye: 'Paid', status: 'Status', type: 'Type', email: 'Email', town: 'Town',
        date: 'DateInvoice', datef: 'DateInvoice', date_lim_reglement: 'DateMaxPayment',
        total_ht: 'TotalHT', total_ttc: 'TotalTTC', total_tva: 'AmountVAT',
        remaintopay: 'RemainderToPay', price: 'Price', price_ttc: 'PriceTTC', tva_tx: 'VATRate',
        code_client: 'CustomerCode', code_fournisseur: 'SupplierCode', fournisseur: 'Supplier'
    };
    const MONEY_FIELDS = new Set(['total_ht', 'total_ttc', 'total_tva', 'total_localtax1', 'total_localtax2',
        'remaintopay', 'resteapayer', 'price', 'price_ttc', 'price_min', 'subprice', 'totalpaid',
        'multicurrency_total_ht', 'multicurrency_total_ttc', 'multicurrency_total_tva',
		'opp_amount', 'price_booth', 'price_registration'
		]);
    const isDateField = (k) => k === 'date' || k === 'datef' || k === 'tms' || /(^|_)date($|_)|_date$|^date_/.test(k);

    function fieldLabel(k) {
        if (FIELD_LABELS[k] && t(FIELD_LABELS[k]) !== FIELD_LABELS[k]) return t(FIELD_LABELS[k]);
        if (FIELD_LABELS[k]) return t(FIELD_LABELS[k]);
        return k.replace(/_/g, ' ').toUpperCase();
    }
    function fmtMoney(v) {
        const n = parseFloat(v);
        if (isNaN(n)) return v;
        try {
            return price(n, 'MT');
        } catch (e) { return n.toFixed(2); }
    }
    // Reduced date format, like dol_print_date(..., 'dayreduceformat'): year on 2 digits to save space in chat tables.
    function fmtDate(v) {
        const n = parseInt(v, 10);
        if (isNaN(n) || n < 100000000 || n > 9999999999) return v; // not a plausible unix timestamp
        try {
            return new Intl.DateTimeFormat(config.locale || undefined, { day: '2-digit', month: '2-digit', year: '2-digit' }).format(new Date(n * 1000));
        } catch (e) { return v; }
    }
    // Which Dolibarr card a tool's rows open; %id% replaced per row.
    const TOOL_CARD_URLS = {
        api_invoices: '/compta/facture/card.php?facid=%id%',
        api_thirdparties: '/societe/card.php?socid=%id%',
        api_products: '/product/card.php?id=%id%',
        api_proposals: '/comm/propal/card.php?id=%id%',
        api_orders: '/commande/card.php?id=%id%',
        api_projects: '/projet/card.php?id=%id%',
        api_contracts: '/contrat/card.php?id=%id%',
        api_tickets: '/ticket/card.php?id=%id%',
        api_supplier_invoices: '/fourn/facture/card.php?facid=%id%',
        api_supplier_orders: '/fourn/commande/card.php?id=%id%',
        api_supplier_proposals: '/supplier_proposal/card.php?id=%id%',
        api_categories: '/categories/card.php?id=%id%'
    };
    // The picto of the object a card link points to, like getNomUrl() does in
    // Dolibarr pages: the icons are the ones the core assigns to each object
    // (see the picto table of img_picto()), keyed on the card's path.
    const CARD_PICTOS = [
        ['/societe/', 'building'], ['/compta/facture/', 'file-invoice-dollar'], ['/fourn/facture/', 'file-invoice-dollar'],
        ['/commande/', 'file-invoice'], ['/fourn/commande/', 'file-invoice'], ['/comm/propal/', 'file-signature'],
        ['/supplier_proposal/', 'file-signature'], ['/product/', 'cube'], ['/projet/', 'project-diagram'],
        ['/contrat/', 'suitcase'], ['/ticket/', 'ticket-alt'], ['/fichinter/', 'ambulance'], ['/expedition/', 'dolly'],
        ['/reception/', 'dolly'], ['/user/', 'user'], ['/contact/', 'address-book'], ['/adherents/', 'user-alt'],
        ['/categories/', 'tag'], ['/document.php', 'file']
    ];
    function pictoForUrl(url) {
        if (typeof url !== 'string') return '';
        const hit = CARD_PICTOS.find((p) => url.indexOf(p[0]) >= 0);
        return hit ? `<span class="fas fa-${hit[1]} chat-picto"></span>` : '';
    }

    function cardUrlFor(tool, id) {
        if (!tool || !id) return null;
        const prefix = Object.keys(TOOL_CARD_URLS).find(p => tool.indexOf(p) === 0);
        return prefix ? (config.urlRoot || '') + TOOL_CARD_URLS[prefix].replace('%id%', encodeURIComponent(id)) : null;
    }
    // API payloads arrive with HTML entities already encoded ("Client
    // g&eacute;n&eacute;rique..."): decode them BEFORE escaping, or accented
    // names render as raw entities in the chat tables and answers.
    function decodeHtmlEntities(s) {
        if (typeof s !== 'string' || s.indexOf('&') === -1) return s;
        const ta = document.createElement('textarea');
        ta.innerHTML = s;
        return ta.value;
    }
    function formatCell(k, v) {
        if (v === null || v === undefined || v === '') return '-';
        // Hand-written report tools legitimately embed a single link around a
        // ref (server-generated, same origin): pass it through instead of
        // escaping it into visible markup.
        if (typeof v === 'string' && /^<a\s[^>]*href="[^"]*"[^>]*>[^<]*<\/a>$/i.test(v.trim())) return v.trim();
        if (typeof v === 'object') return '…'; // nested structures are noise in a summary table
        if (MONEY_FIELDS.has(k)) return fmtMoney(v);
        if (isDateField(k)) return fmtDate(v);
        if (k === 'paye') return (String(v) === '1' ? '✓' : '✗');
        return escapeHtml(decodeHtmlEntities(String(v)));
    }
    // socid -> customer name, resolved once per render through the bridge.
    async function resolveThirdpartyNames(container) {
        const cells = container.querySelectorAll('td[data-socid]');
        if (!cells.length) return;
        const ids = [...new Set([...cells].map(c => c.getAttribute('data-socid')))].filter(x => x && x !== '0');
        if (!ids.length) return;
        try {
            const filters = '(t.rowid:in:' + ids.join(',') + ')';	// UFS 'in' takes a bare list: value part may not contain parentheses
            const res = await fetch(epUrl('execute_tool.php'), {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ tool: 'api_thirdparties_list', arguments: { sqlfilters: filters, properties: 'id,name', limit: 100 } })
            });
            const rows = await res.json();
            if (!Array.isArray(rows)) return;
            const names = {};
            rows.forEach(r => { names[String(r.id)] = r.name; });
            cells.forEach(c => {
                const id = c.getAttribute('data-socid');
                if (names[id]) {
                    c.innerHTML = `<a href="${(config.urlRoot || '')}/societe/card.php?socid=${encodeURIComponent(id)}" target="_blank" class="chat-link">${escapeHtml(decodeHtmlEntities(names[id]))}</a>`;
                }
            });
            saveChatHistory();
        } catch (e) { /* names stay as ids */ }
    }

    function formatResult(data, isRecursive = false, toolName = '') {
        if (!data) return t('NoDataAvailable');
        if (data.error) return `<span style="color:red">${t('error')}: ${data.error}</span>`;
        let content = '';
        let isArray = false;
        let isObject = false;
        let objectUrl = null;

        if (Array.isArray(data)) {
            if (data.length === 0) return t('NoRecordFound');
            isArray = true;
            let keys = Object.keys(data[0]).filter(k => k !== 'url' && k !== 'rowid');
            content += '<div class="chat-table-wrap"><table class="chat-table"><thead><tr>';
            // Rows that are files (their url is a document.php download): the
            // "name" column is the file, not a third party, and the file gets a
            // magnifier opening Dolibarr's preview (same link, attachment=0).
            const isFileList = data.length > 0 && typeof data[0].url === 'string' && /\/document\.php\?/.test(data[0].url);
            keys.forEach(k => content += `<th>${(isFileList && k === 'name') ? escapeHtml(t('File')) : fieldLabel(k)}</th>`);
            content += '</tr></thead><tbody>';
            data.forEach(row => {
                content += '<tr>';
                keys.forEach(k => {
                    let val = formatCell(k, row[k]);
                    const cardUrl = row.url || cardUrlFor(toolName, row.id || row.rowid);
                    if (cardUrl && val.indexOf('<a ') !== 0 && ['ref', 'name', 'nom', 'label', 'customer', 'supplier', 'subject'].includes(k)) {
                        val = `<a href="${cardUrl}" target="_blank" class="chat-link">${pictoForUrl(cardUrl)}${val}</a>`;
                        if (isFileList && k === 'name') {
                            const previewUrl = cardUrl + (cardUrl.indexOf('attachment=') >= 0 ? '' : '&attachment=0');
                            val += ` <a href="${previewUrl}" target="_blank" class="chat-link chat-preview" title="${escapeHtml(t('Preview'))}"><span class="fas fa-search-plus"></span></a>`;
                        }
                    }
                    if ((k === 'socid' || k === 'fk_soc') && row[k]) {
                        content += `<td data-socid="${escapeHtml(String(row[k]))}">${val}</td>`;
                    } else {
                        content += `<td>${val}</td>`;
                    }
                });
                content += '</tr>';
            });
            content += '</tbody></table></div>';
        }
        else if (typeof data === 'object') {
            isObject = true;
            // A create answers with its new id and no url: build the card link from
            // the tool name, so the user can open what was just written.
            objectUrl = data.url || cardUrlFor(toolName, data.id || data.rowid) || null;
            content += '<div class="chat-object"><ul>';
            let nested = '';
            for (const [key, value] of Object.entries(data)) {
                if (key === 'url' || key === 'chart') continue;
                if (typeof value !== 'object') { content += `<li><strong>${fieldLabel(key)}:</strong> ${formatCell(key, value)}</li>`; }
                // A list inside the answer is the answer: a tool that wraps its rows
                // in {count, offset, limit, results} would otherwise show only the
                // counters, and the rows would never reach the user.
                else if (Array.isArray(value) && value.length && typeof value[0] === 'object') {
                    nested += formatResult(value, true, toolName);
                }
            }
            content += '</ul></div>';
            content += nested;
            if (data.chart && Array.isArray(data.chart.labels) && Array.isArray(data.chart.datasets)) {
                const cid = 'ai-chart-' + Math.random().toString(36).slice(2, 10);
                content += `<div class="chat-chart-wrap"><canvas id="${cid}"></canvas></div>`;
                setTimeout(() => aiRenderChart(cid, data.chart, config.urlRoot || config.baseUrl || ''), 0);
            }
        }
        else { return String(data); }

        if (!isRecursive) {
            let toolbarContent = '';
            if (isArray) {
                toolbarContent = `<button class="msg-action-btn" onclick="this.closest('.ai-chat-container').dispatchEvent(new CustomEvent('triggerPdf'))" title="${t('AIPdfReport')}"><span class="fas fa-file-pdf"></span> ${t('AIPdfReport')}</button>`;
            } else if (isObject && objectUrl) {
                toolbarContent = `<a href="${objectUrl}" target="_blank" class="msg-action-btn primary" title="${t('OpenVerb')}"><span class="fas fa-external-link-alt"></span> ${t('OpenVerb')}</a>`;
            }
            if (toolbarContent) { content += `<div class="msg-toolbar">${toolbarContent}</div>`; }
        }
        return content;
    }

    // Resume the conversation in progress: the id in the URL (popover -> full
    // page hand-over) wins, else the one this browser was last in (full page ->
    // popover, or the popover reopened on another page). Ownership is
    // re-checked server-side on load; a deleted one simply stays blank. Without a
    // server-side conversation, the copy this tab kept across pages is restored.
    let openConv = parseInt(container.dataset.aiOpenConversation, 10) || 0;
    if (!openConv) { try { openConv = parseInt(localStorage.getItem('aiCurrentConversation'), 10) || 0; } catch (e) { openConv = 0; } }
    if (openConv > 0) {
        loadConversation(openConv);
    } else {
        restoreChatHistory();
    }
}

// Renders a report chart payload ({type, labels, datasets}) on a canvas using the
// bundled Chart.js ESM build — imported lazily so the chat carries no extra cost
// until a tool actually returns a chart.
async function aiRenderChart(canvasId, chartCfg, urlRoot) {
    const el = document.getElementById(canvasId);
    if (!el) return;
    try {
        // Resolved against location.origin so the URL never inherits user:pass@ from the page (e.g. DoliDroid)
        const mod = await import(new URL(urlRoot + '/includes/nnnick/chartjs/dist/chart.esm.js', window.location.origin).href);
        const Chart = mod.Chart || mod.default;
        if (!Chart) return;
        el.parentElement.classList.add('chat-chart-ready');
        new Chart(el, {
            type: chartCfg.type || 'bar',
            data: { labels: chartCfg.labels, datasets: chartCfg.datasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: chartCfg.datasets.length > 1 } }
            }
        });
    } catch (e) { /* chart is optional sugar: the table is already rendered */ }
}

// Auto-init for server-rendered containers (standalone page mode). The topbar
// popover path calls initAiAssistant() explicitly after the AJAX injection.
// Module scripts are deferred, so the DOM may already be ready when this runs.
function aiAutoInit() {
    document.querySelectorAll('.ai-chat-container[data-ai-autoinit]').forEach((el) => initAiAssistant(el));
}
if (document.readyState !== 'loading') {
    aiAutoInit();
} else {
    document.addEventListener('DOMContentLoaded', aiAutoInit);
}
