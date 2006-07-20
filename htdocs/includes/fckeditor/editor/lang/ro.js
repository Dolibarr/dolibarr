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
 * File Name: ro.js
 * 	Romanian language file.
 * 
 * File Authors:
 * 		Adrian Nicoara
 * 		Ionut Traian Popa
 */

var FCKLang =
{
// Language direction : "ltr" (left to right) or "rtl" (right to left).
Dir					: "ltr",

ToolbarCollapse		: "Ascunde bara cu opţiuni",
ToolbarExpand		: "Expandează bara cu opţiuni",

// Toolbar Items and Context Menu
Save				: "Salvează",
NewPage				: "Pagină nouă",
Preview				: "Previzualizare",
Cut					: "Taie",
Copy				: "Copiază",
Paste				: "Adaugă",
PasteText			: "Adaugă ca text simplu",
PasteWord			: "Adaugă din Word",
Print				: "Printează",
SelectAll			: "Selectează tot",
RemoveFormat		: "Înlătură formatarea",
InsertLinkLbl		: "Link (Legătură web)",
InsertLink			: "Inserează/Editează link (legătură web)",
RemoveLink			: "Înlătură link (legătură web)",
Anchor				: "Inserează/Editează ancoră",
InsertImageLbl		: "Imagine",
InsertImage			: "Inserează/Editează imagine",
InsertFlashLbl		: "Flash",
InsertFlash			: "Inserează/Editează flash",
InsertTableLbl		: "Tabel",
InsertTable			: "Inserează/Editează tabel",
InsertLineLbl		: "Linie",
InsertLine			: "Inserează linie orizontă",
InsertSpecialCharLbl: "Caracter special",
InsertSpecialChar	: "Inserează caracter special",
InsertSmileyLbl		: "Figură expresivă (Emoticon)",
InsertSmiley		: "Inserează Figură expresivă (Emoticon)",
About				: "Despre FCKeditor",
Bold				: "Îngroşat (bold)",
Italic				: "Înclinat (italic)",
Underline			: "Subliniat (underline)",
StrikeThrough		: "Tăiat (strike through)",
Subscript			: "Indice (subscript)",
Superscript			: "Putere (superscript)",
LeftJustify			: "Aliniere la stânga",
CenterJustify		: "Aliniere centrală",
RightJustify		: "Aliniere la dreapta",
BlockJustify		: "Aliniere în bloc (Block Justify)",
DecreaseIndent		: "Scade indentarea",
IncreaseIndent		: "Creşte indentarea",
Undo				: "Starea anterioară (undo)",
Redo				: "Starea ulterioară (redo)",
NumberedListLbl		: "Listă numerotată",
NumberedList		: "Inserează/Şterge listă numerotată",
BulletedListLbl		: "Listă cu puncte",
BulletedList		: "Inserează/Şterge listă cu puncte",
ShowTableBorders	: "Arată marginile tabelului",
ShowDetails			: "Arată detalii",
Style				: "Stil",
FontFormat			: "Formatare",
Font				: "Font",
FontSize			: "Mărime",
TextColor			: "Culoarea textului",
BGColor				: "Coloarea fundalului",
Source				: "Sursa",
Find				: "Găseşte",
Replace				: "Înlocuieşte",
SpellCheck			: "Verifică text",
UniversalKeyboard	: "Tastatură universală",
PageBreakLbl		: "Separator de pagină (Page Break)",
PageBreak			: "Inserează separator de pagină (Page Break)",

Form			: "Formular (Form)",
Checkbox		: "Bifă (Checkbox)",
RadioButton		: "Buton radio (RadioButton)",
TextField		: "Câmp text (TextField)",
Textarea		: "Suprafaţă text (Textarea)",
HiddenField		: "Câmp ascuns (HiddenField)",
Button			: "Buton",
SelectionField	: "Câmp selecţie (SelectionField)",
ImageButton		: "Buton imagine (ImageButton)",

FitWindow		: "Maximizează mărimea editorului",

// Context Menu
EditLink			: "Editează Link",
CellCM				: "Celulă",
RowCM				: "Linie",
ColumnCM			: "Coloană",
InsertRow			: "Inserează linie",
DeleteRows			: "Şterge linii",
InsertColumn		: "Inserează coloană",
DeleteColumns		: "Şterge celule",
InsertCell			: "Inserează celulă",
DeleteCells			: "Şterge celule",
MergeCells			: "Uneşte celule",
SplitCell			: "Împarte celulă",
TableDelete			: "Şterge tabel",
CellProperties		: "Proprietăţile celulei",
TableProperties		: "Proprietăţile tabelului",
ImageProperties		: "Proprietăţile imaginii",
FlashProperties		: "Proprietăţile flash-ului",

AnchorProp			: "Proprietăţi ancoră",
ButtonProp			: "Proprietăţi buton",
CheckboxProp		: "Proprietăţi bifă (Checkbox)",
HiddenFieldProp		: "Proprietăţi câmp ascuns (Hidden Field)",
RadioButtonProp		: "Proprietăţi buton radio (Radio Button)",
ImageButtonProp		: "Proprietăţi buton imagine (Image Button)",
TextFieldProp		: "Proprietăţi câmp text (Text Field)",
SelectionFieldProp	: "Proprietăţi câmp selecţie (Selection Field)",
TextareaProp		: "Proprietăţi suprafaţă text (Textarea)",
FormProp			: "Proprietăţi formular (Form)",

FontFormats			: "Normal;Formatat;Adresa;Titlu 1;Titlu 2;Titlu 3;Titlu 4;Titlu 5;Titlu 6;Paragraf (DIV)",

// Alerts and Messages
ProcessingXHTML		: "Procesăm XHTML. Vă rugăm aşteptaţi...",
Done				: "Am terminat",
PasteWordConfirm	: "Textul pe care doriţi să-l adăugaţi pare a fi formatat pentru Word. Doriţi să-l curăţaţi de această formatare înainte de a-l adăuga?",
NotCompatiblePaste	: "Această facilitate e disponibilă doar pentru Microsoft Internet Explorer, versiunea 5.5 sau ulterioară. Vreţi să-l adăugaţi fără a-i fi înlăturat formatarea?",
UnknownToolbarItem	: "Obiectul \"%1\" din bara cu opţiuni necunoscut",
UnknownCommand		: "Comanda \"%1\" necunoscută",
NotImplemented		: "Comandă neimplementată",
UnknownToolbarSet	: "Grupul din bara cu opţiuni \"%1\" nu există",
NoActiveX			: "Setările de securitate ale programului dvs. cu care navigaţi pe internet (browser) pot limita anumite funcţionalităţi ale editorului. Pentru a evita asta, trebuie să activaţi opţiunea \"Run ActiveX controls and plug-ins\". Poate veţi întâlni erori sau veţi observa funcţionalităţi lipsă.",
BrowseServerBlocked : "The resources browser could not be opened. Asiguraţi-vă că nu e activ niciun \"popup blocker\" (funcţionalitate a programului de navigat (browser) sau a unui plug-in al acestuia de a bloca deschiderea unui noi ferestre).",
DialogBlocked		: "Nu a fost posibilă deschiderea unei ferestre de dialog. Asiguraţi-vă că nu e activ niciun \"popup blocker\" (funcţionalitate a programului de navigat (browser) sau a unui plug-in al acestuia de a bloca deschiderea unui noi ferestre).",

// Dialogs
DlgBtnOK			: "Bine",
DlgBtnCancel		: "Anulare",
DlgBtnClose			: "Închidere",
DlgBtnBrowseServer	: "Răsfoieşte server",
DlgAdvancedTag		: "Avansat",
DlgOpOther			: "<Altul>",
DlgInfoTab			: "Informaţii",
DlgAlertUrl			: "Vă rugăm să scrieţi URL-ul",

// General Dialogs Labels
DlgGenNotSet		: "<nesetat>",
DlgGenId			: "Id",
DlgGenLangDir		: "Direcţia cuvintelor",
DlgGenLangDirLtr	: "stânga-dreapta (LTR)",
DlgGenLangDirRtl	: "dreapta-stânga (RTL)",
DlgGenLangCode		: "Codul limbii",
DlgGenAccessKey		: "Tasta de acces",
DlgGenName			: "Nume",
DlgGenTabIndex		: "Indexul tabului",
DlgGenLongDescr		: "Descrierea lungă URL",
DlgGenClass			: "Clasele cu stilul paginii (CSS)",
DlgGenTitle			: "Titlul consultativ",
DlgGenContType		: "Tipul consultativ al titlului",
DlgGenLinkCharset	: "Setul de caractere al resursei legate",
DlgGenStyle			: "Stil",

// Image Dialog
DlgImgTitle			: "Proprietăţile imaginii",
DlgImgInfoTab		: "Informaţii despre imagine",
DlgImgBtnUpload		: "Trimite la server",
DlgImgURL			: "URL",
DlgImgUpload		: "Încarcă",
DlgImgAlt			: "Text alternativ",
DlgImgWidth			: "Lăţime",
DlgImgHeight		: "Înălţime",
DlgImgLockRatio		: "Păstrează proporţiile",
DlgBtnResetSize		: "Resetează mărimea",
DlgImgBorder		: "Margine",
DlgImgHSpace		: "HSpace",
DlgImgVSpace		: "VSpace",
DlgImgAlign			: "Aliniere",
DlgImgAlignLeft		: "Stânga",
DlgImgAlignAbsBottom: "Jos absolut (Abs Bottom)",
DlgImgAlignAbsMiddle: "Mijloc absolut (Abs Middle)",
DlgImgAlignBaseline	: "Linia de jos (Baseline)",
DlgImgAlignBottom	: "Jos",
DlgImgAlignMiddle	: "Mijloc",
DlgImgAlignRight	: "Dreapta",
DlgImgAlignTextTop	: "Text sus",
DlgImgAlignTop		: "Sus",
DlgImgPreview		: "Previzualizare",
DlgImgAlertUrl		: "Vă rugăm să scrieţi URL-ul imaginii",
DlgImgLinkTab		: "Link (Legătură web)",

// Flash Dialog
DlgFlashTitle		: "Proprietăţile flash-ului",
DlgFlashChkPlay		: "Rulează automat",
DlgFlashChkLoop		: "Repetă (Loop)",
DlgFlashChkMenu		: "Activează meniul flash",
DlgFlashScale		: "Scală",
DlgFlashScaleAll	: "Arată tot",
DlgFlashScaleNoBorder	: "Fără margini (No border)",
DlgFlashScaleFit	: "Potriveşte",

// Link Dialog
DlgLnkWindowTitle	: "Link (Legătură web)",
DlgLnkInfoTab		: "Informaţii despre link (Legătură web)",
DlgLnkTargetTab		: "Ţintă (Target)",

DlgLnkType			: "Tipul link-ului (al legăturii web)",
DlgLnkTypeURL		: "URL",
DlgLnkTypeAnchor	: "Ancoră în această pagină",
DlgLnkTypeEMail		: "E-Mail",
DlgLnkProto			: "Protocol",
DlgLnkProtoOther	: "<altul>",
DlgLnkURL			: "URL",
DlgLnkAnchorSel		: "Selectaţi o ancoră",
DlgLnkAnchorByName	: "după numele ancorei",
DlgLnkAnchorById	: "după Id-ul elementului",
DlgLnkNoAnchors		: "<Nicio ancoră disponibilă în document>",
DlgLnkEMail			: "Adresă de e-mail",
DlgLnkEMailSubject	: "Subiectul mesajului",
DlgLnkEMailBody		: "Conţinutul mesajului",
DlgLnkUpload		: "Încarcă",
DlgLnkBtnUpload		: "Trimite la server",

DlgLnkTarget		: "Ţintă (Target)",
DlgLnkTargetFrame	: "<frame>",
DlgLnkTargetPopup	: "<fereastra popup>",
DlgLnkTargetBlank	: "Fereastră nouă (_blank)",
DlgLnkTargetParent	: "Fereastra părinte (_parent)",
DlgLnkTargetSelf	: "Aceeaşi fereastră (_self)",
DlgLnkTargetTop		: "Fereastra din topul ierarhiei (_top)",
DlgLnkTargetFrameName	: "Numele frame-ului ţintă",
DlgLnkPopWinName	: "Numele ferestrei popup",
DlgLnkPopWinFeat	: "Proprietăţile ferestrei popup",
DlgLnkPopResize		: "Scalabilă",
DlgLnkPopLocation	: "Bara de locaţie",
DlgLnkPopMenu		: "Bara de meniu",
DlgLnkPopScroll		: "Scroll Bars",
DlgLnkPopStatus		: "Bara de status",
DlgLnkPopToolbar	: "Bara de opţiuni",
DlgLnkPopFullScrn	: "Tot ecranul (Full Screen)(IE)",
DlgLnkPopDependent	: "Dependent (Netscape)",
DlgLnkPopWidth		: "Lăţime",
DlgLnkPopHeight		: "Înălţime",
DlgLnkPopLeft		: "Poziţia la stânga",
DlgLnkPopTop		: "Poziţia la dreapta",

DlnLnkMsgNoUrl		: "Vă rugăm să scrieţi URL-ul",
DlnLnkMsgNoEMail	: "Vă rugăm să scrieţi adresa de e-mail",
DlnLnkMsgNoAnchor	: "Vă rugăm să selectaţi o ancoră",

// Color Dialog
DlgColorTitle		: "Selectează culoare",
DlgColorBtnClear	: "Curăţă",
DlgColorHighlight	: "Subliniază (Highlight)",
DlgColorSelected	: "Selectat",

// Smiley Dialog
DlgSmileyTitle		: "Inserează o figură expresivă (Emoticon)",

// Special Character Dialog
DlgSpecialCharTitle	: "Selectează caracter special",

// Table Dialog
DlgTableTitle		: "Proprietăţile tabelului",
DlgTableRows		: "Linii",
DlgTableColumns		: "Coloane",
DlgTableBorder		: "Mărimea marginii",
DlgTableAlign		: "Aliniament",
DlgTableAlignNotSet	: "<Nesetat>",
DlgTableAlignLeft	: "Stânga",
DlgTableAlignCenter	: "Centru",
DlgTableAlignRight	: "Dreapta",
DlgTableWidth		: "Lăţime",
DlgTableWidthPx		: "pixeli",
DlgTableWidthPc		: "procente",
DlgTableHeight		: "Înălţime",
DlgTableCellSpace	: "Spaţiu între celule",
DlgTableCellPad		: "Spaţiu în cadrul celulei",
DlgTableCaption		: "Titlu (Caption)",
DlgTableSummary		: "Rezumat",

// Table Cell Dialog
DlgCellTitle		: "Proprietăţile celulei",
DlgCellWidth		: "Lăţime",
DlgCellWidthPx		: "pixeli",
DlgCellWidthPc		: "procente",
DlgCellHeight		: "Înălţime",
DlgCellWordWrap		: "Desparte cuvintele (Wrap)",
DlgCellWordWrapNotSet	: "<Nesetat>",
DlgCellWordWrapYes	: "Da",
DlgCellWordWrapNo	: "Nu",
DlgCellHorAlign		: "Aliniament orizontal",
DlgCellHorAlignNotSet	: "<Nesetat>",
DlgCellHorAlignLeft	: "Stânga",
DlgCellHorAlignCenter	: "Centru",
DlgCellHorAlignRight: "Dreapta",
DlgCellVerAlign		: "Aliniament vertical",
DlgCellVerAlignNotSet	: "<Nesetat>",
DlgCellVerAlignTop	: "Sus",
DlgCellVerAlignMiddle	: "Mijloc",
DlgCellVerAlignBottom	: "Jos",
DlgCellVerAlignBaseline	: "Linia de jos (Baseline)",
DlgCellRowSpan		: "Lungimea în linii (Span)",
DlgCellCollSpan		: "Lungimea în coloane (Span)",
DlgCellBackColor	: "Culoarea fundalului",
DlgCellBorderColor	: "Culoarea marginii",
DlgCellBtnSelect	: "Selectaţi...",

// Find Dialog
DlgFindTitle		: "Găseşte",
DlgFindFindBtn		: "Găseşte",
DlgFindNotFoundMsg	: "Textul specificat nu a fost găsit.",

// Replace Dialog
DlgReplaceTitle			: "Replace",
DlgReplaceFindLbl		: "Găseşte:",
DlgReplaceReplaceLbl	: "Înlocuieşte cu:",
DlgReplaceCaseChk		: "Deosebeşte majuscule de minuscule (Match case)",
DlgReplaceReplaceBtn	: "Înlocuieşte",
DlgReplaceReplAllBtn	: "Înlocuieşte tot",
DlgReplaceWordChk		: "Doar cuvintele întregi",

// Paste Operations / Dialog
PasteErrorPaste	: "Setările de securitate ale navigatorului (browser) pe care îl folosiţi nu permit editorului să execute automat operaţiunea de adăugare. Vă rugăm folosiţi tastatura (Ctrl+V).",
PasteErrorCut	: "Setările de securitate ale navigatorului (browser) pe care îl folosiţi nu permit editorului să execute automat operaţiunea de tăiere. Vă rugăm folosiţi tastatura (Ctrl+X).",
PasteErrorCopy	: "Setările de securitate ale navigatorului (browser) pe care îl folosiţi nu permit editorului să execute automat operaţiunea de copiere. Vă rugăm folosiţi tastatura (Ctrl+C).",

PasteAsText		: "Adaugă ca text simplu (Plain Text)",
PasteFromWord	: "Adaugă din Word",

DlgPasteMsg2	: "Vă rugăm adăugaţi în căsuţa următoare folosind tastatura (<STRONG>Ctrl+V</STRONG>) şi apăsaţi <STRONG>OK</STRONG>.",
DlgPasteIgnoreFont		: "Ignoră definiţiile Font Face",
DlgPasteRemoveStyles	: "Şterge definiţiile stilurilor",
DlgPasteCleanBox		: "Şterge căsuţa",

// Color Picker
ColorAutomatic	: "Automatic",
ColorMoreColors	: "Mai multe culori...",

// Document Properties
DocProps		: "Proprietăţile documentului",

// Anchor Dialog
DlgAnchorTitle		: "Proprietăţile ancorei",
DlgAnchorName		: "Numele ancorei",
DlgAnchorErrorName	: "Vă rugăm scrieţi numele ancorei",

// Speller Pages Dialog
DlgSpellNotInDic		: "Nu e în dicţionar",
DlgSpellChangeTo		: "Schimbă în",
DlgSpellBtnIgnore		: "Ignoră",
DlgSpellBtnIgnoreAll	: "Ignoră toate",
DlgSpellBtnReplace		: "Înlocuieşte",
DlgSpellBtnReplaceAll	: "Înlocuieşte tot",
DlgSpellBtnUndo			: "Starea anterioară (undo)",
DlgSpellNoSuggestions	: "- Fără sugestii -",
DlgSpellProgress		: "Verificarea textului în desfăşurare...",
DlgSpellNoMispell		: "Verificarea textului terminată: Nicio greşeală găsită",
DlgSpellNoChanges		: "Verificarea textului terminată: Niciun cuvânt modificat",
DlgSpellOneChange		: "Verificarea textului terminată: Un cuvânt modificat",
DlgSpellManyChanges		: "Verificarea textului terminată: 1% cuvinte modificate",

IeSpellDownload			: "Unealta pentru verificat textul (Spell checker) neinstalată. Doriţi să o descărcaţi acum?",

// Button Dialog
DlgButtonText	: "Text (Valoare)",
DlgButtonType	: "Tip",

// Checkbox and Radio Button Dialogs
DlgCheckboxName		: "Nume",
DlgCheckboxValue	: "Valoare",
DlgCheckboxSelected	: "Selectat",

// Form Dialog
DlgFormName		: "Nume",
DlgFormAction	: "Acţiune",
DlgFormMethod	: "Metodă",

// Select Field Dialog
DlgSelectName		: "Nume",
DlgSelectValue		: "Valoare",
DlgSelectSize		: "Mărime",
DlgSelectLines		: "linii",
DlgSelectChkMulti	: "Permite selecţii multiple",
DlgSelectOpAvail	: "Opţiuni disponibile",
DlgSelectOpText		: "Text",
DlgSelectOpValue	: "Valoare",
DlgSelectBtnAdd		: "Adaugă",
DlgSelectBtnModify	: "Modifică",
DlgSelectBtnUp		: "Sus",
DlgSelectBtnDown	: "Jos",
DlgSelectBtnSetValue : "Setează ca valoare selectată",
DlgSelectBtnDelete	: "Şterge",

// Textarea Dialog
DlgTextareaName	: "Nume",
DlgTextareaCols	: "Coloane",
DlgTextareaRows	: "Linii",

// Text Field Dialog
DlgTextName			: "Nume",
DlgTextValue		: "Valoare",
DlgTextCharWidth	: "Lărgimea caracterului",
DlgTextMaxChars		: "Caractere maxime",
DlgTextType			: "Tip",
DlgTextTypeText		: "Text",
DlgTextTypePass		: "Parolă",

// Hidden Field Dialog
DlgHiddenName	: "Nume",
DlgHiddenValue	: "Valoare",

// Bulleted List Dialog
BulletedListProp	: "Proprietăţile listei punctate (Bulleted List)",
NumberedListProp	: "Proprietăţile listei numerotate (Numbered List)",
DlgLstType			: "Tip",
DlgLstTypeCircle	: "Cerc",
DlgLstTypeDisc		: "Disc",
DlgLstTypeSquare	: "Pătrat",
DlgLstTypeNumbers	: "Numere (1, 2, 3)",
DlgLstTypeLCase		: "Minuscule-litere mici (a, b, c)",
DlgLstTypeUCase		: "Majuscule (A, B, C)",
DlgLstTypeSRoman	: "Cifre romane mici (i, ii, iii)",
DlgLstTypeLRoman	: "Cifre romane mari (I, II, III)",

// Document Properties Dialog
DlgDocGeneralTab	: "General",
DlgDocBackTab		: "Fundal",
DlgDocColorsTab		: "Culori si margini",
DlgDocMetaTab		: "Meta Data",

DlgDocPageTitle		: "Titlul paginii",
DlgDocLangDir		: "Descrierea limbii",
DlgDocLangDirLTR	: "stânga-dreapta (LTR)",
DlgDocLangDirRTL	: "dreapta-stânga (RTL)",
DlgDocLangCode		: "Codul limbii",
DlgDocCharSet		: "Encoding setului de caractere",
DlgDocCharSetOther	: "Alt encoding al setului de caractere",

DlgDocDocType		: "Document Type Heading",
DlgDocDocTypeOther	: "Alt Document Type Heading",
DlgDocIncXHTML		: "Include declaraţii XHTML",
DlgDocBgColor		: "Culoarea fundalului (Background Color)",
DlgDocBgImage		: "URL-ul imaginii din fundal (Background Image URL)",
DlgDocBgNoScroll	: "Fundal neflotant, fix (Nonscrolling Background)",
DlgDocCText			: "Text",
DlgDocCLink			: "Link (Legătură web)",
DlgDocCVisited		: "Link (Legătură web) vizitat",
DlgDocCActive		: "Link (Legătură web) activ",
DlgDocMargins		: "Marginile paginii",
DlgDocMaTop			: "Sus",
DlgDocMaLeft		: "Stânga",
DlgDocMaRight		: "Dreapta",
DlgDocMaBottom		: "Jos",
DlgDocMeIndex		: "Cuvinte cheie după care se va indexa documentul (separate prin virgulă)",
DlgDocMeDescr		: "Descrierea documentului",
DlgDocMeAuthor		: "Autor",
DlgDocMeCopy		: "Drepturi de autor",
DlgDocPreview		: "Previzualizare",

// Templates Dialog
Templates			: "Template-uri (şabloane)",
DlgTemplatesTitle	: "Template-uri (şabloane) de conţinut",
DlgTemplatesSelMsg	: "Vă rugăm selectaţi template-ul (şablonul) ce se va deschide în editor<br>(conţinutul actual va fi pierdut):",
DlgTemplatesLoading	: "Se încarcă lista cu template-uri (şabloane). Vă rugăm aşteptaţi...",
DlgTemplatesNoTpl	: "(Niciun template (şablon) definit)",

// About Dialog
DlgAboutAboutTab	: "Despre",
DlgAboutBrowserInfoTab	: "Informaţii browser",
DlgAboutLicenseTab	: "Licenţă",
DlgAboutVersion		: "versiune",
DlgAboutLicense		: "Licenţiat sub termenii GNU Lesser General Public License",
DlgAboutInfo		: "Pentru informaţii amănunţite, vizitaţi"
}