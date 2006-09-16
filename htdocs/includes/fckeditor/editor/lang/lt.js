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
 * File Name: lt.js
 * 	Lithuanian language file.
 * 
 * File Authors:
 * 		Tauras Paliulis (tauras.paliulis@tauras.com)
 */

var FCKLang =
{
// Language direction : "ltr" (left to right) or "rtl" (right to left).
Dir					: "ltr",

ToolbarCollapse		: "Sutraukti mygtukų juostą",
ToolbarExpand		: "Išplėsti mygtukų juostą",

// Toolbar Items and Context Menu
Save				: "Išsaugoti",
NewPage				: "Naujas puslapis",
Preview				: "Peržiūra",
Cut					: "Iškirpti",
Copy				: "Kopijuoti",
Paste				: "Įdėti",
PasteText			: "Įdėti kaip gryną tekstą",
PasteWord			: "Įdėti iš Word",
Print				: "Spausdinti",
SelectAll			: "Pažymėti viską",
RemoveFormat		: "Panaikinti formatą",
InsertLinkLbl		: "Nuoroda",
InsertLink			: "Įterpti/taisyti nuorodą",
RemoveLink			: "Panaikinti nuorodą",
Anchor				: "Įterpti/modifikuoti žymę",
InsertImageLbl		: "Vaizdas",
InsertImage			: "Įterpti/taisyti vaizdą",
InsertFlashLbl		: "Flash",
InsertFlash			: "Įterpti/taisyti Flash",
InsertTableLbl		: "Lentelė",
InsertTable			: "Įterpti/taisyti lentelę",
InsertLineLbl		: "Linija",
InsertLine			: "Įterpti horizontalią liniją",
InsertSpecialCharLbl: "Spec. simbolis",
InsertSpecialChar	: "Įterpti specialų simbolį",
InsertSmileyLbl		: "Veideliai",
InsertSmiley		: "Įterpti veidelį",
About				: "Apie FCKeditor",
Bold				: "Pusjuodis",
Italic				: "Kursyvas",
Underline			: "Pabrauktas",
StrikeThrough		: "Perbrauktas",
Subscript			: "Apatinis indeksas",
Superscript			: "Viršutinis indeksas",
LeftJustify			: "Lygiuoti kairę",
CenterJustify		: "Centruoti",
RightJustify		: "Lygiuoti dešinę",
BlockJustify		: "Lygiuoti abi puses",
DecreaseIndent		: "Sumažinti įtrauką",
IncreaseIndent		: "Padidinti įtrauką",
Undo				: "Atšaukti",
Redo				: "Atstatyti",
NumberedListLbl		: "Numeruotas sąrašas",
NumberedList		: "Įterpti/Panaikinti numeruotą sąrašą",
BulletedListLbl		: "Suženklintas sąrašas",
BulletedList		: "Įterpti/Panaikinti suženklintą sąrašą",
ShowTableBorders	: "Rodyti lentelės rėmus",
ShowDetails			: "Rodyti detales",
Style				: "Stilius",
FontFormat			: "Šrifto formatas",
Font				: "Šriftas",
FontSize			: "Šrifto dydis",
TextColor			: "Teksto spalva",
BGColor				: "Fono spalva",
Source				: "Šaltinis",
Find				: "Rasti",
Replace				: "Pakeisti",
SpellCheck			: "Rašybos tikrinimas",
UniversalKeyboard	: "Universali klaviatūra",
PageBreakLbl		: "Puslapių skirtukas",
PageBreak			: "Įterpti puslapių skirtuką",

Form			: "Forma",
Checkbox		: "Žymimasis langelis",
RadioButton		: "Žymimoji akutė",
TextField		: "Teksto laukas",
Textarea		: "Teksto sritis",
HiddenField		: "Nerodomas laukas",
Button			: "Mygtukas",
SelectionField	: "Atrankos laukas",
ImageButton		: "Vaizdinis mygtukas",

FitWindow		: "Maximize the editor size",	//MISSING

// Context Menu
EditLink			: "Taisyti nuorodą",
CellCM				: "Cell",	//MISSING
RowCM				: "Row",	//MISSING
ColumnCM			: "Column",	//MISSING
InsertRow			: "Įterpti eilutę",
DeleteRows			: "Šalinti eilutes",
InsertColumn		: "Įterpti stulpelį",
DeleteColumns		: "Šalinti stulpelius",
InsertCell			: "Įterpti langelį",
DeleteCells			: "Šalinti langelius",
MergeCells			: "Sujungti langelius",
SplitCell			: "Skaidyti langelius",
TableDelete			: "Šalinti lentelę",
CellProperties		: "Langelio savybės",
TableProperties		: "Lentelės savybės",
ImageProperties		: "Vaizdo savybės",
FlashProperties		: "Flash savybės",

AnchorProp			: "Žymės savybės",
ButtonProp			: "Mygtuko savybės",
CheckboxProp		: "Žymimojo langelio savybės",
HiddenFieldProp		: "Nerodomo lauko savybės",
RadioButtonProp		: "Žymimosios akutės savybės",
ImageButtonProp		: "Vaizdinio mygtuko savybės",
TextFieldProp		: "Teksto lauko savybės",
SelectionFieldProp	: "Atrankos lauko savybės",
TextareaProp		: "Teksto srities savybės",
FormProp			: "Formos savybės",

FontFormats			: "Normalus;Formuotas;Kreipinio;Antraštinis 1;Antraštinis 2;Antraštinis 3;Antraštinis 4;Antraštinis 5;Antraštinis 6",

// Alerts and Messages
ProcessingXHTML		: "Apdorojamas XHTML. Prašome palaukti...",
Done				: "Baigta",
PasteWordConfirm	: "Įdedamas tekstas yra panašus į kopiją iš Word. Ar Jūs norite prieš įdėjimą išvalyti jį?",
NotCompatiblePaste	: "Ši komanda yra prieinama tik per Internet Explorer 5.5 ar aukštesnę versiją. Ar Jūs norite įterpti be valymo?",
UnknownToolbarItem	: "Nežinomas mygtukų juosta elementas \"%1\"",
UnknownCommand		: "Nežinomas komandos vardas \"%1\"",
NotImplemented		: "Komanda nėra įgyvendinta",
UnknownToolbarSet	: "Mygtukų juostos rinkinys \"%1\" neegzistuoja",
NoActiveX			: "Jūsų naršyklės saugumo nuostatos gali riboti kai kurias redaktoriaus savybes. Jūs turite aktyvuoti opciją \"Run ActiveX controls and plug-ins\". Kitu atveju Jums bus pranešama apie klaidas ir trūkstamas savybes.",
BrowseServerBlocked : "Neįmanoma atidaryti naujo naršyklės lango. Įsitikinkite, kad iškylančių langų blokavimo programos neveiksnios.",
DialogBlocked		: "Neįmanoma atidaryti dialogo lango. Įsitikinkite, kad iškylančių langų blokavimo programos neveiksnios.",

// Dialogs
DlgBtnOK			: "OK",
DlgBtnCancel		: "Nutraukti",
DlgBtnClose			: "Uždaryti",
DlgBtnBrowseServer	: "Naršyti po serverį",
DlgAdvancedTag		: "Papildomas",
DlgOpOther			: "<Kita>",
DlgInfoTab			: "Informacija",
DlgAlertUrl			: "Prašome įrašyti URL",

// General Dialogs Labels
DlgGenNotSet		: "<nėra nustatyta>",
DlgGenId			: "Id",
DlgGenLangDir		: "Teksto kryptis",
DlgGenLangDirLtr	: "Iš kairės į dešinę (LTR)",
DlgGenLangDirRtl	: "Iš dešinės į kairę (RTL)",
DlgGenLangCode		: "Kalbos kodas",
DlgGenAccessKey		: "Prieigos raktas",
DlgGenName			: "Vardas",
DlgGenTabIndex		: "Tabuliavimo indeksas",
DlgGenLongDescr		: "Ilgas aprašymas URL",
DlgGenClass			: "Stilių lentelės klasės",
DlgGenTitle			: "Konsultacinė antraštė",
DlgGenContType		: "Konsultacinio turinio tipas",
DlgGenLinkCharset	: "Susietų išteklių simbolių lentelė",
DlgGenStyle			: "Stilius",

// Image Dialog
DlgImgTitle			: "Vaizdo savybės",
DlgImgInfoTab		: "Vaizdo informacija",
DlgImgBtnUpload		: "Siųsti į serverį",
DlgImgURL			: "URL",
DlgImgUpload		: "Nusiųsti",
DlgImgAlt			: "Alternatyvus Tekstas",
DlgImgWidth			: "Plotis",
DlgImgHeight		: "Aukštis",
DlgImgLockRatio		: "Išlaikyti proporciją",
DlgBtnResetSize		: "Atstatyti dydį",
DlgImgBorder		: "Rėmelis",
DlgImgHSpace		: "Hor.Erdvė",
DlgImgVSpace		: "Vert.Erdvė",
DlgImgAlign			: "Lygiuoti",
DlgImgAlignLeft		: "Kairę",
DlgImgAlignAbsBottom: "Absoliučią apačią",
DlgImgAlignAbsMiddle: "Absoliutų vidurį",
DlgImgAlignBaseline	: "Apatinę liniją",
DlgImgAlignBottom	: "Apačią",
DlgImgAlignMiddle	: "Vidurį",
DlgImgAlignRight	: "Dešinę",
DlgImgAlignTextTop	: "Teksto viršūnę",
DlgImgAlignTop		: "Viršūnę",
DlgImgPreview		: "Peržiūra",
DlgImgAlertUrl		: "Prašome įvesti vaizdo URL",
DlgImgLinkTab		: "Nuoroda",

// Flash Dialog
DlgFlashTitle		: "Flash savybės",
DlgFlashChkPlay		: "Automatinis paleidimas",
DlgFlashChkLoop		: "Ciklas",
DlgFlashChkMenu		: "Leisti Flash meniu",
DlgFlashScale		: "Mastelis",
DlgFlashScaleAll	: "Rodyti visą",
DlgFlashScaleNoBorder	: "Be rėmelio",
DlgFlashScaleFit	: "Tikslus atitikimas",

// Link Dialog
DlgLnkWindowTitle	: "Nuoroda",
DlgLnkInfoTab		: "Nuorodos informacija",
DlgLnkTargetTab		: "Paskirtis",

DlgLnkType			: "Nuorodos tipas",
DlgLnkTypeURL		: "URL",
DlgLnkTypeAnchor	: "Žymė šiame puslapyje",
DlgLnkTypeEMail		: "El.paštas",
DlgLnkProto			: "Protokolas",
DlgLnkProtoOther	: "<kitas>",
DlgLnkURL			: "URL",
DlgLnkAnchorSel		: "Pasirinkite žymę",
DlgLnkAnchorByName	: "Pagal žymės vardą",
DlgLnkAnchorById	: "Pagal žymės Id",
DlgLnkNoAnchors		: "<Šiame dokumente žymių nėra>",
DlgLnkEMail			: "El.pašto adresas",
DlgLnkEMailSubject	: "Žinutės tema",
DlgLnkEMailBody		: "Žinutės turinys",
DlgLnkUpload		: "Siųsti",
DlgLnkBtnUpload		: "Siųsti į serverį",

DlgLnkTarget		: "Paskirties vieta",
DlgLnkTargetFrame	: "<kadras>",
DlgLnkTargetPopup	: "<išskleidžiamas langas>",
DlgLnkTargetBlank	: "Naujas langas (_blank)",
DlgLnkTargetParent	: "Pirminis langas (_parent)",
DlgLnkTargetSelf	: "Tas pats langas (_self)",
DlgLnkTargetTop		: "Svarbiausias langas (_top)",
DlgLnkTargetFrameName	: "Paskirties kadro vardas",
DlgLnkPopWinName	: "Paskirties lango vardas",
DlgLnkPopWinFeat	: "Išskleidžiamo lango savybės",
DlgLnkPopResize		: "Keičiamas dydis",
DlgLnkPopLocation	: "Adreso juosta",
DlgLnkPopMenu		: "Meniu juosta",
DlgLnkPopScroll		: "Slinkties juostos",
DlgLnkPopStatus		: "Būsenos juosta",
DlgLnkPopToolbar	: "Mygtukų juosta",
DlgLnkPopFullScrn	: "Visas ekranas (IE)",
DlgLnkPopDependent	: "Priklausomas (Netscape)",
DlgLnkPopWidth		: "Plotis",
DlgLnkPopHeight		: "Aukštis",
DlgLnkPopLeft		: "Kairė pozicija",
DlgLnkPopTop		: "Viršutinė pozicija",

DlnLnkMsgNoUrl		: "Prašome įvesti nuorodos URL",
DlnLnkMsgNoEMail	: "Prašome įvesti el.pašto adresą",
DlnLnkMsgNoAnchor	: "Prašome pasirinkti žymę",

// Color Dialog
DlgColorTitle		: "Pasirinkite spalvą",
DlgColorBtnClear	: "Trinti",
DlgColorHighlight	: "Paryškinta",
DlgColorSelected	: "Pažymėta",

// Smiley Dialog
DlgSmileyTitle		: "Įterpti veidelį",

// Special Character Dialog
DlgSpecialCharTitle	: "Pasirinkite specialų simbolį",

// Table Dialog
DlgTableTitle		: "Lentelės savybės",
DlgTableRows		: "Eilutės",
DlgTableColumns		: "Stulpeliai",
DlgTableBorder		: "Rėmelio dydis",
DlgTableAlign		: "Lygiuoti",
DlgTableAlignNotSet	: "<Nenustatyta>",
DlgTableAlignLeft	: "Kairę",
DlgTableAlignCenter	: "Centrą",
DlgTableAlignRight	: "Dešinę",
DlgTableWidth		: "Plotis",
DlgTableWidthPx		: "taškais",
DlgTableWidthPc		: "procentais",
DlgTableHeight		: "Aukštis",
DlgTableCellSpace	: "Tarpas tarp langelių",
DlgTableCellPad		: "Trapas nuo langelio rėmo iki teksto",
DlgTableCaption		: "Antraštė",
DlgTableSummary		: "Santrauka",

// Table Cell Dialog
DlgCellTitle		: "Langelio savybės",
DlgCellWidth		: "Plotis",
DlgCellWidthPx		: "taškais",
DlgCellWidthPc		: "procentais",
DlgCellHeight		: "Aukštis",
DlgCellWordWrap		: "Teksto laužymas",
DlgCellWordWrapNotSet	: "<Nenustatyta>",
DlgCellWordWrapYes	: "Taip",
DlgCellWordWrapNo	: "Ne",
DlgCellHorAlign		: "Horizontaliai lygiuoti",
DlgCellHorAlignNotSet	: "<Nenustatyta>",
DlgCellHorAlignLeft	: "Kairę",
DlgCellHorAlignCenter	: "Centrą",
DlgCellHorAlignRight: "Dešinę",
DlgCellVerAlign		: "Vertikaliai lygiuoti",
DlgCellVerAlignNotSet	: "<Nenustatyta>",
DlgCellVerAlignTop	: "Viršų",
DlgCellVerAlignMiddle	: "Vidurį",
DlgCellVerAlignBottom	: "Apačią",
DlgCellVerAlignBaseline	: "Apatinę liniją",
DlgCellRowSpan		: "Eilučių apjungimas",
DlgCellCollSpan		: "Stulpelių apjungimas",
DlgCellBackColor	: "Fono spalva",
DlgCellBorderColor	: "Rėmelio spalva",
DlgCellBtnSelect	: "Pažymėti...",

// Find Dialog
DlgFindTitle		: "Paieška",
DlgFindFindBtn		: "Surasti",
DlgFindNotFoundMsg	: "Nurodytas tekstas nerastas.",

// Replace Dialog
DlgReplaceTitle			: "Pakeisti",
DlgReplaceFindLbl		: "Surasti tekstą:",
DlgReplaceReplaceLbl	: "Pakeisti tekstu:",
DlgReplaceCaseChk		: "Skirti didžiąsias ir mažąsias raides",
DlgReplaceReplaceBtn	: "Pakeisti",
DlgReplaceReplAllBtn	: "Pakeisti viską",
DlgReplaceWordChk		: "Atitikti pilną žodį",

// Paste Operations / Dialog
PasteErrorPaste	: "Jūsų naršyklės saugumo nustatymai neleidžia redaktoriui automatiškai įvykdyti įdėjimo operacijų. Tam prašome naudoti klaviatūrą (Ctrl+V).",
PasteErrorCut	: "Jūsų naršyklės saugumo nustatymai neleidžia redaktoriui automatiškai įvykdyti iškirpimo operacijų. Tam prašome naudoti klaviatūrą (Ctrl+X).",
PasteErrorCopy	: "Jūsų naršyklės saugumo nustatymai neleidžia redaktoriui automatiškai įvykdyti kopijavimo operacijų. Tam prašome naudoti klaviatūrą (Ctrl+C).",

PasteAsText		: "Įdėti kaip gryną tekstą",
PasteFromWord	: "Įdėti iš Word",

DlgPasteMsg2	: "Žemiau esančiame įvedimo lauke įdėkite tekstą, naudodami klaviatūrą (<STRONG>Ctrl+V</STRONG>) ir spūstelkite mygtuką <STRONG>OK</STRONG>.",
DlgPasteIgnoreFont		: "Ignoruoti šriftų nustatymus",
DlgPasteRemoveStyles	: "Pašalinti stilių nustatymus",
DlgPasteCleanBox		: "Trinti įvedimo lauką",

// Color Picker
ColorAutomatic	: "Automatinis",
ColorMoreColors	: "Daugiau spalvų...",

// Document Properties
DocProps		: "Dokumento savybės",

// Anchor Dialog
DlgAnchorTitle		: "Žymės savybės",
DlgAnchorName		: "Žymės vardas",
DlgAnchorErrorName	: "Prašome įvesti žymės vardą",

// Speller Pages Dialog
DlgSpellNotInDic		: "Žodyne nerastas",
DlgSpellChangeTo		: "Pakeisti į",
DlgSpellBtnIgnore		: "Ignoruoti",
DlgSpellBtnIgnoreAll	: "Ignoruoti visus",
DlgSpellBtnReplace		: "Pakeisti",
DlgSpellBtnReplaceAll	: "Pakeisti visus",
DlgSpellBtnUndo			: "Atšaukti",
DlgSpellNoSuggestions	: "- Nėra pasiūlymų -",
DlgSpellProgress		: "Vyksta rašybos tikrinimas...",
DlgSpellNoMispell		: "Rašybos tikrinimas baigtas: Nerasta rašybos klaidų",
DlgSpellNoChanges		: "Rašybos tikrinimas baigtas: Nėra pakeistų žodžių",
DlgSpellOneChange		: "Rašybos tikrinimas baigtas: Vienas žodis pakeistas",
DlgSpellManyChanges		: "Rašybos tikrinimas baigtas: Pakeista %1 žodžių",

IeSpellDownload			: "Rašybos tikrinimas neinstaliuotas. Ar Jūs norite jį dabar atsisiųsti?",

// Button Dialog
DlgButtonText	: "Tekstas (Reikšmė)",
DlgButtonType	: "Tipas",

// Checkbox and Radio Button Dialogs
DlgCheckboxName		: "Vardas",
DlgCheckboxValue	: "Reikšmė",
DlgCheckboxSelected	: "Pažymėtas",

// Form Dialog
DlgFormName		: "Vardas",
DlgFormAction	: "Veiksmas",
DlgFormMethod	: "Metodas",

// Select Field Dialog
DlgSelectName		: "Vardas",
DlgSelectValue		: "Reikšmė",
DlgSelectSize		: "Dydis",
DlgSelectLines		: "eilučių",
DlgSelectChkMulti	: "Leisti daugeriopą atranką",
DlgSelectOpAvail	: "Galimos parinktys",
DlgSelectOpText		: "Tekstas",
DlgSelectOpValue	: "Reikšmė",
DlgSelectBtnAdd		: "Įtraukti",
DlgSelectBtnModify	: "Modifikuoti",
DlgSelectBtnUp		: "Aukštyn",
DlgSelectBtnDown	: "Žemyn",
DlgSelectBtnSetValue : "Laikyti pažymėta reikšme",
DlgSelectBtnDelete	: "Trinti",

// Textarea Dialog
DlgTextareaName	: "Vardas",
DlgTextareaCols	: "Ilgis",
DlgTextareaRows	: "Plotis",

// Text Field Dialog
DlgTextName			: "Vardas",
DlgTextValue		: "Reikšmė",
DlgTextCharWidth	: "Ilgis simboliais",
DlgTextMaxChars		: "Maksimalus simbolių skaičius",
DlgTextType			: "Tipas",
DlgTextTypeText		: "Tekstas",
DlgTextTypePass		: "Slaptažodis",

// Hidden Field Dialog
DlgHiddenName	: "Vardas",
DlgHiddenValue	: "Reikšmė",

// Bulleted List Dialog
BulletedListProp	: "Suženklinto sąrašo savybės",
NumberedListProp	: "Numeruoto sąrašo savybės",
DlgLstType			: "Tipas",
DlgLstTypeCircle	: "Apskritimas",
DlgLstTypeDisc		: "Diskas",
DlgLstTypeSquare	: "Kvadratas",
DlgLstTypeNumbers	: "Skaičiai (1, 2, 3)",
DlgLstTypeLCase		: "Mažosios raidės (a, b, c)",
DlgLstTypeUCase		: "Didžiosios raidės (A, B, C)",
DlgLstTypeSRoman	: "Romėnų mažieji skaičiai (i, ii, iii)",
DlgLstTypeLRoman	: "Romėnų didieji skaičiai (I, II, III)",

// Document Properties Dialog
DlgDocGeneralTab	: "Bendros savybės",
DlgDocBackTab		: "Fonas",
DlgDocColorsTab		: "Spalvos ir kraštinės",
DlgDocMetaTab		: "Meta duomenys",

DlgDocPageTitle		: "Puslapio antraštė",
DlgDocLangDir		: "Kalbos kryptis",
DlgDocLangDirLTR	: "Iš kairės į dešinę (LTR)",
DlgDocLangDirRTL	: "Iš dešinės į kairę (RTL)",
DlgDocLangCode		: "Kalbos kodas",
DlgDocCharSet		: "Simbolių kodavimo lentelė",
DlgDocCharSetOther	: "Kita simbolių kodavimo lentelė",

DlgDocDocType		: "Dokumento tipo antraštė",
DlgDocDocTypeOther	: "Kita dokumento tipo antraštė",
DlgDocIncXHTML		: "Įtraukti XHTML deklaracijas",
DlgDocBgColor		: "Fono spalva",
DlgDocBgImage		: "Fono paveikslėlio nuoroda (URL)",
DlgDocBgNoScroll	: "Neslenkantis fonas",
DlgDocCText			: "Tekstas",
DlgDocCLink			: "Nuoroda",
DlgDocCVisited		: "Aplankyta nuoroda",
DlgDocCActive		: "Aktyvi nuoroda",
DlgDocMargins		: "Puslapio kraštinės",
DlgDocMaTop			: "Viršuje",
DlgDocMaLeft		: "Kairėje",
DlgDocMaRight		: "Dešinėje",
DlgDocMaBottom		: "Apačioje",
DlgDocMeIndex		: "Dokumento indeksavimo raktiniai žodžiai (atskirti kableliais)",
DlgDocMeDescr		: "Dokumento apibūdinimas",
DlgDocMeAuthor		: "Autorius",
DlgDocMeCopy		: "Autorinės teisės",
DlgDocPreview		: "Peržiūra",

// Templates Dialog
Templates			: "Šablonai",
DlgTemplatesTitle	: "Turinio šablonai",
DlgTemplatesSelMsg	: "Pasirinkite norimą šabloną<br>(<b>Dėmesio!</b> esamas turinys bus prarastas):",
DlgTemplatesLoading	: "Įkeliamas šablonų sąrašas. Prašome palaukti...",
DlgTemplatesNoTpl	: "(Šablonų sąrašas tuščias)",

// About Dialog
DlgAboutAboutTab	: "Apie",
DlgAboutBrowserInfoTab	: "Naršyklės informacija",
DlgAboutLicenseTab	: "License",	//MISSING
DlgAboutVersion		: "versija",
DlgAboutLicense		: "Licencijuota pagal GNU mažesnės atsakomybės pagrindinės viešos licencijos sąlygas",
DlgAboutInfo		: "Papildomą informaciją galima gauti"
}