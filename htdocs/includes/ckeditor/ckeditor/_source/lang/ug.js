/**
 * @license Copyright (c) 2003-2014, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */

/**
 * @fileOverview
 */

/**#@+
   @type String
   @example
*/

/**
 * Contains the dictionary of language entries.
 * @namespace
 */
CKEDITOR.lang[ 'ug' ] = {
	// ARIA description.
	editor: 'تەھرىرلىگۈچ',
	editorPanel: 'Rich Text Editor panel', // MISSING

	// Common messages and labels.
	common: {
		// Screenreader titles. Please note that screenreaders are not always capable
		// of reading non-English words. So be careful while translating it.
		editorHelp: 'ALT+0 نى بېسىپ ياردەمنى كۆرۈڭ',

		browseServer: 'كۆرسىتىش مۇلازىمېتىر',
		url: 'ئەسلى ھۆججەت',
		protocol: 'كېلىشىم',
		upload: 'يۈكلە',
		uploadSubmit: 'مۇلازىمېتىرغا يۈكلە',
		image: 'سۈرەت',
		flash: 'Flash',
		form: 'جەدۋەل',
		checkbox: 'كۆپ تاللاش رامكىسى',
		radio: 'يەككە تاللاش توپچىسى',
		textField: 'يەككە قۇر تېكىست',
		textarea: 'كۆپ قۇر تېكىست',
		hiddenField: 'يوشۇرۇن دائىرە',
		button: 'توپچا',
		select: 'تىزىم/تىزىملىك',
		imageButton: 'سۈرەت دائىرە',
		notSet: '‹تەڭشەلمىگەن›',
		id: 'ID',
		name: 'ئات',
		langDir: 'تىل يۆنىلىشى',
		langDirLtr: 'سولدىن ئوڭغا (LTR)',
		langDirRtl: 'ئوڭدىن سولغا (RTL)',
		langCode: 'تىل كودى',
		longDescr: 'تەپسىلىي چۈشەندۈرۈش ئادرېسى',
		cssClass: 'ئۇسلۇب خىلىنىڭ ئاتى',
		advisoryTitle: 'ماۋزۇ',
		cssStyle: 'قۇر ئىچىدىكى ئۇسلۇبى',
		ok: 'جەزملە',
		cancel: 'ۋاز كەچ',
		close: 'تاقا',
		preview: 'ئالدىن كۆزەت',
		resize: 'چوڭلۇقىنى ئۆزگەرت',
		generalTab: 'ئادەتتىكى',
		advancedTab: 'ئالىي',
		validateNumberFailed: 'سان پىچىمىدا كىرگۈزۈش زۆرۈر',
		confirmNewPage: 'نۆۋەتتىكى پۈتۈك مەزمۇنى ساقلانمىدى، يېڭى پۈتۈك قۇرامسىز؟',
		confirmCancel: 'قىسمەن ئۆزگەرتىش ساقلانمىدى، بۇ سۆزلەشكۈنى تاقامسىز؟',
		options: 'تاللانما',
		target: 'نىشان كۆزنەك',
		targetNew: 'يېڭى كۆزنەك (_blank)',
		targetTop: 'پۈتۈن بەت (_top)',
		targetSelf: 'مەزكۇر كۆزنەك (_self)',
		targetParent: 'ئاتا كۆزنەك (_parent)',
		langDirLTR: 'سولدىن ئوڭغا (LTR)',
		langDirRTL: 'ئوڭدىن سولغا (RTL)',
		styles: 'ئۇسلۇبلار',
		cssClasses: 'ئۇسلۇب خىللىرى',
		width: 'كەڭلىك',
		height: 'ئېگىزلىك',
		align: 'توغرىلىنىشى',
		alignLeft: 'سول',
		alignRight: 'ئوڭ',
		alignCenter: 'ئوتتۇرا',
		alignTop: 'ئۈستى',
		alignMiddle: 'ئوتتۇرا',
		alignBottom: 'ئاستى',
		invalidValue	: 'ئىناۋەتسىز قىممەت.',
		invalidHeight: 'ئېگىزلىك چوقۇم رەقەم پىچىمىدا بولۇشى زۆرۈر',
		invalidWidth: 'كەڭلىك چوقۇم رەقەم پىچىمىدا بولۇشى زۆرۈر',
		invalidCssLength: 'بۇ سۆز بۆلىكى چوقۇم مۇۋاپىق بولغان CSS ئۇزۇنلۇق قىممىتى بولۇشى زۆرۈر، بىرلىكى (px, %, in, cm, mm, em, ex, pt ياكى pc)',
		invalidHtmlLength: 'بۇ سۆز بۆلىكى چوقۇم بىرىكمە HTML ئۇزۇنلۇق قىممىتى بولۇشى كېرەك. ئۆز ئىچىگە ئالىدىغان بىرلىك (px ياكى %)',
		invalidInlineStyle: 'ئىچكى باغلانما ئۇسلۇبى چوقۇم چېكىتلىك پەش بىلەن ئايرىلغان بىر ياكى كۆپ «خاسلىق ئاتى:خاسلىق قىممىتى» پىچىمىدا بولۇشى لازىم',
		cssLengthTooltip: 'بۇ سۆز بۆلىكى بىرىكمە CSS ئۇزۇنلۇق قىممىتى بولۇشى كېرەك. ئۆز ئىچىگە ئالىدىغان بىرلىك (px, %, in, cm, mm, em, ex, pt ياكى pc)',

		// Put the voice-only part of the label in the span.
		unavailable: '%1<span class=\\\\"cke_accessibility\\\\">، ئىشلەتكىلى بولمايدۇ</span>'
	}
};
