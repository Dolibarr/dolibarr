/**
 * @license Copyright (c) 2003-2012, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.html or http://ckeditor.com/license
 */
 (function() {
  // Create support tools
 var appTools = (function(){
 	var _init = function(handler) {
		if (document.addEventListener) {
			window.addEventListener('message', handler, false);
		} else {
			window.attachEvent("onmessage", handler);
		}
	};

	var _sendCmd = function(o) {
		var str,
			type = Object.prototype.toString,
			objObject = "[object Object]",
			fn = o.fn || null,
			id = o.id || '',
			target = o.target || window,
			message = o.message || {
				'id': id
			};

		if (type.call(o.message) == objObject) {
			(o.message.id) ? o.message.id : o.message.id = id;
			message = o.message;
		}

		str = window.JSON.stringify(message, fn);
		target.postMessage(str, '*');
	};

	var _hashCreate = function(o, fn) {
		fn = fn || null;
		var str = window.JSON.stringify(o, fn);
		return str;
	};

	var _hashParse = function(str, fn) {
		fn = fn || null;
		return window.JSON.parse(str, fn);
	};

	var setCookie = function(name, value, options) {
	  options = options || {};

	  var expires = options.expires;

	  if (typeof expires == "number" && expires) {
	    var d = new Date();
	    d.setTime(d.getTime() + expires*1000);
	    expires = options.expires = d;
	  }
	  if (expires && expires.toUTCString) {
	  	options.expires = expires.toUTCString();
	  }

	  value = encodeURIComponent(value);
	  var updatedCookie = name + "=" + value;

	  for(var propName in options) {
	  	var propValue = options[propName];
	    	updatedCookie += "; " + propName;
	    if (propValue !== true) {
			updatedCookie += "=" + propValue;
		}
	  }
	  document.cookie = updatedCookie;
	};

	var getCookie = function(name) {
	  var matches = document.cookie.match(new RegExp(
	    "(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"
	  ));
	  return matches ? decodeURIComponent(matches[1]) : undefined;
	};

	var deleteCookie = function(name) {
	  setCookie(name, "", { expires: -1 });
	};

	return {
		postMessage: {
			init: _init,
			send: _sendCmd
		},
		hash: {
			create: function() {

			},

			parse: function() {

			}
		},
		cookie: {
			set: setCookie,
			get: getCookie,
			remove: deleteCookie
		}
	};
 })();

	var NS = NS || {};
		NS.TextAreaNumber = null;
		NS.load = true;
		NS.cmd = {
			"SpellTab": 'spell',
			"Thesaurus": 'thes',
			"GrammTab": 'grammar'
		};
		NS.dialog = null;
		NS.optionNode = null;
		NS.selectNode = null;
		NS.grammerSuggest = null;
		NS.textNode = {};
		NS.iframeMain = null;
		NS.dataTemp = '';
		NS.div_overlay = null;
		NS.textNodeInfo = {};
		NS.selectNode = {};
		NS.selectNodeResponce = {};
		NS.langList = null;
		NS.langSelectbox = null;
		NS.banner = '';
		NS.show_grammar = null;
		NS.div_overlay_no_check = null;
		NS.targetFromFrame = {};
		NS.onLoadOverlay = null;
		NS.LocalizationComing = {};
		NS.OverlayPlace = null;
		NS.LocalizationButton = {
			'ChangeTo': {
				'instance' : null,
				'text' : 'Change to'
			},

			'ChangeAll': {
				'instance' : null,
				'text' : 'Change All'
			},

			'IgnoreWord': {
				'instance' : null,
				'text' : 'Ignore word'
			},

			'IgnoreAllWords': {
				'instance' : null,
				'text' : 'Ignore all words'
			},

			'Options': {
				'instance' : null,
				'text' : 'Options',
				'optionsDialog': {
					'instance' : null
				}
			},

			'AddWord': {
				'instance' : null,
				'text' : 'Add word'
			},

			'FinishChecking': {
				'instance' : null,
				'text' : 'Finish Checking'
			}
		};

		NS.LocalizationLabel = {
			'ChangeTo': {
				'instance' : null,
				'text' : 'Change to'
			},

			'Suggestions': {
				'instance' : null,
				'text' : 'Suggestions'
			}
		};

	var SetLocalizationButton = function(obj) {

		for(var i in obj) {
			obj[i].instance.getElement().setText(NS.LocalizationComing[i]);
		}
	};

	var SetLocalizationLabel = function(obj) {

		for(var i in obj) {
			if (!obj[i].instance.setLabel) {
				return;
			}
			obj[i].instance.setLabel(NS.LocalizationComing[i]);
		}
	};
	var OptionsConfirm = function(state) {
		if (state) {
			nameNode.setValue('');
		}
	};

	var iframeOnload = false;
	var nameNode, selectNode, frameId;

	NS.framesetHtml = function(tab) {
		var str = '<iframe src="' + NS.templatePath + '" id=' + NS.iframeNumber + '_' + tab + ' frameborder="0" allowtransparency="1" style="width:100%;border: 1px solid #AEB3B9;overflow: auto;background:#fff; border-radius: 3px;"></iframe>';
		return str;
	};

	NS.setIframe = function(that, nameTab) {
		var str = NS.framesetHtml(nameTab),
			iframeId = NS.iframeNumber + nameTab;
		return that.getElement().setHtml(str);
	};

	NS.setCurrentIframe = function(currentTab) {
		var that = NS.dialog._.contents[currentTab].Content,
			tabID, iframe;

		NS.setIframe(that, currentTab);

	};

	NS.setHeightBannerFrame = function() {
		var height = "90px",
			bannerPlaceSpellTab = NS.dialog.getContentElement('SpellTab', 'banner').getElement(),
			bannerPlaceGrammTab = NS.dialog.getContentElement('GrammTab', 'banner').getElement(),
			bannerPlaceThesaurus = NS.dialog.getContentElement('Thesaurus', 'banner').getElement();

		bannerPlaceSpellTab.setStyle('height', height);
		bannerPlaceGrammTab.setStyle('height', height);
		bannerPlaceThesaurus.setStyle('height', height);
	};

	NS.setHeightFrame = function() {
		var currentTab = NS.dialog._.currentTabId,
			tabID = NS.iframeNumber + '_' + currentTab,
			iframe = document.getElementById(tabID);

		iframe.style.height = '240px';
	};

	NS.sendData = function(scope) {
		var currentTab = scope._.currentTabId,
			that = scope._.contents[currentTab].Content,
			tabID, iframe;


		NS.setIframe(that, currentTab);
		scope.parts.tabs.removeAllListeners();

		scope.parts.tabs.on('click', function(event) {
			event = event || window.event;

			if (!event.data.getTarget().is('a')) {
				return;
			}

			if (currentTab == scope._.currentTabId) { return; }

			currentTab = scope._.currentTabId;
			that = scope._.contents[currentTab].Content;
			tabID = NS.iframeNumber + '_' + currentTab;
			NS.div_overlay.setEnable();

			if (!that.getElement().getChildCount()) {
				NS.setIframe(that, currentTab);
				iframe = document.getElementById(tabID);
				NS.targetFromFrame[tabID] = iframe.contentWindow;
			} else {
				sendData(NS.targetFromFrame[tabID], NS.cmd[currentTab]);
			}
		});
	};

	NS.buildSelectLang = function(aId) {
		var divContainer = new CKEDITOR.dom.element('div'),
			selectContainer = new CKEDITOR.dom.element('select'),
			id = "wscLang" + aId;

		divContainer.addClass("cke_dialog_ui_input_select");
		divContainer.setAttribute("role", "presentation");
		divContainer.setStyles({
			'height': 'auto',
			'position': 'absolute',
			'right': '0',
			'top': '-1px',
			'width': '160px',
			'white-space': 'normal'
		});

		selectContainer.setAttribute('id', id);
		selectContainer.addClass("cke_dialog_ui_input_select");
		selectContainer.setStyles({
			'width': '160px'
		});
		var currentTabId = NS.dialog._.currentTabId,
				frameId = NS.iframeNumber + '_' + currentTabId;

		divContainer.append(selectContainer);

		return  divContainer;

	};

	NS.buildOptionLang = function(key, aId) {
		var id = "wscLang" + aId;
		var select = document.getElementById(id),
			fragment = document.createDocumentFragment(),
			create_option, txt_option,
			sort = [];

		if(select.options.length === 0) {
			for (var lang in key) {
				sort.push([lang, key[lang]]);
			}
			sort.sort();

			for (var i = 0; i < sort.length; i++) {
				create_option=document.createElement("option");
				create_option.setAttribute("value", sort[i][1]);
				txt_option = document.createTextNode(sort[i][0]);
				create_option.appendChild(txt_option);

				if (sort[i][1] == NS.selectingLang) {
					create_option.setAttribute("selected", "selected");
				}

				fragment.appendChild(create_option);
			}
			select.appendChild(fragment);
		}
	};

	NS.buildOptionSynonyms = function(key) {
		var syn = NS.selectNodeResponce[key];
		NS.selectNode['synonyms'].clear();

		for (var item = 0; item < syn.length; item++) {
			NS.selectNode['synonyms'].add(syn[item], syn[item]);
		}

		NS.selectNode['synonyms'].getInputElement().$.firstChild.selected = true;
		NS.textNode['Thesaurus'].setValue(NS.selectNode['synonyms'].getInputElement().getValue());
	};

	var setBannerInPlace = function(htmlBanner) {
		var findBannerPlace = NS.dialog.getContentElement(NS.dialog._.currentTabId, 'banner').getElement();
		findBannerPlace.setHtml(htmlBanner);

	};

	var overlayBlock = function overlayBlock(opt) {
		var progress = opt.progress || "",
			doc = document,
			target = opt.target || doc.body,
			overlayId = opt.id || "overlayBlock",
			opacity = opt.opacity || "0.9",
			background = opt.background || "#f1f1f1",
			getOverlay = doc.getElementById(overlayId),
			thisOverlay = getOverlay || doc.createElement("div");

		thisOverlay.style.cssText = "position: absolute;" +
			"top:30px;" +
			"bottom:41px;" +
			"left:1px;" +
			"right:1px;" +
			"z-index: 10020;" +
			"padding:0;" +
			"margin:0;" +
			"background:" + background + ";" +
			"opacity: " + opacity + ";" +
			"filter: alpha(opacity=" + opacity * 100 + ");" +
			"display: none;";
		thisOverlay.id = overlayId;

		if (!getOverlay) {
		target.appendChild(thisOverlay);
		}

		return {
			setDisable: function() {
				thisOverlay.style.display = "none";
			},
			setEnable: function() {
				thisOverlay.style.display = "block";
			}
		};
	};

	var buildRadioInputs = function(key, value, check) {
		var divContainer = new CKEDITOR.dom.element('div'),
			radioButton = new CKEDITOR.dom.element('input'),
			radioLabel = new CKEDITOR.dom.element('label'),
			id = "wscGrammerSuggest" + key + "_" + value;

		divContainer.addClass("cke_dialog_ui_input_radio");
		divContainer.setAttribute("role", "presentation");
		divContainer.setStyles({
			width: "97%",
			padding: "5px",
			'white-space': 'normal'
		});

		radioButton.setAttributes({
			type: "radio",
			value: value,
			name: 'wscGrammerSuggest',
			id: id
		});
		radioButton.setStyles({
			"float":"left"
		});

		radioButton.on("click", function(data) {
			NS.textNode['GrammTab'].setValue(data.sender.getValue());
		});

		(check) ? radioButton.setAttribute("checked", true) : false;

		radioButton.addClass("cke_dialog_ui_radio_input");

		radioLabel.appendText(key);
		radioLabel.setAttribute("for", id);
		radioLabel.setStyles({
			'display': "block",
			'line-height': '16px',
			'margin-left': '18px',
			'white-space': 'normal'
		});

		divContainer.append(radioButton);
		divContainer.append(radioLabel);

		return divContainer;
	};

	var statusGrammarTab = function(aState) {  //#19221
		aState = aState || 'true';
		if(aState !== null && aState == 'false'){
			hideGrammTab();
		}
	};

	var langConstructor = function(lang) {
		var langSelectBox = new __constructLangSelectbox(lang),
			selectId = "wscLang" + NS.dialog.getParentEditor().name,
			selectContainer = document.getElementById(selectId),
			currentTabId = NS.dialog._.currentTabId,
			frameId = NS.iframeNumber + '_' + currentTabId;

		NS.buildOptionLang(langSelectBox.setLangList, NS.dialog.getParentEditor().name);
		tabView[langSelectBox.getCurrentLangGroup(NS.selectingLang)]();
		statusGrammarTab(NS.show_grammar);

		selectContainer.onchange = function(e){
			e = e || window.event;
			tabView[langSelectBox.getCurrentLangGroup(this.value)]();
			statusGrammarTab(NS.show_grammar);

			NS.div_overlay.setEnable();

			NS.selectingLang = this.value;

			appTools.postMessage.send({
			 	'message': {
			 		'changeLang': NS.selectingLang,
			 		'text': NS.dataTemp
			 	},
				'target': NS.targetFromFrame[frameId],
				'id': 'selectionLang_outer__page'
			});
		};

	};

	var disableButtonSuggest = function(word) {
		if (word == 'no_any_suggestions') {
			word = 'No suggestions';
			NS.LocalizationButton['ChangeTo'].instance.disable();
			NS.LocalizationButton['ChangeAll'].instance.disable();

			var styleDisable = function(instanceButton) {
				var button = NS.LocalizationButton[instanceButton].instance;
				button.getElement().hasClass('cke_disabled') ? button.getElement().setStyle('color', '#a0a0a0') : button.disable();
			};

			styleDisable('ChangeTo');
			styleDisable('ChangeAll');

			return word;
		} else {
			NS.LocalizationButton['ChangeTo'].instance.enable();
			NS.LocalizationButton['ChangeAll'].instance.enable();
			NS.LocalizationButton['ChangeTo'].instance.getElement().setStyle('color', '#333');
			NS.LocalizationButton['ChangeAll'].instance.getElement().setStyle('color', '#333');
			return word;
		}
	};

	var handlerId = {
		iframeOnload: function(response) {
			NS.div_overlay.setEnable();
			iframeOnload = true;
			var currentTab = NS.dialog._.currentTabId,
				tabId = NS.iframeNumber + '_' + currentTab;
			sendData(NS.targetFromFrame[tabId], NS.cmd[currentTab]);
		},

		suggestlist: function(response) {
			delete response.id;
			NS.div_overlay_no_check.setDisable();
			hideCurrentFinishChecking();
			langConstructor(NS.langList);

			var word =  disableButtonSuggest(response.word),
				suggestionsList = '';

			if (word instanceof Array) {
				word = response.word[0];
			}

			word = word.split(',');
			suggestionsList = word;
			selectNode.clear();
			NS.textNode['SpellTab'].setValue(suggestionsList[0]);

			for (var item = 0; item < suggestionsList.length; item++) {
				selectNode.add(suggestionsList[item], suggestionsList[item]);
			}

			showCurrentTabs();
			NS.div_overlay.setDisable();

		},

		grammerSuggest: function(response) {
			delete response.id;
			delete response.mocklangs;

			hideCurrentFinishChecking();
			langConstructor(NS.langList);	// Show select language for this command CKEDITOR.config.wsc_cmd
			var firstSuggestValue = response.grammSuggest[0];// ? firstSuggestValue = response.grammSuggest[0] : firstSuggestValue = 'No suggestion for this words';
			NS.grammerSuggest.getElement().setHtml('');

			NS.textNode['GrammTab'].reset();
			NS.textNode['GrammTab'].setValue(firstSuggestValue);

			NS.textNodeInfo['GrammTab'].getElement().setHtml('');
			NS.textNodeInfo['GrammTab'].getElement().setText(response.info);

			var arr = response.grammSuggest,
				len = arr.length,
				check = true;

				for (var i = 0; i < len; i++) {
					NS.grammerSuggest.getElement().append(buildRadioInputs(arr[i], arr[i], check));
					check = false;
				}

			showCurrentTabs();
			NS.div_overlay.setDisable();
		},

		thesaurusSuggest: function(response) {
			delete response.id;
			delete response.mocklangs;

			hideCurrentFinishChecking();
			langConstructor(NS.langList);	// Show select language for this command CKEDITOR.config.wsc_cmd
			NS.selectNodeResponce = response;

			NS.textNode['Thesaurus'].reset();
			NS.selectNode['categories'].clear();

			for (var i in response) {
				NS.selectNode['categories'].add(i, i);
			}

			var synKey = NS.selectNode['categories'].getInputElement().getChildren().$[0].value;
			NS.selectNode['categories'].getInputElement().getChildren().$[0].selected = true;
			NS.buildOptionSynonyms(synKey);

			showCurrentTabs();
			NS.div_overlay.setDisable();
		},
		finish: function(response) {
			delete response.id;

			hideCurrentTabs();
			showCurrentFinishChecking();
			NS.div_overlay.setDisable();
		},
		settext: function(response) {
			delete response.id;

			var command = NS.dialog.getParentEditor().getCommand( 'checkspell' ),
				editor = NS.dialog.getParentEditor();

			editor.focus();

			editor.setData(response.text, function(){
				NS.dataTemp = '';
				editor.unlockSelection();
				editor.fire('saveSnapshot');
				NS.dialog.hide();
			});

		},
		ReplaceText: function(response) {
			delete response.id;
			NS.div_overlay.setEnable();

			NS.dataTemp = response.text;
			NS.selectingLang = response.currentLang;

			window.setTimeout(function() {
				NS.div_overlay.setDisable();
			}, 500);

			SetLocalizationButton(NS.LocalizationButton);
			SetLocalizationLabel(NS.LocalizationLabel);

		},
		options_checkbox_send: function(response) {
			delete response.id;

			var obj = {
				'osp': appTools.cookie.get('osp'),
				'udn': appTools.cookie.get('udn'),
				'cust_dic_ids': NS.cust_dic_ids
			};

			var currentTabId = NS.dialog._.currentTabId,
				frameId = NS.iframeNumber + '_' + currentTabId;

			appTools.postMessage.send({
				'message': obj,
				'target': NS.targetFromFrame[frameId],
				'id': 'options_outer__page'
			});
		},

		getOptions: function(response) {
			var udn = response.DefOptions.udn;
			NS.LocalizationComing = response.DefOptions.localizationButtonsAndText;
			NS.show_grammar = response.show_grammar;
			NS.langList = response.lang;
			NS.bnr = response.bannerId;
			if (response.bannerId) {
				NS.setHeightBannerFrame();
				setBannerInPlace(response.banner);
			} else {
				NS.setHeightFrame();
			}

			if (udn == 'undefined') {
				if (NS.userDictionaryName) {
					udn = NS.userDictionaryName;

					var obj = {
						'osp': appTools.cookie.get('osp'),
						'udn': NS.userDictionaryName,
						'cust_dic_ids': NS.cust_dic_ids,
						'id': 'options_dic_send',
						'udnCmd': 'create'
					};

					appTools.postMessage.send({
						'message': obj,
						'target': NS.targetFromFrame[frameId]
					});

				} else{
					udn = '';
				}
			}

			appTools.cookie.set('osp', response.DefOptions.osp);
			appTools.cookie.set('udn', udn);
			appTools.cookie.set('cust_dic_ids', response.DefOptions.cust_dic_ids);

			appTools.postMessage.send({
				'id': 'giveOptions'
			});
		},

		options_dic_send: function(response) {

			var obj = {
				'osp': appTools.cookie.get('osp'),
				'udn': appTools.cookie.get('udn'),
				'cust_dic_ids': NS.cust_dic_ids,
				'id': 'options_dic_send',
				'udnCmd': appTools.cookie.get('udnCmd')
			};

			var currentTabId = NS.dialog._.currentTabId,
				frameId = NS.iframeNumber + '_' + currentTabId;

			appTools.postMessage.send({
				'message': obj,
				'target': NS.targetFromFrame[frameId]
			});
		},
		data: function(response) {
			delete response.id;
		},

		giveOptions: function() {

		},

		setOptionsConfirmF:function() {
			 OptionsConfirm(false);
		},

		setOptionsConfirmT:function() {
			OptionsConfirm(true);
		},

		clickBusy: function() {
			NS.div_overlay.setEnable();
		},

		suggestAllCame: function() {
			NS.div_overlay.setDisable();
			NS.div_overlay_no_check.setDisable();
		},

		TextCorrect: function() {
			langConstructor(NS.langList);
		}

	};

	var handlerIncomingData = function(event) {
		event = event || window.event;
		var response = window.JSON.parse(event.data);

		if(response && response.id) {
			handlerId[response.id](response);
		}
	};

	var handlerButtonOptions = function(event) {
		event = event || window.event;

		var currentTabId = NS.dialog._.currentTabId,
			frameId = NS.iframeNumber + '_' + currentTabId;

		appTools.postMessage.send({
			'message': {
				'cmd': 'Options'
			},
			'target': NS.targetFromFrame[frameId],
			'id': 'cmd'
		});

	};

	var sendData = function(frameTarget, cmd, sendText, reset_suggest) {
		cmd = cmd || CKEDITOR.config.wsc_cmd;
		reset_suggest = reset_suggest || false;
		sendText = sendText || NS.dataTemp;

		appTools.postMessage.send({
			'message': {
				'customerId': NS.wsc_customerId,
				'text': sendText,
				'txt_ctrl': NS.TextAreaNumber,
				'cmd': cmd,
				'cust_dic_ids': NS.cust_dic_ids,
				'udn': NS.userDictionaryName,
				'slang': NS.selectingLang,
				'reset_suggest': reset_suggest
			},
			'target': frameTarget,
			'id': 'data_outer__page'
		});

		NS.div_overlay.setEnable();
	};

	var tabView = {
		"superset" : function() {
			showThesaurusTab();
			showGrammTab();
			showSpellTab();
		},
		"usual"    : function() {
			hideThesaurusTab();
			hideGrammTab();
			showSpellTab();
		},
		"rtl"    : function() {
			hideThesaurusTab();
			hideGrammTab();
			showSpellTab();
		}
	};

	var showFirstTab = function(scope) {
		var cmdManger = function(cmdView) {
			var obj = {};
			var _getCmd = function(cmd) {
				for (var tabId in cmdView) {
					obj[cmdView[tabId]] = tabId;
				}
			return obj[cmd];
			};
			return {
				getCmdByTab: _getCmd
			};
		};

		var cmdM = new cmdManger(NS.cmd);
		scope.selectPage(cmdM.getCmdByTab(CKEDITOR.config.wsc_cmd));
		NS.sendData(scope);
	};

	var showThesaurusTab = function() {
		NS.dialog.showPage('Thesaurus');
	};

	var hideThesaurusTab = function() {
		NS.dialog.hidePage('Thesaurus');
	};

	var showGrammTab = function() {
		NS.dialog.showPage('GrammTab');
	};

	var hideGrammTab = function() {
		NS.dialog.hidePage('GrammTab');
	};

	var showSpellTab = function() {
		NS.dialog.showPage('SpellTab');
	};

	var hideSpellTab = function() {
		NS.dialog.hidePage('SpellTab');
	};

	var showCurrentTabs = function() {
		NS.dialog.getContentElement(NS.dialog._.currentTabId, 'bottomGroup').getElement().show();
	};

	var hideCurrentTabs = function() {
		NS.dialog.getContentElement(NS.dialog._.currentTabId, 'bottomGroup').getElement().hide();
	};

	var showCurrentFinishChecking = function() {
		NS.dialog.getContentElement(NS.dialog._.currentTabId, 'BlockFinishChecking').getElement().show();
	};

	var hideCurrentFinishChecking = function() {
		NS.dialog.getContentElement(NS.dialog._.currentTabId, 'BlockFinishChecking').getElement().hide();
	};



function __constructLangSelectbox(languageGroup) {
	if( !languageGroup ) { throw "Languages-by-groups list are required for construct selectbox"; }

	var that = this,
		o_arr = [],
		priorLang ="en_US",
		priorLangTitle = "",
		currLang = NS.selectingLang;

	for ( var group in languageGroup){
		for ( var langCode in languageGroup[group]){
			var langName = languageGroup[group][langCode];
			if ( langName == priorLang ) {
				priorLangTitle = langName;
			} else {
				o_arr.push( langName );
			}
		}
	}

	o_arr.sort();
	if(priorLangTitle) {
		o_arr.unshift( priorLangTitle );
	}

	var searchGroup = function ( code ){
		for ( var group in languageGroup){
			for ( var langCode in languageGroup[group]){
				if ( langCode.toUpperCase() === code.toUpperCase() ) {
					return group;
				}
			}
		}
		return "";
	};

	var _setLangList = function() {
		var langList = {},
			langArray = [];
		for (var group in languageGroup) {
			for ( var langCode in languageGroup[group]){
				langList[languageGroup[group][langCode]] = langCode;
			}
		}
		return langList;
	};

	var _return = {
		getCurrentLangGroup: function(code) {
			return searchGroup(code);
		},
		setLangList: _setLangList()
	};

	return _return;
}

CKEDITOR.dialog.add('checkspell', function(editor) {
	var handlerButtons = function(event) {
		event = event || window.event;

		NS.div_overlay.setEnable();
		var currentTabId = NS.dialog._.currentTabId,
			frameId = NS.iframeNumber + '_' + currentTabId,
			new_word = NS.textNode[currentTabId].getValue(),
			cmd = this.getElement().getAttribute("title-cmd");


		appTools.postMessage.send({
			'message': {
				'cmd': cmd,
				'tabId': currentTabId,
				'new_word': new_word
			},
			'target': NS.targetFromFrame[frameId],
			'id': 'cmd_outer__page'
		});

		if (cmd == 'ChangeTo' || cmd == 'ChangeAll') {
			editor.fire('saveSnapshot');
		}

		if (cmd == 'FinishChecking') {
			editor.config.wsc_onFinish.call(CKEDITOR.document.getWindow().getFrame());
		}

	};

	var oneLoadFunction = null;

 return {
		title: editor.config.wsc_dialogTitle || editor.lang.wsc.title,
		minWidth: 560,
		minHeight: 444,
		buttons: [CKEDITOR.dialog.cancelButton],
		onLoad: function() {
			NS.dialog = this;

			hideThesaurusTab();
			hideGrammTab();
			showSpellTab();
		},
		onShow: function() {
			editor.lockSelection(editor.getSelection());

			NS.TextAreaNumber = 'cke_textarea_' + CKEDITOR.currentInstance.name;
			appTools.postMessage.init(handlerIncomingData);
			NS.dataTemp = CKEDITOR.currentInstance.getData();
			//NS.div_overlay.setDisable();
			NS.OverlayPlace = NS.dialog.parts.tabs.getParent().$;
			if(CKEDITOR && CKEDITOR.config){
				NS.wsc_customerId =  editor.config.wsc_customerId;
				NS.cust_dic_ids = editor.config.wsc_customDictionaryIds;
				NS.userDictionaryName = editor.config.wsc_userDictionaryName;
				NS.defaultLanguage = CKEDITOR.config.defaultLanguage;
				var	protocol = document.location.protocol == "file:" ? "http:" : document.location.protocol;
				var wscCoreUrl = editor.config.wsc_customLoaderScript  || ( protocol + '//loader.webspellchecker.net/sproxy_fck/sproxy.php?plugin=fck2&customerid=' + NS.wsc_customerId + '&cmd=script&doc=wsc&schema=22');
			} else {
				NS.dialog.hide();
				return;
			}

			CKEDITOR.scriptLoader.load(wscCoreUrl, function(success) {

				if(CKEDITOR.config && CKEDITOR.config.wsc && CKEDITOR.config.wsc.DefaultParams){
					NS.serverLocationHash = CKEDITOR.config.wsc.DefaultParams.serviceHost;
					NS.logotype = CKEDITOR.config.wsc.DefaultParams.logoPath;
					NS.loadIcon = CKEDITOR.config.wsc.DefaultParams.iconPath;
					NS.loadIconEmptyEditor = CKEDITOR.config.wsc.DefaultParams.iconPathEmptyEditor;
					NS.LangComparer = new CKEDITOR.config.wsc.DefaultParams._SP_FCK_LangCompare();
				}else{
					NS.serverLocationHash = DefaultParams.serviceHost;
					NS.logotype = DefaultParams.logoPath;
					NS.loadIcon = DefaultParams.iconPath;
					NS.loadIconEmptyEditor = DefaultParams.iconPathEmptyEditor;
					NS.LangComparer = new _SP_FCK_LangCompare();
				}

				NS.pluginPath = CKEDITOR.getUrl(editor.plugins.wsc.path);
				NS.iframeNumber = NS.TextAreaNumber;
				NS.templatePath = NS.pluginPath + 'dialogs/tmp.html';
				NS.LangComparer.setDefaulLangCode( NS.defaultLanguage );
				NS.currentLang = editor.config.wsc_lang || NS.LangComparer.getSPLangCode( editor.langCode );
				NS.selectingLang = NS.currentLang;
				NS.div_overlay = new overlayBlock({
					opacity: "1",
					background: "#fff url(" + NS.loadIcon + ") no-repeat 50% 50%",
					target: NS.OverlayPlace

				});

				var number_ck = NS.dialog.parts.tabs.getId(),
					dialogPartsTab = CKEDITOR.document.getById(number_ck);

				dialogPartsTab.setStyle('width', '97%');
				if (!dialogPartsTab.getElementsByTag('DIV').count()){
					dialogPartsTab.append(NS.buildSelectLang(NS.dialog.getParentEditor().name));
				}

				NS.div_overlay_no_check = new overlayBlock({
					opacity: "1",
					id: 'no_check_over',
					background: "#fff url(" + NS.loadIconEmptyEditor + ") no-repeat 50% 50%",
					target: NS.OverlayPlace
				});



				if (success) {
					showFirstTab(NS.dialog);
					NS.dialog.setupContent(NS.dialog);
				}
			});

		},
		onHide: function() {
			NS.dataTemp = '';
		},
		contents: [
			{
				id: 'SpellTab',
				label: 'SpellChecker',
				accessKey: 'S',
				elements: [
					{
						type: 'html',
						id: 'banner',
						label: 'banner',
						style: '', //TODO
						html: '<div></div>'
					},
					{
						type: 'html',
						id: 'Content',
						label: 'spellContent',
						html: '',
						setup: function(dialog) {// debugger;
							var tabId = NS.iframeNumber + '_' + dialog._.currentTabId;
							var iframe = document.getElementById(tabId);
							NS.targetFromFrame[tabId] = iframe.contentWindow;
						}
					},
					{
						type: 'hbox',
						id: 'bottomGroup',
						style: 'width:560px; margin: 0 auto;',
						widths: ['50%', '50%'],
						children: [
							{
								type: 'hbox',
								id: 'leftCol',
								align: 'left',
								width: '50%',
								children: [
									{
										type: 'vbox',
										id: 'rightCol1',
										widths: ['50%', '50%'],
										children: [
											{
												type: 'text',
												id: 'text',
												label: NS.LocalizationLabel['ChangeTo'].text + ':',
												labelLayout: 'horizontal',
												labelStyle: 'font: 12px/25px arial, sans-serif;',
												width: '140px',
												'default': '',
												onShow: function() {
													NS.textNode['SpellTab'] = this;
													NS.LocalizationLabel['ChangeTo'].instance = this;
												},
												onHide: function() {
													this.reset();
												}
											},
											{
												type: 'hbox',
												id: 'rightCol',
												align: 'right',
												width: '30%',
												children: [
													{
														type: 'vbox',
														id: 'rightCol_col__left',
														children: [
															{
																type: 'text',
																id: 'labelSuggestions',
																label: NS.LocalizationLabel['Suggestions'].text + ':',
																onShow: function() {
																	NS.LocalizationLabel['Suggestions'].instance = this;
																	this.getInputElement().hide();
																}
															},
						 									{
																type: 'html',
																id: 'logo',
																html: '<img width="99" height="68" border="0" src="" title="WebSpellChecker.net" alt="WebSpellChecker.net" style="display: inline-block;">',
																setup: function(dialog) {
																	this.getElement().$.src = NS.logotype;
																	this.getElement().getParent().setStyles({
																		"text-align": "left"
																	});
																}
															}
														]
													},
													{
														type: 'select',
														id: 'list_of_suggestions',
														labelStyle: 'font: 12px/25px arial, sans-serif;',
														size: '6',
														inputStyle: 'width: 140px; height: auto;',
														items: [['loading...']],
														onShow: function() {
															selectNode = this;
														},
														onHide: function() {
															this.clear();
														},
														onChange: function() {
															NS.textNode['SpellTab'].setValue(this.getValue());
														}
													}
												]
											}
										]
									}
								]
							},
							{
								type: 'hbox',
								id: 'rightCol',
								align: 'right',
								width: '50%',
								children: [
									{
										type: 'vbox',
										id: 'rightCol_col__left',
										widths: ['50%', '50%', '50%', '50%'],
										children: [
											{
												type: 'button',
												id: 'ChangeTo',
												label: NS.LocalizationButton['ChangeTo'].text,
												title: 'Change to',
												style: 'width: 100%;',
												onLoad: function() {
													this.getElement().setAttribute("title-cmd", this.id);
													NS.LocalizationButton['ChangeTo'].instance = this;
												},
												onClick: handlerButtons
											},
											{
												type: 'button',
												id: 'ChangeAll',
												label: NS.LocalizationButton['ChangeAll'].text,
												title: 'Change All',
												style: 'width: 100%;',
												onLoad: function() {
													this.getElement().setAttribute("title-cmd", this.id);
													NS.LocalizationButton['ChangeAll'].instance = this;
												},
												onClick: handlerButtons
											},
											{
												type: 'button',
												id: 'AddWord',
												label: NS.LocalizationButton['AddWord'].text,
												title: 'Add word',
												style: 'width: 100%;',
												onLoad: function() {
													this.getElement().setAttribute("title-cmd", this.id);
													NS.LocalizationButton['AddWord'].instance = this;
												},
												onClick: handlerButtons
											},
											{
												type: 'button',
												id: 'FinishChecking',
												label: NS.LocalizationButton['FinishChecking'].text,
												title: 'Finish Checking',
												style: 'width: 100%;margin-top: 9px;',
												onLoad: function() {
													this.getElement().setAttribute("title-cmd", this.id);
													NS.LocalizationButton['FinishChecking'].instance = this;
												},
												onClick: handlerButtons
											}
										]
									},
									{
										type: 'vbox',
										id: 'rightCol_col__right',
										widths: ['50%', '50%', '50%'],
										children: [
											{
												type: 'button',
												id: 'IgnoreWord',
												label: NS.LocalizationButton['IgnoreWord'].text,
												title: 'Ignore word',
												style: 'width: 100%;',
												onLoad: function() {
													this.getElement().setAttribute("title-cmd", this.id);
													NS.LocalizationButton['IgnoreWord'].instance = this;
												},
												onClick: handlerButtons
											},
											{
												type: 'button',
												id: 'IgnoreAllWords',
												label: NS.LocalizationButton['IgnoreAllWords'].text,
												title: 'Ignore all words',
												style: 'width: 100%;',
												onLoad: function() {
													this.getElement().setAttribute("title-cmd", this.id);
													NS.LocalizationButton['IgnoreAllWords'].instance = this;
												},
												onClick: handlerButtons
											},
											{
												type: 'button',
												id: 'option',
												label: NS.LocalizationButton['Options'].text,
												title: 'Option',
												style: 'width: 100%;',
												onLoad: function() {
													NS.LocalizationButton['Options'].instance = this;
													if (document.location.protocol == "file:") {
														this.disable();
													}
												},
												onClick: function() {
													if (document.location.protocol == "file:") {
														alert('WSC: Options functionality is disabled when runing from file system');
													} else {
														editor.openDialog('options');
													}
												}
											}
										]
									}
								]
							}
				]
			},
			{
				type: 'hbox',
				id: 'BlockFinishChecking',
				style: 'width:560px; margin: 0 auto;',
				widths: ['70%', '30%'],
				onShow: function() {
					this.getElement().hide();
				},
				onHide: showCurrentTabs,
				children: [
					{
						type: 'hbox',
						id: 'leftCol',
						align: 'left',
						width: '70%',
						children: [
							{
								type: 'vbox',
								id: 'rightCol1',
								setup: function() {
									this.getChild()[0].getElement().$.src = NS.logotype;
									this.getChild()[0].getElement().getParent().setStyles({
										"text-align": "center"
									});
								},
								children: [
									{
										type: 'html',
										id: 'logo',
										html: '<img width="99" height="68" border="0" src="" title="WebSpellChecker.net" alt="WebSpellChecker.net" style="display: inline-block;">'
									}
								]
							}
						]
					},
					{
						type: 'hbox',
						id: 'rightCol',
						align: 'right',
						width: '30%',
						children: [
							{
								type: 'vbox',
								id: 'rightCol_col__left',
								children: [
									{
										type: 'button',
										id: 'Option_button',
										label: NS.LocalizationButton['Options'].text,
										title: 'Option',
										style: 'width: 100%;',
										onLoad: function() {
											this.getElement().setAttribute("title-cmd", this.id);
											if (document.location.protocol == "file:") {
												this.disable();
											}
										},
										onClick: function() {
											if (document.location.protocol == "file:") {
												alert('WSC: Options functionality is disabled when runing from file system');
											} else {
												editor.openDialog('options');
											}
										}
									},
									{
										type: 'button',
										id: 'FinishChecking',
										label: NS.LocalizationButton['FinishChecking'].text,
										title: 'Finish Checking',
										style: 'width: 100%;',
										onLoad: function() {
											this.getElement().setAttribute("title-cmd", this.id);
										},
										onClick: handlerButtons
									}
								]
							}
						]
					}
				]
			}
		]
		},
			{
				id: 'GrammTab',
				label: 'Grammar',
				accessKey: 'G',
				elements: [
					{
						type: 'html',
						id: 'banner',
						label: 'banner',
						style: '', //TODO
						html: '<div></div>'
					},
					{
						type: 'html',
						id: 'Content',
						label: 'GrammarContent',
						html: '',
						setup: function() {
							var tabId = NS.iframeNumber + '_' + NS.dialog._.currentTabId;
							var iframe = document.getElementById(tabId);
							NS.targetFromFrame[tabId] = iframe.contentWindow;
						}
					},
					{
						type: 'vbox',
						id: 'bottomGroup',
						style: 'width:560px; margin: 0 auto;',
						children: [
							{
								type: 'hbox',
								id: 'leftCol',
								widths: ['66%', '34%'],
								children: [
									{
										type: 'vbox',
										children: [
											{
												type: 'text',
												id: 'text',
												label: "Change to:",
												labelLayout: 'horizontal',
												labelStyle: 'font: 12px/25px arial, sans-serif;',
												inputStyle: 'float: right; width: 200px;',
												'default': '',
												onShow: function() {
													NS.textNode['GrammTab'] = this;
												},
												onHide: function() {
													this.reset();
												}
											},
											{
												type: 'html',
												id: 'html_text',
												html: "<div style='min-height: 17px; line-height: 17px; padding: 5px; text-align: left;background: #F1F1F1;color: #595959; white-space: normal!important;'></div>",
												onShow: function(e) {
													NS.textNodeInfo['GrammTab'] = this;
												}
											},
											{
												type: 'html',
												id: 'radio',
												html: "",
												onShow: function() {
													NS.grammerSuggest = this;
												}
											}
										]
									},
									{
										type: 'vbox',
										children: [
											{
												type: 'button',
												id: 'ChangeTo',
												label: 'Change to',
												title: 'Change to',
												style: 'width: 133px; float: right;',
												onLoad: function() {
													this.getElement().setAttribute("title-cmd", this.id);
												},
												onClick: handlerButtons
											},
											{
												type: 'button',
												id: 'IgnoreWord',
												label: 'Ignore word',
												title: 'Ignore word',
												style: 'width: 133px; float: right;',
												onLoad: function() {
													this.getElement().setAttribute("title-cmd", this.id);
												},
												onClick: handlerButtons
											},
											{
												type: 'button',
												id: 'IgnoreAllWords',
												label: 'Ignore Problem',
												title: 'Ignore Problem',
												style: 'width: 133px; float: right;',
												onLoad: function() {
													this.getElement().setAttribute("title-cmd", this.id);
												},
												onClick: handlerButtons
											},
											{
												type: 'button',
												id: 'FinishChecking',
												label: 'Finish Checking',
												title: 'Finish Checking',
												style: 'width: 133px; float: right; margin-top: 9px;',
												onLoad: function() {
													this.getElement().setAttribute("title-cmd", this.id);
												},
												onClick: handlerButtons
											}
										]
									}
								]
							}
						]
					},
					{
						type: 'hbox',
						id: 'BlockFinishChecking',
						style: 'width:560px; margin: 0 auto;',
						widths: ['70%', '30%'],
						onShow: function() {
							this.getElement().hide();
						},
						onHide: showCurrentTabs,
						children: [
							{
								type: 'hbox',
								id: 'leftCol',
								align: 'left',
								width: '70%',
								children: [
									{
										type: 'vbox',
										id: 'rightCol1',
										children: [
											{
												type: 'html',
												id: 'logo',
												html: '<img width="99" height="68" border="0" src="" title="WebSpellChecker.net" alt="WebSpellChecker.net" style="display: inline-block;">',
												setup: function() {
													this.getElement().$.src = NS.logotype;
													this.getElement().getParent().setStyles({
														"text-align": "center"
													});
												}
											}
										]
									}
								]
							},
							{
								type: 'hbox',
								id: 'rightCol',
								align: 'right',
								width: '30%',
								children: [
									{
										type: 'vbox',
										id: 'rightCol_col__left',
										children: [
											{
												type: 'button',
												id: 'FinishChecking',
												label: 'Finish Checking',
												title: 'Finish Checking',
												style: 'width: 100%;',
												onLoad: function() {
													this.getElement().setAttribute("title-cmd", this.id);
												},
												onClick: handlerButtons
											}
										]
									}
								]
							}
						]
					}
				]
			},
			{
				id: 'Thesaurus',
				label: 'Thesaurus',
				accessKey: 'T',
				elements: [
					{
						type: 'html',
						id: 'banner',
						label: 'banner',
						style: '', //TODO
						html: '<div></div>'
					},
					{
						type: 'html',
						id: 'Content',
						label: 'spellContent',
						html: '',
						setup: function() {
							var tabId = NS.iframeNumber + '_' + NS.dialog._.currentTabId;
							var iframe = document.getElementById(tabId);
							NS.targetFromFrame[tabId] = iframe.contentWindow;
						}
					},
					{
						type: 'vbox',
						id: 'bottomGroup',
						style: 'width:560px; margin: -10px auto; overflow: hidden;',
						children: [
							{
								type: 'hbox',
								widths: ['75%', '25%'],
								children: [
									{
										type: 'vbox',
										children: [
											{
												type: 'hbox',
												widths: ['65%', '35%'],
												children: [
													{
														type: 'text',
														id: 'ChangeTo',
														label: 'Change to:',
														labelLayout: 'horizontal',
														inputStyle: 'width: 160px;',
														labelStyle: 'font: 12px/25px arial, sans-serif;',
														'default': '',
														onShow: function(e) {
															NS.textNode['Thesaurus'] = this;
														},
														onHide: function() {
															this.reset();
														}
													},
													{
														type: 'button',
														id: 'ChangeTo',
														label: 'Change to',
														title: 'Change to',
														style: 'width: 121px; margin-top: 1px;',
														onLoad: function() {
															this.getElement().setAttribute("title-cmd", this.id);
														},
														onClick: handlerButtons
													}
												]
											},
											{
												type: 'hbox',
												children: [
													{
														type: 'select',
														id: 'categories',
														label: "Categories:",
														labelStyle: 'font: 12px/25px arial, sans-serif;',
														size: '5',
														inputStyle: 'width: 180px; height: auto;',
														items: [],
														onShow: function() {
															NS.selectNode['categories'] = this;
														},
														onHide: function() {
															this.clear();
														},
														onChange: function() {
															NS.buildOptionSynonyms(this.getValue());
														}
													},
													{
														type: 'select',
														id: 'synonyms',
														label: "Synonyms:",
														labelStyle: 'font: 12px/25px arial, sans-serif;',
														size: '5',
														inputStyle: 'width: 180px; height: auto;',
														items: [],
														onShow: function() {
															NS.selectNode['synonyms'] = this;
															NS.textNode['Thesaurus'].setValue(this.getValue());
														},
														onHide: function() {
															this.clear();
														},
														onChange: function(e) {
															NS.textNode['Thesaurus'].setValue(this.getValue());
														}
													}
												]
											}
										]
									},
									{
										type: 'vbox',
										width: '120px',
										style: "margin-top:46px;",
										children: [
											{
												type: 'html',
												id: 'logotype',
												label: 'WebSpellChecker.net',
												html: '<img width="99" height="68" border="0" src="" title="WebSpellChecker.net" alt="WebSpellChecker.net" style="display: inline-block;">',
												setup: function() {
													this.getElement().$.src = NS.logotype;
													this.getElement().getParent().setStyles({
														"text-align": "center"
													});
												}
											},
											{
												type: 'button',
												id: 'FinishChecking',
												label: 'Finish Checking',
												title: 'Finish Checking',
												style: 'width: 121px; float: right; margin-top: 9px;',
												onLoad: function() {
													this.getElement().setAttribute("title-cmd", this.id);
												},
												onClick: handlerButtons
											}
										]
									}
								]
							}
						]
					},
					{
						type: 'hbox',
						id: 'BlockFinishChecking',
						style: 'width:560px; margin: 0 auto;',
						widths: ['70%', '30%'],
						onShow: function() {
							this.getElement().hide();
						},
						children: [
							{
								type: 'hbox',
								id: 'leftCol',
								align: 'left',
								width: '70%',
								children: [
									{
										type: 'vbox',
										id: 'rightCol1',
										children: [
											{
												type: 'html',
												id: 'logo',
												html: '<img width="99" height="68" border="0" src="" title="WebSpellChecker.net" alt="WebSpellChecker.net" style="display: inline-block;">',
												setup: function() {
													this.getElement().$.src = NS.logotype;
													this.getElement().getParent().setStyles({
														"text-align": "center"
													});
												}
											}
										]
									}
								]
							},
							{
								type: 'hbox',
								id: 'rightCol',
								align: 'right',
								width: '30%',
								children: [
									{
										type: 'vbox',
										id: 'rightCol_col__left',
										children: [
											{
												type: 'button',
												id: 'FinishChecking',
												label: 'Finish Checking',
												title: 'Finish Checking',
												style: 'width: 100%;',
												onLoad: function() {
													this.getElement().setAttribute("title-cmd", this.id);
												},
												onClick: handlerButtons
											}
										]
									}
								]
							}
						]
					}
				]
			}
		]
	};
});

// Options dialog
CKEDITOR.dialog.add('options', function(editor) {
	var dialog = null;
	var linkOnCheckbox = {};
	var checkboxState = {};
	var ospString = null;
	var OptionsTextError = null;
	var cmd = null;

	var set_osp = [];
	var dictionaryState = {
		'udn': appTools.cookie.get('udn'),
		'osp': appTools.cookie.get('osp')
	};

	var setHandlerOptions = function() {
		var osp = appTools.cookie.get('osp'),
			strToArr =  osp.split("");

		checkboxState['IgnoreAllCapsWords']		= strToArr[0];
		checkboxState['IgnoreWordsNumbers']		= strToArr[1];
		checkboxState['IgnoreMixedCaseWords']	= strToArr[2];
		checkboxState['IgnoreDomainNames']		= strToArr[3];
	};

	var sendDicOptions = function(event) {
		event = event || window.event;
		cmd = this.getElement().getAttribute("title-cmd");
		var osp = [];

		osp[0] = checkboxState['IgnoreAllCapsWords'];
		osp[1] = checkboxState['IgnoreWordsNumbers'];
		osp[2] = checkboxState['IgnoreMixedCaseWords'];
		osp[3] = checkboxState['IgnoreDomainNames'];

		osp = osp.toString().replace(/,/g, "");


		appTools.cookie.set('osp', osp);
		appTools.cookie.set('udnCmd', cmd ? cmd : 'ignore');
		if (cmd == "delete") {

			appTools.postMessage.send({
				'id': 'options_dic_send'
			});
		} else {
			var udn = '';
			if(nameNode.getValue() !== ''){
				udn = nameNode.getValue();
			}
			appTools.cookie.set('udn', udn);
			appTools.postMessage.send({
				'id': 'options_dic_send'
			});
		}

	};


	var sendAllOptions = function() {
		var osp = [];

		osp[0] = checkboxState['IgnoreAllCapsWords'];
		osp[1] = checkboxState['IgnoreWordsNumbers'];
		osp[2] = checkboxState['IgnoreMixedCaseWords'];
		osp[3] = checkboxState['IgnoreDomainNames'];

		osp = osp.toString().replace(/,/g, "");

		appTools.cookie.set('osp', osp);
		appTools.cookie.set('udn', nameNode.getValue());

		appTools.postMessage.send({
			'id': 'options_checkbox_send'
		});


	};

	var cameOptions = function() {
		OptionsTextError.getElement().setHtml(NS.LocalizationComing['error']);
		OptionsTextError.getElement().show();
	};

	return {
		title: NS.LocalizationComing['Options'],
		minWidth: 430,
		minHeight: 130,
		resizable: CKEDITOR.DIALOG_RESIZE_NONE,
		contents: [
			{
			id: 'OptionsTab',
			label: 'Options',
			accessKey: 'O',
			elements: [
				{
					type: 'hbox',
					id: 'options_error',
					children: [
						{
							type: 'html',
							style: "display: block;text-align: center;white-space: normal!important; font-size: 12px;color:red",
							html: '<div></div>',
							onShow: function() {
								OptionsTextError = this;
							}
						}
					]
				},
				{
				type: 'vbox',
				id: 'Options_content',
				children: [
					{
						type: 'hbox',
						id: 'Options_manager',
						widths: ['52%', '48%'],
						children: [
							{
								type: 'fieldset',
								label: 'Spell Checking Options',
								style: 'border: none;margin-top: 13px;padding: 10px 0 10px 10px',
								onShow: function() {
									this.getInputElement().$.children[0].innerHTML = NS.LocalizationComing['SpellCheckingOptions'];
								},
								children: [
									{
										type: 'vbox',
										id: 'Options_checkbox',
										children: [
											{
												type: 'checkbox',
												id: 'IgnoreAllCapsWords',
												label: 'Ignore All-Caps Words',
												labelStyle: 'margin-left: 5px; font: 12px/16px arial, sans-serif;display: inline-block;white-space: normal;',
												style: "float:left; min-height: 16px;",
												'default': '',
												onClick: function() {
													checkboxState[this.id] = (!this.getValue()) ? 0 : 1;
												}
											},
											{
												type: 'checkbox',
												id: 'IgnoreWordsNumbers',
												label: 'Ignore Words with Numbers',
												labelStyle: 'margin-left: 5px; font: 12px/16px arial, sans-serif;display: inline-block;white-space: normal;',
												style: "float:left; min-height: 16px;",
												'default': '',
												onClick: function() {
													checkboxState[this.id] = (!this.getValue()) ? 0 : 1;
												}
											},
											{
												type: 'checkbox',
												id: 'IgnoreMixedCaseWords',
												label: 'Ignore Mixed-Case Words',
												labelStyle: 'margin-left: 5px; font: 12px/16px arial, sans-serif;display: inline-block;white-space: normal;',
												style: "float:left; min-height: 16px;",
												'default': '',
												onClick: function() {
													checkboxState[this.id] = (!this.getValue()) ? 0 : 1;
												}
											},
											{
												type: 'checkbox',
												id: 'IgnoreDomainNames',
												label: 'Ignore Domain Names',
												labelStyle: 'margin-left: 5px; font: 12px/16px arial, sans-serif;display: inline-block;white-space: normal;',
												style: "float:left; min-height: 16px;",
												'default': '',
												onClick: function() {
													checkboxState[this.id] = (!this.getValue()) ? 0 : 1;
												}
											}
									]
								}
							]
						},
						{
							type: 'vbox',
							id: 'Options_DictionaryName',
							children: [
								{
									type: 'text',
									id: 'DictionaryName',
									style: 'margin-bottom: 10px',
									label: 'Dictionary Name:',
									labelLayout: 'vertical',
									labelStyle: 'font: 12px/25px arial, sans-serif;',
									'default': '',
									onLoad: function() {
										nameNode = this;
										var udn = NS.userDictionaryName ? NS.userDictionaryName : appTools.cookie.get('udn') && undefined ? ' ' : this.getValue();
										this.setValue(udn);
									},
									onShow: function() {
										nameNode = this;
										var udn = !appTools.cookie.get('udn') ? this.getValue() : appTools.cookie.get('udn');
										this.setValue(udn);
										this.setLabel(NS.LocalizationComing['DictionaryName']);
									},
									onHide: function() {
										this.reset();
									}
								},
								{
									type: 'hbox',
									id: 'Options_buttons',
									children: [
										{
											type: 'vbox',
											id: 'Options_leftCol_col',
											widths: ['50%', '50%'],
											children: [
												{
													type: 'button',
													id: 'create',
													label: 'Create',
													title: 'Create',
													style: 'width: 100%;',
													onLoad: function() {
														this.getElement().setAttribute("title-cmd", this.id);
													},
													onShow: function() {
														this.getElement().setText(NS.LocalizationComing['Create']);
													},
													onClick: sendDicOptions
												},
												{
													type: 'button',
													id: 'restore',
													label: 'Restore',
													title: 'Restore',
													style: 'width: 100%;',
													onLoad: function() {
														this.getElement().setAttribute("title-cmd", this.id);
													},
													onShow: function() {
														this.getElement().setText(NS.LocalizationComing['Restore']);
													},
													onClick: sendDicOptions
												}
											]
										},
										{
											type: 'vbox',
											id: 'Options_rightCol_col',
											widths: ['50%', '50%'],
											children: [
												{
													type: 'button',
													id: 'rename',
													label: 'Rename',
													title: 'Rename',
													style: 'width: 100%;',
													onLoad: function() {
														this.getElement().setAttribute("title-cmd", this.id);
													},
													onShow: function() {
														this.getElement().setText(NS.LocalizationComing['Rename']);
													},
													onClick: sendDicOptions
												},
												{
													type: 'button',
													id: 'delete',
													label: 'Remove',
													title: 'Remove',
													style: 'width: 100%;',
													onLoad: function() {
														this.getElement().setAttribute("title-cmd", this.id);
													},
													onShow: function() {
														this.getElement().setText(NS.LocalizationComing['Remove']);
													},
													onClick: sendDicOptions
												}
											]
										}
									]
								}
							]
						}
					]
				},
				{
					type: 'hbox',
					id: 'Options_text',
					children: [
						{
							type: 'html',
							style: "text-align: justify;margin-top: 15px;white-space: normal!important; font-size: 12px;color:#777;",
							html: "<div>" + NS.LocalizationComing['OptionsTextIntro'] + "</div>",
							onShow: function() {
								this.getElement().setText(NS.LocalizationComing['OptionsTextIntro']);
							}
						}
					]
				}
			]
		}
	]
}
],
		buttons: [CKEDITOR.dialog.okButton, CKEDITOR.dialog.cancelButton],
		onOk: function() {
			sendAllOptions();
			OptionsTextError.getElement().hide();
			OptionsTextError.getElement().setHtml(' ');
		},
		onLoad: function() {
			dialog = this;
			appTools.postMessage.init(cameOptions);

			linkOnCheckbox['IgnoreAllCapsWords'] = dialog.getContentElement('OptionsTab', 'IgnoreAllCapsWords');
			linkOnCheckbox['IgnoreWordsNumbers'] = dialog.getContentElement('OptionsTab', 'IgnoreWordsNumbers');
			linkOnCheckbox['IgnoreMixedCaseWords'] = dialog.getContentElement('OptionsTab', 'IgnoreMixedCaseWords');
			linkOnCheckbox['IgnoreDomainNames'] = dialog.getContentElement('OptionsTab', 'IgnoreDomainNames');

		},
		onShow: function() {
			setHandlerOptions();

			(!parseInt(checkboxState['IgnoreAllCapsWords'], 10)) ? linkOnCheckbox['IgnoreAllCapsWords'].setValue('', false) : linkOnCheckbox['IgnoreAllCapsWords'].setValue('checked', false);
			(!parseInt(checkboxState['IgnoreWordsNumbers'], 10)) ? linkOnCheckbox['IgnoreWordsNumbers'].setValue('', false) : linkOnCheckbox['IgnoreWordsNumbers'].setValue('checked', false);
			(!parseInt(checkboxState['IgnoreMixedCaseWords'], 10)) ? linkOnCheckbox['IgnoreMixedCaseWords'].setValue('', false) : linkOnCheckbox['IgnoreMixedCaseWords'].setValue('checked', false);
			(!parseInt(checkboxState['IgnoreDomainNames'], 10)) ? linkOnCheckbox['IgnoreDomainNames'].setValue('', false) : linkOnCheckbox['IgnoreDomainNames'].setValue('checked', false);

			checkboxState['IgnoreAllCapsWords'] = (!linkOnCheckbox['IgnoreAllCapsWords'].getValue()) ? 0 : 1;
			checkboxState['IgnoreWordsNumbers'] = (!linkOnCheckbox['IgnoreWordsNumbers'].getValue()) ? 0 : 1;
			checkboxState['IgnoreMixedCaseWords'] = (!linkOnCheckbox['IgnoreMixedCaseWords'].getValue()) ? 0 : 1;
			checkboxState['IgnoreDomainNames'] = (!linkOnCheckbox['IgnoreDomainNames'].getValue()) ? 0 : 1;

			linkOnCheckbox['IgnoreAllCapsWords'].getElement().$.lastChild.innerHTML = NS.LocalizationComing['IgnoreAllCapsWords'];
			linkOnCheckbox['IgnoreWordsNumbers'].getElement().$.lastChild.innerHTML = NS.LocalizationComing['IgnoreWordsWithNumbers'];
			linkOnCheckbox['IgnoreMixedCaseWords'].getElement().$.lastChild.innerHTML = NS.LocalizationComing['IgnoreMixedCaseWords'];
			linkOnCheckbox['IgnoreDomainNames'].getElement().$.lastChild.innerHTML = NS.LocalizationComing['IgnoreDomainNames'];
		}
	};
});

// Expand the spell-check frame when dialog resized. (#6829)
CKEDITOR.dialog.on( 'resize', function( evt ) {
	var data = evt.data,
		dialog = data.dialog,
		currentTabId = dialog._.currentTabId,
		tabID = NS.iframeNumber + '_' + currentTabId,
		iframe = CKEDITOR.document.getById(tabID);

	if ( dialog._.name == 'checkspell' ) {
		if (NS.bnr) {
			iframe && iframe.setSize( 'height', data.height - '310' );
		} else {
			iframe && iframe.setSize( 'height', data.height - '220' );
		}
	}
});

CKEDITOR.on('dialogDefinition', function(dialogDefinitionEvent) {
    var dialogDefinition = dialogDefinitionEvent.data.definition;

    NS.onLoadOverlay = new overlayBlock({
		opacity: "1",
		background: "#fff",
		target: dialogDefinition.dialog.parts.tabs.getParent().$
	});
	NS.onLoadOverlay.setEnable();
	dialogDefinition.dialog.on('show', function(event) {
		//NS.div_overlay.setEnable();
	});
    dialogDefinition.dialog.on('cancel', function(cancelEvent) {
    		dialogDefinition.dialog.getParentEditor().config.wsc_onClose.call(this.document.getWindow().getFrame());
    		NS.div_overlay.setDisable();
    	return false;
    }, this, null, -1);
});
})();
