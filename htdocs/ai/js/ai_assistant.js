/**
 * \file htdocs/ai/js/ai_assistant.js
 * \brief Frontend logic for the AI Assistant
 * \ingroup ai
 */

document.addEventListener('DOMContentLoaded', () => {

    // =========================================================================
    // CONFIGURATION & INITIALIZATION
    // =========================================================================

    // 1. Load configuration passed from PHP
    const config = window.AI_CONFIG || {};
    const CONFIG_MODE = config.mode || 'text';
    const aiLabels = config.labels || {};

    // Helper for safe translation retrieval
    const t = (key) => aiLabels[key] || key;

    // 2. Select DOM Elements
    const micBtn = document.getElementById('mic-btn');
    const micWrapper = document.getElementById('mic-wrapper');

    const uploadBtn = document.getElementById('upload-btn');
    const uploadWrapper = document.getElementById('upload-wrapper');
    const fileInput = document.getElementById('file-upload');

    const clearBtn = document.getElementById('clear-btn');
    const engineSelect = document.getElementById('engine-select');
    const input = document.getElementById('user-input');
    const chat = document.getElementById('chat-history');
    const statusBar = document.getElementById('status-bar');
    const sendBtn = document.getElementById('send-btn');

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
    input.focus();
    engineSelect.value = CONFIG_MODE;
    updateInterfaceMode(CONFIG_MODE);

    // Initialize Doc Parsing UI Listeners
    initDocParsingUI();

    // Listen for custom event to trigger PDF download from buttons
    document.addEventListener('triggerPdf', () => {
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
        const input = document.getElementById('user-input');
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
    }

    // Handle Submit (Enter) vs New Line (Shift+Enter)
    input.addEventListener('keydown', (e) => {
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
     * Update UI visibility based on selected engine
     * @param {string} mode - 'text', 'cloud', 'whisper', 'local_docs', 'cloud_docs'
     */
    function updateInterfaceMode(mode) {
        // Reset all wrappers
        micWrapper.classList.add('hidden');
        uploadWrapper.classList.add('hidden');

        if (mode === 'text') {
            input.placeholder = t('TypeYourQuestion');
            statusBar.innerText = '';
        }
        else if (mode === 'cloud' || mode === 'whisper') {
            // Voice Modes
            micWrapper.classList.remove('hidden');
            input.placeholder = 'Type or speak...';
            initEngine(mode);
        }
        else if (mode === 'local_docs' || mode === 'cloud_docs') {
            // Document Modes
            uploadWrapper.classList.remove('hidden');
            input.placeholder = mode === 'local_docs'
                ? t('UploadLocalDoc')
                : t('UploadCloudDoc');
        }
    }

    // Clear Chat History
    clearBtn.addEventListener('click', () => {
        if (confirm(t('ClearChatHistoryTitle'))) {
            chat.innerHTML = `<div class="msg system">${t('HistoryCleared')}</div>`;
            lastResult = { data: null, tool: '', query: '' };
            clarificationContext = null;
            input.focus();
        }
    });

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
            const file = e.target.files[0];
            if (!file) return;

            const mode = engineSelect.value;
            statusBar.innerText = t('ProcessingFile') + ` ${file.name} (${mode})...`;

            try {
                let contentPayload = "";

                if (mode === 'local_docs') {
                    contentPayload = await processLocalFile(file);
                } else if (mode === 'cloud_docs') {
                    contentPayload = await processCloudFile(file);
                }

                if (!contentPayload || contentPayload.length < 5) {
                    throw new Error(t('UnsupportedFileType'));
                }

                const docContext = `${t('DocContextIntro')}

		${contentPayload}

		--- ${t('DocContextOutro')} ---
		`;

                input.value = docContext;
                setTimeout(autoResizeInput, 0); // Trigger resize so the text is visible

                // Update placeholder to guide the user
                //input.placeholder = "Document loaded. Ask something (e.g., 'Summarize this', 'Create an invoice for line 2')...";

                // Focus input so user can type immediately
                input.focus();
                statusBar.innerText = t('DocLoaded');

            } catch (err) {
                console.error(err);
                statusBar.innerText = t('Error') + ": " + err.message;
            }

            fileInput.value = '';
        });
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
        const base64 = await fileToBase64(file);
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
            cloudRecognition.onend = () => { if (isRecording && engineSelect.value === 'cloud') stopRecording(); };
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
                setMicState('idle');
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
        else if (state === 'processing') { micBtn.classList.add('processing'); micBtn.innerHTML = '<span class="fa fa-circle-o-notch fa-spin"></span>'; }
        else { micBtn.innerHTML = '<span class="fa fa-microphone"></span>'; micBtn.title = "Start Recording"; }
    }

    // =========================================================================
    // CHAT UI RENDERING
    // =========================================================================

    function appendMsg(type, html, actions = null) {
        const div = document.createElement('div');
        div.className = `msg ${type}`;
        div.innerHTML = html;
        if (actions) {
            const actionsDiv = document.createElement('div'); actionsDiv.className = 'msg-actions';
            actions.forEach(action => {
                const btn = document.createElement('button');
                btn.className = `msg-action-btn ${action.class || ''}`;
                btn.innerHTML = action.icon ? `<span class="fa ${action.icon}"></span> ${action.text}` : action.text;
                btn.onclick = action.onclick;
                actionsDiv.appendChild(btn);
            });
            div.appendChild(actionsDiv);
        }
        chat.appendChild(div);
        chat.scrollTop = chat.scrollHeight;
    }

    function handleClarification(question, context) {
        clarificationContext = context;
        let html = `<div><strong>${question}</strong></div><input type="text" id="clarification-input" placeholder="${t('TypeResponse')}" style="width:100%; margin-top:10px; padding:8px; border:1px solid #ccc; border-radius:4px;">`;
        const actions = [
            {
                text: t('Submit'), class: 'primary', icon: 'fa-check', onclick: () => {
                    const response = document.getElementById('clarification-input').value;
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
        const clarInput = document.getElementById('clarification-input');
        if (clarInput) clarInput.focus();
    }

    function handleResponse(message) {
        if (!message) message = t('EmptyAIResponse');
        appendMsg('bot', message);
    }

    function handleConfirmation(action, details, originalIntent) {
        pendingIntent = originalIntent.arguments.original_intent || originalIntent;
        const toolName = pendingIntent.tool || 'unknown tool';
        let template = t('ConfirmAiAction');
        let messageHtml = template.replace('%1$s', `<strong>${action}</strong>`).replace('%2$s', `<strong>${toolName}</strong>`);
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
        if (confirmationRecognition) try { confirmationRecognition.stop(); } catch (e) { }
        const msg = chat.lastElementChild;
        if (msg && msg.classList.contains('confirmation')) msg.remove();
        appendMsg('system', t('ActionCancelled'));
        pendingIntent = null;
    }

    function showVoiceFeedback(message) {
        const dialog = document.querySelector('.confirmation-dialog .confirmation-body');
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
        try {
            const toolRes = await fetch('execute_tool.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(pendingIntent)
            });
            const result = await toolRes.json();
            loadingMsg.remove();
            lastResult = { data: result, tool: pendingIntent.tool, query: pendingIntent.query || '' };
            appendMsg('bot', formatResult(result));
            pendingIntent = null;
        } catch (e) { loadingMsg.remove(); appendMsg('error', t('NetworkError') + ': ' + e.message); }
        input.disabled = false;
        input.focus();
    }

    async function handleQuery() {
        const query = input.value.trim();
        if (!query) return;
        appendMsg('user', query);
        input.value = '';
        input.style.height = '44px';
        input.disabled = true;
        appendMsg('system', '<span class="fa fa-circle-notch fa-spin"></span> Thinking...');
        const loadingMsg = chat.lastElementChild;
        try {
            const intentRes = await fetch('parse_intent.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ query: query })
            });
            const intent = await intentRes.json();
            loadingMsg.remove();
            if (intent.error) { appendMsg('error', t('AIError') + ': ' + intent.error); input.disabled = false; input.focus(); return; }

            if (intent.tool === 'ask_for_clarification') { handleClarification(intent.arguments.question, query); input.disabled = false; input.focus(); return; }
            if (intent.tool === 'respond_to_user' || intent.tool === 'reject_general_question') { const msg = (intent.arguments && intent.arguments.message) ? intent.arguments.message : t('EmptyAIResponse'); handleResponse(msg); input.disabled = false; input.focus(); return; }
            if (intent.tool === 'ask_for_confirmation') { handleConfirmation(intent.arguments.action, intent.arguments.details, intent); input.disabled = false; input.focus(); return; }
            if (intent.tool === 'generate_navigation_url') {
                appendMsg('system', t('GeneratingLink'));
                const loadingNav = chat.lastElementChild;
                const navRes = await fetch('execute_tool.php', {
                    method: 'POST', headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(intent)
                });
                const nav = await navRes.json();
                loadingNav.remove();
                if (nav.error) { appendMsg('error', nav.error); }
                else { const html = `${t('Found')}: <a href="${nav.url}" target="_blank" class="msg-action-btn primary"><span class="fa fa-external-link"></span> ${t('Open')} ${nav.description}</a>`; appendMsg('bot', html); }
                input.disabled = false; input.focus(); return;
            }

            appendMsg('system', t('FetchingData'));
            const loadingData = chat.lastElementChild;
            const toolRes = await fetch('execute_tool.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(intent)
            });
            const result = await toolRes.json();
            loadingData.remove();
            lastResult = { data: result, tool: intent.tool, query: query };
            appendMsg('bot', formatResult(result));
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
        form.method = 'POST'; form.action = 'download_pdf.php'; form.target = '_blank';
        const addField = (name, val) => {
            const i = document.createElement('input'); i.type = 'hidden'; i.name = name; i.value = val; form.appendChild(i);
        };
        addField('data', JSON.stringify(resultObj.data));
        addField('title', reportTitle);
        addField('filename', filename);
        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    }

    function formatResult(data, isRecursive = false) {
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
            content += '<div style="overflow-x:auto;"><table class="chat-table"><thead><tr>';
            keys.forEach(k => content += `<th>${k.replace(/_/g, ' ').toUpperCase()}</th>`);
            content += '</tr></thead><tbody>';
            data.forEach(row => {
                content += '<tr>';
                keys.forEach(k => {
                    let val = row[k];
                    if (row.url && ['ref', 'name', 'nom', 'label', 'customer', 'supplier', 'subject'].includes(k)) {
                        val = `<a href="${row.url}" target="_blank" style="color:#0055aa; text-decoration:none; font-weight:bold;">${val}</a>`;
                    }
                    content += `<td>${val}</td>`;
                });
                content += '</tr>';
            });
            content += '</tbody></table></div>';
        }
        else if (typeof data === 'object') {
            isObject = true;
            objectUrl = data.url || null;
            content += '<div style="background:#f9f9f9; padding:10px; border-radius:5px;"><ul style="padding-left:20px; margin:0;">';
            for (const [key, value] of Object.entries(data)) {
                if (key === 'url') continue;
                if (typeof value !== 'object') { content += `<li><strong>${key.replace(/_/g, ' ')}:</strong> ${value}</li>`; }
            }
            content += '</ul></div>';
        }
        else { return String(data); }

        if (!isRecursive) {
            let toolbarContent = '';
            if (isArray) {
                toolbarContent = `<button class="msg-action-btn" onclick="document.dispatchEvent(new CustomEvent('triggerPdf'))" title="${t('DownloadPdf')}"><span class="fa fa-file-pdf-o"></span> ${t('downloadPdf')}</button>`;
            } else if (isObject && objectUrl) {
                toolbarContent = `<a href="${objectUrl}" target="_blank" class="msg-action-btn primary" style="display:inline-flex; align-items:center; gap:5px; text-decoration:none;" title="${t('OpenVerb')}"><span class="fa fa-external-link"></span> ${t('openRecord')}</a>`;
            }
            if (toolbarContent) { content += `<div style="margin-top:8px; border-top:1px solid #eee; padding-top:5px; text-align:right;">${toolbarContent}</div>`; }
        }
        return content;
    }
});
