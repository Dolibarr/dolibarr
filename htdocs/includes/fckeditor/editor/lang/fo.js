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
 * File Name: fo.js
 * 	Faroese language file.
 * 
 * File Authors:
 * 		Símin Lassaberg
 * 		Helgi Arnthorsson
 */

var FCKLang =
{
// Language direction : "ltr" (left to right) or "rtl" (right to left).
Dir					: "ltr",

ToolbarCollapse		: "Goym amboðalistan",
ToolbarExpand		: "Vís amboðalistan",

// Toolbar Items and Context Menu
Save				: "Geim",
NewPage				: "Nýggj síða",
Preview				: "Vís dømi",
Cut					: "Klipp",
Copy				: "Avrita",
Paste				: "Set inn",
PasteText			: "Set inn sum reinan tekst",
PasteWord			: "Set inn frá Word",
Print				: "Prenta",
SelectAll			: "Markera alt",
RemoveFormat		: "Sletta sniðgeving",
InsertLinkLbl		: "Leinkja",
InsertLink			: "Seta inn/Broyta Leinkju",
RemoveLink			: "Sletta Leinkju",
Anchor				: "Seta inn/Broyta staðsetingarmerki",
InsertImageLbl		: "Seta inn mynd",
InsertImage			: "Seta inn/Broyta mynd",
InsertFlashLbl		: "Flash",	//MISSING
InsertFlash			: "Insert/Edit Flash",	//MISSING
InsertTableLbl		: "Talva",
InsertTable			: "Seta inn/Broyta talvu",
InsertLineLbl		: "Linja",
InsertLine			: "Seta inn vatnrætta linju",
InsertSpecialCharLbl: "Serlig tekn",
InsertSpecialChar	: "Seta inn serligt tekn",
InsertSmileyLbl		: "Smiley",
InsertSmiley		: "Seta inn Smiley",
About				: "Um FCKeditor",
Bold				: "Feit",
Italic				: "Skástillað",
Underline			: "Undirstrikað",
StrikeThrough		: "Strikað yvir",
Subscript			: "Lækkað skrift",
Superscript			: "Hækkað skrift",
LeftJustify			: "Vinstristillað",
CenterJustify		: "Miðstillað",
RightJustify		: "Hægristillað",
BlockJustify		: "Beinir tekstkantar",
DecreaseIndent		: "Økja innrykk",
IncreaseIndent		: "Minka innrykk",
Undo				: "Angra",
Redo				: "Broyt aftur í upprunamynd",
NumberedListLbl		: "Talsettur listi",
NumberedList		: "Seta inn/Sletta talsettan lista",
BulletedListLbl		: "Punktsettur listi",
BulletedList		: "Seta inn/Sletta punktsettan lista",
ShowTableBorders	: "Vísa talvukantar ",
ShowDetails			: "Vísa detaljur",
Style				: "Tekstsnið",
FontFormat			: "Sniðgeving",
Font				: "Skrift",
FontSize			: "Skriftstødd",
TextColor			: "Tekstlitur",
BGColor				: "Litur aftanfyri",
Source				: "Kelda",
Find				: "Leita",
Replace				: "Set í staðin",
SpellCheck			: "Stavseting",
UniversalKeyboard	: "Universalt Tastatur",
PageBreakLbl		: "Page Break",	//MISSING
PageBreak			: "Insert Page Break",	//MISSING

Form			: "Seta inn Form",
Checkbox		: "Seta inn Avmerkingarboks",
RadioButton		: "Seta inn Radioknap",
TextField		: "Seta inn Tekstteig",
Textarea		: "Seta inn Tekstøki",
HiddenField		: "Seta inn GoymdanTeig",
Button			: "Seta inn knapp",
SelectionField	: "Seta inn Valteig",
ImageButton		: "Seta inn Myndaknapp",

FitWindow		: "Maximize the editor size",	//MISSING

// Context Menu
EditLink			: "Broyt leinkju",
CellCM				: "Cell",	//MISSING
RowCM				: "Row",	//MISSING
ColumnCM			: "Column",	//MISSING
InsertRow			: "Seta inn rekkju",
DeleteRows			: "Sletta rekkjur",
InsertColumn		: "Seta inn søjlur",
DeleteColumns		: "Sletta søjlur",
InsertCell			: "Seta inn sellu",
DeleteCells			: "Sletta sellu",
MergeCells			: "Flætta sellur",
SplitCell			: "Deila sellur",
TableDelete			: "Delete Table",	//MISSING
CellProperties		: "Eginleikar fyri sellu",
TableProperties		: "Eginleikar fyri talvu",
ImageProperties		: "Eginleikar fyri mynd",
FlashProperties		: "Flash Properties",	//MISSING

AnchorProp			: "Eginleikar fyri staðsetingarpunkt",
ButtonProp			: "Eginleikar fyri knapp",
CheckboxProp		: "Eginleikar fyri avmerkingarboks",
HiddenFieldProp		: "Eginleikar fyri goymdan teig",
RadioButtonProp		: "Eginleikar fyri radioknapp",
ImageButtonProp		: "Eginleikar fyri myndaknapp",
TextFieldProp		: "Eginleikar fyri Tekstateig",
SelectionFieldProp	: "Eginleikar fyri Valteig",
TextareaProp		: "Eginleikar fyri Tekstaøki",
FormProp			: "Eginleikar fyri form",

FontFormats			: "Normalt;Sniðgevið;Adressa;Yvirskrift 1;Yvirskrift 2;Yvirskrift 3;Yvirskrift 4;Yvirskrift 5;Yvirskrift 6",

// Alerts and Messages
ProcessingXHTML		: "Viðgerir XHTML. Bíða...",
Done				: "Liðugt",
PasteWordConfirm	: "Teksturin, tú roynir at seta inn, sýnist at vera frá Word. Vilt tú reinsa tekstin, áðrenn hann verður settur inn?",
NotCompatiblePaste	: "Hesin ordri er tøkur í Internet Explorer 5.5 og nýggjari. Vilt tú seta tekstin inn, uttan at reinsa hann?",
UnknownToolbarItem	: "Ókendur lutur í amboðalinju \"%1\"",
UnknownCommand		: "Kenni ikki ordra \"%1\"",
NotImplemented		: "Ordrin er ikki gjørdur virkin",
UnknownToolbarSet	: "Amboðalinjan \"%1\" finst ikki",
NoActiveX			: "Your browser's security settings could limit some features of the editor. You must enable the option \"Run ActiveX controls and plug-ins\". You may experience errors and notice missing features.",	//MISSING
BrowseServerBlocked : "The resources browser could not be opened. Make sure that all popup blockers are disabled.",	//MISSING
DialogBlocked		: "It was not possible to open the dialog window. Make sure all popup blockers are disabled.",	//MISSING

// Dialogs
DlgBtnOK			: "OK",
DlgBtnCancel		: "Angra",
DlgBtnClose			: "Lukka",
DlgBtnBrowseServer	: "Hyggja á servara",
DlgAdvancedTag		: "Útvíðka",
DlgOpOther			: "<Annað>",
DlgInfoTab			: "Info",	//MISSING
DlgAlertUrl			: "Please insert the URL",	//MISSING

// General Dialogs Labels
DlgGenNotSet		: "<ikki sett>",
DlgGenId			: "Id",
DlgGenLangDir		: "Tekstakós",
DlgGenLangDirLtr	: "Vinstri móti høgri (LTR)",
DlgGenLangDirRtl	: "Høgri móti vinstri (RTL)",
DlgGenLangCode		: "Málkoda",
DlgGenAccessKey		: "Atgongdslykil",
DlgGenName			: "Navn",
DlgGenTabIndex		: "Tabulator Indeks",
DlgGenLongDescr		: "víðka frágreiðing",
DlgGenClass			: "Typografiark",
DlgGenTitle			: "Heiti",
DlgGenContType		: "Innihaldsslag",
DlgGenLinkCharset	: "Teknset",
DlgGenStyle			: "Prentlist",

// Image Dialog
DlgImgTitle			: "Mynd eginleikar",
DlgImgInfoTab		: "Mynd info",
DlgImgBtnUpload		: "Send til serveren",
DlgImgURL			: "URL",
DlgImgUpload		: "Upload",
DlgImgAlt			: "Annar tekstur",
DlgImgWidth			: "Breidd",
DlgImgHeight		: "Hædd",
DlgImgLockRatio		: "Læs støddarlutfall",
DlgBtnResetSize		: "Nulstilla stødd",
DlgImgBorder		: "Ramma",
DlgImgHSpace		: "HMargin",
DlgImgVSpace		: "VMargin",
DlgImgAlign			: "Justering",
DlgImgAlignLeft		: "Vinstra",
DlgImgAlignAbsBottom: "Abs botnur",
DlgImgAlignAbsMiddle: "Abs Miðja",
DlgImgAlignBaseline	: "Botnlinja",
DlgImgAlignBottom	: "Botnur",
DlgImgAlignMiddle	: "Miðja",
DlgImgAlignRight	: "Høgra",
DlgImgAlignTextTop	: "Tekstur ovast",
DlgImgAlignTop		: "Ovast",
DlgImgPreview		: "Vís dømi",
DlgImgAlertUrl		: "Slá inn slóðina til myndina",
DlgImgLinkTab		: "Leinkja",

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
DlgLnkWindowTitle	: "Leinkja",
DlgLnkInfoTab		: "Leinkju info",
DlgLnkTargetTab		: "Mál",

DlgLnkType			: "Leinkju slag",
DlgLnkTypeURL		: "URL",
DlgLnkTypeAnchor	: "Akker á hesari síðuni",
DlgLnkTypeEMail		: "Teldupostur",
DlgLnkProto			: "Protokoll",
DlgLnkProtoOther	: "<onnur>",
DlgLnkURL			: "URL",
DlgLnkAnchorSel		: "VEl eitt akker",
DlgLnkAnchorByName	: "Eftir akker navni",
DlgLnkAnchorById	: "Eftir element Id",
DlgLnkNoAnchors		: "<Tað eru ongi akker tøk í hesum dokumentinum;",
DlgLnkEMail			: "Teldupost Adresse",
DlgLnkEMailSubject	: "Evni",
DlgLnkEMailBody		: "Boð",
DlgLnkUpload		: "Upload",
DlgLnkBtnUpload		: "Send til servaran",

DlgLnkTarget		: "Mál",
DlgLnkTargetFrame	: "<ramma>",
DlgLnkTargetPopup	: "<popup vindeyga>",
DlgLnkTargetBlank	: "Nytt vindeyga (_blank)",
DlgLnkTargetParent	: "Omaná liggjandi vindeyga (_parent)",
DlgLnkTargetSelf	: "Sama vindeyga (_self)",
DlgLnkTargetTop		: "ovasta vindeyga (_top)",
DlgLnkTargetFrameName	: "vísa vindeygas navn",
DlgLnkPopWinName	: "Popup vindeygas navn",
DlgLnkPopWinFeat	: "Popup vindeygas eginleikar",
DlgLnkPopResize		: "Skalering",
DlgLnkPopLocation	: "Lokationslinja",
DlgLnkPopMenu		: "Menulinja",
DlgLnkPopScroll		: "Scrollbars",
DlgLnkPopStatus		: "Statuslinja",
DlgLnkPopToolbar	: "Værktøjslinja",
DlgLnkPopFullScrn	: "Fullur skermur (IE)",
DlgLnkPopDependent	: "Bundin (Netscape)",
DlgLnkPopWidth		: "Breidd",
DlgLnkPopHeight		: "Hædd",
DlgLnkPopLeft		: "Positión frá vinstru",
DlgLnkPopTop		: "Positión frá toppinum",

DlnLnkMsgNoUrl		: "Inntasta leinkju URL",
DlnLnkMsgNoEMail	: "Inntasta teldupost addressuna",
DlnLnkMsgNoAnchor	: "Vel akker",

// Color Dialog
DlgColorTitle		: "vel farvu",
DlgColorBtnClear	: "sletta alt",
DlgColorHighlight	: "Markera",
DlgColorSelected	: "valt",

// Smiley Dialog
DlgSmileyTitle		: "Innset ein smiley",

// Special Character Dialog
DlgSpecialCharTitle	: "vel specialkarakter",

// Table Dialog
DlgTableTitle		: "Tabel eginleikar",
DlgTableRows		: "Rekkjur",
DlgTableColumns		: "Kolonnur",
DlgTableBorder		: "Rammu stødd",
DlgTableAlign		: "Justering",
DlgTableAlignNotSet	: "<Ikki sett>",
DlgTableAlignLeft	: "Vinstrastilla",
DlgTableAlignCenter	: "Miðseta",
DlgTableAlignRight	: "Høgrastilla",
DlgTableWidth		: "Breidd",
DlgTableWidthPx		: "pixels",
DlgTableWidthPc		: "prosent",
DlgTableHeight		: "Hædd",
DlgTableCellSpace	: "Fjarstøða millum sellur",
DlgTableCellPad		: "Sellu breddi",
DlgTableCaption		: "Heiti",
DlgTableSummary		: "Summary",	//MISSING

// Table Cell Dialog
DlgCellTitle		: "Sellu eginleikar",
DlgCellWidth		: "Breidd",
DlgCellWidthPx		: "pixels",
DlgCellWidthPc		: "prosent",
DlgCellHeight		: "Hædd",
DlgCellWordWrap		: "Orðbýti",
DlgCellWordWrapNotSet	: "<Ikki sett>",
DlgCellWordWrapYes	: "Ja",
DlgCellWordWrapNo	: "Nej",
DlgCellHorAlign		: "Horisontal justering",
DlgCellHorAlignNotSet	: "<Ikke sat>",
DlgCellHorAlignLeft	: "Vinstrastilla",
DlgCellHorAlignCenter	: "Miðsett",
DlgCellHorAlignRight: "Høgrastilla",
DlgCellVerAlign		: "Lodrøtt Justering",
DlgCellVerAlignNotSet	: "<Ikki sett>",
DlgCellVerAlignTop	: "Ovast",
DlgCellVerAlignMiddle	: "Miðja",
DlgCellVerAlignBottom	: "Niðast",
DlgCellVerAlignBaseline	: "Botnlinja",
DlgCellRowSpan		: "Tal av rekkjum sellan spennur yvir",
DlgCellCollSpan		: "Tal av talrøðum sellan spennur yvir",
DlgCellBackColor	: "Bakgrundsfarva",
DlgCellBorderColor	: "rammufarva",
DlgCellBtnSelect	: "Vel...",

// Find Dialog
DlgFindTitle		: "Finn",
DlgFindFindBtn		: "Finn",
DlgFindNotFoundMsg	: "Teksturin bleiv ikki funnin",

// Replace Dialog
DlgReplaceTitle			: "Set í staðin",
DlgReplaceFindLbl		: "Finn:",
DlgReplaceReplaceLbl	: "Set í staðin við:",
DlgReplaceCaseChk		: "Munur á stórum og smáðum stavum",
DlgReplaceReplaceBtn	: "Set í staðin",
DlgReplaceReplAllBtn	: "Skift alt út",
DlgReplaceWordChk		: "Bert heil orð",

// Paste Operations / Dialog
PasteErrorPaste	: "Leitarans trygdarinstillingar loyva ikki editorinum at innseta tekstin automatiskt. Brúka knappaborðið til at innseta tekstin (Ctrl+V).",
PasteErrorCut	: "Leitarans trygdarinstillingar loyva ikki editorinum at klippa tekstin automatiskt. Brúka í staðin knappaborðið til at klippa tekstin (Ctrl+X).",
PasteErrorCopy	: "Leitarans trygdarinstillingar loyva ikki editorinum at avrita tekstin automatiskt. Brúka í staðin knappaborðið til at avrita tekstin (Ctrl+V).",

PasteAsText		: "Seta inn som reinur tekstur",
PasteFromWord	: "Seta inn fra Word",

DlgPasteMsg2	: "Please paste inside the following box using the keyboard (<STRONG>Ctrl+V</STRONG>) and hit <STRONG>OK</STRONG>.",	//MISSING
DlgPasteIgnoreFont		: "Ignore Font Face definitions",	//MISSING
DlgPasteRemoveStyles	: "Remove Styles definitions",	//MISSING
DlgPasteCleanBox		: "Clean Up Box",	//MISSING

// Color Picker
ColorAutomatic	: "Automatisk",
ColorMoreColors	: "Fleiri farvur...",

// Document Properties
DocProps		: "Dokument eginleikar",

// Anchor Dialog
DlgAnchorTitle		: "Akker eginleikar",
DlgAnchorName		: "Akker navn",
DlgAnchorErrorName	: "Slá innn akker navn",

// Speller Pages Dialog
DlgSpellNotInDic		: "Finnst ikki í orðabókini",
DlgSpellChangeTo		: "broyta til",
DlgSpellBtnIgnore		: "Ignorera",
DlgSpellBtnIgnoreAll	: "Ignorera alt",
DlgSpellBtnReplace		: "Skift út",
DlgSpellBtnReplaceAll	: "Skift út alt",
DlgSpellBtnUndo			: "Aftur",
DlgSpellNoSuggestions	: "- Einki uppskot -",
DlgSpellProgress		: "Stavarin arbeiðir...",
DlgSpellNoMispell		: "Stavarain liðugur: Eingin feilur funnin",
DlgSpellNoChanges		: "Stavarain liðugur: Einki orð broytt",
DlgSpellOneChange		: "Stavarain liðugur: Eitt orð broytt",
DlgSpellManyChanges		: "Stavarain liðugur: %1 orð broytt",

IeSpellDownload			: "Stavarin ikki lagdur inn. vilt tú heinta hann nú?",

// Button Dialog
DlgButtonText	: "Tekstur (Virði)",
DlgButtonType	: "Slag",

// Checkbox and Radio Button Dialogs
DlgCheckboxName		: "Navn",
DlgCheckboxValue	: "Virði",
DlgCheckboxSelected	: "Valgt",

// Form Dialog
DlgFormName		: "Navn",
DlgFormAction	: "Gerð",
DlgFormMethod	: "Háttur",

// Select Field Dialog
DlgSelectName		: "Navn",
DlgSelectValue		: "Virði",
DlgSelectSize		: "Stødd",
DlgSelectLines		: "linjir",
DlgSelectChkMulti	: "Loyv fleiri valmøguleikar",
DlgSelectOpAvail	: "valmøguleikar",
DlgSelectOpText		: "Tekstur",
DlgSelectOpValue	: "Virði",
DlgSelectBtnAdd		: "Legg afturat",
DlgSelectBtnModify	: "Broyt",
DlgSelectBtnUp		: "Upp",
DlgSelectBtnDown	: "Niður",
DlgSelectBtnSetValue : "Set sum útvald",
DlgSelectBtnDelete	: "Sletta",

// Textarea Dialog
DlgTextareaName	: "Navn",
DlgTextareaCols	: "talrøð",
DlgTextareaRows	: "Rekkja",

// Text Field Dialog
DlgTextName			: "Navn",
DlgTextValue		: "Virði",
DlgTextCharWidth	: "Sjónligt tal av bókstavum",
DlgTextMaxChars		: "Hægst loyvda tal av bókstavum",
DlgTextType			: "Slag",
DlgTextTypeText		: "Tekstur",
DlgTextTypePass		: "Koduorð",

// Hidden Field Dialog
DlgHiddenName	: "Navn",
DlgHiddenValue	: "Virði",

// Bulleted List Dialog
BulletedListProp	: "Punktteknsuppsetingar eginleikar",
NumberedListProp	: "Taluppsetingar eginleikar",
DlgLstType			: "Slag",
DlgLstTypeCircle	: "Sirkul",
DlgLstTypeDisc		: "Disc",	//MISSING
DlgLstTypeSquare	: "Fýrakantur",
DlgLstTypeNumbers	: "Talmerkt (1, 2, 3)",
DlgLstTypeLCase		: "Smáir bókstavir (a, b, c)",
DlgLstTypeUCase		: "Stórir bókstavir (A, B, C)",
DlgLstTypeSRoman	: "Smá rómaratøl (i, ii, iii)",
DlgLstTypeLRoman	: "Stór rómaratøl (I, II, III)",

// Document Properties Dialog
DlgDocGeneralTab	: "Generelt",
DlgDocBackTab		: "Bakgrund",
DlgDocColorsTab		: "Farva og Breddin",
DlgDocMetaTab		: "Meta Information",

DlgDocPageTitle		: "Síðu heiti",
DlgDocLangDir		: "Mál",
DlgDocLangDirLTR	: "Frá vinstru móti høgru (LTR)",
DlgDocLangDirRTL	: "Frá høgru móti vinstru (RTL)",
DlgDocLangCode		: "Landakoda",
DlgDocCharSet		: "Karakter set kodu",
DlgDocCharSetOther	: "Annar karakter set kodu",

DlgDocDocType		: "Dokument slag kategori",
DlgDocDocTypeOther	: "Annað dokument slag kategori",
DlgDocIncXHTML		: "Inkludere XHTML deklartion",
DlgDocBgColor		: "Bakgrundsfarva",
DlgDocBgImage		: "Bakgrundsmynd URL",
DlgDocBgNoScroll	: "Ikki scrollbar bakgrund",
DlgDocCText			: "Tekstur",
DlgDocCLink			: "Leinkja",
DlgDocCVisited		: "Vitja leinkja",
DlgDocCActive		: "Aktiv leinkja",
DlgDocMargins		: "Síðu breddi",
DlgDocMaTop			: "Ovast",
DlgDocMaLeft		: "Vinstra",
DlgDocMaRight		: "Høgra",
DlgDocMaBottom		: "Niðast",
DlgDocMeIndex		: "Dokument index lyklaorð (komma sundurskilt)",
DlgDocMeDescr		: "Dokument lýsing",
DlgDocMeAuthor		: "Høvundur",
DlgDocMeCopy		: "Copyright",
DlgDocPreview		: "Vís",

// Templates Dialog
Templates			: "Frymlar",
DlgTemplatesTitle	: "Innihaldsfrymlar",
DlgTemplatesSelMsg	: "Vel tann frymilin, sum skal opnast í editorinum<br>(Tað verður skriva útyvir núverandi innihald):",
DlgTemplatesLoading	: "Heintar lista yvir frymlar. Vinarliga bíða...",
DlgTemplatesNoTpl	: "(Ongin frymil er valdur)",

// About Dialog
DlgAboutAboutTab	: "Um",
DlgAboutBrowserInfoTab	: "Browsara upplýsingar",
DlgAboutLicenseTab	: "License",	//MISSING
DlgAboutVersion		: "versión",
DlgAboutLicense		: "Loyvi undir treytum fyri GNU Lesser General Public License",
DlgAboutInfo		: "Fleiri upplýsingar, far til"
}