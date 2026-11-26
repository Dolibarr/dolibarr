document.addEventListener('Dolibarr:Init', function(e) {
	/**
	 * Dolibarr.tools.langs
	 * --------------------
	 * Manage translations in JS context with IndexedDB cache, multi-locale support and fallback.
	 * Parallel loading of language files for performance.
	 * Automatic cache invalidation if Dolibarr version changes.
	 * The cache is checked again in background every few minutes (HTTP ETag, 304 when unchanged): the translations
	 * of a module change with its files, without any change of the Dolibarr version.
	 *
	 * Require Dolibarr context vars
	 * DOL_LANG_INTERFACE_URL, MAIN_LANG_DEFAULT, DOL_VERSION
	 *
	 */
	const langs = function() {

		const CACHE_MSTIME = 86400000;
		const REVALIDATE_MSTIME = 300000;
		let revalidating = new Set(); // domain@locale being checked in background
		let currentLocale = Dolibarr.getContextVar('MAIN_LANG_DEFAULT', 'en_US');
		let translations = {}; // { en_US: {KEY: TEXT}, fr_FR: {...} }
		let domainsLoaded = {}; // { en_US: Set(['main','other']), fr_FR: Set([...]) }
		if (!domainsLoaded[currentLocale]) domainsLoaded[currentLocale] = new Set();
		let domainsRequested = new Set();     // Set of domain names that were requested at least once
		let dbPromise = null; // Shared IndexedDB connection, see getDB()
		let currentDb = null;



		/**
		 * Open or create IndexedDB for caching translations
		 * @returns {Promise<IDBDatabase>}
		 */
		async function openDB() {

			// Generate a unique name per instance
			const dbName = await getSafeDbName();

			return new Promise((resolve, reject) => {
				const request = indexedDB.open(dbName, 1);
				request.onupgradeneeded = e => {
					const db = e.target.result;
					if (!db.objectStoreNames.contains('langs')) db.createObjectStore('langs');
				};
				request.onsuccess = () => {
					const db = request.result;
					// Release the connection if another tab wants to delete or upgrade the database
					db.onversionchange = () => closeDB(db);
					resolve(db);
				};
				request.onerror = () => reject(request.error);
			});
		}

		/**
		 * Get the shared IndexedDB connection, opened only once
		 * @returns {Promise<IDBDatabase>}
		 */
		function getDB() {
			if (!dbPromise) {
				dbPromise = openDB().then(db => (currentDb = db)).catch(err => {
					dbPromise = null;
					throw err;
				});
			}
			return dbPromise;
		}

		/**
		 * Close the shared IndexedDB connection
		 * @param {IDBDatabase} db
		 */
		function closeDB(db) {
			db.close();
			if (db === currentDb) {
				currentDb = null;
				dbPromise = null;
			}
		}

		// Create a secure DB name
		async function getSafeDbName() {
			const path = Dolibarr.getContextVar('DOL_URL_ROOT', Dolibarr.getContextVar('DOL_LANG_INTERFACE_URL', window.location.hostname));
			const hashedPath = await hashString(path);
			return `DolibarrLangs_${hashedPath}`;
		}

		function stringToHex(str) {
		    return [...new TextEncoder().encode(str)]
		        .map(b => b.toString(16).padStart(2, "0"))
		        .join("");
		}

		// Simple function to generate a hash from a string
		async function hashString(str) {
			const encoder = new TextEncoder();
			const data = encoder.encode(str);
			if (globalThis.crypto?.subtle) {
				const hashBuffer = await crypto.subtle.digest('SHA-1', data);
				const hashArray = Array.from(new Uint8Array(hashBuffer));
				return hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
			} else {
				const hashBuffer = stringToHex(data);
				return hashBuffer;
			}
		}

		/**
		 * Get cached translation for a domain + locale
		 * The date of the last check is kept in a small record of its own ("checked"), so that a check without any
		 * change does not write the translations again.
		 * @param {string} domain
		 * @param {string} locale
		 * @returns {Promise<Object|null>} { key, data, timestamp, dolibarrVersion, etag, checked }
		 */
		async function getCache(domain, locale) {
			try {
				const db = await getDB();
				const tx = db.transaction('langs', 'readonly');
				const store = tx.objectStore('langs');
				const read = (key) => new Promise((resolve, reject) => {
					const request = store.get(key);
					request.onsuccess = () => resolve(request.result);
					request.onerror = () => reject(request.error);
				});
				const [cache, meta] = await Promise.all([read(`${domain}@${locale}`), read(`${domain}@${locale}|checked`)]);
				if (!cache) return null;
				cache.checked = Math.max(cache.timestamp || 0, (meta && meta.etag === cache.etag && meta.checked) || 0);
				return cache;
			} catch (err) {
				return null;
			}
		}

		/**
		 * Set cached translation for a domain + locale
		 * @param {string} domain
		 * @param {string} locale
		 * @param {Object} data
		 * @param {string|null} etag ETag of the answer of the translations interface
		 */
		async function setCache(domain, locale, data, etag = null) {
			try {
				const db = await getDB();
				const tx = db.transaction('langs', 'readwrite');
				const store = tx.objectStore('langs');
				const dolibarrVersion = Dolibarr.getContextVar('DOL_VERSION', 0);
				await store.put({ key: `${domain}@${locale}`, data, timestamp: Date.now(), dolibarrVersion, etag }, `${domain}@${locale}`);
			} catch (err) {
				// fail silently
				Dolibarr.log('Save langs in cache fail');
			}
		}

		/**
		 * Save the date of a check that found the cached translations unchanged
		 * @param {string} domain
		 * @param {string} locale
		 * @param {string} etag
		 */
		async function setCacheChecked(domain, locale, etag) {
			try {
				const db = await getDB();
				const tx = db.transaction('langs', 'readwrite');
				const store = tx.objectStore('langs');
				await store.put({ checked: Date.now(), etag }, `${domain}@${locale}|checked`);
			} catch (err) {
				// fail silently
				Dolibarr.log('Save langs check date in cache fail');
			}
		}

		/**
		 * Clear all cached translations in IndexedDB and in-memory
		 */
		async function clearCache(clearMemory = false, rebuildDatabase = false) {


			try {
				const db = await getDB();
				await new Promise((resolve, reject) => {
					const tx = db.transaction('langs', 'readwrite');
					const store = tx.objectStore('langs');

					store.clear();

					tx.oncomplete = resolve;
					tx.onerror = () => reject(tx.error);
					tx.onabort = () => reject(tx.error);
				});

				// Delete database
				if(rebuildDatabase) {
					closeDB(db);
					const dbName = await getSafeDbName();
					await new Promise((resolve, reject) => {
						const deleteRequest = indexedDB.deleteDatabase(dbName);

						deleteRequest.onsuccess = () => {
							Dolibarr.log('tools: langs: database deleted');
							if (clearMemory) {
								translations = {};
								domainsLoaded = {};
							}
							resolve();
						};

						deleteRequest.onerror = () => {
							console.error('Dolibarr: tools: langs Failed to delete DB', deleteRequest.error);
							reject(deleteRequest.error);
						};

						deleteRequest.onblocked = () => {
							console.warn('Dolibarr: tools: langs DB deletion blocked, maybe open connections exist');
						};
					});

					// Try Delete all langs database
					deleteAllDolibarrLangsDbs();
				}

				Dolibarr.log('tools: langs: cache cleared');
				Dolibarr.executeHook('tools:langs:CacheCleared');
			} catch (err) {
				console.error('Dolibarr: tools: langs failed to clear cache', err);
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
				deleteRequest.onsuccess = () => Dolibarr.log(`Database ${dbInfo.name} deleted`);
				deleteRequest.onerror = () => console.error(`Dolibarr: tools: langs Failed to delete database ${dbInfo.name}`, deleteRequest.error);
				deleteRequest.onblocked = () => console.warn(`Dolibarr: tools: langs Deletion of database ${dbInfo.name} blocked, maybe open connections exist`);
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

			if (cache && cache.data && (now - cache.checked < CACHE_MSTIME) && cache.dolibarrVersion === dolibarrVersion) {
				Dolibarr.log('tools: langs: Load lang from cache');
				if (now - cache.checked > REVALIDATE_MSTIME) {
					revalidateLocale(domain, locale, cache.etag);
				}
				return cache.data;
			}

			Dolibarr.log('tools: langs: Load lang from interface');
			const result = await fetchLocale(domain, locale, cache && cache.dolibarrVersion === dolibarrVersion ? cache.etag : null);
			if (!result) {
				return (cache && cache.dolibarrVersion === dolibarrVersion && cache.data) || {};
			}
			if (result.unchanged) {
				await setCacheChecked(domain, locale, result.etag);
				return cache.data;
			}
			await setCache(domain, locale, result.data, result.etag);
			return result.data;
		}

		/**
		 * Get the translations of a domain from the server
		 * The browser checks its HTTP copy each time (no-cache): unchanged translations come back as a 304 without body,
		 * and when the ETag is the one of the cached translations, the body is not even read.
		 * @param {string} domain
		 * @param {string} locale
		 * @param {string|null} knownEtag ETag of the cached translations
		 * @returns {Promise<Object|null>} { data, etag } or { unchanged: true, etag }, null if error
		 */
		async function fetchLocale(domain, locale, knownEtag = null) {
			const langInterfaceUrl = Dolibarr.getContextVar('DOL_LANG_INTERFACE_URL', false);
			if(!langInterfaceUrl) {
				console.error('Dolibarr: tools: langs missing DOL_LANG_INTERFACE_URL')
				return null;
			}

			try {
				const params = new URLSearchParams({ domain, local: locale });
				const resp = await fetch(`${langInterfaceUrl}?${params.toString()}`, { cache: 'no-cache' });
				if (!resp.ok) {
					return null;
				}
				const etag = resp.headers.get('ETag');
				if (knownEtag && etag === knownEtag) {
					resp.body?.cancel?.();
					return { unchanged: true, etag };
				}
				const json = await resp.json();
				return { data: json[locale] || {}, etag };
			} catch (err) {
				Dolibarr.log('tools: langs: Load lang from interface fail');
				return null;
			}
		}

		/**
		 * Check a cached domain in background: new translations are saved and used at once in memory
		 * @param {string} domain
		 * @param {string} locale
		 * @param {string|null} knownEtag ETag of the cached translations
		 */
		async function revalidateLocale(domain, locale, knownEtag) {
			const key = `${domain}@${locale}`;
			if (revalidating.has(key)) return;
			revalidating.add(key);
			try {
				const result = await fetchLocale(domain, locale, knownEtag);
				if (result && result.unchanged) {
					await setCacheChecked(domain, locale, result.etag);
					Dolibarr.log('tools: langs: Lang checked again from interface, unchanged');
				} else if (result) {
					await setCache(domain, locale, result.data, result.etag);
					if (!translations[locale]) translations[locale] = {};
					Object.assign(translations[locale], result.data);
					Dolibarr.log('tools: langs: Lang checked again from interface, updated');
				}
			} finally {
				revalidating.delete(key);
			}
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
					if (!domainsLoaded[locale].has(domain)) {
						await load(domain, locale);
					}
				}
			}

			Dolibarr.log(`tools:langs: Locale changed: ${prev} -> ${locale}`);
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
		document.addEventListener('keydown', (e) => {
			if (e.key === 'F5' && (e.ctrlKey || e.shiftKey)) {
				sessionStorage.setItem('lang_hard_reload', '1');
				Dolibarr.log('Lang tool : Hard reload detected');
			}
		});

		(async () => {
			const navEntries = performance?.getEntriesByType?.("navigation");
			const isReload = navEntries?.[0]?.type === "reload" || performance.navigation?.type === 1;

			if (!isReload) return;

			const isHardReload = sessionStorage.getItem('lang_hard_reload') === '1';
			sessionStorage.removeItem('lang_hard_reload');

			if (isHardReload) {
				await clearCache(true);
				Dolibarr.log('Lang tool : Lang cache cleared (hard reload detected)');
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
