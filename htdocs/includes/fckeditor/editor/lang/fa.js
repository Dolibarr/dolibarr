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
 * File Name: fa.js
 * 	Persian language file.
 * 
 * File Authors:
 * 		Hamed Taj-Abadi (hamed@ranginkaman.com)
 */

var FCKLang =
{
// Language direction : "ltr" (left to right) or "rtl" (right to left).
Dir					: "rtl",

ToolbarCollapse		: "بستن منوابزار",
ToolbarExpand		: "بازکردن منوابزار",

// Toolbar Items and Context Menu
Save				: "ذخيره",
NewPage				: "جديد",
Preview				: "پيش نمايش",
Cut					: "برش",
Copy				: "کپی",
Paste				: "چسباندن",
PasteText			: "چسباندن به عنوان متن ساده",
PasteWord			: "چسباندن از WORD",
Print				: "چاپ",
SelectAll			: "انتخاب همه",
RemoveFormat		: "برداشتن فرمت",
InsertLinkLbl		: "لينک",
InsertLink			: "درج/ويرايش لينک",
RemoveLink			: "برداشتن لينک",
Anchor				: "درج/ويرايش لنگر",
InsertImageLbl		: "تصوير",
InsertImage			: "درج/ويرايش تصوير",
InsertFlashLbl		: "Flash",	//MISSING
InsertFlash			: "Insert/Edit Flash",	//MISSING
InsertTableLbl		: "جدول",
InsertTable			: "درج/ويرايش جدول",
InsertLineLbl		: "خط",
InsertLine			: "درج خط افقی",
InsertSpecialCharLbl: "شناسه ويژه",
InsertSpecialChar	: "درج شناسه ويژه",
InsertSmileyLbl		: "خندانک",
InsertSmiley		: "درج خندانک",
About				: "درباره FCKeditor",
Bold				: "پررنگ",
Italic				: "ايتاليک",
Underline			: "زيرخط",
StrikeThrough		: "ميان خط",
Subscript			: "انديس پائين",
Superscript			: "انديس بالا",
LeftJustify			: "چپ چين",
CenterJustify		: "وسط چين",
RightJustify		: "راست چين",
BlockJustify		: "بلوک چين",
DecreaseIndent		: "کاهش تورفتگی",
IncreaseIndent		: "افزايش تورفتگی",
Undo				: "واچيدن",
Redo				: "بازچيدن",
NumberedListLbl		: "فهرست عددی",
NumberedList		: "درج/برداشتن فهرست عددی",
BulletedListLbl		: "فهرست نقطه ای",
BulletedList		: "درج/برداشتن فهرست نقطه ای",
ShowTableBorders	: "نمايش لبه جدول",
ShowDetails			: "نمايش جزئيات",
Style				: "سبک",
FontFormat			: "فرمت",
Font				: "قلم",
FontSize			: "اندازه",
TextColor			: "رنگ متن",
BGColor				: "رنگ پس زمينه",
Source				: "منبع",
Find				: "جستجو",
Replace				: "جايگزينی",
SpellCheck			: "کنترل املا",
UniversalKeyboard	: "صفحه کليد جهانی",
PageBreakLbl		: "Page Break",	//MISSING
PageBreak			: "Insert Page Break",	//MISSING

Form			: "فرم",
Checkbox		: "دکمه گزينه ای",
RadioButton		: "دکمه راديويی",
TextField		: "فيلد متنی",
Textarea		: "ناحيه متنی",
HiddenField		: "فيلد پنهان",
Button			: "دکمه",
SelectionField	: "فيلد انتخابی",
ImageButton		: "دکمه تصويری",

FitWindow		: "Maximize the editor size",	//MISSING

// Context Menu
EditLink			: "ويرايش لينک",
CellCM				: "Cell",	//MISSING
RowCM				: "Row",	//MISSING
ColumnCM			: "Column",	//MISSING
InsertRow			: "درج سطر",
DeleteRows			: "حذف سطرها",
InsertColumn		: "درج ستون",
DeleteColumns		: "حذف ستونها",
InsertCell			: "درج سلول",
DeleteCells			: "حذف سلولها",
MergeCells			: "ادغام سلولها",
SplitCell			: "تفکيک سلول",
TableDelete			: "Delete Table",	//MISSING
CellProperties		: "ويژگيهای سلول",
TableProperties		: "ويژگيهای جدول",
ImageProperties		: "ويژگيهای تصوير",
FlashProperties		: "Flash Properties",	//MISSING

AnchorProp			: "ويژگيهای لنگر",
ButtonProp			: "ويژگيهای دکمه",
CheckboxProp		: "ويژگيهای دکمه گزينه ای",
HiddenFieldProp		: "ويژگيهای فيلد پنهان",
RadioButtonProp		: "ويژگيهای دکمه راديويی",
ImageButtonProp		: "ويژگيهای دکمه تصويری",
TextFieldProp		: "ويژگيهای فيلد متنی",
SelectionFieldProp	: "ويژگيهای فيلد انتخابی",
TextareaProp		: "ويژگيهای ناحيه متنی",
FormProp			: "ويژگيهای فرم",

FontFormats			: "نرمال;فرمت شده;آدرس;سرنويس 1;سرنويس 2;سرنويس 3;سرنويس 4;سرنويس 5;سرنويس 6;بند;(DIV)",

// Alerts and Messages
ProcessingXHTML		: "پردازش XHTML. لطفا صبر کنيد...",
Done				: "انجام شد",
PasteWordConfirm	: "متنی که می خواهيد بچسبانيد به نظر از WORD کپی شده است. آيا مايليد قبل از چسباندن آنرا تميز کنيد؟ ",
NotCompatiblePaste	: "اين فرمان برای مرورگر Internet Explorer از نگارش 5.5 يا بالاتر در دسترس است. آيا مايليد بدون تميز کردن متن را بچسبانيد؟",
UnknownToolbarItem	: "فقره منوابزار ناشناخته \"%1\"",
UnknownCommand		: "نام دستور ناشناخته \"%1\"",
NotImplemented		: "دستور اجرا نشد",
UnknownToolbarSet	: "مجموعه منوابزار \"%1\" وجود ندارد",
NoActiveX			: "Your browser's security settings could limit some features of the editor. You must enable the option \"Run ActiveX controls and plug-ins\". You may experience errors and notice missing features.",	//MISSING
BrowseServerBlocked : "The resources browser could not be opened. Make sure that all popup blockers are disabled.",	//MISSING
DialogBlocked		: "It was not possible to open the dialog window. Make sure all popup blockers are disabled.",	//MISSING

// Dialogs
DlgBtnOK			: "تائيد",
DlgBtnCancel		: "انصراف",
DlgBtnClose			: "بستن",
DlgBtnBrowseServer	: "فهرست نمايی سرور",
DlgAdvancedTag		: "پيشرفته",
DlgOpOther			: "<غيره>",
DlgInfoTab			: "Info",	//MISSING
DlgAlertUrl			: "Please insert the URL",	//MISSING

// General Dialogs Labels
DlgGenNotSet		: "<تعين نشده>",
DlgGenId			: "کد",
DlgGenLangDir		: "جهت نمای زبان",
DlgGenLangDirLtr	: "چپ به راست (LTR)",
DlgGenLangDirRtl	: "راست به چپ (RTL)",
DlgGenLangCode		: "کد زبان",
DlgGenAccessKey		: "کليد دستيابی",
DlgGenName			: "نام",
DlgGenTabIndex		: "انديس برگه",
DlgGenLongDescr		: "URL توضيح طولانی",
DlgGenClass			: "کلاسهای استايل شيت",
DlgGenTitle			: "عنوان کمکی",
DlgGenContType		: "نوع محتوی کمکی",
DlgGenLinkCharset	: "مجموعه نويسه منبع لينک شده",
DlgGenStyle			: "سبک",

// Image Dialog
DlgImgTitle			: "ويژگيهای تصوير",
DlgImgInfoTab		: "اطلاعات تصوير",
DlgImgBtnUpload		: "به سرور ارسال کن",
DlgImgURL			: "URL",
DlgImgUpload		: "انتقال به سرور",
DlgImgAlt			: "متن جايگزين",
DlgImgWidth			: "پهنا",
DlgImgHeight		: "درازا",
DlgImgLockRatio		: "قفل کردن نسبت",
DlgBtnResetSize		: "بازنشانی اندازه",
DlgImgBorder		: "لبه",
DlgImgHSpace		: "فاصله افقی",
DlgImgVSpace		: "فاصله عمودی",
DlgImgAlign			: "چينش",
DlgImgAlignLeft		: "چپ",
DlgImgAlignAbsBottom: "پائين مطلق",
DlgImgAlignAbsMiddle: "وسط مطلق",
DlgImgAlignBaseline	: "خط پايه",
DlgImgAlignBottom	: "پائين",
DlgImgAlignMiddle	: "وسط",
DlgImgAlignRight	: "راست",
DlgImgAlignTextTop	: "متن بالا",
DlgImgAlignTop		: "بالا",
DlgImgPreview		: "پيش نمايش",
DlgImgAlertUrl		: "لطفا URL تصوير را انتخاب کنيد",
DlgImgLinkTab		: "لينک",

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
DlgLnkWindowTitle	: "لينک",
DlgLnkInfoTab		: "اطلاعات لينک",
DlgLnkTargetTab		: "مقصد",

DlgLnkType			: "نوع لينک",
DlgLnkTypeURL		: "URL",
DlgLnkTypeAnchor	: "پيوند در صفحه جاری",
DlgLnkTypeEMail		: "پست الکترونيکی",
DlgLnkProto			: "پروتکل",
DlgLnkProtoOther	: "<غيره>",
DlgLnkURL			: "URL",
DlgLnkAnchorSel		: "يک پيوند انتخاب کنيد",
DlgLnkAnchorByName	: "با نام پيوند",
DlgLnkAnchorById	: "با کد المان",
DlgLnkNoAnchors		: "<در اين سند پيوندی موجود نيست>",
DlgLnkEMail			: "آدرس پست الکترونيکی",
DlgLnkEMailSubject	: "موضوع پيام",
DlgLnkEMailBody		: "متن پيام",
DlgLnkUpload		: "انتقال به سرور",
DlgLnkBtnUpload		: "به سرور ارسال کن",

DlgLnkTarget		: "مقصد",
DlgLnkTargetFrame	: "<فريم>",
DlgLnkTargetPopup	: "<پنجره پاپاپ>",
DlgLnkTargetBlank	: "پنجره جديد (_blank)",
DlgLnkTargetParent	: "پنجره والد (_parent)",
DlgLnkTargetSelf	: "همان پنجره (_self)",
DlgLnkTargetTop		: "بالاترين پنجره (_top)",
DlgLnkTargetFrameName	: "نام فريم مقصد",
DlgLnkPopWinName	: "نام پنجره پاپاپ",
DlgLnkPopWinFeat	: "خصوصيات پنجره پاپاپ",
DlgLnkPopResize		: "قابل تغيراندازه",
DlgLnkPopLocation	: "نوار موقعيت",
DlgLnkPopMenu		: "نوار منو",
DlgLnkPopScroll		: "نوارهای طومار",
DlgLnkPopStatus		: "نوار وضعيت",
DlgLnkPopToolbar	: "نوارابزار",
DlgLnkPopFullScrn	: "تمام صفحه (IE)",
DlgLnkPopDependent	: "وابسته (Netscape)",
DlgLnkPopWidth		: "پهنا",
DlgLnkPopHeight		: "درازا",
DlgLnkPopLeft		: "موقعيت چپ",
DlgLnkPopTop		: "موقعيت بالا",

DlnLnkMsgNoUrl		: "لطفا URL لينک را وارد کنيد",
DlnLnkMsgNoEMail	: "لطفا آدرس ايميل را وارد کنيد",
DlnLnkMsgNoAnchor	: "لطفا پيوندی انتخاب کنيد",

// Color Dialog
DlgColorTitle		: "انتخاب رنگ",
DlgColorBtnClear	: "پاک کردن",
DlgColorHighlight	: "هايلايت",
DlgColorSelected	: "انتخاب شده",

// Smiley Dialog
DlgSmileyTitle		: "درج  خندانک",

// Special Character Dialog
DlgSpecialCharTitle	: "انتخاب شناسه های ويژه",

// Table Dialog
DlgTableTitle		: "ويژگيهای جدول",
DlgTableRows		: "سطرها",
DlgTableColumns		: "ستونها",
DlgTableBorder		: "اندازه لبه",
DlgTableAlign		: "چينش",
DlgTableAlignNotSet	: "<تعين نشده>",
DlgTableAlignLeft	: "چپ",
DlgTableAlignCenter	: "وسط",
DlgTableAlignRight	: "راست",
DlgTableWidth		: "پهنا",
DlgTableWidthPx		: "پيکسل",
DlgTableWidthPc		: "درصد",
DlgTableHeight		: "درازا",
DlgTableCellSpace	: "فاصله ميان سلولها",
DlgTableCellPad		: "فاصله پرشده در سلول",
DlgTableCaption		: "عنوان",
DlgTableSummary		: "Summary",	//MISSING

// Table Cell Dialog
DlgCellTitle		: "ويژگيهای سلول",
DlgCellWidth		: "پهنا",
DlgCellWidthPx		: "پيکسل",
DlgCellWidthPc		: "درصد",
DlgCellHeight		: "درازا",
DlgCellWordWrap		: "شکستن کلمات",
DlgCellWordWrapNotSet	: "<تعين نشده>",
DlgCellWordWrapYes	: "بله",
DlgCellWordWrapNo	: "خير",
DlgCellHorAlign		: "چينش افقی",
DlgCellHorAlignNotSet	: "<تعين نشده>",
DlgCellHorAlignLeft	: "چپ",
DlgCellHorAlignCenter	: "وسط",
DlgCellHorAlignRight: "راست",
DlgCellVerAlign		: "چينش عمودی",
DlgCellVerAlignNotSet	: "<تعين نشده>",
DlgCellVerAlignTop	: "بالا",
DlgCellVerAlignMiddle	: "ميان",
DlgCellVerAlignBottom	: "پائين",
DlgCellVerAlignBaseline	: "خط پايه",
DlgCellRowSpan		: "گستردگی سطرها",
DlgCellCollSpan		: "گستردگی ستونها",
DlgCellBackColor	: "رنگ پس زمينه",
DlgCellBorderColor	: "رنگ لبه",
DlgCellBtnSelect	: "انتخاب کنيد...",

// Find Dialog
DlgFindTitle		: "يافتن",
DlgFindFindBtn		: "يافتن",
DlgFindNotFoundMsg	: "متن مورد نظر يافت نشد.",

// Replace Dialog
DlgReplaceTitle			: "جايگزينی",
DlgReplaceFindLbl		: "چه چيز را می يابيد:",
DlgReplaceReplaceLbl	: "جايگزينی با:",
DlgReplaceCaseChk		: "انطباق قالب",
DlgReplaceReplaceBtn	: "جايگزينی",
DlgReplaceReplAllBtn	: "جايگزينی همه موارد",
DlgReplaceWordChk		: "انطباق کلمه کامل",

// Paste Operations / Dialog
PasteErrorPaste	: "تنظيمات امنيتی مرورگر شما اجازه نمی دهد که ويرايشگر به طور خودکار عملکردهای چسباندن کلمات را انجام دهد. لطفا از کلمه کليدی مرتبط با اينکار را استفاده کنيد (Ctrl+V).",
PasteErrorCut	: "تنظيمات امنيتی مرورگر شما اجازه نمی دهد که ويرايشگر به طور خودکار عملکردهای برش کلمات را انجام دهد. لطفا از کلمه کليدی مرتبط با اينکار را استفاده کنيد (Ctrl+X).",
PasteErrorCopy	: "تنظيمات امنيتی مرورگر شما اجازه نمی دهد که ويرايشگر به طور خودکار عملکردهای کپی کردن کلمات را انجام دهد. لطفا از کلمه کليدی مرتبط با اينکار را استفاده کنيد (Ctrl+C).",

PasteAsText		: "چسباندن به عنوان متن ساده",
PasteFromWord	: "چسباندن از Word",

DlgPasteMsg2	: "Please paste inside the following box using the keyboard (<STRONG>Ctrl+V</STRONG>) and hit <STRONG>OK</STRONG>.",	//MISSING
DlgPasteIgnoreFont		: "Ignore Font Face definitions",	//MISSING
DlgPasteRemoveStyles	: "Remove Styles definitions",	//MISSING
DlgPasteCleanBox		: "Clean Up Box",	//MISSING

// Color Picker
ColorAutomatic	: "خودکار",
ColorMoreColors	: "رنگهای بيشتر...",

// Document Properties
DocProps		: "ويژگيهای سند",

// Anchor Dialog
DlgAnchorTitle		: "ويژگيهای لنگر",
DlgAnchorName		: "نام لنگر",
DlgAnchorErrorName	: "لطفا نام لنگر را وارد کنيد",

// Speller Pages Dialog
DlgSpellNotInDic		: "در واژه نامه موجود نيست",
DlgSpellChangeTo		: "تغير به",
DlgSpellBtnIgnore		: "چشم پوشی",
DlgSpellBtnIgnoreAll	: "چشم پوشی همه",
DlgSpellBtnReplace		: "جايگزينی",
DlgSpellBtnReplaceAll	: "جايگزينی همه",
DlgSpellBtnUndo			: "واچينش",
DlgSpellNoSuggestions	: "- پيشنهادی نيست -",
DlgSpellProgress		: "کنترل املا در حال انجام...",
DlgSpellNoMispell		: "کنترل املا انجام شد. هيچ غلط املائی يافت نشد",
DlgSpellNoChanges		: "کنترل املا انجام شد. هيچ کلمه ای تغير نيافت",
DlgSpellOneChange		: "کنترل املا انجام شد. يک کلمه تغير يافت",
DlgSpellManyChanges		: "کنترل املا انجام شد. %1 کلمه تغير يافت",

IeSpellDownload			: "کنترل کننده املا نصب نشده است. آيا مايليد آنرا هم اکنون دريافت کنيد؟",

// Button Dialog
DlgButtonText	: "متن (مقدار)",
DlgButtonType	: "نوع",

// Checkbox and Radio Button Dialogs
DlgCheckboxName		: "نام",
DlgCheckboxValue	: "مقدار",
DlgCheckboxSelected	: "برگزيده",

// Form Dialog
DlgFormName		: "نام",
DlgFormAction	: "اقدام",
DlgFormMethod	: "متد",

// Select Field Dialog
DlgSelectName		: "نام",
DlgSelectValue		: "مقدار",
DlgSelectSize		: "اندازه",
DlgSelectLines		: "خطوط",
DlgSelectChkMulti	: "انتخاب چند گزينه ای مجاز",
DlgSelectOpAvail	: "گزينه های موجود",
DlgSelectOpText		: "متن",
DlgSelectOpValue	: "مقدار",
DlgSelectBtnAdd		: "اضافه",
DlgSelectBtnModify	: "ويرايش",
DlgSelectBtnUp		: "بالا",
DlgSelectBtnDown	: "پائين",
DlgSelectBtnSetValue : "تنظيم به عنوان مقدار برگزيده",
DlgSelectBtnDelete	: "حذف",

// Textarea Dialog
DlgTextareaName	: "نام",
DlgTextareaCols	: "ستونها",
DlgTextareaRows	: "سطرها",

// Text Field Dialog
DlgTextName			: "نام",
DlgTextValue		: "مقدار",
DlgTextCharWidth	: "پهنای شناسه",
DlgTextMaxChars		: "بيشينه شناسه",
DlgTextType			: "نوع",
DlgTextTypeText		: "متن",
DlgTextTypePass		: "کلمه عبور",

// Hidden Field Dialog
DlgHiddenName	: "نام",
DlgHiddenValue	: "مقدار",

// Bulleted List Dialog
BulletedListProp	: "ويژگيهای فهرست دکمه ای",
NumberedListProp	: "ويژگيهای فهرست عددی",
DlgLstType			: "نوع",
DlgLstTypeCircle	: "دايره",
DlgLstTypeDisc		: "Disc",	//MISSING
DlgLstTypeSquare	: "مربع",
DlgLstTypeNumbers	: "شماره ها (1، 2، 3)",
DlgLstTypeLCase		: "حروف کوچک (a، b، c)",
DlgLstTypeUCase		: "حروف بزرگ (A، B، C)",
DlgLstTypeSRoman	: "ارقام يونانی کوچک (i، ii، iii)",
DlgLstTypeLRoman	: "ارقام يونانی بزرگ (I، II، III)",

// Document Properties Dialog
DlgDocGeneralTab	: "عمومی",
DlgDocBackTab		: "پس زمينه",
DlgDocColorsTab		: "رنگها و حاشيه ها",
DlgDocMetaTab		: "فراداده",

DlgDocPageTitle		: "عنوان صفحه",
DlgDocLangDir		: "جهت زبان",
DlgDocLangDirLTR	: "چپ به راست (LTF(",
DlgDocLangDirRTL	: "راست به چپ (RTL(",
DlgDocLangCode		: "کد زبان",
DlgDocCharSet		: "رمزگذاری نويسه",
DlgDocCharSetOther	: "رمزگذاری نويسه های ديگر",

DlgDocDocType		: "عنوان نوع سند",
DlgDocDocTypeOther	: "عنوان نوع سند های ديگر",
DlgDocIncXHTML		: "شامل تعاريف XHTML",
DlgDocBgColor		: "رنگ پس زمينه",
DlgDocBgImage		: "URL تصوير پس زمينه",
DlgDocBgNoScroll	: "پس زمينه غير طوماری",
DlgDocCText			: "متن",
DlgDocCLink			: "لينک",
DlgDocCVisited		: "لينک مشاهده شده",
DlgDocCActive		: "لينک فعال",
DlgDocMargins		: "حاشيه صفحه",
DlgDocMaTop			: "رو",
DlgDocMaLeft		: "چپ",
DlgDocMaRight		: "راست",
DlgDocMaBottom		: "زير",
DlgDocMeIndex		: "کلمات کليدی انديس کردن سند (با کاما جدا شوند)",
DlgDocMeDescr		: "سند",
DlgDocMeAuthor		: "نويسنده",
DlgDocMeCopy		: "کپی رايت",
DlgDocPreview		: "پيش نمايش",

// Templates Dialog
Templates			: "الگوها",
DlgTemplatesTitle	: "الگوهای محتويات",
DlgTemplatesSelMsg	: "لطفا الگوی مورد نظر را برای باز کردن در ويرايشگر انتخاب نمائيد<br>(محتويات اصلی از دست خواهند رفت):",
DlgTemplatesLoading	: "بارگذاری فهرست الگوها. لطفا صبر کنيد...",
DlgTemplatesNoTpl	: "(الگوئی تعريف نشده است)",

// About Dialog
DlgAboutAboutTab	: "درباره",
DlgAboutBrowserInfoTab	: "اطلاعات مرورگر",
DlgAboutLicenseTab	: "License",	//MISSING
DlgAboutVersion		: "نگارش",
DlgAboutLicense		: "ليسانس تحت توافقنامه GNU Lesser General Public License",
DlgAboutInfo		: "برای اطلاعات بيشتر به آدرس زير برويد"
}