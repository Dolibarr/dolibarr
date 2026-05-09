document.addEventListener('Dolibarr:Init', function(e) {
	/**
	 * Dolibarr.tools.langs
	 * --------------------
	 * Manage translations in JS context with IndexedDB cache, multi-locale support and fallback.
	 * Parallel loading of language files for performance.
	 * Automatic cache invalidation if Dolibarr version changes.
	 *
	 * Require Dolibarr context vars
	 * DOL_LANG_INTERFACE_URL, MAIN_LANG_DEFAULT, DOL_VERSION
	 *
	 */
	const langs = function() {

		const CACHE_MSTIME = 86400000;
		let currentLocale = Dolibarr.getContextVar('MAIN_LANG_DEFAULT', 'en_US');
		let translations = {}; // { en_US: {KEY: TEXT}, fr_FR: {...} }
		let domainsLoaded = {}; // { en_US: Set(['main','other']), fr_FR: Set([...]) }
		if (!domainsLoaded[currentLocale]) domainsLoaded[currentLocale] = new Set();
		let domainsRequested = new Set();     // Set of domain names that were requested at least once



		/**
		 * Open or create IndexedDB for caching translations
		 * @returns {Promise<IDBDatabase>}
		 */
		async function openDB(clear = false) {

			// Generate a unique name per instance
			const dbName = await getSafeDbName();

			return new Promise((resolve, reject) => {
				const request = indexedDB.open(dbName, 1);
				request.onupgradeneeded = e => {
					const db = e.target.result;
					if (!db.objectStoreNames.contains('langs')) db.createObjectStore('langs');
				};
				request.onsuccess = async () => {
					const db = request.result;
					if (clear) {
						const tx = db.transaction('langs', 'readwrite');
						tx.objectStore('langs').clear();
					}
					resolve(db);
				};
				request.onerror = () => reject(request.error);
			});
		}

		// Create a secure DB name
		async function getSafeDbName() {
			const path = Dolibarr.getContextVar('DOL_URL_ROOT', Dolibarr.getContextVar('DOL_LANG_INTERFACE_URL', window.location.hostname));
			const hashedPath = await hashString(path);
			return `DolibarrLangs_${hashedPath}`;
		}

		// Simple function to generate a hash from a string
		async function hashString(str) {
			const encoder = new TextEncoder();
			const data = encoder.encode(str);
			const hashBuffer = await crypto.subtle.digest('SHA-1', data);
			const hashArray = Array.from(new Uint8Array(hashBuffer));
			return hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
		}

		/**
		 * Get cached translation for a domain + locale
		 * @param {string} domain
		 * @param {string} locale
		 * @returns {Promise<Object|null>}
		 */
		async function getCache(domain, locale) {
			try {
				const db = await openDB();
				const tx = db.transaction('langs', 'readonly');
				const store = tx.objectStore('langs');
				return new Promise((resolve, reject) => {
					const request = store.get(`${domain}@${locale}`);
					request.onsuccess = () => resolve(request.result);
					request.onerror = () => reject(request.error);
				});
			} catch (err) {
				return null;
			}
		}

		/**
		 * Set cached translation for a domain + locale
		 * @param {string} domain
		 * @param {string} locale
		 * @param {Object} data
		 */
		async function setCache(domain, locale, data) {
			try {
				const db = await openDB();
				const tx = db.transaction('langs', 'readwrite');
				const store = tx.objectStore('langs');
				const dolibarrVersion = Dolibarr.getContextVar('DOL_VERSION', 0);
				await store.put({ key: `${domain}@${locale}`, data, timestamp: Date.now(), dolibarrVersion }, `${domain}@${locale}`);
			} catch (err) {
				// fail silently
				Dolibarr.log('Save langs in cache fail');
			}
		}

		/**
		 * Clear all cached translations in IndexedDB and in-memory
		 */
		async function clearCache(clearMemory = false) {
			if(clearMemory) {
				translations = {};
				domainsLoaded = {};
			}

			try {
				const db = await openDB(true);
				const tx = db.transaction('langs', 'readwrite');
				const store = tx.objectStore('langs');
				await store.clear();

				// Delete database
				const dbName = await getSafeDbName();
				const deleteRequest = indexedDB.deleteDatabase(dbName);
				deleteRequest.onsuccess = () => Dolibarr.log('Dolibarr.tools.langs: database deleted');
				deleteRequest.onerror = () => console.error('Dolibarr.tools.langs:Failed to delete DB', deleteRequest.error);
				deleteRequest.onblocked = () => console.warn('Dolibarr.tools.langs: DB deletion blocked, maybe open connections exist');

				// Try Delete all langs database
				deleteAllDolibarrLangsDbs();

				Dolibarr.log('Dolibarr.tools.langs: cache cleared');
			} catch (err) {
				console.error('Dolibarr.tools.langs: failed to clear cache', err);
			}
		}

		async function deleteAllDolibarrLangsDbs() {
			if (!indexedDB.databases) {
				console.warn("Your browser does not support indexedDB.databases(), bulk deletion is not possible.");
				return;
			}

			const dbs = await indexedDB.databases();
			const dolibarrDbs = dbs.filter(db => db.name && db.name.startsWith('DolibarrLangs_'));

			for (const dbInfo of dolibarrDbs) {
				const deleteRequest = indexedDB.deleteDatabase(dbInfo.name);
				deleteRequest.onsuccess = () => console.log(`Database ${dbInfo.name} deleted`);
				deleteRequest.onerror = () => console.error(`Failed to delete database ${dbInfo.name}`, deleteRequest.error);
				deleteRequest.onblocked = () => console.warn(`Deletion of database ${dbInfo.name} blocked, maybe open connections exist`);
			}
		}

		/**
		 * Load a single locale from cache or fetch
		 * @param {string} domain
		 * @param {string} locale
		 * @returns {Promise<Object>} translation object
		 */
		async function loadLocale(domain, locale) {
			const cache = await getCache(domain, locale);
			const now = Date.now();
			const dolibarrVersion = Dolibarr.getContextVar('DOL_VERSION', 0);

			if (cache && cache.data && (now - cache.timestamp < CACHE_MSTIME) && cache.dolibarrVersion === dolibarrVersion) {
				Dolibarr.log('Langs tool : Load lang from cache');
				return cache.data;
			}

			const langInterfaceUrl = Dolibarr.getContextVar('DOL_LANG_INTERFACE_URL', false);
			if(!langInterfaceUrl) {
				console.error('Dolibarr langs: missing DOL_LANG_INTERFACE_URL')
				return;
			}

			Dolibarr.log('Langs tool : Load lang from interface');
			const params = new URLSearchParams({ domain, local: locale });
			const resp = await fetch(`${langInterfaceUrl}?${params.toString()}`);
			const json = await resp.json();
			const data = json[locale] || {};
			await setCache(domain, locale, data);
			return data;
		}

		/**
		 * Load translations for a domain (multiple locales)
		 * @param {string} domain
		 * @param {string} locales - comma-separated list
		 * @returns {Promise<Object>}
		 */
		async function load(domain, locales = currentLocale) {
			const list = locales.split(',');

			// flag domaine as requested for future load when local change
			domainsRequested.add(domain);

			const results = await Promise.all(list.map(loc => loadLocale(domain, loc)));

			list.forEach((loc, i) => {
				if (!translations[loc]) translations[loc] = {};
				Object.assign(translations[loc], results[i]);

				if (!domainsLoaded[loc]) domainsLoaded[loc] = new Set();
				domainsLoaded[loc].add(domain);
			});

			return translations;
		}

		/**
		 * Set the current locale to use for translations
		 * @param {string} locale
		 */
		async function setLocale(locale, noDomainReload = false) {
			if (!locale || locale === currentLocale) return;

			const prev = currentLocale;
			currentLocale = locale;

			if (!domainsLoaded[locale]) domainsLoaded[locale] = new Set();

			if (!noDomainReload) {
				// priorité : domainsLoaded[prev], sinon fallback sur domainsRequested
				let toReload = Array.from(domainsLoaded[prev] || []);
				if (toReload.length === 0) {
					// aucun domaine marqué comme "loaded" pour prev : utiliser la liste des domaines demandés
					toReload = Array.from(domainsRequested);
				}

				for (const domain of toReload) {
					// load(domain, locale) accepte le param locale ; l'appel charge et met domainsLoaded
					if (domainsLoaded[locale].size === 0) {
						await load(domain, locale);
					}
				}
			}

			Dolibarr.log(`Locale changed: ${prev} -> ${locale}`);
		}


		/**
		 * Translate a key using current locale
		 * Supports placeholders like %s, %d, %f (simple sprintf)
		 * @param {string} key
		 * @param  {...any} args
		 * @returns {string}
		 */
		function trans(key, ...args) {
			const text = translations[currentLocale]?.[key] || key;
			if (!args.length) return text;

			// Utilisation de la fonction sprintf pour le formatage
			return sprintf(text, ...args);
		}

		/**
		 * Translate a key using current locale
		 * Supports placeholders like %s, %d, %f (simple sprintf)
		 * @param {string} key
		 * @param  {...any} args
		 * @returns {string}
		 */
		function transNoEntities(key, ...args) {
			let str = trans(key, ...args);
			let txt = document.createElement('textarea');
			txt.innerHTML = str;
			return txt.value;
		}


		function sprintf(fmt, ...args) {
			let i = 0;
			return fmt.replace(/%[%bcdeEfFgGosuxX]/g, (match) => {
				if (match === '%%') return '%';
				const arg = args[i++];
				switch (match) {
					case '%s': return String(arg);
					case '%d':
					case '%u': return Number(arg);
					case '%f':
					case '%F': return parseFloat(arg);
					case '%b': return Number(arg).toString(2);
					case '%o': return Number(arg).toString(8);
					case '%x': return Number(arg).toString(16);
					case '%X': return Number(arg).toString(16).toUpperCase();
					case '%c': return String.fromCharCode(Number(arg));
					default: return match;
				}
			});
		}


		/**
		 * Clear cache automatically under specific conditions
		 * Support for Hard Reload (Shift or Ctrl) and Debug Mode
		 */
		(async () => {
			const navEntries = performance?.getEntriesByType?.("navigation");
			const isReload = navEntries?.[0]?.type === "reload" || performance.navigation.type === 1;

			if (isReload) {
				window.addEventListener('keydown', async (e) => {
					// Check if user is holding Shift OR Control OR if Dolibarr Debug is enabled
					if (e.shiftKey || e.ctrlKey) {
						await clearCache(true);
						Dolibarr.log('Langs tool: Cache cleared (Reason: Hard Reload or Debug Mode)');
					}
				}, { once: true });
			}
		})();

		return {
			load,
			clearCache,
			setLocale,
			trans,
			transNoEntities,
			get currentLocale() { return currentLocale; }
		};
	};

	Dolibarr.defineTool('langs',langs());
});
