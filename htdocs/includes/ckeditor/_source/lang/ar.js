/**
 * @license Copyright (c) 2003-2014, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */

/**
 * @fileOverview Defines the {@link CKEDITOR.lang} object, for the
 * Arabic language.
 */

/**#@+
   @type String
   @example
*/

/**
 * Contains the dictionary of language entries.
 * @namespace
 */
CKEDITOR.lang[ 'ar' ] = {
	// ARIA description.
	editor: 'محرر النص الغني',
	editorPanel: 'Rich Text Editor panel', // MISSING

	// Common messages and labels.
	common: {
		// Screenreader titles. Please note that screenreaders are not always capable
		// of reading non-English words. So be careful while translating it.
		editorHelp: 'إضغط على ALT + 0 للحصول على المساعدة.',

		browseServer: 'تصفح',
		url: 'الرابط',
		protocol: 'البروتوكول',
		upload: 'رفع',
		uploadSubmit: 'أرسل',
		image: 'صورة',
		flash: 'فلاش',
		form: 'نموذج',
		checkbox: 'خانة إختيار',
		radio: 'زر اختيار',
		textField: 'مربع نص',
		textarea: 'مساحة نصية',
		hiddenField: 'إدراج حقل خفي',
		button: 'زر ضغط',
		select: 'اختار',
		imageButton: 'زر صورة',
		notSet: '<بدون تحديد>',
		id: 'الرقم',
		name: 'إسم',
		langDir: 'إتجاه النص',
		langDirLtr: 'اليسار لليمين (LTR)',
		langDirRtl: 'اليمين لليسار (RTL)',
		langCode: 'رمز اللغة',
		longDescr: 'الوصف التفصيلى',
		cssClass: 'فئات التنسيق',
		advisoryTitle: 'عنوان التقرير',
		cssStyle: 'نمط',
		ok: 'موافق',
		cancel: 'إلغاء الأمر',
		close: 'أغلق',
		preview: 'استعراض',
		resize: 'تغيير الحجم',
		generalTab: 'عام',
		advancedTab: 'متقدم',
		validateNumberFailed: 'لايوجد نتيجة',
		confirmNewPage: 'ستفقد أي متغييرات اذا لم تقم بحفظها اولا. هل أنت متأكد أنك تريد صفحة جديدة؟',
		confirmCancel: 'بعض الخيارات قد تغيرت. هل أنت متأكد من إغلاق مربع النص؟',
		options: 'خيارات',
		target: 'هدف الرابط',
		targetNew: 'نافذة جديدة',
		targetTop: 'النافذة الأعلى',
		targetSelf: 'داخل النافذة',
		targetParent: 'النافذة الأم',
		langDirLTR: 'اليسار لليمين (LTR)',
		langDirRTL: 'اليمين لليسار (RTL)',
		styles: 'نمط',
		cssClasses: 'فئات التنسيق',
		width: 'العرض',
		height: 'الإرتفاع',
		align: 'محاذاة',
		alignLeft: 'يسار',
		alignRight: 'يمين',
		alignCenter: 'وسط',
		alignTop: 'أعلى',
		alignMiddle: 'وسط',
		alignBottom: 'أسفل',
		invalidValue	: 'قيمة غير مفبولة.',
		invalidHeight: 'الارتفاع يجب أن يكون عدداً.',
		invalidWidth: 'العرض يجب أن يكون عدداً.',
		invalidCssLength: 'قيمة الخانة المخصصة لـ "%1" يجب أن تكون رقما موجبا، باستخدام أو من غير استخدام وحدة CSS قياس مقبولة (px, %, in, cm, mm, em, ex, pt, or pc).',
		invalidHtmlLength: 'قيمة الخانة المخصصة لـ "%1" يجب أن تكون رقما موجبا، باستخدام أو من غير استخدام وحدة HTML قياس مقبولة (px or %).',
		invalidInlineStyle: 'قيمة الخانة المخصصة لـ  Inline Style يجب أن تختوي على مجموع واحد أو أكثر بالشكل التالي: "name : value", مفصولة بفاصلة منقزطة.',
		cssLengthTooltip: 'أدخل رقما للقيمة بالبكسل أو رقما بوحدة CSS مقبولة (px, %, in, cm, mm, em, ex, pt, or pc).',

		// Put the voice-only part of the label in the span.
		unavailable: '%1<span class="cke_accessibility">, غير متاح</span>'
	}
};
