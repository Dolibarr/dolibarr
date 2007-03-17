/*
 * FCKeditor - The text editor for Internet - http://www.fckeditor.net
 * Copyright (C) 2003-2007 Frederico Caldeira Knabben
 * 
 * == BEGIN LICENSE ==
 * 
 * Licensed under the terms of any of the following licenses at your
 * choice:
 * 
 *  - GNU General Public License Version 2 or later (the "GPL")
 *    http://www.gnu.org/licenses/gpl.html
 * 
 *  - GNU Lesser General Public License Version 2.1 or later (the "LGPL")
 *    http://www.gnu.org/licenses/lgpl.html
 * 
 *  - Mozilla Public License Version 1.1 or later (the "MPL")
 *    http://www.mozilla.org/MPL/MPL-1.1.html
 * 
 * == END LICENSE ==
 * 
 * File Name: th.js
 * 	Thai language file.
 * 
 * File Authors:
 * 		Audy Charin Arsakit (arsakit@gmail.com)
 * 		Joy Piyanoot Promnuan (piyanoot@gmail.com)
 */

var FCKLang =
{
// Language direction : "ltr" (left to right) or "rtl" (right to left).
Dir					: "ltr",

ToolbarCollapse		: "ซ่อนแถบเครื่องมือ",
ToolbarExpand		: "แสดงแถบเครื่องมือ",

// Toolbar Items and Context Menu
Save				: "บันทึก",
NewPage				: "สร้างหน้าเอกสารใหม่",
Preview				: "ดูหน้าเอกสารตัวอย่าง",
Cut					: "ตัด",
Copy				: "สำเนา",
Paste				: "วาง",
PasteText			: "วางสำเนาจากตัวอักษรธรรมดา",
PasteWord			: "วางสำเนาจากตัวอักษรเวิร์ด",
Print				: "สั่งพิมพ์",
SelectAll			: "เลือกทั้งหมด",
RemoveFormat		: "ล้างรูปแบบ",
InsertLinkLbl		: "ลิงค์เชื่อมโยงเว็บ อีเมล์ รูปภาพ หรือไฟล์อื่นๆ",
InsertLink			: "แทรก/แก้ไข ลิงค์",
RemoveLink			: "ลบ ลิงค์",
Anchor				: "แทรก/แก้ไข Anchor",
InsertImageLbl		: "รูปภาพ",
InsertImage			: "แทรก/แก้ไข รูปภาพ",
InsertFlashLbl		: "Flash",	//MISSING
InsertFlash			: "Insert/Edit Flash",	//MISSING
InsertTableLbl		: "ตาราง",
InsertTable			: "แทรก/แก้ไข ตาราง",
InsertLineLbl		: "เส้นคั่นบรรทัด",
InsertLine			: "แทรกเส้นคั่นบรรทัด",
InsertSpecialCharLbl: "ตัวอักษรพิเศษ",
InsertSpecialChar	: "แทรกตัวอักษรพิเศษ",
InsertSmileyLbl		: "รูปสื่ออารมณ์",
InsertSmiley		: "แทรกรูปสื่ออารมณ์",
About				: "เกี่ยวกับโปรแกรม FCKeditor",
Bold				: "ตัวหนา",
Italic				: "ตัวเอียง",
Underline			: "ตัวขีดเส้นใต้",
StrikeThrough		: "ตัวขีดเส้นทับ",
Subscript			: "ตัวห้อย",
Superscript			: "ตัวยก",
LeftJustify			: "จัดชิดซ้าย",
CenterJustify		: "จัดกึ่งกลาง",
RightJustify		: "จัดชิดขวา",
BlockJustify		: "จัดพอดีหน้ากระดาษ",
DecreaseIndent		: "ลดระยะย่อหน้า",
IncreaseIndent		: "เพิ่มระยะย่อหน้า",
Undo				: "ยกเลิกคำสั่ง",
Redo				: "ทำซ้ำคำสั่ง",
NumberedListLbl		: "ลำดับรายการแบบตัวเลข",
NumberedList		: "แทรก/แก้ไข ลำดับรายการแบบตัวเลข",
BulletedListLbl		: "ลำดับรายการแบบสัญลักษณ์",
BulletedList		: "แทรก/แก้ไข ลำดับรายการแบบสัญลักษณ์",
ShowTableBorders	: "แสดงขอบของตาราง",
ShowDetails			: "แสดงรายละเอียด",
Style				: "ลักษณะ",
FontFormat			: "รูปแบบ",
Font				: "แบบอักษร",
FontSize			: "ขนาด",
TextColor			: "สีตัวอักษร",
BGColor				: "สีพื้นหลัง",
Source				: "ดูรหัส HTML",
Find				: "ค้นหา",
Replace				: "ค้นหาและแทนที่",
SpellCheck			: "ตรวจการสะกดคำ",
UniversalKeyboard	: "คีย์บอร์ดหลากภาษา",
PageBreakLbl		: "Page Break",	//MISSING
PageBreak			: "Insert Page Break",	//MISSING

Form			: "แบบฟอร์ม",
Checkbox		: "เช็คบ๊อก",
RadioButton		: "เรดิโอบัตตอน",
TextField		: "เท็กซ์ฟิลด์",
Textarea		: "เท็กซ์แอเรีย",
HiddenField		: "ฮิดเดนฟิลด์",
Button			: "ปุ่ม",
SelectionField	: "แถบตัวเลือก",
ImageButton		: "ปุ่มแบบรูปภาพ",

FitWindow		: "Maximize the editor size",	//MISSING

// Context Menu
EditLink			: "แก้ไข ลิงค์",
CellCM				: "Cell",	//MISSING
RowCM				: "Row",	//MISSING
ColumnCM			: "Column",	//MISSING
InsertRow			: "แทรกแถว",
DeleteRows			: "ลบแถว",
InsertColumn		: "แทรกสดมน์",
DeleteColumns		: "ลบสดมน์",
InsertCell			: "แทรกช่อง",
DeleteCells			: "ลบช่อง",
MergeCells			: "ผสานช่อง",
SplitCell			: "แยกช่อง",
TableDelete			: "Delete Table",	//MISSING
CellProperties		: "คุณสมบัติของช่อง",
TableProperties		: "คุณสมบัติของตาราง",
ImageProperties		: "คุณสมบัติของรูปภาพ",
FlashProperties		: "Flash Properties",	//MISSING

AnchorProp			: "รายละเอียด Anchor",
ButtonProp			: "รายละเอียดของ ปุ่ม",
CheckboxProp		: "คุณสมบัติของ เช็คบ๊อก",
HiddenFieldProp		: "คุณสมบัติของ ฮิดเดนฟิลด์",
RadioButtonProp		: "คุณสมบัติของ เรดิโอบัตตอน",
ImageButtonProp		: "คุณสมบัติของ ปุ่มแบบรูปภาพ",
TextFieldProp		: "คุณสมบัติของ เท็กซ์ฟิลด์",
SelectionFieldProp	: "คุณสมบัติของ แถบตัวเลือก",
TextareaProp		: "คุณสมบัติของ เท็กแอเรีย",
FormProp			: "คุณสมบัติของ แบบฟอร์ม",

FontFormats			: "Normal;Formatted;Address;Heading 1;Heading 2;Heading 3;Heading 4;Heading 5;Heading 6;Paragraph (DIV)",		//REVIEW : Check _getfontformat.html

// Alerts and Messages
ProcessingXHTML		: "โปรแกรมกำลังทำงานด้วยเทคโนโลยี XHTML กรุณารอสักครู่...",
Done				: "โปรแกรมทำงานเสร็จสมบูรณ์",
PasteWordConfirm	: "ข้อมูลที่ท่านต้องการวางลงในแผ่นงาน ถูกจัดรูปแบบจากโปรแกรมเวิร์ด. ท่านต้องการล้างรูปแบบที่มาจากโปรแกรมเวิร์ดหรือไม่?",
NotCompatiblePaste	: "คำสั่งนี้ทำงานในโปรแกรมท่องเว็บ Internet Explorer version รุ่น 5.5 หรือใหม่กว่าเท่านั้น. ท่านต้องการวางตัวอักษรโดยไม่ล้างรูปแบบที่มาจากโปรแกรมเวิร์ดหรือไม่?",
UnknownToolbarItem	: "ไม่สามารถระบุปุ่มเครื่องมือได้ \"%1\"",
UnknownCommand		: "ไม่สามารถระบุชื่อคำสั่งได้ \"%1\"",
NotImplemented		: "ไม่สามารถใช้งานคำสั่งได้",
UnknownToolbarSet	: "ไม่มีการติดตั้งชุดคำสั่งในแถบเครื่องมือ \"%1\" กรุณาติดต่อผู้ดูแลระบบ",
NoActiveX			: "Your browser's security settings could limit some features of the editor. You must enable the option \"Run ActiveX controls and plug-ins\". You may experience errors and notice missing features.",	//MISSING
BrowseServerBlocked : "The resources browser could not be opened. Make sure that all popup blockers are disabled.",	//MISSING
DialogBlocked		: "It was not possible to open the dialog window. Make sure all popup blockers are disabled.",	//MISSING

// Dialogs
DlgBtnOK			: "ตกลง",
DlgBtnCancel		: "ยกเลิก",
DlgBtnClose			: "ปิด",
DlgBtnBrowseServer	: "เปิดหน้าต่างจัดการไฟล์อัพโหลด",
DlgAdvancedTag		: "ขั้นสูง",
DlgOpOther			: "<อื่นๆ>",
DlgInfoTab			: "Info",	//MISSING
DlgAlertUrl			: "Please insert the URL",	//MISSING

// General Dialogs Labels
DlgGenNotSet		: "<ไม่ระบุ>",
DlgGenId			: "ไอดี",
DlgGenLangDir		: "การเขียน-อ่านภาษา",
DlgGenLangDirLtr	: "จากซ้ายไปขวา (LTR)",
DlgGenLangDirRtl	: "จากขวามาซ้าย (RTL)",
DlgGenLangCode		: "รหัสภาษา",
DlgGenAccessKey		: "แอคเซส คีย์",
DlgGenName			: "ชื่อ",
DlgGenTabIndex		: "ลำดับของ แท็บ",
DlgGenLongDescr		: "คำอธิบายประกอบ URL",
DlgGenClass			: "คลาสของไฟล์กำหนดลักษณะการแสดงผล",
DlgGenTitle			: "คำเกริ่นนำ",
DlgGenContType		: "ชนิดของคำเกริ่นนำ",
DlgGenLinkCharset	: "ลิงค์เชื่อมโยงไปยังชุดตัวอักษร",
DlgGenStyle			: "ลักษณะการแสดงผล",

// Image Dialog
DlgImgTitle			: "คุณสมบัติของ รูปภาพ",
DlgImgInfoTab		: "ข้อมูลของรูปภาพ",
DlgImgBtnUpload		: "อัพโหลดไฟล์ไปเก็บไว้ที่เครื่องแม่ข่าย (เซิร์ฟเวอร์)",
DlgImgURL			: "ที่อยู่อ้างอิง URL",
DlgImgUpload		: "อัพโหลดไฟล์",
DlgImgAlt			: "คำประกอบรูปภาพ",
DlgImgWidth			: "ความกว้าง",
DlgImgHeight		: "ความสูง",
DlgImgLockRatio		: "กำหนดอัตราส่วน กว้าง-สูง แบบคงที่",
DlgBtnResetSize		: "กำหนดรูปเท่าขนาดจริง",
DlgImgBorder		: "ขนาดขอบรูป",
DlgImgHSpace		: "ระยะแนวนอน",
DlgImgVSpace		: "ระยะแนวตั้ง",
DlgImgAlign			: "การจัดวาง",
DlgImgAlignLeft		: "ชิดซ้าย",
DlgImgAlignAbsBottom: "ชิดด้านล่างสุด",
DlgImgAlignAbsMiddle: "กึ่งกลาง",
DlgImgAlignBaseline	: "ชิดบรรทัด",
DlgImgAlignBottom	: "ชิดด้านล่าง",
DlgImgAlignMiddle	: "กึ่งกลางแนวตั้ง",
DlgImgAlignRight	: "ชิดขวา",
DlgImgAlignTextTop	: "ใต้ตัวอักษร",
DlgImgAlignTop		: "บนสุด",
DlgImgPreview		: "หน้าเอกสารตัวอย่าง",
DlgImgAlertUrl		: "กรุณาระบุที่อยู่อ้างอิงออนไลน์ของไฟล์รูปภาพ (URL)",
DlgImgLinkTab		: "Link",	//MISSING

// Flash Dialog
DlgFlashTitle		: "Flash Properties",	//MISSING
DlgFlashChkPlay		: "Auto Play",	//MISSING
DlgFlashChkLoop		: "Loop",	//MISSING
DlgFlashChkMenu		: "Enable Flash Menu",	//MISSING
DlgFlashScale		: "Scale",	//MISSING
DlgFlashScaleAll	: "Show all",	//MISSING
DlgFlashScaleNoBorder	: "No Border",	//MISSING
DlgFlashScaleFit	: "Exact Fit",	//MISSING

// Link Dialog
DlgLnkWindowTitle	: "ลิงค์เชื่อมโยงเว็บ อีเมล์ รูปภาพ หรือไฟล์อื่นๆ",
DlgLnkInfoTab		: "รายละเอียด",
DlgLnkTargetTab		: "การเปิดหน้าจอ",

DlgLnkType			: "ประเภทของลิงค์",
DlgLnkTypeURL		: "ที่อยู่อ้างอิงออนไลน์ (URL)",
DlgLnkTypeAnchor	: "จุดเชื่อมโยง (Anchor)",
DlgLnkTypeEMail		: "ส่งอีเมล์ (E-Mail)",
DlgLnkProto			: "โปรโตคอล",
DlgLnkProtoOther	: "<อื่นๆ>",
DlgLnkURL			: "ที่อยู่อ้างอิงออนไลน์ (URL)",
DlgLnkAnchorSel		: "ระบุข้อมูลของจุดเชื่อมโยง (Anchor)",
DlgLnkAnchorByName	: "ชื่อ",
DlgLnkAnchorById	: "ไอดี",
DlgLnkNoAnchors		: "<ยังไม่มีจุดเชื่อมโยงภายในหน้าเอกสารนี้>",		//REVIEW : Change < and > with ( and )
DlgLnkEMail			: "อีเมล์ (E-Mail)",
DlgLnkEMailSubject	: "หัวเรื่อง",
DlgLnkEMailBody		: "ข้อความ",
DlgLnkUpload		: "อัพโหลดไฟล์",
DlgLnkBtnUpload		: "บันทึกไฟล์ไว้บนเซิร์ฟเวอร์",

DlgLnkTarget		: "การเปิดหน้าลิงค์",
DlgLnkTargetFrame	: "<เปิดในเฟรม>",
DlgLnkTargetPopup	: "<เปิดหน้าจอเล็ก (Pop-up)>",
DlgLnkTargetBlank	: "เปิดหน้าจอใหม่ (_blank)",
DlgLnkTargetParent	: "เปิดในหน้าหลัก (_parent)",
DlgLnkTargetSelf	: "เปิดในหน้าปัจจุบัน (_self)",
DlgLnkTargetTop		: "เปิดในหน้าบนสุด (_top)",
DlgLnkTargetFrameName	: "ชื่อทาร์เก็ตเฟรม",
DlgLnkPopWinName	: "ระบุชื่อหน้าจอเล็ก (Pop-up)",
DlgLnkPopWinFeat	: "คุณสมบัติของหน้าจอเล็ก (Pop-up)",
DlgLnkPopResize		: "ปรับขนาดหน้าจอ",
DlgLnkPopLocation	: "แสดงที่อยู่ของไฟล์",
DlgLnkPopMenu		: "แสดงแถบเมนู",
DlgLnkPopScroll		: "แสดงแถบเลื่อน",
DlgLnkPopStatus		: "แสดงแถบสถานะ",
DlgLnkPopToolbar	: "แสดงแถบเครื่องมือ",
DlgLnkPopFullScrn	: "แสดงเต็มหน้าจอ (IE5.5++ เท่านั้น)",
DlgLnkPopDependent	: "แสดงเต็มหน้าจอ (Netscape)",
DlgLnkPopWidth		: "กว้าง",
DlgLnkPopHeight		: "สูง",
DlgLnkPopLeft		: "พิกัดซ้าย (Left Position)",
DlgLnkPopTop		: "พิกัดบน (Top Position)",

DlnLnkMsgNoUrl		: "กรุณาระบุที่อยู่อ้างอิงออนไลน์ (URL)",
DlnLnkMsgNoEMail	: "กรุณาระบุอีเมล์ (E-mail)",
DlnLnkMsgNoAnchor	: "กรุณาระบุจุดเชื่อมโยง (Anchor)",
DlnLnkMsgInvPopName	: "The popup name must begin with an alphabetic character and must not contain spaces",	//MISSING

// Color Dialog
DlgColorTitle		: "เลือกสี",
DlgColorBtnClear	: "ล้างค่ารหัสสี",
DlgColorHighlight	: "ตัวอย่างสี",
DlgColorSelected	: "สีที่เลือก",

// Smiley Dialog
DlgSmileyTitle		: "แทรกสัญักษณ์สื่ออารมณ์",

// Special Character Dialog
DlgSpecialCharTitle	: "แทรกตัวอักษรพิเศษ",

// Table Dialog
DlgTableTitle		: "คุณสมบัติของ ตาราง",
DlgTableRows		: "แถว",
DlgTableColumns		: "สดมน์",
DlgTableBorder		: "ขนาดเส้นขอบ",
DlgTableAlign		: "การจัดตำแหน่ง",
DlgTableAlignNotSet	: "<ไม่ระบุ>",
DlgTableAlignLeft	: "ชิดซ้าย",
DlgTableAlignCenter	: "กึ่งกลาง",
DlgTableAlignRight	: "ชิดขวา",
DlgTableWidth		: "กว้าง",
DlgTableWidthPx		: "จุดสี",
DlgTableWidthPc		: "เปอร์เซ็น",
DlgTableHeight		: "สูง",
DlgTableCellSpace	: "ระยะแนวนอนน",
DlgTableCellPad		: "ระยะแนวตั้ง",
DlgTableCaption		: "หัวเรื่องของตาราง",
DlgTableSummary		: "Summary",	//MISSING

// Table Cell Dialog
DlgCellTitle		: "คุณสมบัติของ ช่อง",
DlgCellWidth		: "กว้าง",
DlgCellWidthPx		: "จุดสี",
DlgCellWidthPc		: "เปอร์เซ็น",
DlgCellHeight		: "สูง",
DlgCellWordWrap		: "ตัดบรรทัดอัตโนมัติ",
DlgCellWordWrapNotSet	: "<ไม่ระบุ>",
DlgCellWordWrapYes	: "ใ่ช่",
DlgCellWordWrapNo	: "ไม่",
DlgCellHorAlign		: "การจัดวางแนวนอน",
DlgCellHorAlignNotSet	: "<ไม่ระบุ>",
DlgCellHorAlignLeft	: "ชิดซ้าย",
DlgCellHorAlignCenter	: "กึ่งกลาง",
DlgCellHorAlignRight: "ชิดขวา",
DlgCellVerAlign		: "การจัดวางแนวตั้ง",
DlgCellVerAlignNotSet	: "<ไม่ระบุ>",
DlgCellVerAlignTop	: "บนสุด",
DlgCellVerAlignMiddle	: "กึ่งกลาง",
DlgCellVerAlignBottom	: "ล่างสุด",
DlgCellVerAlignBaseline	: "อิงบรรทัด",
DlgCellRowSpan		: "จำนวนแถวที่คร่อมกัน",
DlgCellCollSpan		: "จำนวนสดมน์ที่คร่อมกัน",
DlgCellBackColor	: "สีพื้นหลัง",
DlgCellBorderColor	: "สีเส้นขอบ",
DlgCellBtnSelect	: "เลือก..",

// Find Dialog
DlgFindTitle		: "ค้นหา",
DlgFindFindBtn		: "ค้นหา",
DlgFindNotFoundMsg	: "ไม่พบคำที่ค้นหา.",

// Replace Dialog
DlgReplaceTitle			: "ค้นหาและแทนที่",
DlgReplaceFindLbl		: "ค้นหาคำว่า:",
DlgReplaceReplaceLbl	: "แทนที่ด้วย:",
DlgReplaceCaseChk		: "ตัวโหญ่-เล็ก ต้องตรงกัน",
DlgReplaceReplaceBtn	: "แทนที่",
DlgReplaceReplAllBtn	: "แทนที่ทั้งหมดที่พบ",
DlgReplaceWordChk		: "ต้องตรงกันทุกคำ",

// Paste Operations / Dialog
PasteErrorPaste	: "ไม่สามารถวางข้อความที่สำเนามาได้เนื่องจากการกำหนดค่าระดับความปลอดภัย. กรุณาใช้ปุ่มลัดเพื่อวางข้อความแทน (กดปุ่ม Ctrl และตัว V พร้อมกัน).",
PasteErrorCut	: "ไม่สามารถตัดข้อความที่เลือกไว้ได้เนื่องจากการกำหนดค่าระดับความปลอดภัย. กรุณาใช้ปุ่มลัดเพื่อวางข้อความแทน (กดปุ่ม Ctrl และตัว X พร้อมกัน).",
PasteErrorCopy	: "ไม่สามารถสำเนาข้อความที่เลือกไว้ได้เนื่องจากการกำหนดค่าระดับความปลอดภัย. กรุณาใช้ปุ่มลัดเพื่อวางข้อความแทน (กดปุ่ม Ctrl และตัว C พร้อมกัน).",

PasteAsText		: "วางแบบตัวอักษรธรรมดา",
PasteFromWord	: "วางแบบตัวอักษรจากโปรแกรมเวิร์ด",

DlgPasteMsg2	: "Please paste inside the following box using the keyboard (<strong>Ctrl+V</strong>) and hit <strong>OK</strong>.",	//MISSING
DlgPasteIgnoreFont		: "Ignore Font Face definitions",	//MISSING
DlgPasteRemoveStyles	: "Remove Styles definitions",	//MISSING
DlgPasteCleanBox		: "Clean Up Box",	//MISSING

// Color Picker
ColorAutomatic	: "สีอัตโนมัติ",
ColorMoreColors	: "เลือกสีอื่นๆ...",

// Document Properties
DocProps		: "คุณสมบัติของเอกสาร",

// Anchor Dialog
DlgAnchorTitle		: "คุณสมบัติของ Anchor",
DlgAnchorName		: "ชื่อ Anchor",
DlgAnchorErrorName	: "กรุณาระบุชื่อของ Anchor",

// Speller Pages Dialog
DlgSpellNotInDic		: "ไม่พบในดิกชันนารี",
DlgSpellChangeTo		: "แก้ไขเป็น",
DlgSpellBtnIgnore		: "ยกเว้น",
DlgSpellBtnIgnoreAll	: "ยกเว้นทั้งหมด",
DlgSpellBtnReplace		: "แทนที่",
DlgSpellBtnReplaceAll	: "แทนที่ทั้งหมด",
DlgSpellBtnUndo			: "ยกเลิก",
DlgSpellNoSuggestions	: "- ไม่มีคำแนะนำใดๆ -",
DlgSpellProgress		: "กำลังตรวจสอบคำสะกด...",
DlgSpellNoMispell		: "ตรวจสอบคำสะกดเสร็จสิ้น: ไม่พบคำสะกดผิด",
DlgSpellNoChanges		: "ตรวจสอบคำสะกดเสร็จสิ้น: ไม่มีการแก้คำใดๆ",
DlgSpellOneChange		: "ตรวจสอบคำสะกดเสร็จสิ้น: แก้ไข1คำ",
DlgSpellManyChanges		: "ตรวจสอบคำสะกดเสร็จสิ้น:: แก้ไข %1 คำ",

IeSpellDownload			: "ไม่ได้ติดตั้งระบบตรวจสอบคำสะกด. ต้องการติดตั้งไหมครับ?",

// Button Dialog
DlgButtonText		: "ข้อความ (ค่าตัวแปร)",
DlgButtonType		: "ข้อความ",
DlgButtonTypeBtn	: "Button",	//MISSING
DlgButtonTypeSbm	: "Submit",	//MISSING
DlgButtonTypeRst	: "Reset",	//MISSING

// Checkbox and Radio Button Dialogs
DlgCheckboxName		: "ชื่อ",
DlgCheckboxValue	: "ค่าตัวแปร",
DlgCheckboxSelected	: "เลือกเป็นค่าเริ่มต้น",

// Form Dialog
DlgFormName		: "ชื่อ",
DlgFormAction	: "แอคชั่น",
DlgFormMethod	: "เมธอด",

// Select Field Dialog
DlgSelectName		: "ชื่อ",
DlgSelectValue		: "ค่าตัวแปร",
DlgSelectSize		: "ขนาด",
DlgSelectLines		: "บรรทัด",
DlgSelectChkMulti	: "เลือกหลายค่าได้",
DlgSelectOpAvail	: "รายการตัวเลือก",
DlgSelectOpText		: "ข้อความ",
DlgSelectOpValue	: "ค่าตัวแปร",
DlgSelectBtnAdd		: "เพิ่ม",
DlgSelectBtnModify	: "แก้ไข",
DlgSelectBtnUp		: "บน",
DlgSelectBtnDown	: "ล่าง",
DlgSelectBtnSetValue : "เลือกเป็นค่าเริ่มต้น",
DlgSelectBtnDelete	: "ลบ",

// Textarea Dialog
DlgTextareaName	: "ชื่อ",
DlgTextareaCols	: "สดมภ์",
DlgTextareaRows	: "แถว",

// Text Field Dialog
DlgTextName			: "ชื่อ",
DlgTextValue		: "ค่าตัวแปร",
DlgTextCharWidth	: "ความกว้าง",
DlgTextMaxChars		: "จำนวนตัวอักษรสูงสุด",
DlgTextType			: "ชนิด",
DlgTextTypeText		: "ข้อความ",
DlgTextTypePass		: "รหัสผ่าน",

// Hidden Field Dialog
DlgHiddenName	: "ชื่อ",
DlgHiddenValue	: "ค่าตัวแปร",

// Bulleted List Dialog
BulletedListProp	: "คุณสมบัติของ บูลเล็ตลิสต์",
NumberedListProp	: "คุณสมบัติของ นัมเบอร์ลิสต์",
DlgLstStart			: "Start",	//MISSING
DlgLstType			: "ชนิด",
DlgLstTypeCircle	: "รูปวงกลม",
DlgLstTypeDisc		: "Disc",	//MISSING
DlgLstTypeSquare	: "รูปสี่เหลี่ยม",
DlgLstTypeNumbers	: "หมายเลข (1, 2, 3)",
DlgLstTypeLCase		: "ตัวพิมพ์เล็ก (a, b, c)",
DlgLstTypeUCase		: "ตัวพิมพ์ใหญ่ (A, B, C)",
DlgLstTypeSRoman	: "เลขโรมันพิมพ์เล็ก (i, ii, iii)",
DlgLstTypeLRoman	: "เลขโรมันพิมพ์ใหญ่ (I, II, III)",

// Document Properties Dialog
DlgDocGeneralTab	: "ลักษณะทั่วไปของเอกสาร",
DlgDocBackTab		: "พื้นหลัง",
DlgDocColorsTab		: "สีและระยะขอบ",
DlgDocMetaTab		: "ข้อมูลสำหรับเสิร์ชเอนจิ้น",

DlgDocPageTitle		: "ชื่อไตเติ้ล",
DlgDocLangDir		: "การอ่านภาษา",
DlgDocLangDirLTR	: "จากซ้ายไปขวา (LTR)",
DlgDocLangDirRTL	: "จากขวาไปซ้าย (RTL)",
DlgDocLangCode		: "รหัสภาษา",
DlgDocCharSet		: "ชุดตัวอักษร",
DlgDocCharSetCE		: "Central European",	//MISSING
DlgDocCharSetCT		: "Chinese Traditional (Big5)",	//MISSING
DlgDocCharSetCR		: "Cyrillic",	//MISSING
DlgDocCharSetGR		: "Greek",	//MISSING
DlgDocCharSetJP		: "Japanese",	//MISSING
DlgDocCharSetKR		: "Korean",	//MISSING
DlgDocCharSetTR		: "Turkish",	//MISSING
DlgDocCharSetUN		: "Unicode (UTF-8)",	//MISSING
DlgDocCharSetWE		: "Western European",	//MISSING
DlgDocCharSetOther	: "ชุดตัวอักษรอื่นๆ",

DlgDocDocType		: "ประเภทของเอกสาร",
DlgDocDocTypeOther	: "ประเภทเอกสารอื่นๆ",
DlgDocIncXHTML		: "รวมเอา  XHTML Declarations ไว้ด้วย",
DlgDocBgColor		: "สีพื้นหลัง",
DlgDocBgImage		: "ที่อยู่อ้างอิงออนไลน์ของรูปพื้นหลัง (Image URL)",
DlgDocBgNoScroll	: "พื้นหลังแบบไม่มีแถบเลื่อน",
DlgDocCText			: "ข้อความ",
DlgDocCLink			: "ลิงค์",
DlgDocCVisited		: "ลิงค์ที่เคยคลิ้กแล้ว Visited Link",
DlgDocCActive		: "ลิงค์ที่กำลังคลิ้ก Active Link",
DlgDocMargins		: "ระยะขอบของหน้าเอกสาร",
DlgDocMaTop			: "ด้านบน",
DlgDocMaLeft		: "ด้านซ้าย",
DlgDocMaRight		: "ด้านขวา",
DlgDocMaBottom		: "ด้านล่าง",
DlgDocMeIndex		: "คำสำคัญอธิบายเอกสาร (คั่นคำด้วย คอมม่า)",
DlgDocMeDescr		: "ประโยคอธิบายเกี่ยวกับเอกสาร",
DlgDocMeAuthor		: "ผู้สร้างเอกสาร",
DlgDocMeCopy		: "สงวนลิขสิทธิ์",
DlgDocPreview		: "ตัวอย่างหน้าเอกสาร",

// Templates Dialog
Templates			: "Templates",	//MISSING
DlgTemplatesTitle	: "Content Templates",	//MISSING
DlgTemplatesSelMsg	: "Please select the template to open in the editor<br />(the actual contents will be lost):",	//MISSING
DlgTemplatesLoading	: "Loading templates list. Please wait...",	//MISSING
DlgTemplatesNoTpl	: "(No templates defined)",	//MISSING
DlgTemplatesReplace	: "Replace actual contents",	//MISSING

// About Dialog
DlgAboutAboutTab	: "เกี่ยวกับโปรแกรม",
DlgAboutBrowserInfoTab	: "โปรแกรมท่องเว็บที่ท่านใช้",
DlgAboutLicenseTab	: "License",	//MISSING
DlgAboutVersion		: "รุ่น",
DlgAboutInfo		: "ข้อมูลเพิ่มเติมภาษาไทยติดต่อ</BR>นาย ชรินทร์ อาษากิจ (อู้ด)</BR><A HREF='mailto:arsakit@gmail.com'>arsakit@gmail.com</A> tel. (+66) 06-9241924</BR>หรือดาวน์โหลดรุ่นภาษาไทยได้ที่เว็บไซต์</BR><A HREF='http://www.thaimall4u.com'>www.Thaimall4u.com</A></BR>ข้อมูลเพิ่มเติมภาษาอังกฤษ กรุณาไปที่นี่"
};