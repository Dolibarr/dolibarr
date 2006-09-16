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
 * File Name: tr.js
 * 	Turkish language file.
 * 
 * File Authors:
 * 		Bogac Guven (bogacmx@yahoo.com)
 */

var FCKLang =
{
// Language direction : "ltr" (left to right) or "rtl" (right to left).
Dir					: "ltr",

ToolbarCollapse		: "Araç Çubugunu Kapat",
ToolbarExpand		: "Araç Çubugunu Aç",

// Toolbar Items and Context Menu
Save				: "Kaydet",
NewPage				: "Yeni Sayfa",
Preview				: "Ön Izleme",
Cut					: "Kes",
Copy				: "Kopyala",
Paste				: "Yapistir",
PasteText			: "Düzyazi Olarak Yapistir",
PasteWord			: "Word'den Yapistir",
Print				: "Yazdir",
SelectAll			: "Tümünü Seç",
RemoveFormat		: "Biçimi Kaldir",
InsertLinkLbl		: "Köprü",
InsertLink			: "Köprü Ekle/Düzenle",
RemoveLink			: "Köprü Kaldir",
Anchor				: "Çapa Ekle/Düzenle",
InsertImageLbl		: "Resim",
InsertImage			: "Resim Ekle/Düzenle",
InsertFlashLbl		: "Flash",
InsertFlash			: "Flash Ekle/Düzenle",
InsertTableLbl		: "Tablo",
InsertTable			: "Tablo Ekle/Düzenle",
InsertLineLbl		: "Satir",
InsertLine			: "Yatay Satir Ekle",
InsertSpecialCharLbl: "Özel Karakter",
InsertSpecialChar	: "Özel Karakter Ekle",
InsertSmileyLbl		: "Ifade",
InsertSmiley		: "Ifade Ekle",
About				: "FCKeditor Hakkinda",
Bold				: "Kalin",
Italic				: "Italik",
Underline			: "Alti Çizgili",
StrikeThrough		: "Üstü Çizgili",
Subscript			: "Alt Simge",
Superscript			: "Üst Simge",
LeftJustify			: "Sola Dayali",
CenterJustify		: "Ortalanmis",
RightJustify		: "Saga Dayali",
BlockJustify		: "Iki Kenara Yaslanmis",
DecreaseIndent		: "Sekme Azalt",
IncreaseIndent		: "Sekme Arttir",
Undo				: "Geri Al",
Redo				: "Tekrarla",
NumberedListLbl		: "Numarali Liste",
NumberedList		: "Numarali Liste Ekle/Kaldir",
BulletedListLbl		: "Simgeli Liste",
BulletedList		: "Simgeli Liste Ekle/Kaldir",
ShowTableBorders	: "Tablo Kenarlarini Göster",
ShowDetails			: "Detaylari Göster",
Style				: "Stil",
FontFormat			: "Biçim",
Font				: "Yazi Tipi",
FontSize			: "Boyut",
TextColor			: "Yazi Rengi",
BGColor				: "Arka Renk",
Source				: "Kaynak",
Find				: "Bul",
Replace				: "Degistir",
SpellCheck			: "Yazim Denetimi",
UniversalKeyboard	: "Evrensel Klavye",
PageBreakLbl		: "Page Break",	//MISSING
PageBreak			: "Insert Page Break",	//MISSING

Form			: "Form",
Checkbox		: "Onay Kutusu",
RadioButton		: "Seçenek Dügmesi",
TextField		: "Metin Girisi",
Textarea		: "Çok Satirli Metin",
HiddenField		: "Gizli Veri",
Button			: "Dügme",
SelectionField	: "Seçim Mönüsü",
ImageButton		: "Resimli Dügme",

FitWindow		: "Maximize the editor size",	//MISSING

// Context Menu
EditLink			: "Köprü Düzenle",
CellCM				: "Cell",	//MISSING
RowCM				: "Row",	//MISSING
ColumnCM			: "Column",	//MISSING
InsertRow			: "Satir Ekle",
DeleteRows			: "Satir Sil",
InsertColumn		: "Sütun Ekle",
DeleteColumns		: "Sütun Sil",
InsertCell			: "Hücre Ekle",
DeleteCells			: "Hücre Sil",
MergeCells			: "Hücreleri Birlestir",
SplitCell			: "Hücre Böl",
TableDelete			: "Delete Table",	//MISSING
CellProperties		: "Hücre Özellikleri",
TableProperties		: "Tablo Özellikleri",
ImageProperties		: "Resim Özellikleri",
FlashProperties		: "Flash Özellikleri",

AnchorProp			: "Çapa Özellikleri",
ButtonProp			: "Dügme Özellikleri",
CheckboxProp		: "Onay Kutusu Özellikleri",
HiddenFieldProp		: "Gizli Veri Özellikleri",
RadioButtonProp		: "Seçenek Dügmesi Özellikleri",
ImageButtonProp		: "Resimli Dügme Özellikleri",
TextFieldProp		: "Metin Girisi Özellikleri",
SelectionFieldProp	: "Seçim Mönüsü Özellikleri",
TextareaProp		: "Çok Satirli Metin Özellikleri",
FormProp			: "Form Özellikleri",

FontFormats			: "Normal;Biçimli;Adres;Baslik 1;Baslik 2;Baslik 3;Baslik 4;Baslik 5;Baslik 6;Paragraf (DIV)",

// Alerts and Messages
ProcessingXHTML		: "XHTML isleniyor. Lütfen bekleyin...",
Done				: "Bitti",
PasteWordConfirm	: "Yapistirdiginiz yazi Word'den gelmise benziyor. Yapistirmadan önce silmek ister misiniz?",
NotCompatiblePaste	: "Bu komut Internet Explorer 5.5 ve ileriki sürümleri için mevcuttur. Temizlenmeden yapistirilmasini ister misiniz ?",
UnknownToolbarItem	: "Bilinmeyen araç çubugu ögesi \"%1\"",
UnknownCommand		: "Bilinmeyen komut \"%1\"",
NotImplemented		: "Komut uyarlanamadi",
UnknownToolbarSet	: "\"%1\" araç çubugu ögesi mevcut degil",
NoActiveX			: "Your browser's security settings could limit some features of the editor. You must enable the option \"Run ActiveX controls and plug-ins\". You may experience errors and notice missing features.",	//MISSING
BrowseServerBlocked : "The resources browser could not be opened. Make sure that all popup blockers are disabled.",	//MISSING
DialogBlocked		: "It was not possible to open the dialog window. Make sure all popup blockers are disabled.",	//MISSING

// Dialogs
DlgBtnOK			: "Tamam",
DlgBtnCancel		: "Iptal",
DlgBtnClose			: "Kapat",
DlgBtnBrowseServer	: "Sunucuyu Gez",
DlgAdvancedTag		: "Gelismis",
DlgOpOther			: "<Diger>",
DlgInfoTab			: "Bilgi",
DlgAlertUrl			: "Lütfen URL girin",

// General Dialogs Labels
DlgGenNotSet		: "<tanimlanmamis>",
DlgGenId			: "Kimlik",
DlgGenLangDir		: "Lisan Yönü",
DlgGenLangDirLtr	: "Soldan Saga (LTR)",
DlgGenLangDirRtl	: "Sagdan Sola (RTL)",
DlgGenLangCode		: "Lisan Kodlamasi",
DlgGenAccessKey		: "Erisim Tusu",
DlgGenName			: "Isim",
DlgGenTabIndex		: "Sekme Indeksi",
DlgGenLongDescr		: "Uzun Tanimli URL",
DlgGenClass			: "Stil Klaslari",
DlgGenTitle			: "Danisma Basligi",
DlgGenContType		: "Danisma Içerik Türü",
DlgGenLinkCharset	: "Bagli Kaynak Karakter Gurubu",
DlgGenStyle			: "Stil",

// Image Dialog
DlgImgTitle			: "Resim Özellikleri",
DlgImgInfoTab		: "Resim Bilgisi",
DlgImgBtnUpload		: "Sunucuya Yolla",
DlgImgURL			: "URL",
DlgImgUpload		: "Karsiya Yükle",
DlgImgAlt			: "Alternatif Yazi",
DlgImgWidth			: "Genislik",
DlgImgHeight		: "Yükseklik",
DlgImgLockRatio		: "Orani Kilitle",
DlgBtnResetSize		: "Boyutu Basa Döndür",
DlgImgBorder		: "Kenar",
DlgImgHSpace		: "Yatay Bosluk",
DlgImgVSpace		: "Dikey Bosluk",
DlgImgAlign			: "Hizalama",
DlgImgAlignLeft		: "Sol",
DlgImgAlignAbsBottom: "Tam Alti",
DlgImgAlignAbsMiddle: "Tam Ortasi",
DlgImgAlignBaseline	: "Taban Çizgisi",
DlgImgAlignBottom	: "Alt",
DlgImgAlignMiddle	: "Orta",
DlgImgAlignRight	: "Sag",
DlgImgAlignTextTop	: "Yazi Tepeye",
DlgImgAlignTop		: "Tepe",
DlgImgPreview		: "Ön Izleme",
DlgImgAlertUrl		: "Lütfen resimin URL'sini yaziniz",
DlgImgLinkTab		: "Köprü",

// Flash Dialog
DlgFlashTitle		: "Flash Özellikleri",
DlgFlashChkPlay		: "Otomatik Oynat",
DlgFlashChkLoop		: "Döngü",
DlgFlashChkMenu		: "Flash Mönüsünü Kullan",
DlgFlashScale		: "Boyutlandır",
DlgFlashScaleAll	: "Hepsini Göster",
DlgFlashScaleNoBorder	: "Kenar Yok",
DlgFlashScaleFit	: "Tam Sığdır",

// Link Dialog
DlgLnkWindowTitle	: "Köprü",
DlgLnkInfoTab		: "Köprü Bilgisi",
DlgLnkTargetTab		: "Hedef",

DlgLnkType			: "Köprü Türü",
DlgLnkTypeURL		: "URL",
DlgLnkTypeAnchor	: "Bu sayfada çapa",
DlgLnkTypeEMail		: "E-Posta",
DlgLnkProto			: "Protokol",
DlgLnkProtoOther	: "<diger>",
DlgLnkURL			: "URL",
DlgLnkAnchorSel		: "Çapa Seç",
DlgLnkAnchorByName	: "Çapa Ismi ile",
DlgLnkAnchorById	: "Eleman Id ile",
DlgLnkNoAnchors		: "<Bu dokümanda hiç çapa yok>",
DlgLnkEMail			: "E-Posta Adresi",
DlgLnkEMailSubject	: "Mesaj Konusu",
DlgLnkEMailBody		: "Mesaj Vücudu",
DlgLnkUpload		: "Karsiya Yükle",
DlgLnkBtnUpload		: "Sunucuya Gönder",

DlgLnkTarget		: "Hedef",
DlgLnkTargetFrame	: "<çerçeve>",
DlgLnkTargetPopup	: "<yeni açilan pencere>",
DlgLnkTargetBlank	: "Yeni Pencere(_blank)",
DlgLnkTargetParent	: "Anne Pencere (_parent)",
DlgLnkTargetSelf	: "Kendi Penceresi (_self)",
DlgLnkTargetTop		: "En Üst Pencere (_top)",
DlgLnkTargetFrameName	: "Hedef Çerçeve Ismi",
DlgLnkPopWinName	: "Yeni Açilan Pencere Ismi",
DlgLnkPopWinFeat	: "Yeni Açilan Pencere Özellikleri",
DlgLnkPopResize		: "Boyutlandirilabilir",
DlgLnkPopLocation	: "Yer Çubugu",
DlgLnkPopMenu		: "Mönü Çubugu",
DlgLnkPopScroll		: "Kaydirma Çubuklari",
DlgLnkPopStatus		: "Statü Çubugu",
DlgLnkPopToolbar	: "Araç Çubugu",
DlgLnkPopFullScrn	: "Tam Ekran (IE)",
DlgLnkPopDependent	: "Bagli-Dependent- (Netscape)",
DlgLnkPopWidth		: "Genislik",
DlgLnkPopHeight		: "Yükseklik",
DlgLnkPopLeft		: "Sola Göre Pozisyon",
DlgLnkPopTop		: "Yukariya Göre Pozisyon",

DlnLnkMsgNoUrl		: "Lütfen köprü URL'sini yazin",
DlnLnkMsgNoEMail	: "Lütfen E-posta adresini yazin",
DlnLnkMsgNoAnchor	: "Lütfen bir çapa seçin",

// Color Dialog
DlgColorTitle		: "Renk Seç",
DlgColorBtnClear	: "Temizle",
DlgColorHighlight	: "Belirle",
DlgColorSelected	: "Seçilmis",

// Smiley Dialog
DlgSmileyTitle		: "Ifade Ekle",

// Special Character Dialog
DlgSpecialCharTitle	: "Özel Karakter Seç",

// Table Dialog
DlgTableTitle		: "Tablo Özellikleri",
DlgTableRows		: "Satirlar",
DlgTableColumns		: "Sütunlar",
DlgTableBorder		: "Kenar Kalinligi",
DlgTableAlign		: "Hizalama",
DlgTableAlignNotSet	: "<Tanimlanmamis>",
DlgTableAlignLeft	: "Sol",
DlgTableAlignCenter	: "Merkez",
DlgTableAlignRight	: "Sag",
DlgTableWidth		: "Genislik",
DlgTableWidthPx		: "piksel",
DlgTableWidthPc		: "yüzde",
DlgTableHeight		: "Yükseklik",
DlgTableCellSpace	: "Izgara kalinligi",
DlgTableCellPad		: "Izgara yazi arasi",
DlgTableCaption		: "Baslik",
DlgTableSummary		: "Summary",	//MISSING

// Table Cell Dialog
DlgCellTitle		: "Hücre Özellikleri",
DlgCellWidth		: "Genislik",
DlgCellWidthPx		: "piksel",
DlgCellWidthPc		: "yüzde",
DlgCellHeight		: "Yükseklik",
DlgCellWordWrap		: "Sözcük Kaydir",
DlgCellWordWrapNotSet	: "<Tanimlanmamis>",
DlgCellWordWrapYes	: "Evet",
DlgCellWordWrapNo	: "Hayir",
DlgCellHorAlign		: "Yatay Hizalama",
DlgCellHorAlignNotSet	: "<Tanimlanmamis>",
DlgCellHorAlignLeft	: "Sol",
DlgCellHorAlignCenter	: "Merkez",
DlgCellHorAlignRight: "Sag",
DlgCellVerAlign		: "Dikey Hizalama",
DlgCellVerAlignNotSet	: "<Tanimlanmamis>",
DlgCellVerAlignTop	: "Tepe",
DlgCellVerAlignMiddle	: "Orta",
DlgCellVerAlignBottom	: "Alt",
DlgCellVerAlignBaseline	: "Taban Çizgisi",
DlgCellRowSpan		: "Satir Kapla",
DlgCellCollSpan		: "Sütun Kapla",
DlgCellBackColor	: "Arka Plan Rengi",
DlgCellBorderColor	: "Kenar Rengi",
DlgCellBtnSelect	: "Seç...",

// Find Dialog
DlgFindTitle		: "Bul",
DlgFindFindBtn		: "Bul",
DlgFindNotFoundMsg	: "Belirtilen yazi bulunamadi.",

// Replace Dialog
DlgReplaceTitle			: "Degistir",
DlgReplaceFindLbl		: "Aranan:",
DlgReplaceReplaceLbl	: "Bunla degistir:",
DlgReplaceCaseChk		: "Büyük/küçük harf duyarli",
DlgReplaceReplaceBtn	: "Degistir",
DlgReplaceReplAllBtn	: "Tümünü Degistir",
DlgReplaceWordChk		: "Kelimenin tamami uysun",

// Paste Operations / Dialog
PasteErrorPaste	: "Gezgin yaziliminizin güvenlik ayarlari editörün otomatik yapistirma islemine izin vermiyor. Islem için (Ctrl+V) tuslarini kullanin.",
PasteErrorCut	: "Gezgin yaziliminizin güvenlik ayarlari editörün otomatik kesme islemine izin vermiyor. Islem için (Ctrl+X) tuslarini kullanin.",
PasteErrorCopy	: "Gezgin yaziliminizin güvenlik ayarlari editörün otomatik kopyalama islemine izin vermiyor. Islem için (Ctrl+C) tuslarini kullanin.",

PasteAsText		: "Düz Metin Olarak Yapistir",
PasteFromWord	: "Word'den yapistir",

DlgPasteMsg2	: "Please paste inside the following box using the keyboard (<STRONG>Ctrl+V</STRONG>) and hit <STRONG>OK</STRONG>.",	//MISSING
DlgPasteIgnoreFont		: "Yazı Tipi tanımlarını yoksay",
DlgPasteRemoveStyles	: "Sitil Tanımlarını çıkar",
DlgPasteCleanBox		: "Temizlik Kutusu",

// Color Picker
ColorAutomatic	: "Otomatik",
ColorMoreColors	: "Diger renkler...",

// Document Properties
DocProps		: "Doküman Özellikleri",

// Anchor Dialog
DlgAnchorTitle		: "Çapa Özellikleri",
DlgAnchorName		: "Çapa Ismi",
DlgAnchorErrorName	: "Lütfen çapa için isim giriniz",

// Speller Pages Dialog
DlgSpellNotInDic		: "Sözlükte Yok",
DlgSpellChangeTo		: "Suna degistir:",
DlgSpellBtnIgnore		: "Yoksay",
DlgSpellBtnIgnoreAll	: "Tümünü Yoksay",
DlgSpellBtnReplace		: "Degistir",
DlgSpellBtnReplaceAll	: "Tümünü Degistir",
DlgSpellBtnUndo			: "Geri Al",
DlgSpellNoSuggestions	: "- Öneri Yok -",
DlgSpellProgress		: "Yazim denetimi islemde...",
DlgSpellNoMispell		: "Yazim denetimi tamamlandi: Yanlis yazima raslanmadi",
DlgSpellNoChanges		: "Yazim denetimi tamamlandi: Hiçbir kelime degistirilmedi",
DlgSpellOneChange		: "Yazim denetimi tamamlandi: Bir kelime degistirildi",
DlgSpellManyChanges		: "Yazim denetimi tamamlandi: %1 kelime degistirildi",

IeSpellDownload			: "Yazim denetimi yüklenmemis. Simdi yüklemek ister misiniz?",

// Button Dialog
DlgButtonText	: "Metin (Deger)",
DlgButtonType	: "Tip",

// Checkbox and Radio Button Dialogs
DlgCheckboxName		: "Isim",
DlgCheckboxValue	: "Deger",
DlgCheckboxSelected	: "Seçili",

// Form Dialog
DlgFormName		: "Isim",
DlgFormAction	: "Islem",
DlgFormMethod	: "Metod",

// Select Field Dialog
DlgSelectName		: "Isim",
DlgSelectValue		: "Deger",
DlgSelectSize		: "Boyut",
DlgSelectLines		: "satir",
DlgSelectChkMulti	: "Çoklu seçime izin ver",
DlgSelectOpAvail	: "Mevcut Seçenekler",
DlgSelectOpText		: "Metin",
DlgSelectOpValue	: "Deger",
DlgSelectBtnAdd		: "Ekle",
DlgSelectBtnModify	: "Düzenle",
DlgSelectBtnUp		: "Yukari",
DlgSelectBtnDown	: "Asagi",
DlgSelectBtnSetValue : "Seçili deger olarak ata",
DlgSelectBtnDelete	: "Sil",

// Textarea Dialog
DlgTextareaName	: "Isim",
DlgTextareaCols	: "Sütunlar",
DlgTextareaRows	: "Satirlar",

// Text Field Dialog
DlgTextName			: "Isim",
DlgTextValue		: "Deger",
DlgTextCharWidth	: "Karakter Genisligi",
DlgTextMaxChars		: "En Fazla Karakter",
DlgTextType			: "Tip",
DlgTextTypeText		: "Metin",
DlgTextTypePass		: "Sifre",

// Hidden Field Dialog
DlgHiddenName	: "Isim",
DlgHiddenValue	: "Deger",

// Bulleted List Dialog
BulletedListProp	: "Simgeli Liste Özellikleri",
NumberedListProp	: "Numarali Liste Özellikleri",
DlgLstType			: "Tip",
DlgLstTypeCircle	: "Çember",
DlgLstTypeDisc		: "Disc",	//MISSING
DlgLstTypeSquare	: "Kare",
DlgLstTypeNumbers	: "Sayilar (1, 2, 3)",
DlgLstTypeLCase		: "Küçük Harfler (a, b, c)",
DlgLstTypeUCase		: "Büyük Harfler (A, B, C)",
DlgLstTypeSRoman	: "Küçük Romen Rakamlari (i, ii, iii)",
DlgLstTypeLRoman	: "Büyük Romen Rakamlari (I, II, III)",

// Document Properties Dialog
DlgDocGeneralTab	: "Genel",
DlgDocBackTab		: "Arka Plan",
DlgDocColorsTab		: "Renler ve Mesafeler",
DlgDocMetaTab		: "Tanim Bilgisi (Meta)",

DlgDocPageTitle		: "Sayfa Basligi",
DlgDocLangDir		: "Lisan Yönü",
DlgDocLangDirLTR	: "Soldan Saga (LTR)",
DlgDocLangDirRTL	: "Sagdan Sola (RTL)",
DlgDocLangCode		: "Lisan Kodu",
DlgDocCharSet		: "Karakter Kümesi Kodlamasi",
DlgDocCharSetOther	: "Diger Karakter Kümesi Kodlamasi",

DlgDocDocType		: "Doküman Türü Basligi",
DlgDocDocTypeOther	: "Diger Doküman Türü Basligi",
DlgDocIncXHTML		: "XHTML Bildirimlerini Dahil Et",
DlgDocBgColor		: "Arka Plan Rengi",
DlgDocBgImage		: "Arka Plan Resim URLsi",
DlgDocBgNoScroll	: "Sabit Arka Plan",
DlgDocCText			: "Metin",
DlgDocCLink			: "Köprü",
DlgDocCVisited		: "Görülmüs Köprü",
DlgDocCActive		: "Aktif Köprü",
DlgDocMargins		: "Kenar Bosluklari",
DlgDocMaTop			: "Tepe",
DlgDocMaLeft		: "Sol",
DlgDocMaRight		: "Sag",
DlgDocMaBottom		: "Alt",
DlgDocMeIndex		: "Doküman Indeksleme Anahtar Kelimeleri (virgülle ayrilmis)",
DlgDocMeDescr		: "Doküman Tanimi",
DlgDocMeAuthor		: "Yazar",
DlgDocMeCopy		: "Telif",
DlgDocPreview		: "Ön Izleme",

// Templates Dialog
Templates			: "Düzenler",
DlgTemplatesTitle	: "İçerik Düzenleri",
DlgTemplatesSelMsg	: "Editörde açmak için lütfen bir düzen seçin.<br>(hali hazırdaki içerik kaybolacaktır.):",
DlgTemplatesLoading	: "Düzenler listesi yüklenmekte. Lütfen bekleyiniz...",
DlgTemplatesNoTpl	: "(Belirli bir düzen seçilmedi)",

// About Dialog
DlgAboutAboutTab	: "Hakkinda",
DlgAboutBrowserInfoTab	: "Gezgin Bilgisi",
DlgAboutLicenseTab	: "License",	//MISSING
DlgAboutVersion		: "versiyon",
DlgAboutLicense		: "GNU Kisitli Kamu Lisansi (LGPL) kosullari altinda lisanslanmistir",
DlgAboutInfo		: "Daha fazla bilgi için:"
}