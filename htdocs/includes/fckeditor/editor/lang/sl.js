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
 * File Name: sl.js
 * 	Slovenian language file.
 * 
 * File Authors:
 * 		Boris Volarič (vol@rutka.net)
 */

var FCKLang =
{
// Language direction : "ltr" (left to right) or "rtl" (right to left).
Dir					: "ltr",

ToolbarCollapse		: "Zloži orodno vrstico",
ToolbarExpand		: "Razširi orodno vrstico",

// Toolbar Items and Context Menu
Save				: "Shrani",
NewPage				: "Nova stran",
Preview				: "Predogled",
Cut					: "Izreži",
Copy				: "Kopiraj",
Paste				: "Prilepi",
PasteText			: "Prilepi kot golo besedilo",
PasteWord			: "Prilepi iz Worda",
Print				: "Natisni",
SelectAll			: "Izberi vse",
RemoveFormat		: "Odstrani oblikovanje",
InsertLinkLbl		: "Povezava",
InsertLink			: "Vstavi/uredi povezavo",
RemoveLink			: "Odstrani povezavo",
Anchor				: "Vstavi/uredi zaznamek",
InsertImageLbl		: "Slika",
InsertImage			: "Vstavi/uredi sliko",
InsertFlashLbl		: "Flash",
InsertFlash			: "Vstavi/Uredi Flash",
InsertTableLbl		: "Tabela",
InsertTable			: "Vstavi/uredi tabelo",
InsertLineLbl		: "Črta",
InsertLine			: "Vstavi vodoravno črto",
InsertSpecialCharLbl: "Posebni znak",
InsertSpecialChar	: "Vstavi posebni znak",
InsertSmileyLbl		: "Smeško",
InsertSmiley		: "Vstavi smeška",
About				: "O FCKeditorju",
Bold				: "Krepko",
Italic				: "Ležeče",
Underline			: "Podčrtano",
StrikeThrough		: "Prečrtano",
Subscript			: "Podpisano",
Superscript			: "Nadpisano",
LeftJustify			: "Leva poravnava",
CenterJustify		: "Sredinska poravnava",
RightJustify		: "Desna poravnava",
BlockJustify		: "Obojestranska poravnava",
DecreaseIndent		: "Zmanjšaj zamik",
IncreaseIndent		: "Povečaj zamik",
Undo				: "Razveljavi",
Redo				: "Ponovi",
NumberedListLbl		: "Oštevilčen seznam",
NumberedList		: "Vstavi/odstrani oštevilčevanje",
BulletedListLbl		: "Označen seznam",
BulletedList		: "Vstavi/odstrani označevanje",
ShowTableBorders	: "Pokaži meje tabele",
ShowDetails			: "Pokaži podrobnosti",
Style				: "Slog",
FontFormat			: "Oblika",
Font				: "Pisava",
FontSize			: "Velikost",
TextColor			: "Barva besedila",
BGColor				: "Barva ozadja",
Source				: "Izvorna koda",
Find				: "Najdi",
Replace				: "Zamenjaj",
SpellCheck			: "Preveri črkovanje",
UniversalKeyboard	: "Večjezična tipkovnica",
PageBreakLbl		: "Prelom strani",
PageBreak			: "Vstavi prelom strani",

Form			: "Obrazec",
Checkbox		: "Potrditveno polje",
RadioButton		: "Izbirno polje",
TextField		: "Vnosno polje",
Textarea		: "Vnosno območje",
HiddenField		: "Skrito polje",
Button			: "Gumb",
SelectionField	: "Spustni seznam",
ImageButton		: "Gumb s sliko",

FitWindow		: "Maximize the editor size",	//MISSING

// Context Menu
EditLink			: "Uredi povezavo",
CellCM				: "Cell",	//MISSING
RowCM				: "Row",	//MISSING
ColumnCM			: "Column",	//MISSING
InsertRow			: "Vstavi vrstico",
DeleteRows			: "Izbriši vrstice",
InsertColumn		: "Vstavi stolpec",
DeleteColumns		: "Izbriši stolpce",
InsertCell			: "Vstavi celico",
DeleteCells			: "Izbriši celice",
MergeCells			: "Združi celice",
SplitCell			: "Razdeli celico",
TableDelete			: "Izbriši tabelo",
CellProperties		: "Lastnosti celice",
TableProperties		: "Lastnosti tabele",
ImageProperties		: "Lastnosti slike",
FlashProperties		: "Lastnosti Flash",

AnchorProp			: "Lastnosti zaznamka",
ButtonProp			: "Lastnosti gumba",
CheckboxProp		: "Lastnosti potrditvenega polja",
HiddenFieldProp		: "Lastnosti skritega polja",
RadioButtonProp		: "Lastnosti izbirnega polja",
ImageButtonProp		: "Lastnosti gumba s sliko",
TextFieldProp		: "Lastnosti vnosnega polja",
SelectionFieldProp	: "Lastnosti spustnega seznama",
TextareaProp		: "Lastnosti vnosnega območja",
FormProp			: "Lastnosti obrazca",

FontFormats			: "Navaden;Oblikovan;Napis;Naslov 1;Naslov 2;Naslov 3;Naslov 4;Naslov 5;Naslov 6",

// Alerts and Messages
ProcessingXHTML		: "Obdelujem XHTML. Prosim počakajte...",
Done				: "Narejeno",
PasteWordConfirm	: "Izgleda, da želite prilepiti besedilo iz Worda. Ali ga želite očistiti, preden ga prilepite?",
NotCompatiblePaste	: "Ta ukaz deluje le v Internet Explorerje različice 5.5 ali višje. Ali želite prilepiti brez čiščenja?",
UnknownToolbarItem	: "Neznan element orodne vrstice \"%1\"",
UnknownCommand		: "Neznano ime ukaza \"%1\"",
NotImplemented		: "Ukaz ni izdelan",
UnknownToolbarSet	: "Skupina orodnih vrstic \"%1\" ne obstoja",
NoActiveX			: "Your browser's security settings could limit some features of the editor. You must enable the option \"Run ActiveX controls and plug-ins\". You may experience errors and notice missing features.",	//MISSING
BrowseServerBlocked : "The resources browser could not be opened. Make sure that all popup blockers are disabled.",	//MISSING
DialogBlocked		: "It was not possible to open the dialog window. Make sure all popup blockers are disabled.",	//MISSING

// Dialogs
DlgBtnOK			: "V redu",
DlgBtnCancel		: "Prekliči",
DlgBtnClose			: "Zapri",
DlgBtnBrowseServer	: "Prebrskaj na strežniku",
DlgAdvancedTag		: "Napredno",
DlgOpOther			: "<Ostalo>",
DlgInfoTab			: "Podatki",
DlgAlertUrl			: "Prosim vpiši spletni naslov",

// General Dialogs Labels
DlgGenNotSet		: "<ni postavljen>",
DlgGenId			: "Id",
DlgGenLangDir		: "Smer jezika",
DlgGenLangDirLtr	: "Od leve proti desni (LTR)",
DlgGenLangDirRtl	: "Od desne proti levi (RTL)",
DlgGenLangCode		: "Oznaka jezika",
DlgGenAccessKey		: "Vstopno geslo",
DlgGenName			: "Ime",
DlgGenTabIndex		: "Številka tabulatorja",
DlgGenLongDescr		: "Dolg opis URL-ja",
DlgGenClass			: "Razred stilne predloge",
DlgGenTitle			: "Predlagani naslov",
DlgGenContType		: "Predlagani tip vsebine (content-type)",
DlgGenLinkCharset	: "Kodna tabela povezanega vira",
DlgGenStyle			: "Slog",

// Image Dialog
DlgImgTitle			: "Lastnosti slike",
DlgImgInfoTab		: "Podatki o sliki",
DlgImgBtnUpload		: "Pošlji na strežnik",
DlgImgURL			: "URL",
DlgImgUpload		: "Pošlji",
DlgImgAlt			: "Nadomestno besedilo",
DlgImgWidth			: "Širina",
DlgImgHeight		: "Višina",
DlgImgLockRatio		: "Zakleni razmerje",
DlgBtnResetSize		: "Ponastavi velikost",
DlgImgBorder		: "Obroba",
DlgImgHSpace		: "Vodoravni razmik",
DlgImgVSpace		: "Navpični razmik",
DlgImgAlign			: "Poravnava",
DlgImgAlignLeft		: "Levo",
DlgImgAlignAbsBottom: "Popolnoma na dno",
DlgImgAlignAbsMiddle: "Popolnoma v sredino",
DlgImgAlignBaseline	: "Na osnovno črto",
DlgImgAlignBottom	: "Na dno",
DlgImgAlignMiddle	: "V sredino",
DlgImgAlignRight	: "Desno",
DlgImgAlignTextTop	: "Besedilo na vrh",
DlgImgAlignTop		: "Na vrh",
DlgImgPreview		: "Predogled",
DlgImgAlertUrl		: "Vnesite URL slike",
DlgImgLinkTab		: "Povezava",

// Flash Dialog
DlgFlashTitle		: "Lastnosti Flash",
DlgFlashChkPlay		: "Samodejno predvajaj",
DlgFlashChkLoop		: "Ponavljanje",
DlgFlashChkMenu		: "Omogoči Flash Meni",
DlgFlashScale		: "Povečava",
DlgFlashScaleAll	: "Pokaži vse",
DlgFlashScaleNoBorder	: "Brez obrobe",
DlgFlashScaleFit	: "Natančno prileganje",

// Link Dialog
DlgLnkWindowTitle	: "Povezava",
DlgLnkInfoTab		: "Podatki o povezavi",
DlgLnkTargetTab		: "Cilj",

DlgLnkType			: "Vrsta povezave",
DlgLnkTypeURL		: "URL",
DlgLnkTypeAnchor	: "Zaznamek na tej strani",
DlgLnkTypeEMail		: "Elektronski naslov",
DlgLnkProto			: "Protokol",
DlgLnkProtoOther	: "<drugo>",
DlgLnkURL			: "URL",
DlgLnkAnchorSel		: "Izberi zaznamek",
DlgLnkAnchorByName	: "Po imenu zaznamka",
DlgLnkAnchorById	: "Po ID-ju elementa",
DlgLnkNoAnchors		: "<V tem dokumentu ni zaznamkov>",
DlgLnkEMail			: "Elektronski naslov",
DlgLnkEMailSubject	: "Predmet sporočila",
DlgLnkEMailBody		: "Vsebina sporočila",
DlgLnkUpload		: "Prenesi",
DlgLnkBtnUpload		: "Pošlji na strežnik",

DlgLnkTarget		: "Cilj",
DlgLnkTargetFrame	: "<okvir>",
DlgLnkTargetPopup	: "<pojavno okno>",
DlgLnkTargetBlank	: "Novo okno (_blank)",
DlgLnkTargetParent	: "Starševsko okno (_parent)",
DlgLnkTargetSelf	: "Isto okno (_self)",
DlgLnkTargetTop		: "Najvišje okno (_top)",
DlgLnkTargetFrameName	: "Ime ciljnega okvirja",
DlgLnkPopWinName	: "Ime pojavnega okna",
DlgLnkPopWinFeat	: "Značilnosti pojavnega okna",
DlgLnkPopResize		: "Spremenljive velikosti",
DlgLnkPopLocation	: "Naslovna vrstica",
DlgLnkPopMenu		: "Menijska vrstica",
DlgLnkPopScroll		: "Drsniki",
DlgLnkPopStatus		: "Vrstica stanja",
DlgLnkPopToolbar	: "Orodna vrstica",
DlgLnkPopFullScrn	: "Celozaslonska slika (IE)",
DlgLnkPopDependent	: "Podokno (Netscape)",
DlgLnkPopWidth		: "Širina",
DlgLnkPopHeight		: "Višina",
DlgLnkPopLeft		: "Lega levo",
DlgLnkPopTop		: "Lega na vrhu",

DlnLnkMsgNoUrl		: "Vnesite URL povezave",
DlnLnkMsgNoEMail	: "Vnesite elektronski naslov",
DlnLnkMsgNoAnchor	: "Izberite zaznamek",

// Color Dialog
DlgColorTitle		: "Izberite barvo",
DlgColorBtnClear	: "Počisti",
DlgColorHighlight	: "Označi",
DlgColorSelected	: "Izbrano",

// Smiley Dialog
DlgSmileyTitle		: "Vstavi smeška",

// Special Character Dialog
DlgSpecialCharTitle	: "Izberi posebni znak",

// Table Dialog
DlgTableTitle		: "Lastnosti tabele",
DlgTableRows		: "Vrstice",
DlgTableColumns		: "Stolpci",
DlgTableBorder		: "Velikost obrobe",
DlgTableAlign		: "Poravnava",
DlgTableAlignNotSet	: "<Ni nastavljeno>",
DlgTableAlignLeft	: "Levo",
DlgTableAlignCenter	: "Sredinsko",
DlgTableAlignRight	: "Desno",
DlgTableWidth		: "Širina",
DlgTableWidthPx		: "pik",
DlgTableWidthPc		: "procentov",
DlgTableHeight		: "Višina",
DlgTableCellSpace	: "Razmik med celicami",
DlgTableCellPad		: "Polnilo med celicami",
DlgTableCaption		: "Naslov",
DlgTableSummary		: "Povzetek",

// Table Cell Dialog
DlgCellTitle		: "Lastnosti celice",
DlgCellWidth		: "Širina",
DlgCellWidthPx		: "pik",
DlgCellWidthPc		: "procentov",
DlgCellHeight		: "Višina",
DlgCellWordWrap		: "Pomikanje besedila",
DlgCellWordWrapNotSet	: "<Ni nastavljeno>",
DlgCellWordWrapYes	: "Da",
DlgCellWordWrapNo	: "Ne",
DlgCellHorAlign		: "Vodoravna poravnava",
DlgCellHorAlignNotSet	: "<Ni nastavljeno>",
DlgCellHorAlignLeft	: "Levo",
DlgCellHorAlignCenter	: "Sredinsko",
DlgCellHorAlignRight: "Desno",
DlgCellVerAlign		: "Navpična poravnava",
DlgCellVerAlignNotSet	: "<Ni nastavljeno>",
DlgCellVerAlignTop	: "Na vrh",
DlgCellVerAlignMiddle	: "V sredino",
DlgCellVerAlignBottom	: "Na dno",
DlgCellVerAlignBaseline	: "Na osnovno črto",
DlgCellRowSpan		: "Spojenih vrstic (row-span)",
DlgCellCollSpan		: "Spojenih stolpcev (col-span)",
DlgCellBackColor	: "Barva ozadja",
DlgCellBorderColor	: "Barva obrobe",
DlgCellBtnSelect	: "Izberi...",

// Find Dialog
DlgFindTitle		: "Najdi",
DlgFindFindBtn		: "Najdi",
DlgFindNotFoundMsg	: "Navedeno besedilo ni bilo najdeno.",

// Replace Dialog
DlgReplaceTitle			: "Zamenjaj",
DlgReplaceFindLbl		: "Najdi:",
DlgReplaceReplaceLbl	: "Zamenjaj z:",
DlgReplaceCaseChk		: "Razlikuj velike in male črke",
DlgReplaceReplaceBtn	: "Zamenjaj",
DlgReplaceReplAllBtn	: "Zamenjaj vse",
DlgReplaceWordChk		: "Samo cele besede",

// Paste Operations / Dialog
PasteErrorPaste	: "Varnostne nastavitve brskalnika ne dopuščajo samodejnega lepljenja. Uporabite kombinacijo tipk na tipkovnici (Ctrl+V).",
PasteErrorCut	: "Varnostne nastavitve brskalnika ne dopuščajo samodejnega izrezovanja. Uporabite kombinacijo tipk na tipkovnici (Ctrl+X).",
PasteErrorCopy	: "Varnostne nastavitve brskalnika ne dopuščajo samodejnega kopiranja. Uporabite kombinacijo tipk na tipkovnici (Ctrl+C).",

PasteAsText		: "Prilepi kot golo besedilo",
PasteFromWord	: "Prilepi iz Worda",

DlgPasteMsg2	: "Prosim prilepite v sleči okvir s pomočjo tipkovnice (<STRONG>Ctrl+V</STRONG>) in pritisnite <STRONG>V redu</STRONG>.",
DlgPasteIgnoreFont		: "Prezri obliko pisave",
DlgPasteRemoveStyles	: "Odstrani nastavitve stila",
DlgPasteCleanBox		: "Počisti okvir",

// Color Picker
ColorAutomatic	: "Samodejno",
ColorMoreColors	: "Več barv...",

// Document Properties
DocProps		: "Lastnosti dokumenta",

// Anchor Dialog
DlgAnchorTitle		: "Lastnosti zaznamka",
DlgAnchorName		: "Ime zaznamka",
DlgAnchorErrorName	: "Prosim vnesite ime zaznamka",

// Speller Pages Dialog
DlgSpellNotInDic		: "Ni v slovarju",
DlgSpellChangeTo		: "Spremeni v",
DlgSpellBtnIgnore		: "Prezri",
DlgSpellBtnIgnoreAll	: "Prezri vse",
DlgSpellBtnReplace		: "Zamenjaj",
DlgSpellBtnReplaceAll	: "Zamenjaj vse",
DlgSpellBtnUndo			: "Razveljavi",
DlgSpellNoSuggestions	: "- Ni predlogov -",
DlgSpellProgress		: "Preverjanje črkovanja se izvaja...",
DlgSpellNoMispell		: "Črkovanje je končano: Brez napak",
DlgSpellNoChanges		: "Črkovanje je končano: Nobena beseda ni bila spremenjena",
DlgSpellOneChange		: "Črkovanje je končano: Spremenjena je bila ena beseda",
DlgSpellManyChanges		: "Črkovanje je končano: Spremenjenih je bilo %1 besed",

IeSpellDownload			: "Črkovalnik ni nameščen. Ali ga želite prenesti sedaj?",

// Button Dialog
DlgButtonText	: "Besedilo (Vrednost)",
DlgButtonType	: "Tip",

// Checkbox and Radio Button Dialogs
DlgCheckboxName		: "Ime",
DlgCheckboxValue	: "Vrednost",
DlgCheckboxSelected	: "Izbrano",

// Form Dialog
DlgFormName		: "Ime",
DlgFormAction	: "Akcija",
DlgFormMethod	: "Metoda",

// Select Field Dialog
DlgSelectName		: "Ime",
DlgSelectValue		: "Vrednost",
DlgSelectSize		: "Velikost",
DlgSelectLines		: "vrstic",
DlgSelectChkMulti	: "Dovoli izbor večih vrstic",
DlgSelectOpAvail	: "Razpoložljive izbire",
DlgSelectOpText		: "Besedilo",
DlgSelectOpValue	: "Vrednost",
DlgSelectBtnAdd		: "Dodaj",
DlgSelectBtnModify	: "Spremeni",
DlgSelectBtnUp		: "Gor",
DlgSelectBtnDown	: "Dol",
DlgSelectBtnSetValue : "Postavi kot privzeto izbiro",
DlgSelectBtnDelete	: "Izbriši",

// Textarea Dialog
DlgTextareaName	: "Ime",
DlgTextareaCols	: "Stolpcev",
DlgTextareaRows	: "Vrstic",

// Text Field Dialog
DlgTextName			: "Ime",
DlgTextValue		: "Vrednost",
DlgTextCharWidth	: "Dolžina",
DlgTextMaxChars		: "Največje število znakov",
DlgTextType			: "Tip",
DlgTextTypeText		: "Besedilo",
DlgTextTypePass		: "Geslo",

// Hidden Field Dialog
DlgHiddenName	: "Ime",
DlgHiddenValue	: "Vrednost",

// Bulleted List Dialog
BulletedListProp	: "Lastnosti označenega seznama",
NumberedListProp	: "Lastnosti oštevilčenega seznama",
DlgLstType			: "Tip",
DlgLstTypeCircle	: "Pikica",
DlgLstTypeDisc		: "Kroglica",
DlgLstTypeSquare	: "Kvadratek",
DlgLstTypeNumbers	: "Številke (1, 2, 3)",
DlgLstTypeLCase		: "Male črke (a, b, c)",
DlgLstTypeUCase		: "Velike črke (A, B, C)",
DlgLstTypeSRoman	: "Male rimske številke (i, ii, iii)",
DlgLstTypeLRoman	: "Velike rimske številke (I, II, III)",

// Document Properties Dialog
DlgDocGeneralTab	: "Splošno",
DlgDocBackTab		: "Ozadje",
DlgDocColorsTab		: "Barve in zamiki",
DlgDocMetaTab		: "Meta podatki",

DlgDocPageTitle		: "Naslov strani",
DlgDocLangDir		: "Smer jezika",
DlgDocLangDirLTR	: "Od leve proti desni (LTR)",
DlgDocLangDirRTL	: "Od desne proti levi (RTL)",
DlgDocLangCode		: "Oznaka jezika",
DlgDocCharSet		: "Kodna tabela",
DlgDocCharSetOther	: "Druga kodna tabela",

DlgDocDocType		: "Glava tipa dokumenta",
DlgDocDocTypeOther	: "Druga glava tipa dokumenta",
DlgDocIncXHTML		: "Vstavi XHTML deklaracije",
DlgDocBgColor		: "Barva ozadja",
DlgDocBgImage		: "URL slike za ozadje",
DlgDocBgNoScroll	: "Nepremično ozadje",
DlgDocCText			: "Besedilo",
DlgDocCLink			: "Povezava",
DlgDocCVisited		: "Obiskana povezava",
DlgDocCActive		: "Aktivna povezava",
DlgDocMargins		: "Zamiki strani",
DlgDocMaTop			: "Na vrhu",
DlgDocMaLeft		: "Levo",
DlgDocMaRight		: "Desno",
DlgDocMaBottom		: "Spodaj",
DlgDocMeIndex		: "Ključne besede (ločene z vejicami)",
DlgDocMeDescr		: "Opis strani",
DlgDocMeAuthor		: "Avtor",
DlgDocMeCopy		: "Avtorske pravice",
DlgDocPreview		: "Predogled",

// Templates Dialog
Templates			: "Predloge",
DlgTemplatesTitle	: "Vsebinske predloge",
DlgTemplatesSelMsg	: "Izberite predlogo, ki jo želite odpreti v urejevalniku<br>(trenutna vsebina bo izgubljena):",
DlgTemplatesLoading	: "Nalagam seznam predlog. Prosim počakajte...",
DlgTemplatesNoTpl	: "(Ni pripravljenih predlog)",

// About Dialog
DlgAboutAboutTab	: "Vizitka",
DlgAboutBrowserInfoTab	: "Informacije o brskalniku",
DlgAboutLicenseTab	: "License",	//MISSING
DlgAboutVersion		: "različica",
DlgAboutLicense		: "Pravica za uporabo pod pogoji GNU Lesser General Public License",
DlgAboutInfo		: "Za več informacij obiščite"
}