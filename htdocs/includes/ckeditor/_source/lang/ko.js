/**
 * @license Copyright (c) 2003-2014, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */

/**
 * @fileOverview Defines the {@link CKEDITOR.lang} object, for the
 * Korean language.
 */

/**#@+
   @type String
   @example
*/

/**
 * Contains the dictionary of language entries.
 * @namespace
 */
CKEDITOR.lang[ 'ko' ] = {
	// ARIA description.
	editor: '리치 텍스트 편집기',
	editorPanel: 'Rich Text Editor panel', // MISSING

	// Common messages and labels.
	common: {
		// Screenreader titles. Please note that screenreaders are not always capable
		// of reading non-English words. So be careful while translating it.
		editorHelp: '도움이 필요하시면 ALT 0 을 누르세요',

		browseServer: '서버 보기',
		url: 'URL',
		protocol: '프로토콜',
		upload: '업로드',
		uploadSubmit: '서버로 전송',
		image: '이미지',
		flash: '플래쉬',
		form: '폼',
		checkbox: '체크박스',
		radio: '라디오버튼',
		textField: '입력필드',
		textarea: '입력영역',
		hiddenField: '숨김필드',
		button: '버튼',
		select: '펼침목록',
		imageButton: '이미지버튼',
		notSet: '<설정되지 않음>',
		id: 'ID',
		name: 'Name',
		langDir: '쓰기 방향',
		langDirLtr: '왼쪽에서 오른쪽 (LTR)',
		langDirRtl: '오른쪽에서 왼쪽 (RTL)',
		langCode: '언어 코드',
		longDescr: 'URL 설명',
		cssClass: 'Stylesheet Classes',
		advisoryTitle: 'Advisory Title',
		cssStyle: 'Style',
		ok: '예',
		cancel: '아니오',
		close: '닫기',
		preview: '미리보기',
		resize: '크기 조절',
		generalTab: '일반',
		advancedTab: '자세히',
		validateNumberFailed: '이 값은 숫자가 아닙니다.',
		confirmNewPage: '저장하지 않은 모든 변경사항은 유실됩니다. 정말로 새로운 페이지를 부르겠습니까?',
		confirmCancel: '몇몇개의 옵션이 바꼈습니다. 정말로 창을 닫으시겠습니까?',
		options: '옵션',
		target: '타겟',
		targetNew: '새로운 창 (_blank)',
		targetTop: '최상위 창 (_top)',
		targetSelf: '같은 창 (_self)',
		targetParent: '부모 창 (_parent)',
		langDirLTR: '왼쪽에서 오른쪽 (LTR)',
		langDirRTL: '오른쪽에서 왼쪽 (RTL)',
		styles: 'Style',
		cssClasses: 'Stylesheet Classes',
		width: '너비',
		height: '높이',
		align: '정렬',
		alignLeft: '왼쪽',
		alignRight: '오른쪽',
		alignCenter: '가운데',
		alignTop: '위',
		alignMiddle: '중간',
		alignBottom: '아래',
		invalidValue	: '잘못된 값.',
		invalidHeight: '높이는 숫자여야 합니다.',
		invalidWidth: '넓이는 숫자여야 합니다.',
		invalidCssLength: 'Value specified for the "%1" field must be a positive number with or without a valid CSS measurement unit (px, %, in, cm, mm, em, ex, pt, or pc).', // MISSING
		invalidHtmlLength: 'Value specified for the "%1" field must be a positive number with or without a valid HTML measurement unit (px or %).', // MISSING
		invalidInlineStyle: 'Value specified for the inline style must consist of one or more tuples with the format of "name : value", separated by semi-colons.', // MISSING
		cssLengthTooltip: 'Enter a number for a value in pixels or a number with a valid CSS unit (px, %, in, cm, mm, em, ex, pt, or pc).', // MISSING

		// Put the voice-only part of the label in the span.
		unavailable: '%1<span class="cke_accessibility">, 사용할 수 없음</span>'
	}
};
