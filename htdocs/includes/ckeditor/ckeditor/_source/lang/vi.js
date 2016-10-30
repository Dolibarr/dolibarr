/**
 * @license Copyright (c) 2003-2014, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */

/**
 * @fileOverview Defines the {@link CKEDITOR.lang} object, for the
 * Vietnamese language.
 */

/**#@+
   @type String
   @example
*/

/**
 * Contains the dictionary of language entries.
 * @namespace
 */
CKEDITOR.lang[ 'vi' ] = {
	// ARIA description.
	editor: 'Bộ soạn thảo văn bản có định dạng',
	editorPanel: 'Rich Text Editor panel', // MISSING

	// Common messages and labels.
	common: {
		// Screenreader titles. Please note that screenreaders are not always capable
		// of reading non-English words. So be careful while translating it.
		editorHelp: 'Nhấn ALT + 0 để được giúp đỡ',

		browseServer: 'Duyệt máy chủ',
		url: 'URL',
		protocol: 'Giao thức',
		upload: 'Tải lên',
		uploadSubmit: 'Tải lên máy chủ',
		image: 'Hình ảnh',
		flash: 'Flash',
		form: 'Biểu mẫu',
		checkbox: 'Nút kiểm',
		radio: 'Nút chọn',
		textField: 'Trường văn bản',
		textarea: 'Vùng văn bản',
		hiddenField: 'Trường ẩn',
		button: 'Nút',
		select: 'Ô chọn',
		imageButton: 'Nút hình ảnh',
		notSet: '<không thiết lập>',
		id: 'Định danh',
		name: 'Tên',
		langDir: 'Hướng ngôn ngữ',
		langDirLtr: 'Trái sang phải (LTR)',
		langDirRtl: 'Phải sang trái (RTL)',
		langCode: 'Mã ngôn ngữ',
		longDescr: 'Mô tả URL',
		cssClass: 'Lớp Stylesheet',
		advisoryTitle: 'Nhan đề hướng dẫn',
		cssStyle: 'Kiểu (style)',
		ok: 'Đồng ý',
		cancel: 'Bỏ qua',
		close: 'Đóng',
		preview: 'Xem trước',
		resize: 'Kéo rê để thay đổi kích cỡ',
		generalTab: 'Tab chung',
		advancedTab: 'Tab mở rộng',
		validateNumberFailed: 'Giá trị này không phải là số.',
		confirmNewPage: 'Mọi thay đổi không được lưu lại, nội dung này sẽ bị mất. Bạn có chắc chắn muốn tải một trang mới?',
		confirmCancel: 'Một vài tùy chọn đã bị thay đổi. Bạn có chắc chắn muốn đóng hộp thoại?',
		options: 'Tùy chọn',
		target: 'Đích đến',
		targetNew: 'Cửa sổ mới (_blank)',
		targetTop: 'Cửa sổ trên cùng (_top)',
		targetSelf: 'Tại trang (_self)',
		targetParent: 'Cửa sổ cha (_parent)',
		langDirLTR: 'Trái sang phải (LTR)',
		langDirRTL: 'Phải sang trái (RTL)',
		styles: 'Kiểu',
		cssClasses: 'Lớp CSS',
		width: 'Chiều rộng',
		height: 'chiều cao',
		align: 'Vị trí',
		alignLeft: 'Trái',
		alignRight: 'Phải',
		alignCenter: 'Giữa',
		alignTop: 'Trên',
		alignMiddle: 'Giữa',
		alignBottom: 'Dưới',
		invalidValue	: 'Giá trị không hợp lệ.',
		invalidHeight: 'Chiều cao phải là số nguyên.',
		invalidWidth: 'Chiều rộng phải là số nguyên.',
		invalidCssLength: 'Giá trị quy định cho trường "%1" phải là một số dương có hoặc không có một đơn vị đo CSS hợp lệ (px, %, in, cm, mm, em, ex, pt, hoặc pc).',
		invalidHtmlLength: 'Giá trị quy định cho trường "%1" phải là một số dương có hoặc không có một đơn vị đo HTML hợp lệ (px hoặc %).',
		invalidInlineStyle: 'Giá trị quy định cho kiểu nội tuyến phải bao gồm một hoặc nhiều dữ liệu với định dạng "tên:giá trị", cách nhau bằng dấu chấm phẩy.',
		cssLengthTooltip: 'Nhập một giá trị theo pixel hoặc một số với một đơn vị CSS hợp lệ (px, %, in, cm, mm, em, ex, pt, hoặc pc).',

		// Put the voice-only part of the label in the span.
		unavailable: '%1<span class="cke_accessibility">, không có</span>'
	}
};
