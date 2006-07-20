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
 * File Name: ca.js
 * 	Catalan language file.
 * 
 * File Authors:
 * 		Jordi Cerdan (nan@myp.ad)
 * 		Marc Folch (mcus21@gmail.com)
 */

var FCKLang =
{
// Language direction : "ltr" (left to right) or "rtl" (right to left).
Dir					: "ltr",

ToolbarCollapse		: "Collapsa la barra",
ToolbarExpand		: "Amplia la barra",

// Toolbar Items and Context Menu
Save				: "Desa",
NewPage				: "Nova Pgina",
Preview				: "Vista Prvia",
Cut					: "Retalla",
Copy				: "Copia",
Paste				: "Enganxa",
PasteText			: "Enganxa com a text no formatat",
PasteWord			: "Enganxa des del Word",
Print				: "Imprimeix",
SelectAll			: "Selecciona-ho tot",
RemoveFormat		: "Elimina Format",
InsertLinkLbl		: "Enlla",
InsertLink			: "Insereix/Edita enlla",
RemoveLink			: "Elimina enlla",
Anchor				: "Insereix/Edita ncora",
InsertImageLbl		: "Imatge",
InsertImage			: "Insereix/Edita imatge",
InsertFlashLbl		: "Flash",
InsertFlash			: "Insereix/Edita Flash",
InsertTableLbl		: "Taula",
InsertTable			: "Insereix/Edita taula",
InsertLineLbl		: "Lnia",
InsertLine			: "Insereix lnia horitzontal",
InsertSpecialCharLbl: "Carcter Especial",
InsertSpecialChar	: "Insereix carcter especial",
InsertSmileyLbl		: "Icona",
InsertSmiley		: "Insereix icona",
About				: "Quant a FCKeditor",
Bold				: "Negreta",
Italic				: "Cursiva",
Underline			: "Subratllat",
StrikeThrough		: "Barrat",
Subscript			: "Subndex",
Superscript			: "Superndex",
LeftJustify			: "Aliniament esquerra",
CenterJustify		: "Aliniament centrat",
RightJustify		: "Aliniament dreta",
BlockJustify		: "Justifica",
DecreaseIndent		: "Sagna el text",
IncreaseIndent		: "Treu el sagnat del text",
Undo				: "Desfs",
Redo				: "Refs",
NumberedListLbl		: "Llista numerada",
NumberedList		: "Aplica o elimina la llista numerada",
BulletedListLbl		: "Llista de pics",
BulletedList		: "Aplica o elimina la llista de pics",
ShowTableBorders	: "Mostra les vores de les taules",
ShowDetails			: "Mostra detalls",
Style				: "Estil",
FontFormat			: "Format",
Font				: "Tipus de lletra",
FontSize			: "Mida",
TextColor			: "Color de Text",
BGColor				: "Color de Fons",
Source				: "Codi font",
Find				: "Cerca",
Replace				: "Reemplaa",
SpellCheck			: "Revisa l'ortografia",
UniversalKeyboard	: "Teclat universal",
PageBreakLbl		: "Salt de pgina",
PageBreak			: "Insereix salt de pgina",

Form			: "Formulari",
Checkbox		: "Casella de verificaci",
RadioButton		: "Bot d'opci",
TextField		: "Camp de text",
Textarea		: "rea de text",
HiddenField		: "Camp ocult",
Button			: "Bot",
SelectionField	: "Camp de selecci",
ImageButton		: "Bot d'imatge",

FitWindow		: "Maximiza la mida de l'editor",

// Context Menu
EditLink			: "Edita l'enlla",
CellCM				: "Cella",
RowCM				: "Fila",
ColumnCM			: "Columna",
InsertRow			: "Insereix una fila",
DeleteRows			: "Suprimeix una fila",
InsertColumn		: "Afegeix una columna",
DeleteColumns		: "Suprimeix una columna",
InsertCell			: "Insereix una cella",
DeleteCells			: "Suprimeix les celles",
MergeCells			: "Fusiona les celles",
SplitCell			: "Separa les celles",
TableDelete			: "Suprimeix la taula",
CellProperties		: "Propietats de la cella",
TableProperties		: "Propietats de la taula",
ImageProperties		: "Propietats de la imatge",
FlashProperties		: "Propietats del Flash",

AnchorProp			: "Propietats de l'ncora",
ButtonProp			: "Propietats del bot",
CheckboxProp		: "Propietats de la casella de verificaci",
HiddenFieldProp		: "Propietats del camp ocult",
RadioButtonProp		: "Propietats del bot d'opci",
ImageButtonProp		: "Propietats del bot d'imatge",
TextFieldProp		: "Propietats del camp de text",
SelectionFieldProp	: "Propietats del camp de selecci",
TextareaProp		: "Propietats de l'rea de text",
FormProp			: "Propietats del formulari",

FontFormats			: "Normal;Formatejat;Adrea;Encapalament 1;Encapalament 2;Encapalament 3;Encapalament 4;Encapalament 5;Encapalament 6",

// Alerts and Messages
ProcessingXHTML		: "Processant XHTML. Si us plau esperi...",
Done				: "Fet",
PasteWordConfirm	: "El text que voleu enganxar sembla provenir de Word. Voleu netejar aquest text abans que sigui enganxat?",
NotCompatiblePaste	: "Aquesta funci s disponible per a Internet Explorer versi 5.5 o superior. Voleu enganxar sense netejar?",
UnknownToolbarItem	: "Element de la barra d'eines desconegut \"%1\"",
UnknownCommand		: "Nom de comanda desconegut \"%1\"",
NotImplemented		: "Mtode no implementat",
UnknownToolbarSet	: "Conjunt de barra d'eines \"%1\" inexistent",
NoActiveX			: "Les preferncies del navegador poden limitar algunes funcions d'aquest editor. Cal habilitar l'opci \"Executa controls ActiveX i plug-ins\". Poden sorgir errors i poden faltar algunes funcions.",
BrowseServerBlocked : "El visualitzador de recursos no s'ha pogut obrir. Assegura't de que els bloquejos de finestres emergents estan desactivats.",
DialogBlocked		: "No ha estat possible obrir una finestra de dileg. Assegura't de que els bloquejos de finestres emergents estan desactivats.",

// Dialogs
DlgBtnOK			: "D'acord",
DlgBtnCancel		: "Cancella",
DlgBtnClose			: "Tanca",
DlgBtnBrowseServer	: "Veure servidor",
DlgAdvancedTag		: "Avanat",
DlgOpOther			: "Altres",
DlgInfoTab			: "Info",
DlgAlertUrl			: "Si us plau, afegiu la URL",

// General Dialogs Labels
DlgGenNotSet		: "<no definit>",
DlgGenId			: "Id",
DlgGenLangDir		: "Direcci de l'idioma",
DlgGenLangDirLtr	: "D'esquerra a dreta (LTR)",
DlgGenLangDirRtl	: "De dreta a esquerra (RTL)",
DlgGenLangCode		: "Codi d'idioma",
DlgGenAccessKey		: "Clau d'accs",
DlgGenName			: "Nom",
DlgGenTabIndex		: "Index de Tab",
DlgGenLongDescr		: "Descripci llarga de la URL",
DlgGenClass			: "Classes del full d'estil",
DlgGenTitle			: "Ttol consultiu",
DlgGenContType		: "Tipus de contingut consultiu",
DlgGenLinkCharset	: "Conjunt de carcters font enllaat",
DlgGenStyle			: "Estil",

// Image Dialog
DlgImgTitle			: "Propietats de la imatge",
DlgImgInfoTab		: "Informaci de la imatge",
DlgImgBtnUpload		: "Envia-la al servidor",
DlgImgURL			: "URL",
DlgImgUpload		: "Puja",
DlgImgAlt			: "Text alternatiu",
DlgImgWidth			: "Amplada",
DlgImgHeight		: "Alada",
DlgImgLockRatio		: "Bloqueja les proporcions",
DlgBtnResetSize		: "Restaura la mida",
DlgImgBorder		: "Vora",
DlgImgHSpace		: "Espaiat horit.",
DlgImgVSpace		: "Espaiat vert.",
DlgImgAlign			: "Alineaci",
DlgImgAlignLeft		: "Ajusta a l'esquerra",
DlgImgAlignAbsBottom: "Abs Bottom",
DlgImgAlignAbsMiddle: "Abs Middle",
DlgImgAlignBaseline	: "Baseline",
DlgImgAlignBottom	: "Bottom",
DlgImgAlignMiddle	: "Middle",
DlgImgAlignRight	: "Ajusta a la dreta",
DlgImgAlignTextTop	: "Text Top",
DlgImgAlignTop		: "Top",
DlgImgPreview		: "Vista prvia",
DlgImgAlertUrl		: "Si us plau, escriviu la URL de la imatge",
DlgImgLinkTab		: "Enlla",

// Flash Dialog
DlgFlashTitle		: "Propietats del Flash",
DlgFlashChkPlay		: "Reproduci automtica",
DlgFlashChkLoop		: "Bucle",
DlgFlashChkMenu		: "Habilita men Flash",
DlgFlashScale		: "Escala",
DlgFlashScaleAll	: "Mostra-ho tot",
DlgFlashScaleNoBorder	: "Sense vores",
DlgFlashScaleFit	: "Mida exacta",

// Link Dialog
DlgLnkWindowTitle	: "Enlla",
DlgLnkInfoTab		: "Informaci de l'enlla",
DlgLnkTargetTab		: "Dest",

DlgLnkType			: "Tipus d'enlla",
DlgLnkTypeURL		: "URL",
DlgLnkTypeAnchor	: "ncora en aquesta pgina",
DlgLnkTypeEMail		: "E-Mail",
DlgLnkProto			: "Protocol",
DlgLnkProtoOther	: "<altra>",
DlgLnkURL			: "URL",
DlgLnkAnchorSel		: "Selecciona una ncora",
DlgLnkAnchorByName	: "Per nom d'ncora",
DlgLnkAnchorById	: "Per Id d'element",
DlgLnkNoAnchors		: "<No hi ha ncores disponibles en aquest document>",
DlgLnkEMail			: "Adrea d'E-Mail",
DlgLnkEMailSubject	: "Assumpte del missatge",
DlgLnkEMailBody		: "Cos del missatge",
DlgLnkUpload		: "Puja",
DlgLnkBtnUpload		: "Envia al servidor",

DlgLnkTarget		: "Dest",
DlgLnkTargetFrame	: "<marc>",
DlgLnkTargetPopup	: "<finestra popup>",
DlgLnkTargetBlank	: "Nova finestra (_blank)",
DlgLnkTargetParent	: "Finestra pare (_parent)",
DlgLnkTargetSelf	: "Mateixa finestra (_self)",
DlgLnkTargetTop		: "Finestra Major (_top)",
DlgLnkTargetFrameName	: "Nom del marc de dest",
DlgLnkPopWinName	: "Nom finestra popup",
DlgLnkPopWinFeat	: "Caracterstiques finestra popup",
DlgLnkPopResize		: "Redimensionable",
DlgLnkPopLocation	: "Barra d'adrea",
DlgLnkPopMenu		: "Barra de men",
DlgLnkPopScroll		: "Barres d'scroll",
DlgLnkPopStatus		: "Barra d'estat",
DlgLnkPopToolbar	: "Barra d'eines",
DlgLnkPopFullScrn	: "Pantalla completa (IE)",
DlgLnkPopDependent	: "Depenent (Netscape)",
DlgLnkPopWidth		: "Amplada",
DlgLnkPopHeight		: "Alada",
DlgLnkPopLeft		: "Posici esquerra",
DlgLnkPopTop		: "Posici dalt",

DlnLnkMsgNoUrl		: "Si us plau, escrigui l'enlla URL",
DlnLnkMsgNoEMail	: "Si us plau, escrigui l'adrea e-mail",
DlnLnkMsgNoAnchor	: "Si us plau, escrigui l'ncora",

// Color Dialog
DlgColorTitle		: "Selecciona el color",
DlgColorBtnClear	: "Neteja",
DlgColorHighlight	: "Reala",
DlgColorSelected	: "Selecciona",

// Smiley Dialog
DlgSmileyTitle		: "Insereix una icona",

// Special Character Dialog
DlgSpecialCharTitle	: "Selecciona el carcter especial",

// Table Dialog
DlgTableTitle		: "Propietats de la taula",
DlgTableRows		: "Files",
DlgTableColumns		: "Columnes",
DlgTableBorder		: "Tamany vora",
DlgTableAlign		: "Alineaci",
DlgTableAlignNotSet	: "<No Definit>",
DlgTableAlignLeft	: "Esquerra",
DlgTableAlignCenter	: "Centre",
DlgTableAlignRight	: "Dreta",
DlgTableWidth		: "Amplada",
DlgTableWidthPx		: "pxels",
DlgTableWidthPc		: "percentatge",
DlgTableHeight		: "Alada",
DlgTableCellSpace	: "Espaiat de celles",
DlgTableCellPad		: "Encoixinament de celles",
DlgTableCaption		: "Ttol",
DlgTableSummary		: "Resum",

// Table Cell Dialog
DlgCellTitle		: "Propietats de la cella",
DlgCellWidth		: "Amplada",
DlgCellWidthPx		: "pxels",
DlgCellWidthPc		: "percentatge",
DlgCellHeight		: "Alada",
DlgCellWordWrap		: "Ajust de paraula",
DlgCellWordWrapNotSet	: "<No Definit>",
DlgCellWordWrapYes	: "Si",
DlgCellWordWrapNo	: "No",
DlgCellHorAlign		: "Alineaci horitzontal",
DlgCellHorAlignNotSet	: "<No Definit>",
DlgCellHorAlignLeft	: "Esquerra",
DlgCellHorAlignCenter	: "Centre",
DlgCellHorAlignRight: "Dreta",
DlgCellVerAlign		: "Alineaci vertical",
DlgCellVerAlignNotSet	: "<No definit>",
DlgCellVerAlignTop	: "Top",
DlgCellVerAlignMiddle	: "Middle",
DlgCellVerAlignBottom	: "Bottom",
DlgCellVerAlignBaseline	: "Baseline",
DlgCellRowSpan		: "Rows Span",
DlgCellCollSpan		: "Columns Span",
DlgCellBackColor	: "Color de fons",
DlgCellBorderColor	: "Color de la vora",
DlgCellBtnSelect	: "Seleccioneu...",

// Find Dialog
DlgFindTitle		: "Cerca",
DlgFindFindBtn		: "Cerca",
DlgFindNotFoundMsg	: "El text especificat no s'ha trobat.",

// Replace Dialog
DlgReplaceTitle			: "Reemplaa",
DlgReplaceFindLbl		: "Cerca:",
DlgReplaceReplaceLbl	: "Remplaa amb:",
DlgReplaceCaseChk		: "Sensible a majscules",
DlgReplaceReplaceBtn	: "Reemplaa",
DlgReplaceReplAllBtn	: "Reemplaa'ls tots",
DlgReplaceWordChk		: "Cerca paraula completa",

// Paste Operations / Dialog
PasteErrorPaste	: "La seguretat del vostre navegador no permet executar automticament les operacions d'enganxat. Si us plau, utilitzeu el teclat (Ctrl+V).",
PasteErrorCut	: "La seguretat del vostre navegador no permet executar automticament les operacions de retallar. Si us plau, utilitzeu el teclat (Ctrl+X).",
PasteErrorCopy	: "La seguretat del vostre navegador no permet executar automticament les operacions de copiar. Si us plau, utilitzeu el teclat (Ctrl+C).",

PasteAsText		: "Enganxa com a text sense format",
PasteFromWord	: "Enganxa com a Word",

DlgPasteMsg2	: "Si us plau, enganxeu dins del segent camp utilitzant el teclat (<STRONG>Ctrl+V</STRONG>) i premeu <STRONG>OK</STRONG>.",
DlgPasteIgnoreFont		: "Ignora definicions de font",
DlgPasteRemoveStyles	: "Elimina definicions d'estil",
DlgPasteCleanBox		: "Neteja camp",

// Color Picker
ColorAutomatic	: "Automtic",
ColorMoreColors	: "Ms colors...",

// Document Properties
DocProps		: "Propietats del document",

// Anchor Dialog
DlgAnchorTitle		: "Propietats de l'ncora",
DlgAnchorName		: "Nom de l'ncora",
DlgAnchorErrorName	: "Si us plau, escriviu el nom de l'ancora",

// Speller Pages Dialog
DlgSpellNotInDic		: "No s al diccionari",
DlgSpellChangeTo		: "Canvia a",
DlgSpellBtnIgnore		: "Ignora",
DlgSpellBtnIgnoreAll	: "Ignora-les totes",
DlgSpellBtnReplace		: "Canvia",
DlgSpellBtnReplaceAll	: "Canvia-les totes",
DlgSpellBtnUndo			: "Desfs",
DlgSpellNoSuggestions	: "Cap sugerncia",
DlgSpellProgress		: "Comprovaci ortogrfica en progrs",
DlgSpellNoMispell		: "Comprovaci ortogrfica completada",
DlgSpellNoChanges		: "Comprovaci ortogrfica: cap paraulada canviada",
DlgSpellOneChange		: "Comprovaci ortogrfica: una paraula canviada",
DlgSpellManyChanges		: "Comprovaci ortogrfica %1 paraules canviades",

IeSpellDownload			: "Comprovaci ortogrfica no installada. Voleu descarregar-ho ara?",

// Button Dialog
DlgButtonText	: "Text (Valor)",
DlgButtonType	: "Tipus",

// Checkbox and Radio Button Dialogs
DlgCheckboxName		: "Nom",
DlgCheckboxValue	: "Valor",
DlgCheckboxSelected	: "Seleccionat",

// Form Dialog
DlgFormName		: "Nom",
DlgFormAction	: "Acci",
DlgFormMethod	: "Mtode",

// Select Field Dialog
DlgSelectName		: "Nom",
DlgSelectValue		: "Valor",
DlgSelectSize		: "Tamany",
DlgSelectLines		: "Lnies",
DlgSelectChkMulti	: "Permet mltiples seleccions",
DlgSelectOpAvail	: "Opcions disponibles",
DlgSelectOpText		: "Text",
DlgSelectOpValue	: "Valor",
DlgSelectBtnAdd		: "Afegeix",
DlgSelectBtnModify	: "Modifica",
DlgSelectBtnUp		: "Amunt",
DlgSelectBtnDown	: "Avall",
DlgSelectBtnSetValue : "Selecciona per defecte",
DlgSelectBtnDelete	: "Elimina",

// Textarea Dialog
DlgTextareaName	: "Nom",
DlgTextareaCols	: "Columnes",
DlgTextareaRows	: "Files",

// Text Field Dialog
DlgTextName			: "Nom",
DlgTextValue		: "Valor",
DlgTextCharWidth	: "Amplada de carcter",
DlgTextMaxChars		: "Mxim de carcters",
DlgTextType			: "Tipus",
DlgTextTypeText		: "Text",
DlgTextTypePass		: "Contrasenya",

// Hidden Field Dialog
DlgHiddenName	: "Nom",
DlgHiddenValue	: "Valor",

// Bulleted List Dialog
BulletedListProp	: "Propietats de llista marcada",
NumberedListProp	: "Propietats de llista numerada",
DlgLstType			: "Tipus",
DlgLstTypeCircle	: "Cercle",
DlgLstTypeDisc		: "Disc",	//MISSING
DlgLstTypeSquare	: "Quadrat",
DlgLstTypeNumbers	: "Nmeros (1, 2, 3)",
DlgLstTypeLCase		: "Lletres minscules (a, b, c)",
DlgLstTypeUCase		: "Lletres majscules (A, B, C)",
DlgLstTypeSRoman	: "Nmeros romans minscules (i, ii, iii)",
DlgLstTypeLRoman	: "Nmeros romans majscules (I, II, III)",

// Document Properties Dialog
DlgDocGeneralTab	: "General",
DlgDocBackTab		: "Fons",
DlgDocColorsTab		: "Colors i marges",
DlgDocMetaTab		: "Dades Meta",

DlgDocPageTitle		: "Ttol de la pgina",
DlgDocLangDir		: "Direcci llenguatge",
DlgDocLangDirLTR	: "Esquerra a dreta (LTR)",
DlgDocLangDirRTL	: "Dreta a esquerra (RTL)",
DlgDocLangCode		: "Codi de llenguatge",
DlgDocCharSet		: "Codificaci de conjunt de carcters",
DlgDocCharSetOther	: "Altra codificaci de conjunt de carcters",

DlgDocDocType		: "Capalera de tipus de document",
DlgDocDocTypeOther	: "Altra Capalera de tipus de document",
DlgDocIncXHTML		: "Incloure declaracions XHTML",
DlgDocBgColor		: "Color de fons",
DlgDocBgImage		: "URL de la imatge de fons",
DlgDocBgNoScroll	: "Fons fixe",
DlgDocCText			: "Text",
DlgDocCLink			: "Enlla",
DlgDocCVisited		: "Enlla visitat",
DlgDocCActive		: "Enlla actiu",
DlgDocMargins		: "Marges de pgina",
DlgDocMaTop			: "Cap",
DlgDocMaLeft		: "Esquerra",
DlgDocMaRight		: "Dreta",
DlgDocMaBottom		: "Peu",
DlgDocMeIndex		: "Mots clau per a indexaci (separats per coma)",
DlgDocMeDescr		: "Descripci del document",
DlgDocMeAuthor		: "Autor",
DlgDocMeCopy		: "Copyright",
DlgDocPreview		: "Vista prvia",

// Templates Dialog
Templates			: "Plantilles",
DlgTemplatesTitle	: "Contingut plantilles",
DlgTemplatesSelMsg	: "Si us plau, seleccioneu la plantilla per obrir en l'editor<br>(el contingut actual no ser enregistrat):",
DlgTemplatesLoading	: "Carregant la llista de plantilles. Si us plau, espereu...",
DlgTemplatesNoTpl	: "(No hi ha plantilles definides)",

// About Dialog
DlgAboutAboutTab	: "Quant a",
DlgAboutBrowserInfoTab	: "Informaci del navegador",
DlgAboutLicenseTab	: "Llicncia",
DlgAboutVersion		: "versi",
DlgAboutLicense		: "Segons els termes de la Llicncia GNU Lesser General Public License",
DlgAboutInfo		: "Per a ms informaci aneu a"
}