/**
 * @license Copyright (c) 2003-2014, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */

/**
 * @fileOverview Defines the {@link CKEDITOR.lang} object, for the
 * Hebrew language.
 */

/**#@+
   @type String
   @example
*/

/**
 * Contains the dictionary of language entries.
 * @namespace
 */
CKEDITOR.lang[ 'he' ] = {
	// ARIA description.
	editor: 'עורך טקסט עשיר',
	editorPanel: 'Rich Text Editor panel', // MISSING

	// Common messages and labels.
	common: {
		// Screenreader titles. Please note that screenreaders are not always capable
		// of reading non-English words. So be careful while translating it.
		editorHelp: 'לחץ אלט ALT + 0 לעזרה',

		browseServer: 'סייר השרת',
		url: 'כתובת (URL)',
		protocol: 'פרוטוקול',
		upload: 'העלאה',
		uploadSubmit: 'שליחה לשרת',
		image: 'תמונה',
		flash: 'פלאש',
		form: 'טופס',
		checkbox: 'תיבת סימון',
		radio: 'לחצן אפשרויות',
		textField: 'שדה טקסט',
		textarea: 'איזור טקסט',
		hiddenField: 'שדה חבוי',
		button: 'כפתור',
		select: 'שדה בחירה',
		imageButton: 'כפתור תמונה',
		notSet: '<לא נקבע>',
		id: 'זיהוי (ID)',
		name: 'שם',
		langDir: 'כיוון שפה',
		langDirLtr: 'שמאל לימין (LTR)',
		langDirRtl: 'ימין לשמאל (RTL)',
		langCode: 'קוד שפה',
		longDescr: 'קישור לתיאור מפורט',
		cssClass: 'מחלקת עיצוב (CSS Class)',
		advisoryTitle: 'כותרת מוצעת',
		cssStyle: 'סגנון',
		ok: 'אישור',
		cancel: 'ביטול',
		close: 'סגירה',
		preview: 'תצוגה מקדימה',
		resize: 'יש לגרור בכדי לשנות את הגודל',
		generalTab: 'כללי',
		advancedTab: 'אפשרויות מתקדמות',
		validateNumberFailed: 'הערך חייב להיות מספרי.',
		confirmNewPage: 'כל השינויים שלא נשמרו יאבדו. האם להעלות דף חדש?',
		confirmCancel: 'חלק מהאפשרויות שונו, האם לסגור את הדיאלוג?',
		options: 'אפשרויות',
		target: 'מטרה',
		targetNew: 'חלון חדש (_blank)',
		targetTop: 'החלון העליון ביותר (_top)',
		targetSelf: 'אותו חלון (_self)',
		targetParent: 'חלון האב (_parent)',
		langDirLTR: 'שמאל לימין (LTR)',
		langDirRTL: 'ימין לשמאל (RTL)',
		styles: 'סגנון',
		cssClasses: 'מחלקות גליונות סגנון',
		width: 'רוחב',
		height: 'גובה',
		align: 'יישור',
		alignLeft: 'לשמאל',
		alignRight: 'לימין',
		alignCenter: 'מרכז',
		alignTop: 'למעלה',
		alignMiddle: 'לאמצע',
		alignBottom: 'לתחתית',
		invalidValue	: 'ערך לא חוקי.',
		invalidHeight: 'הגובה חייב להיות מספר.',
		invalidWidth: 'הרוחב חייב להיות מספר.',
		invalidCssLength: 'הערך שצוין לשדה "%1" חייב להיות מספר חיובי עם או ללא יחידת מידה חוקית של CSS (px, %, in, cm, mm, em, ex, pt, או pc).',
		invalidHtmlLength: 'הערך שצוין לשדה "%1" חייב להיות מספר חיובי עם או ללא יחידת מידה חוקית של HTML (px או %).',
		invalidInlineStyle: 'הערך שצויין לשדה הסגנון חייב להכיל זוג ערכים אחד או יותר בפורמט "שם : ערך", מופרדים על ידי נקודה-פסיק.',
		cssLengthTooltip: 'יש להכניס מספר המייצג פיקסלים או מספר עם יחידת גליונות סגנון תקינה (px, %, in, cm, mm, em, ex, pt, או pc).',

		// Put the voice-only part of the label in the span.
		unavailable: '%1<span class="cke_accessibility">, לא זמין</span>'
	}
};
