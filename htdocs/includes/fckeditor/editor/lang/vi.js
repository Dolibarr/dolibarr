/*
 * FCKeditor - The text editor for internet
 * Copyright (C) 2003-2006 Frederico Caldeira Knabben
 * 
 * Licensed under the terms of the GNU Lesser General Public License:
 * 		http://www.opensource.org/licenses/lgpl-license.php
 * 
 * For further information visit:
 * 		http://www.fckeditor.net/
 * 
 * "Support Open Source software. What about a donation today?"
 * 
 * File Name: vi.js
 * 	Chinese Traditional language file.
 * 
 * File Authors:
 * 		Phan Binh Giang (bbbgiang@yahoo.com)
 * 		Hà Thanh Hải (thanhhai.ha@gmail.com)
 */

var FCKLang =
{
// Language direction : "ltr" (left to right) or "rtl" (right to left).
Dir					: "ltr",

ToolbarCollapse		: "Thu gọn Thanh công cụ",
ToolbarExpand		: "Mở rộng Thanh công cụ",

// Toolbar Items and Context Menu
Save				: "Lưu",
NewPage				: "Trang mới",
Preview				: "Xem trước",
Cut					: "Cắt",
Copy				: "Sao chép",
Paste				: "Dán",
PasteText			: "Dán theo dạng văn bản thuần",
PasteWord			: "Dán với định dạng Word",
Print				: "In",
SelectAll			: "Chọn Tất cả",
RemoveFormat		: "Xoá Định dạng",
InsertLinkLbl		: "Liên kết",
InsertLink			: "Chèn/Sửa Liên kết",
RemoveLink			: "Xoá Liên kết",
Anchor				: "Chèn/Sửa Neo",
InsertImageLbl		: "Hình ảnh",
InsertImage			: "Chèn/Sửa Hình ảnh",
InsertFlashLbl		: "Flash",
InsertFlash			: "Chèn/Sửa Flash",
InsertTableLbl		: "Bảng",
InsertTable			: "Chèn/Sửa Bảng",
InsertLineLbl		: "Đường phân cách ngang",
InsertLine			: "Chèn Đường phân cách ngang",
InsertSpecialCharLbl: "Ký tự đặc biệt",
InsertSpecialChar	: "Chèn Ký tự đặc biệt",
InsertSmileyLbl		: "Hình biểu lộ cảm xúc (mặt cười)",
InsertSmiley		: "Chèn Hình biểu lộ cảm xúc (mặt cười)",
About				: "Giới thiệu về FCKeditor",
Bold				: "Đậm",
Italic				: "Nghiêng",
Underline			: "Gạch chân",
StrikeThrough		: "Gạch xuyên ngang",
Subscript			: "Chỉ số dưới",
Superscript			: "Chỉ số trên",
LeftJustify			: "Canh trái",
CenterJustify		: "Canh giữa",
RightJustify		: "Canh phải",
BlockJustify		: "Canh đều",
DecreaseIndent		: "Dịch ra ngoài",
IncreaseIndent		: "Dịch vào trong",
Undo				: "Khôi phục thao tác",
Redo				: "Làm lại thao tác",
NumberedListLbl		: "Danh sách có thứ tự",
NumberedList		: "Chèn/Xoá Danh sách có thứ tự",
BulletedListLbl		: "Danh sách không thứ tự",
BulletedList		: "Chèn/Xoá Danh sách không thứ tự",
ShowTableBorders	: "Hiển thị Đường viền bảng",
ShowDetails			: "Hiển thị Chi tiết",
Style				: "Mẫu",
FontFormat			: "Định dạng",
Font				: "Phông",
FontSize			: "Cỡ chữ",
TextColor			: "Màu chữ",
BGColor				: "Màu nền",
Source				: "Mã HTML",
Find				: "Tìm kiếm",
Replace				: "Thay thế",
SpellCheck			: "Kiểm tra Chính tả",
UniversalKeyboard	: "Bàn phím Quốc tế",
PageBreakLbl		: "Ngắt trang",
PageBreak			: "Chèn Ngắt trang",

Form			: "Biểu mẫu",
Checkbox		: "Nút kiểm",
RadioButton		: "Nút radio",
TextField		: "Trường văn bản",
Textarea		: "Vùng văn bản",
HiddenField		: "Trường ẩn",
Button			: "Nút",
SelectionField	: "Ô chọn",
ImageButton		: "Nút hình ảnh",

FitWindow		: "Mở rộng tối đa kích thước trình biên tập",

// Context Menu
EditLink			: "Sửa Liên kết",
CellCM				: "Ô",
RowCM				: "Hàng",
ColumnCM			: "Cột",
InsertRow			: "Chèn Hàng",
DeleteRows			: "Xoá Hàng",
InsertColumn		: "Chèn Cột",
DeleteColumns		: "Xoá Cột",
InsertCell			: "Chèn Ô",
DeleteCells			: "Xoá Ô",
MergeCells			: "Trộn Ô",
SplitCell			: "Chia Ô",
TableDelete			: "Xóa Bảng",
CellProperties		: "Thuộc tính Ô",
TableProperties		: "Thuộc tính Bảng",
ImageProperties		: "Thuộc tính Hình ảnh",
FlashProperties		: "Thuộc tính Flash",

AnchorProp			: "Thuộc tính Neo",
ButtonProp			: "Thuộc tính Nút",
CheckboxProp		: "Thuộc tính Nút kiểm",
HiddenFieldProp		: "Thuộc tính Trường ẩn",
RadioButtonProp		: "Thuộc tính Nút radio",
ImageButtonProp		: "Thuộc tính Nút hình ảnh",
TextFieldProp		: "Thuộc tính Trường văn bản",
SelectionFieldProp	: "Thuộc tính Ô chọn",
TextareaProp		: "Thuộc tính Vùng văn bản",
FormProp			: "Thuộc tính Biểu mẫu",

FontFormats			: "Normal;Formatted;Address;Heading 1;Heading 2;Heading 3;Heading 4;Heading 5;Heading 6;Paragraph (DIV)",

// Alerts and Messages
ProcessingXHTML		: "Đang xử lý XHTML. Vui lòng đợi trong giây lát...",
Done				: "Đã hoàn thành",
PasteWordConfirm	: "Văn bản bạn muốn dán có kèm định dạng của Word. Bạn có muốn loại bỏ định dạng Word trước khi dán?",
NotCompatiblePaste	: "Lệnh này chỉ được hỗ trợ từ trình duyệt Internet Explorer phiên bản 5.5 hoặc mới hơn. Bạn có muốn dán nguyên mẫu?",
UnknownToolbarItem	: "Không rõ mục trên thanh công cụ \"%1\"",
UnknownCommand		: "Không rõ lệnh \"%1\"",
NotImplemented		: "Lệnh không được thực hiện",
UnknownToolbarSet	: "Thanh công cụ \"%1\" không tồn tại",
NoActiveX			: "Các thiết lập bảo mật của trình duyệt có thể giới hạn một số chức năng của trình biên tập. Bạn phải bật tùy chọn \"Run ActiveX controls and plug-ins\". Bạn có thể gặp một số lỗi và thấy thiếu đi một số chức năng.",
BrowseServerBlocked : "Không thể mở được bộ duyệt tài nguyên. Hãy đảm bảo chức năng chặn popup đã bị vô hiệu hóa.",
DialogBlocked		: "Không thể mở được cửa sổ hộp thoại. Hãy đảm bảo chức năng chặn popup đã bị vô hiệu hóa.",

// Dialogs
DlgBtnOK			: "Đồng ý",
DlgBtnCancel		: "Bỏ qua",
DlgBtnClose			: "Đóng",
DlgBtnBrowseServer	: "Duyệt trên máy chủ",
DlgAdvancedTag		: "Mở rộng",
DlgOpOther			: "<Khác>",
DlgInfoTab			: "Thông tin",
DlgAlertUrl			: "Hãy nhập vào một URL",

// General Dialogs Labels
DlgGenNotSet		: "<không thiết lập>",
DlgGenId			: "Định danh",
DlgGenLangDir		: "Đường dẫn Ngôn ngữ",
DlgGenLangDirLtr	: "Trái sang Phải (LTR)",
DlgGenLangDirRtl	: "Phải sang Trái (RTL)",
DlgGenLangCode		: "Mã Ngôn ngữ",
DlgGenAccessKey		: "Phím Hỗ trợ truy cập",
DlgGenName			: "Tên",
DlgGenTabIndex		: "Chỉ số của Tab",
DlgGenLongDescr		: "Mô tả URL",
DlgGenClass			: "Lớp Stylesheet",
DlgGenTitle			: "Advisory Title",
DlgGenContType		: "Advisory Content Type",
DlgGenLinkCharset	: "Bảng mã của tài nguyên được liên kết đến",
DlgGenStyle			: "Mẫu",

// Image Dialog
DlgImgTitle			: "Thuộc tính Hình ảnh",
DlgImgInfoTab		: "Thông tin Hình ảnh",
DlgImgBtnUpload		: "Tải lên Máy chủ",
DlgImgURL			: "URL",
DlgImgUpload		: "Tải lên",
DlgImgAlt			: "Chú thích Hình ảnh",
DlgImgWidth			: "Rộng",
DlgImgHeight		: "Cao",
DlgImgLockRatio		: "Giữ tỷ lệ",
DlgBtnResetSize		: "Kích thước gốc",
DlgImgBorder		: "Đường viền",
DlgImgHSpace		: "HSpace",
DlgImgVSpace		: "VSpace",
DlgImgAlign			: "Vị trí",
DlgImgAlignLeft		: "Trái",
DlgImgAlignAbsBottom: "Dưới tuyệt đối",
DlgImgAlignAbsMiddle: "Giữa tuyệt đối",
DlgImgAlignBaseline	: "Baseline",
DlgImgAlignBottom	: "Dưới",
DlgImgAlignMiddle	: "Giữa",
DlgImgAlignRight	: "Phải",
DlgImgAlignTextTop	: "Phía trên chữ",
DlgImgAlignTop		: "Trên",
DlgImgPreview		: "Xem trước",
DlgImgAlertUrl		: "Hãy đưa vào URL của hình ảnh",
DlgImgLinkTab		: "Liên kết",

// Flash Dialog
DlgFlashTitle		: "Thuộc tính Flash",
DlgFlashChkPlay		: "Tự động chạy",
DlgFlashChkLoop		: "Lặp",
DlgFlashChkMenu		: "Cho phép bật Menu của Flash",
DlgFlashScale		: "Tỷ lệ",
DlgFlashScaleAll	: "Hiển thị tất cả",
DlgFlashScaleNoBorder	: "Không đường viền",
DlgFlashScaleFit	: "Vừa vặn",

// Link Dialog
DlgLnkWindowTitle	: "Liên kết",
DlgLnkInfoTab		: "Thông tin Liên kết",
DlgLnkTargetTab		: "Đích",

DlgLnkType			: "Kiểu Liên kết",
DlgLnkTypeURL		: "URL",
DlgLnkTypeAnchor	: "Neo trong trang này",
DlgLnkTypeEMail		: "Thư điện tử",
DlgLnkProto			: "Giao thức",
DlgLnkProtoOther	: "<khác>",
DlgLnkURL			: "URL",
DlgLnkAnchorSel		: "Chọn một Neo",
DlgLnkAnchorByName	: "Theo Tên Neo",
DlgLnkAnchorById	: "Theo Định danh Element",
DlgLnkNoAnchors		: "<Không có Neo nào trong tài liệu>",
DlgLnkEMail			: "Thư điện tử",
DlgLnkEMailSubject	: "Tiêu đề Thông điệp",
DlgLnkEMailBody		: "Nội dung Thông điệp",
DlgLnkUpload		: "Tải lên",
DlgLnkBtnUpload		: "Tải lên Máy chủ",

DlgLnkTarget		: "Đích",
DlgLnkTargetFrame	: "<khung>",
DlgLnkTargetPopup	: "<cửa sổ popup>",
DlgLnkTargetBlank	: "Cửa sổ mới (_blank)",
DlgLnkTargetParent	: "Cửa sổ cha (_parent)",
DlgLnkTargetSelf	: "Cùng cửa sổ (_self)",
DlgLnkTargetTop		: "Cửa sổ trên cùng(_top)",
DlgLnkTargetFrameName	: "Tên Khung đích",
DlgLnkPopWinName	: "Tên Cửa sổ Popup",
DlgLnkPopWinFeat	: "Đặc điểm của Cửa sổ Popup",
DlgLnkPopResize		: "Kích thước thay đổi",
DlgLnkPopLocation	: "Thanh vị trí",
DlgLnkPopMenu		: "Thanh Menu",
DlgLnkPopScroll		: "Thanh cuộn",
DlgLnkPopStatus		: "Thanh trạng thái",
DlgLnkPopToolbar	: "Thanh công cụ",
DlgLnkPopFullScrn	: "Toàn màn hình (IE)",
DlgLnkPopDependent	: "Phụ thuộc (Netscape)",
DlgLnkPopWidth		: "Rộng",
DlgLnkPopHeight		: "Cao",
DlgLnkPopLeft		: "Vị trí Trái",
DlgLnkPopTop		: "Vị trí Trên",

DlnLnkMsgNoUrl		: "Hãy đưa vào Liên kết URL",
DlnLnkMsgNoEMail	: "Hãy đưa vào địa chỉ thư điện tử",
DlnLnkMsgNoAnchor	: "Hãy chọn một Neo",

// Color Dialog
DlgColorTitle		: "Chọn màu",
DlgColorBtnClear	: "Xoá",
DlgColorHighlight	: "Tô sáng",
DlgColorSelected	: "Đã chọn",

// Smiley Dialog
DlgSmileyTitle		: "Chèn Hình biểu lộ cảm xúc (mặt cười)",

// Special Character Dialog
DlgSpecialCharTitle	: "Hãy chọn Ký tự đặc biệt",

// Table Dialog
DlgTableTitle		: "Thuộc tính bảng",
DlgTableRows		: "Hàng",
DlgTableColumns		: "Cột",
DlgTableBorder		: "Cỡ Đường viền",
DlgTableAlign		: "Canh lề",
DlgTableAlignNotSet	: "<Chưa thiết lập>",
DlgTableAlignLeft	: "Trái",
DlgTableAlignCenter	: "Giữa",
DlgTableAlignRight	: "Phải",
DlgTableWidth		: "Rộng",
DlgTableWidthPx		: "điểm (px)",
DlgTableWidthPc		: "%",
DlgTableHeight		: "Cao",
DlgTableCellSpace	: "Khoảng cách Ô",
DlgTableCellPad		: "Đệm Ô",
DlgTableCaption		: "Đầu đề",
DlgTableSummary		: "Tóm lược",

// Table Cell Dialog
DlgCellTitle		: "Thuộc tính Ô",
DlgCellWidth		: "Rộng",
DlgCellWidthPx		: "điểm (px)",
DlgCellWidthPc		: "%",
DlgCellHeight		: "Cao",
DlgCellWordWrap		: "Bọc từ",
DlgCellWordWrapNotSet	: "<Chưa thiết lập>",
DlgCellWordWrapYes	: "Đồng ý",
DlgCellWordWrapNo	: "Không",
DlgCellHorAlign		: "Canh theo Chiều ngang",
DlgCellHorAlignNotSet	: "<Chưa thiết lập>",
DlgCellHorAlignLeft	: "Trái",
DlgCellHorAlignCenter	: "Giữa",
DlgCellHorAlignRight: "Phải",
DlgCellVerAlign		: "Canh theo Chiều dọc",
DlgCellVerAlignNotSet	: "<Chưa thiết lập>",
DlgCellVerAlignTop	: "Trên",
DlgCellVerAlignMiddle	: "Giữa",
DlgCellVerAlignBottom	: "Dưới",
DlgCellVerAlignBaseline	: "Baseline",
DlgCellRowSpan		: "Nối Hàng",
DlgCellCollSpan		: "Nối Cột",
DlgCellBackColor	: "Màu nền",
DlgCellBorderColor	: "Màu viền",
DlgCellBtnSelect	: "Chọn...",

// Find Dialog
DlgFindTitle		: "Tìm kiếm",
DlgFindFindBtn		: "Tìm kiếm",
DlgFindNotFoundMsg	: "Không tìm thấy chuỗi cần tìm.",

// Replace Dialog
DlgReplaceTitle			: "Thay thế",
DlgReplaceFindLbl		: "Tìm chuỗi:",
DlgReplaceReplaceLbl	: "Thay bằng:",
DlgReplaceCaseChk		: "Phân biệt chữ HOA/thường",
DlgReplaceReplaceBtn	: "Thay thế",
DlgReplaceReplAllBtn	: "Thay thế Tất cả",
DlgReplaceWordChk		: "Đúng toàn bộ từ",

// Paste Operations / Dialog
PasteErrorPaste	: "Các thiết lập bảo mật của trình duyệt không cho phép trình biên tập tự động thực thi lệnh dán. Hãy sử dụng bàn phím cho lệnh này (Ctrl+V).",
PasteErrorCut	: "Các thiết lập bảo mật của trình duyệt không cho phép trình biên tập tự động thực thi lệnh cắt. Hãy sử dụng bàn phím cho lệnh này (Ctrl+X).",
PasteErrorCopy	: "Các thiết lập bảo mật của trình duyệt không cho phép trình biên tập tự động thực thi lệnh sao chép. Hãy sử dụng bàn phím cho lệnh này (Ctrl+C).",

PasteAsText		: "Dán theo định dạng văn bản thuần",
PasteFromWord	: "Dán với định dạng Word",

DlgPasteMsg2	: "Hãy dán nội dung vào trong khung bên dưới, sử dụng tổ hợp phím (<STRONG>Ctrl+V</STRONG>) và nhấn vào nút <STRONG>Đồng ý</STRONG>.",
DlgPasteIgnoreFont		: "Chấp nhận các định dạng phông",
DlgPasteRemoveStyles	: "Gỡ bỏ các định dạng Styles",
DlgPasteCleanBox		: "Xóa nội dung",

// Color Picker
ColorAutomatic	: "Tự động",
ColorMoreColors	: "Màu khác...",

// Document Properties
DocProps		: "Thuộc tính Tài liệu",

// Anchor Dialog
DlgAnchorTitle		: "Thuộc tính Neo",
DlgAnchorName		: "Tên của Neo",
DlgAnchorErrorName	: "Hãy đưa vào tên của Neo",

// Speller Pages Dialog
DlgSpellNotInDic		: "Không có trong từ điển",
DlgSpellChangeTo		: "Chuyển thành",
DlgSpellBtnIgnore		: "Bỏ qua",
DlgSpellBtnIgnoreAll	: "Bỏ qua Tất cả",
DlgSpellBtnReplace		: "Thay thế",
DlgSpellBtnReplaceAll	: "Thay thế Tất cả",
DlgSpellBtnUndo			: "Phục hồi lại",
DlgSpellNoSuggestions	: "- Không đưa ra gợi ý về từ -",
DlgSpellProgress		: "Đang tiến hành kiểm tra chính tả...",
DlgSpellNoMispell		: "Hoàn tất kiểm tra chính tả: Không có lỗi chính tả",
DlgSpellNoChanges		: "Hoàn tất kiểm tra chính tả: Không từ nào được thay đổi",
DlgSpellOneChange		: "Hoàn tất kiểm tra chính tả: Một từ đã được thay đổi",
DlgSpellManyChanges		: "Hoàn tất kiểm tra chính tả: %1 từ đã được thay đổi",

IeSpellDownload			: "Chức năng kiểm tra chính tả chưa được cài đặt. Bạn có muốn tải về ngay bây giờ?",

// Button Dialog
DlgButtonText	: "Chuỗi hiển thị (Giá trị)",
DlgButtonType	: "Kiểu",

// Checkbox and Radio Button Dialogs
DlgCheckboxName		: "Tên",
DlgCheckboxValue	: "Giá trị",
DlgCheckboxSelected	: "Được chọn",

// Form Dialog
DlgFormName		: "Tên",
DlgFormAction	: "Hành động",
DlgFormMethod	: "Phương thức",

// Select Field Dialog
DlgSelectName		: "Tên",
DlgSelectValue		: "Giá trị",
DlgSelectSize		: "Kích cỡ",
DlgSelectLines		: "dòng",
DlgSelectChkMulti	: "Cho phép chọn nhiều",
DlgSelectOpAvail	: "Các tùy chọn có thể sử dụng",
DlgSelectOpText		: "Văn bản",
DlgSelectOpValue	: "Giá trị",
DlgSelectBtnAdd		: "Thêm",
DlgSelectBtnModify	: "Thay đổi",
DlgSelectBtnUp		: "Lên",
DlgSelectBtnDown	: "Xuống",
DlgSelectBtnSetValue : "Giá trị được chọn",
DlgSelectBtnDelete	: "Xoá",

// Textarea Dialog
DlgTextareaName	: "Tên",
DlgTextareaCols	: "Cột",
DlgTextareaRows	: "Hàng",

// Text Field Dialog
DlgTextName			: "Tên",
DlgTextValue		: "Giá trị",
DlgTextCharWidth	: "Rộng",
DlgTextMaxChars		: "Số Ký tự tối đa",
DlgTextType			: "Kiểu",
DlgTextTypeText		: "Ký tự",
DlgTextTypePass		: "Mật khẩu",

// Hidden Field Dialog
DlgHiddenName	: "Tên",
DlgHiddenValue	: "Giá trị",

// Bulleted List Dialog
BulletedListProp	: "Thuộc tính Danh sách không thứ tự",
NumberedListProp	: "Thuộc tính Danh sách có thứ tự",
DlgLstType			: "Kiểu",
DlgLstTypeCircle	: "Hình tròn",
DlgLstTypeDisc		: "Hình đĩa",
DlgLstTypeSquare	: "Hình vuông",
DlgLstTypeNumbers	: "Số thứ tự (1, 2, 3)",
DlgLstTypeLCase		: "Chữ cái thường (a, b, c)",
DlgLstTypeUCase		: "Chữ cái hoa (A, B, C)",
DlgLstTypeSRoman	: "Số La Mã thường (i, ii, iii)",
DlgLstTypeLRoman	: "Số La Mã hoa (I, II, III)",

// Document Properties Dialog
DlgDocGeneralTab	: "Toàn thể",
DlgDocBackTab		: "Nền",
DlgDocColorsTab		: "Màu sắc và Đường biên",
DlgDocMetaTab		: "Siêu dữ liệu",

DlgDocPageTitle		: "Tiêu đề Trang",
DlgDocLangDir		: "Đường dẫn Ngôn ngữ",
DlgDocLangDirLTR	: "Trái sang Phải (LTR)",
DlgDocLangDirRTL	: "Phải sang Trái (RTL)",
DlgDocLangCode		: "Mã Ngôn ngữ",
DlgDocCharSet		: "Bảng mã ký tự",
DlgDocCharSetOther	: "Bảng mã ký tự khác",

DlgDocDocType		: "Kiểu Đề mục Tài liệu",
DlgDocDocTypeOther	: "Kiểu Đề mục Tài liệu khác",
DlgDocIncXHTML		: "Bao gồm cả định nghĩa XHTML",
DlgDocBgColor		: "Màu nền",
DlgDocBgImage		: "URL của Hình ảnh nền",
DlgDocBgNoScroll	: "Không cuộn nền",
DlgDocCText			: "Văn bản",
DlgDocCLink			: "Liên kết",
DlgDocCVisited		: "Liên kết Đã ghé thăm",
DlgDocCActive		: "Liên kết Hiện hành",
DlgDocMargins		: "Đường biên của Trang",
DlgDocMaTop			: "Trên",
DlgDocMaLeft		: "Trái",
DlgDocMaRight		: "Phải",
DlgDocMaBottom		: "Dưới",
DlgDocMeIndex		: "Các từ khóa chỉ mục tài liệu (phân cách bởi dấu phẩy)",
DlgDocMeDescr		: "Mô tả tài liệu",
DlgDocMeAuthor		: "Tác giả",
DlgDocMeCopy		: "Bản quyền",
DlgDocPreview		: "Xem trước",

// Templates Dialog
Templates			: "Mẫu dựng sẵn",
DlgTemplatesTitle	: "Nội dung Mẫu dựng sẵn",
DlgTemplatesSelMsg	: "Hãy chọn Mẫu dựng sẵn để mở trong trình biên tập<br>(nội dung hiện tại sẽ bị mất):",
DlgTemplatesLoading	: "Đang nạp Danh sách Mẫu dựng sẵn. Vui lòng đợi trong giây lát...",
DlgTemplatesNoTpl	: "(Không có Mẫu dựng sẵn nào được định nghĩa)",

// About Dialog
DlgAboutAboutTab	: "Giới thiệu",
DlgAboutBrowserInfoTab	: "Thông tin trình duyệt",
DlgAboutLicenseTab	: "Giấy phép",
DlgAboutVersion		: "phiên bản",
DlgAboutLicense		: "Được cấp phép theo các điều khoản của giấy phép GNU Lesser General Public License",
DlgAboutInfo		: "Để biết thêm thông tin, hãy truy cập"
}