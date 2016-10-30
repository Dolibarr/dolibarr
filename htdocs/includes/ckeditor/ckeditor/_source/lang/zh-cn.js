/**
 * @license Copyright (c) 2003-2014, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */

/**
 * @fileOverview Defines the {@link CKEDITOR.lang} object, for the
 * Chinese Simplified language.
 */

/**#@+
   @type String
   @example
*/

/**
 * Contains the dictionary of language entries.
 * @namespace
 */
CKEDITOR.lang[ 'zh-cn' ] = {
	// ARIA description.
	editor: '所见即所得编辑器',
	editorPanel: '所见即所得编辑器面板',

	// Common messages and labels.
	common: {
		// Screenreader titles. Please note that screenreaders are not always capable
		// of reading non-English words. So be careful while translating it.
		editorHelp: '按 ALT+0 获得帮助',

		browseServer: '浏览服务器',
		url: 'URL',
		protocol: '协议',
		upload: '上传',
		uploadSubmit: '上传到服务器',
		image: '图像',
		flash: 'Flash',
		form: '表单',
		checkbox: '复选框',
		radio: '单选按钮',
		textField: '单行文本',
		textarea: '多行文本',
		hiddenField: '隐藏域',
		button: '按钮',
		select: '列表/菜单',
		imageButton: '图像按钮',
		notSet: '<没有设置>',
		id: 'ID',
		name: '名称',
		langDir: '语言方向',
		langDirLtr: '从左到右 (LTR)',
		langDirRtl: '从右到左 (RTL)',
		langCode: '语言代码',
		longDescr: '详细说明 URL',
		cssClass: '样式类名称',
		advisoryTitle: '标题',
		cssStyle: '行内样式',
		ok: '确定',
		cancel: '取消',
		close: '关闭',
		preview: '预览',
		resize: '拖拽以改变大小',
		generalTab: '常规',
		advancedTab: '高级',
		validateNumberFailed: '需要输入数字格式',
		confirmNewPage: '当前文档内容未保存，是否确认新建文档？',
		confirmCancel: '部分修改尚未保存，是否确认关闭对话框？',
		options: '选项',
		target: '目标窗口',
		targetNew: '新窗口 (_blank)',
		targetTop: '整页 (_top)',
		targetSelf: '本窗口 (_self)',
		targetParent: '父窗口 (_parent)',
		langDirLTR: '从左到右 (LTR)',
		langDirRTL: '从右到左 (RTL)',
		styles: '样式',
		cssClasses: '样式类',
		width: '宽度',
		height: '高度',
		align: '对齐方式',
		alignLeft: '左对齐',
		alignRight: '右对齐',
		alignCenter: '居中',
		alignTop: '顶端',
		alignMiddle: '居中',
		alignBottom: '底部',
		invalidValue	: '无效的值。',
		invalidHeight: '高度必须为数字格式',
		invalidWidth: '宽度必须为数字格式',
		invalidCssLength: '此“%1”字段的值必须为正数，可以包含或不包含一个有效的 CSS 长度单位(px, %, in, cm, mm, em, ex, pt 或 pc)',
		invalidHtmlLength: '此“%1”字段的值必须为正数，可以包含或不包含一个有效的 HTML 长度单位(px 或 %)',
		invalidInlineStyle: '内联样式必须为格式是以分号分隔的一个或多个“属性名 : 属性值”。',
		cssLengthTooltip: '输入一个表示像素值的数字，或加上一个有效的 CSS 长度单位(px, %, in, cm, mm, em, ex, pt 或 pc)。',

		// Put the voice-only part of the label in the span.
		unavailable: '%1<span class="cke_accessibility">，不可用</span>'
	}
};
