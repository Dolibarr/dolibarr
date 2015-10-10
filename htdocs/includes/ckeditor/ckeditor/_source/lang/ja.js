/**
 * @license Copyright (c) 2003-2014, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */

/**
 * @fileOverview Defines the {@link CKEDITOR.lang} object, for the
 * Japanese language.
 */

/**#@+
   @type String
   @example
*/

/**
 * Contains the dictionary of language entries.
 * @namespace
 */
CKEDITOR.lang[ 'ja' ] = {
	// ARIA description.
	editor: 'リッチテキストエディタ',
	editorPanel: 'リッチテキストエディタパネル',

	// Common messages and labels.
	common: {
		// Screenreader titles. Please note that screenreaders are not always capable
		// of reading non-English words. So be careful while translating it.
		editorHelp: 'ヘルプは ALT 0 を押してください',

		browseServer: 'サーバブラウザ',
		url: 'URL',
		protocol: 'プロトコル',
		upload: 'アップロード',
		uploadSubmit: 'サーバーに送信',
		image: 'イメージ',
		flash: 'Flash',
		form: 'フォーム',
		checkbox: 'チェックボックス',
		radio: 'ラジオボタン',
		textField: '１行テキスト',
		textarea: 'テキストエリア',
		hiddenField: '不可視フィールド',
		button: 'ボタン',
		select: '選択フィールド',
		imageButton: '画像ボタン',
		notSet: '<なし>',
		id: 'Id',
		name: 'Name属性',
		langDir: '文字表記の方向',
		langDirLtr: '左から右 (LTR)',
		langDirRtl: '右から左 (RTL)',
		langCode: '言語コード',
		longDescr: 'longdesc属性(長文説明)',
		cssClass: 'スタイルシートクラス',
		advisoryTitle: 'Title属性',
		cssStyle: 'スタイルシート',
		ok: 'OK',
		cancel: 'キャンセル',
		close: '閉じる',
		preview: 'プレビュー',
		resize: 'ドラッグしてリサイズ',
		generalTab: '全般',
		advancedTab: '高度な設定',
		validateNumberFailed: '値が数ではありません',
		confirmNewPage: '変更内容を保存せず、 新しいページを開いてもよろしいでしょうか？',
		confirmCancel: 'オプション設定を変更しました。ダイアログを閉じてもよろしいでしょうか？',
		options: 'オプション',
		target: 'ターゲット',
		targetNew: '新しいウインドウ (_blank)',
		targetTop: '最上部ウィンドウ (_top)',
		targetSelf: '同じウィンドウ (_self)',
		targetParent: '親ウィンドウ (_parent)',
		langDirLTR: '左から右 (LTR)',
		langDirRTL: '右から左 (RTL)',
		styles: 'スタイル',
		cssClasses: 'スタイルシートクラス',
		width: '幅',
		height: '高さ',
		align: '行揃え',
		alignLeft: '左',
		alignRight: '右',
		alignCenter: '中央',
		alignTop: '上',
		alignMiddle: '中央',
		alignBottom: '下',
		invalidValue	: '不正な値です。',
		invalidHeight: '高さは数値で入力してください。',
		invalidWidth: '幅は数値で入力してください。',
		invalidCssLength: '入力された "%1" 項目の値は、CSSの大きさ(px, %, in, cm, mm, em, ex, pt, または pc)が正しいものである/ないに関わらず、正の値である必要があります。',
		invalidHtmlLength: '入力された "%1" 項目の値は、HTMLの大きさ(px または %)が正しいものである/ないに関わらず、正の値である必要があります。',
		invalidInlineStyle: '入力されたインラインスタイルの値は、"名前 : 値" のフォーマットのセットで、複数の場合はセミコロンで区切られている形式である必要があります。',
		cssLengthTooltip: 'ピクセル数もしくはCSSにセットできる数値を入力してください。(px,%,in,cm,mm,em,ex,pt,or pc)',

		// Put the voice-only part of the label in the span.
		unavailable: '%1<span class="cke_accessibility">, 利用不可能</span>'
	}
};
