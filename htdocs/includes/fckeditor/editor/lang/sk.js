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
 * File Name: sk.js
 * 	Slovak language file.
 * 
 * File Authors:
 * 		Samuel Szabo (samuel@nanete.sk)
 */

var FCKLang =
{
// Language direction : "ltr" (left to right) or "rtl" (right to left).
Dir					: "ltr",

ToolbarCollapse		: "Skryť panel nástrojov",
ToolbarExpand		: "Zobraziť panel nástrojov",

// Toolbar Items and Context Menu
Save				: "Uložit",
NewPage				: "Nová stránka",
Preview				: "Náhľad",
Cut					: "Vystrihnúť",
Copy				: "Kopírovať",
Paste				: "Vložiť",
PasteText			: "Vložiť ako čistý text",
PasteWord			: "Vložiť z Wordu",
Print				: "Tlač",
SelectAll			: "Vybrať všetko",
RemoveFormat		: "Odstrániť formátovanie",
InsertLinkLbl		: "Odkaz",
InsertLink			: "Vložiť/zmeniť odkaz",
RemoveLink			: "Odstrániť odkaz",
Anchor				: "Vložiť/zmeniť kotvu",
InsertImageLbl		: "Obrázok",
InsertImage			: "Vložiť/zmeniť obrazok",
InsertFlashLbl		: "Flash",
InsertFlash			: "Vložiť/zmeniť Flash",
InsertTableLbl		: "Tabuľka",
InsertTable			: "Vložiť/zmeniť tabuľku",
InsertLineLbl		: "Čiara",
InsertLine			: "Vložiť vodorovnú čiara",
InsertSpecialCharLbl: "Speciálne znaky",
InsertSpecialChar	: "Vložiť špeciálne znaky",
InsertSmileyLbl		: "Smajlíky",
InsertSmiley		: "Vložiť smajlíka",
About				: "O aplikáci FCKeditor",
Bold				: "Tučné",
Italic				: "Kurzíva",
Underline			: "Podčiarknuté",
StrikeThrough		: "Prečiarknuté",
Subscript			: "Dolný index",
Superscript			: "Horný index",
LeftJustify			: "Zarovnať vľavo",
CenterJustify		: "Zarovnať na stred",
RightJustify		: "Zarovnať vpravo",
BlockJustify		: "Zarovnať do bloku",
DecreaseIndent		: "Zmenšiť odsadenie",
IncreaseIndent		: "Zväčšiť odsadenie",
Undo				: "Späť",
Redo				: "Znovu",
NumberedListLbl		: "Číslovanie",
NumberedList		: "Vložiť/odstrániť číslovaný zoznam",
BulletedListLbl		: "Odrážky",
BulletedList		: "Vložiť/odstraniť odrážky",
ShowTableBorders	: "Zobraziť okraje tabuliek",
ShowDetails			: "Zobraziť podrobnosti",
Style				: "Štýl",
FontFormat			: "Formát",
Font				: "Písmo",
FontSize			: "Veľkost",
TextColor			: "Farba textu",
BGColor				: "Farba pozadia",
Source				: "Zdroj",
Find				: "Hľadať",
Replace				: "Nahradiť",
SpellCheck			: "Kontrola pravopisu",
UniversalKeyboard	: "Univerzálna klávesnica",
PageBreakLbl		: "Oddeľovač stránky",
PageBreak			: "Vložiť oddeľovač stránky",

Form			: "Formulár",
Checkbox		: "Zaškrtávacie políčko",
RadioButton		: "Prepínač",
TextField		: "Textové pole",
Textarea		: "Textová oblasť",
HiddenField		: "Skryté pole",
Button			: "Tlačítko",
SelectionField	: "Rozbaľovací zoznam",
ImageButton		: "Obrázkové tlačítko",

FitWindow		: "Maximize the editor size",	//MISSING

// Context Menu
EditLink			: "Zmeniť odkaz",
CellCM				: "Cell",	//MISSING
RowCM				: "Row",	//MISSING
ColumnCM			: "Column",	//MISSING
InsertRow			: "Vložiť riadok",
DeleteRows			: "Zmazať riadok",
InsertColumn		: "Vložiť stĺpec",
DeleteColumns		: "Zmazať stĺpec",
InsertCell			: "Vložiť bunku",
DeleteCells			: "Zmazať bunky",
MergeCells			: "Zlúčiť bunky",
SplitCell			: "Rozdeliť bunku",
TableDelete			: "Zmazať tabuľku",
CellProperties		: "Vlastnosti bunky",
TableProperties		: "Vlastnosti tabuľky",
ImageProperties		: "Vlastnosti obrázku",
FlashProperties		: "Vlastnosti Flashu",

AnchorProp			: "Vlastnosti kotvy",
ButtonProp			: "Vlastnosti tlačítka",
CheckboxProp		: "Vlastnosti zaškrtávacieho políčka",
HiddenFieldProp		: "Vlastnosti skrytého poľa",
RadioButtonProp		: "Vlastnosti prepínača",
ImageButtonProp		: "Vlastnosti obrázkového tlačítka",
TextFieldProp		: "Vlastnosti textového pola",
SelectionFieldProp	: "Vlastnosti rozbaľovacieho zoznamu",
TextareaProp		: "Vlastnosti textové oblasti",
FormProp			: "Vlastnosti formulára",

FontFormats			: "Normálny;Formátovaný;Adresa;Nadpis 1;Nadpis 2;Nadpis 3;Nadpis 4;Nadpis 5;Nadpis 6;Odsek (DIV)",

// Alerts and Messages
ProcessingXHTML		: "Prebieha spracovanie XHTML. Čakejte prosím...",
Done				: "Dokončené.",
PasteWordConfirm	: "Vyzerá to tak, že vkladaný text je kopírovaný z Wordu. Chcete ho pred vložením vyčistiť?",
NotCompatiblePaste	: "Tento príkaz je dostupný len v prehliadači Internet Explorer verzie 5.5 alebo vyššej. Chcete vložiť text bez vyčistenia?",
UnknownToolbarItem	: "Neznáma položka panela nástrojov \"%1\"",
UnknownCommand		: "Neznámy príkaz \"%1\"",
NotImplemented		: "Príkaz nie je implementovaný",
UnknownToolbarSet	: "Panel nástrojov \"%1\" neexistuje",
NoActiveX			: "Bezpečnostné nastavenia Vašeho prehliadača môžu obmedzovať niektoré funkcie editora. Pre ich plnú funkčnosť musíte zapnúť voľbu \"Spúšťať ActiveX moduly a zásuvné moduly\", inak sa môžete stretnúť s chybami a nefunkčnosťou niektorých funkcií.",
BrowseServerBlocked : "Prehliadač zdrojových prvkov nebolo možné otvoriť. Uistite sa, že máte vypnuté všetky blokovače vyskakujúcich okien.",
DialogBlocked		: "Dialógové okno nebolo možné otvoriť. Uistite sa, že máte vypnuté všetky blokovače vyskakujúcich okien.",

// Dialogs
DlgBtnOK			: "OK",
DlgBtnCancel		: "Zrušiť",
DlgBtnClose			: "Zavrieť",
DlgBtnBrowseServer	: "Prechádzať server",
DlgAdvancedTag		: "Rozšírené",
DlgOpOther			: "<Ďalšie>",
DlgInfoTab			: "Info",
DlgAlertUrl			: "Prosím vložte URL",

// General Dialogs Labels
DlgGenNotSet		: "<nenastavené>",
DlgGenId			: "Id",
DlgGenLangDir		: "Orientácia jazyka",
DlgGenLangDirLtr	: "Zľava doprava (LTR)",
DlgGenLangDirRtl	: "Zprava doľava (RTL)",
DlgGenLangCode		: "Kód jazyka",
DlgGenAccessKey		: "Prístupový kľúč",
DlgGenName			: "Meno",
DlgGenTabIndex		: "Poradie prvku",
DlgGenLongDescr		: "Dlhý popis URL",
DlgGenClass			: "Trieda štýlu",
DlgGenTitle			: "Pomocný titulok",
DlgGenContType		: "Pomocný typ obsahu",
DlgGenLinkCharset	: "Priradená znaková sada",
DlgGenStyle			: "Štýl",

// Image Dialog
DlgImgTitle			: "Vlastnosti obrázku",
DlgImgInfoTab		: "Informácie o obrázku",
DlgImgBtnUpload		: "Odoslať na server",
DlgImgURL			: "URL",
DlgImgUpload		: "Odoslať",
DlgImgAlt			: "Alternatívny text",
DlgImgWidth			: "Šírka",
DlgImgHeight		: "Výška",
DlgImgLockRatio		: "Zámok",
DlgBtnResetSize		: "Pôvodná veľkosť",
DlgImgBorder		: "Okraje",
DlgImgHSpace		: "H-medzera",
DlgImgVSpace		: "V-medzera",
DlgImgAlign			: "Zarovnanie",
DlgImgAlignLeft		: "Vľavo",
DlgImgAlignAbsBottom: "Úplne dole",
DlgImgAlignAbsMiddle: "Do stredu",
DlgImgAlignBaseline	: "Na základňu",
DlgImgAlignBottom	: "Dole",
DlgImgAlignMiddle	: "Na stred",
DlgImgAlignRight	: "Vpravo",
DlgImgAlignTextTop	: "Na horný okraj textu",
DlgImgAlignTop		: "Nahor",
DlgImgPreview		: "Náhľad",
DlgImgAlertUrl		: "Zadajte prosím URL obrázku",
DlgImgLinkTab		: "Odkaz",

// Flash Dialog
DlgFlashTitle		: "Vlastnosti Flashu",
DlgFlashChkPlay		: "Automatické prehrávanie",
DlgFlashChkLoop		: "Opakovanie",
DlgFlashChkMenu		: "Povoliť Flash Menu",
DlgFlashScale		: "Mierka",
DlgFlashScaleAll	: "Zobraziť mierku",
DlgFlashScaleNoBorder	: "Bez okrajov",
DlgFlashScaleFit	: "Roztiahnuť na celé",

// Link Dialog
DlgLnkWindowTitle	: "Odkaz",
DlgLnkInfoTab		: "Informácie o odkaze",
DlgLnkTargetTab		: "Cieľ",

DlgLnkType			: "Typ odkazu",
DlgLnkTypeURL		: "URL",
DlgLnkTypeAnchor	: "Kotva v tejto stránke",
DlgLnkTypeEMail		: "E-Mail",
DlgLnkProto			: "Protokol",
DlgLnkProtoOther	: "<iný>",
DlgLnkURL			: "URL",
DlgLnkAnchorSel		: "Vybrať kotvu",
DlgLnkAnchorByName	: "Podľa mena kotvy",
DlgLnkAnchorById	: "Podľa Id objektu",
DlgLnkNoAnchors		: "<V stránke nie je definovaná žiadna kotva>",
DlgLnkEMail			: "E-Mailová adresa",
DlgLnkEMailSubject	: "Predmet správy",
DlgLnkEMailBody		: "Telo správy",
DlgLnkUpload		: "Odoslať",
DlgLnkBtnUpload		: "Odoslať na server",

DlgLnkTarget		: "Cieľ",
DlgLnkTargetFrame	: "<rámec>",
DlgLnkTargetPopup	: "<vyskakovacie okno>",
DlgLnkTargetBlank	: "Nové okno (_blank)",
DlgLnkTargetParent	: "Rodičovské okno (_parent)",
DlgLnkTargetSelf	: "Rovnaké okno (_self)",
DlgLnkTargetTop		: "Hlavné okno (_top)",
DlgLnkTargetFrameName	: "Meno rámu cieľa",
DlgLnkPopWinName	: "Názov vyskakovacieho okna",
DlgLnkPopWinFeat	: "Vlastnosti vyskakovacieho okna",
DlgLnkPopResize		: "Meniteľná veľkosť",
DlgLnkPopLocation	: "Panel umiestnenia",
DlgLnkPopMenu		: "Panel ponuky",
DlgLnkPopScroll		: "Posuvníky",
DlgLnkPopStatus		: "Stavový riadok",
DlgLnkPopToolbar	: "Panel nástrojov",
DlgLnkPopFullScrn	: "Celá obrazovka (IE)",
DlgLnkPopDependent	: "Závislosť (Netscape)",
DlgLnkPopWidth		: "Šírka",
DlgLnkPopHeight		: "Výška",
DlgLnkPopLeft		: "Ľavý okraj",
DlgLnkPopTop		: "Horný okraj",

DlnLnkMsgNoUrl		: "Zadajte prosím URL odkazu",
DlnLnkMsgNoEMail	: "Zadajte prosím e-mailovú adresu",
DlnLnkMsgNoAnchor	: "Vyberte prosím kotvu",

// Color Dialog
DlgColorTitle		: "Výber farby",
DlgColorBtnClear	: "Vymazať",
DlgColorHighlight	: "Zvýraznená",
DlgColorSelected	: "Vybraná",

// Smiley Dialog
DlgSmileyTitle		: "Vkladanie smajlíkov",

// Special Character Dialog
DlgSpecialCharTitle	: "Výber speciálneho znaku",

// Table Dialog
DlgTableTitle		: "Vlastnosti tabuľky",
DlgTableRows		: "Riadky",
DlgTableColumns		: "Stĺpce",
DlgTableBorder		: "Ohraničenie",
DlgTableAlign		: "Zarovnanie",
DlgTableAlignNotSet	: "<nenastavené>",
DlgTableAlignLeft	: "Vľavo",
DlgTableAlignCenter	: "Na stred",
DlgTableAlignRight	: "Vpravo",
DlgTableWidth		: "Šírka",
DlgTableWidthPx		: "pixelov",
DlgTableWidthPc		: "percent",
DlgTableHeight		: "Výška",
DlgTableCellSpace	: "Vzdialenosť buniek",
DlgTableCellPad		: "Odsadenie obsahu",
DlgTableCaption		: "Popis",
DlgTableSummary		: "Prehľad",

// Table Cell Dialog
DlgCellTitle		: "Vlastnosti bunky",
DlgCellWidth		: "Šírka",
DlgCellWidthPx		: "bodov",
DlgCellWidthPc		: "percent",
DlgCellHeight		: "Výška",
DlgCellWordWrap		: "Zalamovannie",
DlgCellWordWrapNotSet	: "<nenastavené>",
DlgCellWordWrapYes	: "Áno",
DlgCellWordWrapNo	: "Nie",
DlgCellHorAlign		: "Vodorovné zarovnanie",
DlgCellHorAlignNotSet	: "<nenastavené>",
DlgCellHorAlignLeft	: "Vľavo",
DlgCellHorAlignCenter	: "Na stred",
DlgCellHorAlignRight: "Vpravo",
DlgCellVerAlign		: "Zvyslé zarovnanie",
DlgCellVerAlignNotSet	: "<nenastavené>",
DlgCellVerAlignTop	: "Nahor",
DlgCellVerAlignMiddle	: "Doprostred",
DlgCellVerAlignBottom	: "Dole",
DlgCellVerAlignBaseline	: "Na základňu",
DlgCellRowSpan		: "Zlúčené riadky",
DlgCellCollSpan		: "Zlúčené stĺpce",
DlgCellBackColor	: "Farba pozadia",
DlgCellBorderColor	: "Farba ohraničenia",
DlgCellBtnSelect	: "Výber...",

// Find Dialog
DlgFindTitle		: "Hľadať",
DlgFindFindBtn		: "Hľadať",
DlgFindNotFoundMsg	: "Hľadaný text nebol nájdený.",

// Replace Dialog
DlgReplaceTitle			: "Nahradiť",
DlgReplaceFindLbl		: "Čo hľadať:",
DlgReplaceReplaceLbl	: "Čím nahradiť:",
DlgReplaceCaseChk		: "Rozlišovať malé/veľké písmená",
DlgReplaceReplaceBtn	: "Nahradiť",
DlgReplaceReplAllBtn	: "Nahradiť všetko",
DlgReplaceWordChk		: "Len celé slová",

// Paste Operations / Dialog
PasteErrorPaste	: "Bezpečnostné nastavenie Vášho prohehliadača nedovoľujú editoru spustiť funkciu pre vloženie textu zo schránky. Prosím vložte text zo schránky pomocou klávesnice (Ctrl+V).",
PasteErrorCut	: "Bezpečnostné nastavenie Vášho prohehliadača nedovoľujú editoru spustiť funkciu pre vystrihnutie zvoleného textu do schránky. Prosím vystrihnite zvolený text do schránky pomocou klávesnice (Ctrl+X).",
PasteErrorCopy	: "Bezpečnostné nastavenie Vášho prohehliadača nedovoľujú editoru spustiť funkciu pre kopírovánie zvoleného textu do schránky. Prosím skopírujte zvolený text do schránky pomocou klávesnice (Ctrl+C).",

PasteAsText		: "Vložiť ako čistý text",
PasteFromWord	: "Vložiť text z Wordu",

DlgPasteMsg2	: "Do nasledujúceho boxu vložte obsah schránky použitím klávesnice (<STRONG>Ctrl+V</STRONG>) a stlačte <STRONG>OK</STRONG>.",
DlgPasteIgnoreFont		: "Ignorovať nastavenia typu písma",
DlgPasteRemoveStyles	: "Odstrániť formátovanie",
DlgPasteCleanBox		: "Vyčistiť schránku",

// Color Picker
ColorAutomatic	: "Automaticky",
ColorMoreColors	: "Viac farieb...",

// Document Properties
DocProps		: "Vlastnosti dokumentu",

// Anchor Dialog
DlgAnchorTitle		: "Vlastnosti kotvy",
DlgAnchorName		: "Meno kotvy",
DlgAnchorErrorName	: "Zadajte prosím meno kotvy",

// Speller Pages Dialog
DlgSpellNotInDic		: "Nie je v slovníku",
DlgSpellChangeTo		: "Zmeniť na",
DlgSpellBtnIgnore		: "Ignorovať",
DlgSpellBtnIgnoreAll	: "Ignorovať všetko",
DlgSpellBtnReplace		: "Prepísat",
DlgSpellBtnReplaceAll	: "Prepísat všetko",
DlgSpellBtnUndo			: "Späť",
DlgSpellNoSuggestions	: "- Žiadny návrh -",
DlgSpellProgress		: "Prebieha kontrola pravopisu...",
DlgSpellNoMispell		: "Kontrola pravopisu dokončená: bez chyb",
DlgSpellNoChanges		: "Kontrola pravopisu dokončená: žiadne slová nezmenené",
DlgSpellOneChange		: "Kontrola pravopisu dokončená: zmenené jedno slovo",
DlgSpellManyChanges		: "Kontrola pravopisu dokončená: zmenených %1 slov",

IeSpellDownload			: "Kontrola pravopisu nie je naištalovaná. Chcete ju hneď stiahnuť?",

// Button Dialog
DlgButtonText	: "Text",
DlgButtonType	: "Typ",

// Checkbox and Radio Button Dialogs
DlgCheckboxName		: "Názov",
DlgCheckboxValue	: "Hodnota",
DlgCheckboxSelected	: "Vybrané",

// Form Dialog
DlgFormName		: "Názov",
DlgFormAction	: "Akcie",
DlgFormMethod	: "Metóda",

// Select Field Dialog
DlgSelectName		: "Názov",
DlgSelectValue		: "Hodnota",
DlgSelectSize		: "Veľkosť",
DlgSelectLines		: "riadkov",
DlgSelectChkMulti	: "Povoliť viacnásobný výber",
DlgSelectOpAvail	: "Dostupné možnosti",
DlgSelectOpText		: "Text",
DlgSelectOpValue	: "Hodnota",
DlgSelectBtnAdd		: "Pridať",
DlgSelectBtnModify	: "Zmeniť",
DlgSelectBtnUp		: "Nahor",
DlgSelectBtnDown	: "Dolu",
DlgSelectBtnSetValue : "Nastaviť ako vybranú hodnotu",
DlgSelectBtnDelete	: "Zmazať",

// Textarea Dialog
DlgTextareaName	: "Názov",
DlgTextareaCols	: "Stĺpce",
DlgTextareaRows	: "Riadky",

// Text Field Dialog
DlgTextName			: "Názov",
DlgTextValue		: "Hodnota",
DlgTextCharWidth	: "Šírka pola (znakov)",
DlgTextMaxChars		: "Maximálny počet znakov",
DlgTextType			: "Typ",
DlgTextTypeText		: "Text",
DlgTextTypePass		: "Heslo",

// Hidden Field Dialog
DlgHiddenName	: "Názov",
DlgHiddenValue	: "Hodnota",

// Bulleted List Dialog
BulletedListProp	: "Vlastnosti odrážok",
NumberedListProp	: "Vlastnosti číslovania",
DlgLstType			: "Typ",
DlgLstTypeCircle	: "Krúžok",
DlgLstTypeDisc		: "Disk",
DlgLstTypeSquare	: "Štvorec",
DlgLstTypeNumbers	: "Číslovanie (1, 2, 3)",
DlgLstTypeLCase		: "Malé písmená (a, b, c)",
DlgLstTypeUCase		: "Veľké písmená (A, B, C)",
DlgLstTypeSRoman	: "Malé rímske číslice (i, ii, iii)",
DlgLstTypeLRoman	: "Veľké rímske číslice (I, II, III)",

// Document Properties Dialog
DlgDocGeneralTab	: "Všeobecné",
DlgDocBackTab		: "Pozadie",
DlgDocColorsTab		: "Farby a okraje",
DlgDocMetaTab		: "Meta Data",

DlgDocPageTitle		: "Titulok",
DlgDocLangDir		: "Orientácie jazyka",
DlgDocLangDirLTR	: "Zľava doprava (LTR)",
DlgDocLangDirRTL	: "Zprava doľava (RTL)",
DlgDocLangCode		: "Kód jazyka",
DlgDocCharSet		: "Kódová stránka",
DlgDocCharSetOther	: "Iná kódová stránka",

DlgDocDocType		: "Typ záhlavia dokumentu",
DlgDocDocTypeOther	: "Iný typ záhlavia dokumentu",
DlgDocIncXHTML		: "Obsahuje deklarácie XHTML",
DlgDocBgColor		: "Farba pozadia",
DlgDocBgImage		: "URL adresa obrázku na pozadí",
DlgDocBgNoScroll	: "Fixné pozadie",
DlgDocCText			: "Text",
DlgDocCLink			: "Odkaz",
DlgDocCVisited		: "Navštívený odkaz",
DlgDocCActive		: "Aktívny odkaz",
DlgDocMargins		: "Okraje stránky",
DlgDocMaTop			: "Horný",
DlgDocMaLeft		: "Ľavý",
DlgDocMaRight		: "Pravý",
DlgDocMaBottom		: "Dolný",
DlgDocMeIndex		: "Kľúčové slová pre indexovanie (oddelené čiarkou)",
DlgDocMeDescr		: "Popis stránky",
DlgDocMeAuthor		: "Autor",
DlgDocMeCopy		: "Autorské práva",
DlgDocPreview		: "Náhľad",

// Templates Dialog
Templates			: "Šablóny",
DlgTemplatesTitle	: "Šablóny obsahu",
DlgTemplatesSelMsg	: "Prosím vyberte šablóny ma otvorenie v editore<br>(terajší obsah bude stratený):",
DlgTemplatesLoading	: "Nahrávam zoznam šablón. Čakajte prosím...",
DlgTemplatesNoTpl	: "(žiadne šablóny nenájdené)",

// About Dialog
DlgAboutAboutTab	: "O aplikáci",
DlgAboutBrowserInfoTab	: "Informácie o prehliadači",
DlgAboutLicenseTab	: "License",	//MISSING
DlgAboutVersion		: "verzie",
DlgAboutLicense		: "Licencované pod pravidlami GNU Lesser General Public License",
DlgAboutInfo		: "Viac informácií získate na"
}