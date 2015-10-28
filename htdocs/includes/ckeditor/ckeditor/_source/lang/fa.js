/**
 * @license Copyright (c) 2003-2014, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */

/**
 * @fileOverview Defines the {@link CKEDITOR.lang} object for the
 * Persian language.
 */

/**#@+
   @type String
   @example
*/

/**
 * Contains the dictionary of language entries.
 * @namespace
 */
CKEDITOR.lang[ 'fa' ] = {
	// ARIA description.
	editor: 'ویرایشگر متن کامل',
	editorPanel: 'پنل ویرایشگر متن غنی',

	// Common messages and labels.
	common: {
		// Screenreader titles. Please note that screenreaders are not always capable
		// of reading non-English words. So be careful while translating it.
		editorHelp: 'کلید Alt+0 را برای راهنمایی بفشارید',

		browseServer: 'فهرست​نمایی سرور',
		url: 'URL',
		protocol: 'پروتکل',
		upload: 'آپلود',
		uploadSubmit: 'به سرور بفرست',
		image: 'تصویر',
		flash: 'فلش',
		form: 'فرم',
		checkbox: 'چک‌باکس',
		radio: 'دکمه‌ی رادیویی',
		textField: 'فیلد متنی',
		textarea: 'ناحیهٴ متنی',
		hiddenField: 'فیلد پنهان',
		button: 'دکمه',
		select: 'فیلد انتخاب چند گزینه​ای',
		imageButton: 'دکمه‌ی تصویری',
		notSet: '<تعین نشده>',
		id: 'شناسه',
		name: 'نام',
		langDir: 'جهت​نمای زبان',
		langDirLtr: 'چپ به راست',
		langDirRtl: 'راست به چپ',
		langCode: 'کد زبان',
		longDescr: 'URL توصیف طولانی',
		cssClass: 'کلاس​های شیوه​نامه (Stylesheet)',
		advisoryTitle: 'عنوان کمکی',
		cssStyle: 'شیوه (style)',
		ok: 'پذیرش',
		cancel: 'انصراف',
		close: 'بستن',
		preview: 'پیش‌نمایش',
		resize: 'تغییر اندازه',
		generalTab: 'عمومی',
		advancedTab: 'پیشرفته',
		validateNumberFailed: 'این مقدار یک عدد نیست.',
		confirmNewPage: 'هر تغییر ایجاد شده​ی ذخیره نشده از بین خواهد رفت. آیا اطمینان دارید که قصد بارگیری صفحه جدیدی را دارید؟',
		confirmCancel: 'برخی از گزینه‌ها تغییر کرده‌اند. آیا واقعا قصد بستن این پنجره را دارید؟',
		options: 'گزینه​ها',
		target: 'نحوه باز کردن',
		targetNew: 'پنجره جدید',
		targetTop: 'بالاترین پنجره',
		targetSelf: 'همان پنجره',
		targetParent: 'پنجره والد',
		langDirLTR: 'چپ به راست',
		langDirRTL: 'راست به چپ',
		styles: 'سبک',
		cssClasses: 'کلاس‌های شیوه‌نامه',
		width: 'عرض',
		height: 'طول',
		align: 'چینش',
		alignLeft: 'چپ',
		alignRight: 'راست',
		alignCenter: 'مرکز',
		alignTop: 'بالا',
		alignMiddle: 'وسط',
		alignBottom: 'پائین',
		invalidValue	: 'مقدار نامعتبر.',
		invalidHeight: 'ارتفاع باید یک عدد باشد.',
		invalidWidth: 'عرض باید یک عدد باشد.',
		invalidCssLength: 'عدد تعیین شده برای فیلد "%1" باید یک عدد مثبت با یا بدون یک واحد اندازه گیری CSS معتبر باشد (px, %, in, cm, mm, em, ex, pt, or pc).',
		invalidHtmlLength: 'عدد تعیین شده برای فیلد "%1" باید یک عدد مثبت با یا بدون یک واحد اندازه گیری HTML معتبر باشد (px or %).',
		invalidInlineStyle: 'عدد تعیین شده برای سبک درون​خطی -Inline Style- باید دارای یک یا چند چندتایی با شکلی شبیه "name : value" که باید با یک ";" از هم جدا شوند.',
		cssLengthTooltip: 'یک عدد برای یک مقدار بر حسب پیکسل و یا یک عدد با یک واحد CSS معتبر وارد کنید (px, %, in, cm, mm, em, ex, pt, or pc).',

		// Put the voice-only part of the label in the span.
		unavailable: '%1<span class="cke_accessibility">، غیر قابل دسترس</span>'
	}
};
