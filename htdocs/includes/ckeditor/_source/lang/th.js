/**
 * @license Copyright (c) 2003-2014, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */

/**
 * @fileOverview Defines the {@link CKEDITOR.lang} object, for the
 * Thai language.
 */

/**#@+
   @type String
   @example
*/

/**
 * Contains the dictionary of language entries.
 * @namespace
 */
CKEDITOR.lang[ 'th' ] = {
	// ARIA description.
	editor: 'Rich Text Editor', // MISSING
	editorPanel: 'Rich Text Editor panel', // MISSING

	// Common messages and labels.
	common: {
		// Screenreader titles. Please note that screenreaders are not always capable
		// of reading non-English words. So be careful while translating it.
		editorHelp: 'กด ALT 0 หากต้องการความช่วยเหลือ',

		browseServer: 'เปิดหน้าต่างจัดการไฟล์อัพโหลด',
		url: 'ที่อยู่อ้างอิง URL',
		protocol: 'โปรโตคอล',
		upload: 'อัพโหลดไฟล์',
		uploadSubmit: 'อัพโหลดไฟล์ไปเก็บไว้ที่เครื่องแม่ข่าย (เซิร์ฟเวอร์)',
		image: 'รูปภาพ',
		flash: 'ไฟล์ Flash',
		form: 'แบบฟอร์ม',
		checkbox: 'เช็คบ๊อก',
		radio: 'เรดิโอบัตตอน',
		textField: 'เท็กซ์ฟิลด์',
		textarea: 'เท็กซ์แอเรีย',
		hiddenField: 'ฮิดเดนฟิลด์',
		button: 'ปุ่ม',
		select: 'แถบตัวเลือก',
		imageButton: 'ปุ่มแบบรูปภาพ',
		notSet: '<ไม่ระบุ>',
		id: 'ไอดี',
		name: 'ชื่อ',
		langDir: 'การเขียน-อ่านภาษา',
		langDirLtr: 'จากซ้ายไปขวา (LTR)',
		langDirRtl: 'จากขวามาซ้าย (RTL)',
		langCode: 'รหัสภาษา',
		longDescr: 'คำอธิบายประกอบ URL',
		cssClass: 'คลาสของไฟล์กำหนดลักษณะการแสดงผล',
		advisoryTitle: 'คำเกริ่นนำ',
		cssStyle: 'ลักษณะการแสดงผล',
		ok: 'ตกลง',
		cancel: 'ยกเลิก',
		close: 'ปิด',
		preview: 'ดูหน้าเอกสารตัวอย่าง',
		resize: 'ปรับขนาด',
		generalTab: 'ทั่วไป',
		advancedTab: 'ขั้นสูง',
		validateNumberFailed: 'ค่านี้ไม่ใช่ตัวเลข',
		confirmNewPage: 'การเปลี่ยนแปลงใดๆ ในเนื้อหานี้ ที่ไม่ได้ถูกบันทึกไว้ จะสูญหายทั้งหมด คุณแน่ใจว่าจะเรียกหน้าใหม่?',
		confirmCancel: 'ตัวเลือกบางตัวมีการเปลี่ยนแปลง คุณแน่ใจว่าจะปิดกล่องโต้ตอบนี้?',
		options: 'ตัวเลือก',
		target: 'การเปิดหน้าลิงค์',
		targetNew: 'หน้าต่างใหม่ (_blank)',
		targetTop: 'Topmost Window (_top)', // MISSING
		targetSelf: 'หน้าต่างเดียวกัน (_self)',
		targetParent: 'Parent Window (_parent)', // MISSING
		langDirLTR: 'จากซ้ายไปขวา (LTR)',
		langDirRTL: 'จากขวามาซ้าย (RTL)',
		styles: 'ลักษณะการแสดงผล',
		cssClasses: 'คลาสของไฟล์กำหนดลักษณะการแสดงผล',
		width: 'ความกว้าง',
		height: 'ความสูง',
		align: 'การจัดวาง',
		alignLeft: 'ชิดซ้าย',
		alignRight: 'ชิดขวา',
		alignCenter: 'กึ่งกลาง',
		alignTop: 'บนสุด',
		alignMiddle: 'กึ่งกลางแนวตั้ง',
		alignBottom: 'ชิดด้านล่าง',
		invalidValue	: 'ค่าไม่ถูกต้อง',
		invalidHeight: 'ความสูงต้องเป็นตัวเลข',
		invalidWidth: 'ความกว้างต้องเป็นตัวเลข',
		invalidCssLength: 'Value specified for the "%1" field must be a positive number with or without a valid CSS measurement unit (px, %, in, cm, mm, em, ex, pt, or pc).', // MISSING
		invalidHtmlLength: 'Value specified for the "%1" field must be a positive number with or without a valid HTML measurement unit (px or %).', // MISSING
		invalidInlineStyle: 'Value specified for the inline style must consist of one or more tuples with the format of "name : value", separated by semi-colons.', // MISSING
		cssLengthTooltip: 'Enter a number for a value in pixels or a number with a valid CSS unit (px, %, in, cm, mm, em, ex, pt, or pc).', // MISSING

		// Put the voice-only part of the label in the span.
		unavailable: '%1<span class="cke_accessibility">, unavailable</span>' // MISSING
	}
};
