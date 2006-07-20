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
 * File Name: nl.js
 * 	Dutch language file.
 * 
 * File Authors:
 * 		Bram Crins (bcrins@realdesign.nl)
 * 		Aaron van Geffen (aaron@aaronweb.net)
 */

var FCKLang =
{
// Language direction : "ltr" (left to right) or "rtl" (right to left).
Dir					: "ltr",

ToolbarCollapse		: "Menubalk inklappen",
ToolbarExpand		: "Menubalk uitklappen",

// Toolbar Items and Context Menu
Save				: "Opslaan",
NewPage				: "Nieuwe pagina",
Preview				: "Voorbeeld",
Cut					: "Knippen",
Copy				: "Kopiëren",
Paste				: "Plakken",
PasteText			: "Plakken als platte tekst",
PasteWord			: "Plakken als Word-gegevens",
Print				: "Printen",
SelectAll			: "Alles selecteren",
RemoveFormat		: "Opmaak verwijderen",
InsertLinkLbl		: "Link",
InsertLink			: "Invoegen/Wijzigen link",
RemoveLink			: "Verwijderen link",
Anchor				: "Interne link",
InsertImageLbl		: "Afbeelding",
InsertImage			: "Invoegen/Wijzigen afbeelding",
InsertFlashLbl		: "Flash",
InsertFlash			: "Invoegen/Wijzigen Flash",
InsertTableLbl		: "Tabel",
InsertTable			: "Invoegen/Wijzigen tabel",
InsertLineLbl		: "Lijn",
InsertLine			: "Invoegen horizontale lijn",
InsertSpecialCharLbl: "Speciale tekens",
InsertSpecialChar	: "Speciaal teken invoegen",
InsertSmileyLbl		: "Smiley",
InsertSmiley		: "Smiley invoegen",
About				: "Over FCKeditor",
Bold				: "Vet",
Italic				: "Schuingedrukt",
Underline			: "Onderstreept",
StrikeThrough		: "Doorhalen",
Subscript			: "Subscript",
Superscript			: "Superscript",
LeftJustify			: "Links uitlijnen",
CenterJustify		: "Centreren",
RightJustify		: "Rechts uitlijnen",
BlockJustify		: "Uitvullen",
DecreaseIndent		: "Oplopenend",
IncreaseIndent		: "Aflopend",
Undo				: "Ongedaan maken",
Redo				: "Opnieuw",
NumberedListLbl		: "Genummerde lijst",
NumberedList		: "Invoegen/Verwijderen genummerde lijst",
BulletedListLbl		: "Opsomming",
BulletedList		: "Invoegen/Verwijderen opsomming",
ShowTableBorders	: "Randen tabel weergeven",
ShowDetails			: "Details weergeven",
Style				: "Stijl",
FontFormat			: "Opmaak",
Font				: "Lettertype",
FontSize			: "Grootte",
TextColor			: "Tekstkleur",
BGColor				: "Achtergrondkleur",
Source				: "Code",
Find				: "Zoeken",
Replace				: "Vervangen",
SpellCheck			: "Spellingscontrole",
UniversalKeyboard	: "Universeel toetsenbord",
PageBreakLbl		: "Pagina-einde",
PageBreak			: "Pagina-einde invoegen",

Form			: "Formulier",
Checkbox		: "Aanvinkvakje",
RadioButton		: "Selectievakje",
TextField		: "Tekstveld",
Textarea		: "Tekstvak",
HiddenField		: "Verborgen veld",
Button			: "Knop",
SelectionField	: "Selectieveld",
ImageButton		: "Afbeeldingsknop",

FitWindow		: "De editor maximaliseren",

// Context Menu
EditLink			: "Link wijzigen",
CellCM				: "Cel",
RowCM				: "Rij",
ColumnCM			: "Kolom",
InsertRow			: "Rij invoegen",
DeleteRows			: "Rijen verwijderen",
InsertColumn		: "Kolom invoegen",
DeleteColumns		: "Kolommen verwijderen",
InsertCell			: "Cel",
DeleteCells			: "Cellen verwijderen",
MergeCells			: "Cellen samenvoegen",
SplitCell			: "Cellen splitsen",
TableDelete			: "Tabel verwijderen",
CellProperties		: "Eigenschappen cel",
TableProperties		: "Eigenschappen tabel",
ImageProperties		: "Eigenschappen afbeelding",
FlashProperties		: "Eigenschappen Flash",

AnchorProp			: "Eigenschappen interne link",
ButtonProp			: "Eigenschappen knop",
CheckboxProp		: "Eigenschappen aanvinkvakje",
HiddenFieldProp		: "Eigenschappen verborgen veld",
RadioButtonProp		: "Eigenschappen selectievakje",
ImageButtonProp		: "Eigenschappen afbeeldingsknop",
TextFieldProp		: "Eigenschappen tekstveld",
SelectionFieldProp	: "Eigenschappen selectieveld",
TextareaProp		: "Eigenschappen tekstvak",
FormProp			: "Eigenschappen formulier",

FontFormats			: "Normaal;Met opmaak;Adres;Kop 1;Kop 2;Kop 3;Kop 4;Kop 5;Kop 6",

// Alerts and Messages
ProcessingXHTML		: "Verwerken XHTML. Even geduld aub...",
Done				: "Klaar",
PasteWordConfirm	: "De tekst die je plakte lijkt gekopieerd uit te zijn Word. Wil je de tekst opschonen voordat deze geplakt wordt?",
NotCompatiblePaste	: "Deze opdracht is beschikbaar voor Internet Explorer versie 5.5 of hoger. Wil je plakken zonder op te schonen?",
UnknownToolbarItem	: "Onbekend item op menubalk \"%1\"",
UnknownCommand		: "Onbekende opdrachtnaam: \"%1\"",
NotImplemented		: "Opdracht niet geïmplementeerd.",
UnknownToolbarSet	: "Menubalk \"%1\" bestaat niet.",
NoActiveX			: "De beveilingsinstellingen van je browser zouden sommige functies van de editor kunnen beperken. De optie \"Activeer ActiveX-elementen en plug-ins\" dient ingeschakeld te worden. Het kan zijn dat er nu functies ontbreken of niet werken.",
BrowseServerBlocked : "De bestandsbrowser kon niet geopend worden. Zorg ervoor dat pop-up-blokkeerders uit staan.",
DialogBlocked		: "Kan het dialoogvenster niet weergeven. Zorg ervoor dat pop-up-blokkeerders uit staan.",

// Dialogs
DlgBtnOK			: "OK",
DlgBtnCancel		: "Annuleren",
DlgBtnClose			: "Afsluiten",
DlgBtnBrowseServer	: "Bladeren op server",
DlgAdvancedTag		: "Geavanceerd",
DlgOpOther			: "<Anders>",
DlgInfoTab			: "Informatie",
DlgAlertUrl			: "Geef URL op",

// General Dialogs Labels
DlgGenNotSet		: "<niet ingevuld>",
DlgGenId			: "Kenmerk",
DlgGenLangDir		: "Schrijfrichting",
DlgGenLangDirLtr	: "Links naar rechts (LTR)",
DlgGenLangDirRtl	: "Rechts naar links (RTL)",
DlgGenLangCode		: "Codetaal",
DlgGenAccessKey		: "Toegangstoets",
DlgGenName			: "Naam",
DlgGenTabIndex		: "Tabvolgorde",
DlgGenLongDescr		: "Lange URL-omschrijving",
DlgGenClass			: "Stylesheet-klassen",
DlgGenTitle			: "Aanbevolen titel",
DlgGenContType		: "Aanbevolen content-type",
DlgGenLinkCharset	: "Karakterset van gelinkte bron",
DlgGenStyle			: "Stijl",

// Image Dialog
DlgImgTitle			: "Eigenschappen afbeelding",
DlgImgInfoTab		: "Informatie afbeelding",
DlgImgBtnUpload		: "Naar server verzenden",
DlgImgURL			: "URL",
DlgImgUpload		: "Upload",
DlgImgAlt			: "Alternatieve tekst",
DlgImgWidth			: "Breedte",
DlgImgHeight		: "Hoogte",
DlgImgLockRatio		: "Afmetingen vergrendelen",
DlgBtnResetSize		: "Afmetingen resetten",
DlgImgBorder		: "Rand",
DlgImgHSpace		: "HSpace",
DlgImgVSpace		: "VSpace",
DlgImgAlign			: "Uitlijning",
DlgImgAlignLeft		: "Links",
DlgImgAlignAbsBottom: "Absoluut-onder",
DlgImgAlignAbsMiddle: "Absoluut-midden",
DlgImgAlignBaseline	: "Basislijn",
DlgImgAlignBottom	: "Beneden",
DlgImgAlignMiddle	: "Midden",
DlgImgAlignRight	: "Rechts",
DlgImgAlignTextTop	: "Boven tekst",
DlgImgAlignTop		: "Boven",
DlgImgPreview		: "Voorbeeld",
DlgImgAlertUrl		: "Geef de URL van de afbeelding",
DlgImgLinkTab		: "Link",

// Flash Dialog
DlgFlashTitle		: "Eigenschappen Flash",
DlgFlashChkPlay		: "Automatisch afspelen",
DlgFlashChkLoop		: "Herhalen",
DlgFlashChkMenu		: "Flashmenu\'s inschakelen",
DlgFlashScale		: "Schaal",
DlgFlashScaleAll	: "Alles tonen",
DlgFlashScaleNoBorder	: "Geen rand",
DlgFlashScaleFit	: "Precies passend",

// Link Dialog
DlgLnkWindowTitle	: "Link",
DlgLnkInfoTab		: "Linkomschrijving",
DlgLnkTargetTab		: "Doel",

DlgLnkType			: "Linktype",
DlgLnkTypeURL		: "URL",
DlgLnkTypeAnchor	: "Interne link in pagina",
DlgLnkTypeEMail		: "E-mail",
DlgLnkProto			: "Protocol",
DlgLnkProtoOther	: "<anders>",
DlgLnkURL			: "URL",
DlgLnkAnchorSel		: "Kies een interne link",
DlgLnkAnchorByName	: "Op naam interne link",
DlgLnkAnchorById	: "Op kenmerk interne link",
DlgLnkNoAnchors		: "<Geen interne links in document gevonden.>",
DlgLnkEMail			: "E-mailadres",
DlgLnkEMailSubject	: "Onderwerp bericht",
DlgLnkEMailBody		: "Inhoud bericht",
DlgLnkUpload		: "Upload",
DlgLnkBtnUpload		: "Naar de server versturen",

DlgLnkTarget		: "Doel",
DlgLnkTargetFrame	: "<frame>",
DlgLnkTargetPopup	: "<popup window>",
DlgLnkTargetBlank	: "Nieuw venster (_blank)",
DlgLnkTargetParent	: "Origineel venster (_parent)",
DlgLnkTargetSelf	: "Zelfde venster (_self)",
DlgLnkTargetTop		: "Hele venster (_top)",
DlgLnkTargetFrameName	: "Naam doelframe",
DlgLnkPopWinName	: "Naam popupvenster",
DlgLnkPopWinFeat	: "Instellingen popupvenster",
DlgLnkPopResize		: "Grootte wijzigen",
DlgLnkPopLocation	: "Locatiemenu",
DlgLnkPopMenu		: "Menubalk",
DlgLnkPopScroll		: "Schuifbalken",
DlgLnkPopStatus		: "Statusbalk",
DlgLnkPopToolbar	: "Menubalk",
DlgLnkPopFullScrn	: "Volledig scherm (IE)",
DlgLnkPopDependent	: "Afhankelijk (Netscape)",
DlgLnkPopWidth		: "Breedte",
DlgLnkPopHeight		: "Hoogte",
DlgLnkPopLeft		: "Positie links",
DlgLnkPopTop		: "Positie boven",

DlnLnkMsgNoUrl		: "Geef de link van de URL",
DlnLnkMsgNoEMail	: "Geef een e-mailadres",
DlnLnkMsgNoAnchor	: "Selecteer een interne link",

// Color Dialog
DlgColorTitle		: "Selecteer kleur",
DlgColorBtnClear	: "Opschonen",
DlgColorHighlight	: "Accentueren",
DlgColorSelected	: "Geselecteerd",

// Smiley Dialog
DlgSmileyTitle		: "Smiley invoegen",

// Special Character Dialog
DlgSpecialCharTitle	: "Selecteer speciaal teken",

// Table Dialog
DlgTableTitle		: "Eigenschappen tabel",
DlgTableRows		: "Rijen",
DlgTableColumns		: "Kolommen",
DlgTableBorder		: "Breedte rand",
DlgTableAlign		: "Uitlijning",
DlgTableAlignNotSet	: "<Niet ingevoerd>",
DlgTableAlignLeft	: "Links",
DlgTableAlignCenter	: "Centreren",
DlgTableAlignRight	: "Rechts",
DlgTableWidth		: "Breedte",
DlgTableWidthPx		: "pixels",
DlgTableWidthPc		: "procent",
DlgTableHeight		: "Hoogte",
DlgTableCellSpace	: "Afstand tussen cellen",
DlgTableCellPad		: "Afstand vanaf rand cel",
DlgTableCaption		: "Naam",
DlgTableSummary		: "Samenvatting",

// Table Cell Dialog
DlgCellTitle		: "Eigenschappen cel",
DlgCellWidth		: "Breedte",
DlgCellWidthPx		: "pixels",
DlgCellWidthPc		: "procent",
DlgCellHeight		: "Hoogte",
DlgCellWordWrap		: "Afbreken woorden",
DlgCellWordWrapNotSet	: "<Niet ingevoerd>",
DlgCellWordWrapYes	: "Ja",
DlgCellWordWrapNo	: "Nee",
DlgCellHorAlign		: "Horizontale uitlijning",
DlgCellHorAlignNotSet	: "<Niet ingevoerd>",
DlgCellHorAlignLeft	: "Links",
DlgCellHorAlignCenter	: "Centreren",
DlgCellHorAlignRight: "Rechts",
DlgCellVerAlign		: "Verticale uitlijning",
DlgCellVerAlignNotSet	: "<Niet ingevoerd>",
DlgCellVerAlignTop	: "Boven",
DlgCellVerAlignMiddle	: "Midden",
DlgCellVerAlignBottom	: "Beneden",
DlgCellVerAlignBaseline	: "Basislijn",
DlgCellRowSpan		: "Overkoepeling rijen",
DlgCellCollSpan		: "Overkoepeling kolommen",
DlgCellBackColor	: "Achtergrondkleur",
DlgCellBorderColor	: "Randkleur",
DlgCellBtnSelect	: "Selecteren...",

// Find Dialog
DlgFindTitle		: "Zoeken",
DlgFindFindBtn		: "Zoeken",
DlgFindNotFoundMsg	: "De opgegeven tekst is niet gevonden.",

// Replace Dialog
DlgReplaceTitle			: "Vervangen",
DlgReplaceFindLbl		: "Zoeken naar:",
DlgReplaceReplaceLbl	: "Vervangen met:",
DlgReplaceCaseChk		: "Hoofdlettergevoelig",
DlgReplaceReplaceBtn	: "Vervangen",
DlgReplaceReplAllBtn	: "Alles vervangen",
DlgReplaceWordChk		: "Hele woord moet voorkomen",

// Paste Operations / Dialog
PasteErrorPaste	: "De beveiligingsinstelling van de browser verhinderen het automatisch plakken. Gebruik de sneltoets Ctrl+V van het toetsenbord.",
PasteErrorCut	: "De beveiligingsinstelling van de browser verhinderen het automatisch knippen. Gebruik de sneltoets Ctrl+X van het toetsenbord.",
PasteErrorCopy	: "De beveiligingsinstelling van de browser verhinderen het automatisch kopiëren. Gebruik de sneltoets Ctrl+C van het toetsenbord.",

PasteAsText		: "Plakken als platte tekst",
PasteFromWord	: "Plakken als Word-gegevens",

DlgPasteMsg2	: "Plak de tekst in het volgende vak gebruik makend van je toetstenbord (<STRONG>Ctrl+V</STRONG>) en klik op <STRONG>OK</STRONG>.",
DlgPasteIgnoreFont		: "Negeer \"Font Face\"-definities",
DlgPasteRemoveStyles	: "Verwijder \"Style\"-definities",
DlgPasteCleanBox		: "Vak opschonen",

// Color Picker
ColorAutomatic	: "Automatisch",
ColorMoreColors	: "Meer kleuren...",

// Document Properties
DocProps		: "Eigenschappen document",

// Anchor Dialog
DlgAnchorTitle		: "Eigenschappen interne link",
DlgAnchorName		: "Naam interne link",
DlgAnchorErrorName	: "Geef de naam van de interne link op",

// Speller Pages Dialog
DlgSpellNotInDic		: "Niet in het woordenboek",
DlgSpellChangeTo		: "Wijzig in",
DlgSpellBtnIgnore		: "Negeren",
DlgSpellBtnIgnoreAll	: "Alles negeren",
DlgSpellBtnReplace		: "Vervangen",
DlgSpellBtnReplaceAll	: "Alles vervangen",
DlgSpellBtnUndo			: "Ongedaan maken",
DlgSpellNoSuggestions	: "-Geen suggesties-",
DlgSpellProgress		: "Bezig met spellingscontrole...",
DlgSpellNoMispell		: "Klaar met spellingscontrole: geen fouten gevonden",
DlgSpellNoChanges		: "Klaar met spellingscontrole: geen woorden aangepast",
DlgSpellOneChange		: "Klaar met spellingscontrole: één woord aangepast",
DlgSpellManyChanges		: "Klaar met spellingscontrole: %1 woorden aangepast",

IeSpellDownload			: "De spellingscontrole niet geïnstalleerd. Wil je deze nu downloaden?",

// Button Dialog
DlgButtonText	: "Tekst (waarde)",
DlgButtonType	: "Soort",

// Checkbox and Radio Button Dialogs
DlgCheckboxName		: "Naam",
DlgCheckboxValue	: "Waarde",
DlgCheckboxSelected	: "Geselecteerd",

// Form Dialog
DlgFormName		: "Naam",
DlgFormAction	: "Actie",
DlgFormMethod	: "Methode",

// Select Field Dialog
DlgSelectName		: "Naam",
DlgSelectValue		: "Waarde",
DlgSelectSize		: "Grootte",
DlgSelectLines		: "Regels",
DlgSelectChkMulti	: "Gecombineerde selecties toestaan",
DlgSelectOpAvail	: "Beschikbare opties",
DlgSelectOpText		: "Tekst",
DlgSelectOpValue	: "Waarde",
DlgSelectBtnAdd		: "Toevoegen",
DlgSelectBtnModify	: "Wijzigen",
DlgSelectBtnUp		: "Omhoog",
DlgSelectBtnDown	: "Omlaag",
DlgSelectBtnSetValue : "Als geselecteerde waarde instellen",
DlgSelectBtnDelete	: "Verwijderen",

// Textarea Dialog
DlgTextareaName	: "Naam",
DlgTextareaCols	: "Kolommen",
DlgTextareaRows	: "Rijen",

// Text Field Dialog
DlgTextName			: "Naam",
DlgTextValue		: "Waarde",
DlgTextCharWidth	: "Breedte (tekens)",
DlgTextMaxChars		: "Maximum aantal tekens",
DlgTextType			: "Soort",
DlgTextTypeText		: "Tekst",
DlgTextTypePass		: "Wachtwoord",

// Hidden Field Dialog
DlgHiddenName	: "Naam",
DlgHiddenValue	: "Waarde",

// Bulleted List Dialog
BulletedListProp	: "Eigenschappen opsommingslijst",
NumberedListProp	: "Eigenschappen genummerde opsommingslijst",
DlgLstType			: "Soort",
DlgLstTypeCircle	: "Cirkel",
DlgLstTypeDisc		: "Schijf",
DlgLstTypeSquare	: "Vierkant",
DlgLstTypeNumbers	: "Nummers (1, 2, 3)",
DlgLstTypeLCase		: "Kleine letters (a, b, c)",
DlgLstTypeUCase		: "Hoofdletters (A, B, C)",
DlgLstTypeSRoman	: "Klein Romeins (i, ii, iii)",
DlgLstTypeLRoman	: "Groot Romeins (I, II, III)",

// Document Properties Dialog
DlgDocGeneralTab	: "Algemeen",
DlgDocBackTab		: "Achtergrond",
DlgDocColorsTab		: "Kleuring en marges",
DlgDocMetaTab		: "META-data",

DlgDocPageTitle		: "Paginatitel",
DlgDocLangDir		: "Schrijfrichting",
DlgDocLangDirLTR	: "Links naar rechts",
DlgDocLangDirRTL	: "Rechts naar links",
DlgDocLangCode		: "Taalcode",
DlgDocCharSet		: "Karakterset-encoding",
DlgDocCharSetOther	: "Andere karakterset-encoding",

DlgDocDocType		: "Opschrift documentsoort",
DlgDocDocTypeOther	: "Ander opschrift documentsoort",
DlgDocIncXHTML		: "XHTML-declaraties meenemen",
DlgDocBgColor		: "Achtergrondkleur",
DlgDocBgImage		: "URL achtergrondplaatje",
DlgDocBgNoScroll	: "Vaste achtergrond",
DlgDocCText			: "Tekst",
DlgDocCLink			: "Link",
DlgDocCVisited		: "Bezochte link",
DlgDocCActive		: "Active link",
DlgDocMargins		: "Afstandsinstellingen document",
DlgDocMaTop			: "Boven",
DlgDocMaLeft		: "Links",
DlgDocMaRight		: "Rechts",
DlgDocMaBottom		: "Onder",
DlgDocMeIndex		: "Trefwoorden betreffende document (kommagescheiden)",
DlgDocMeDescr		: "Beschrijving document",
DlgDocMeAuthor		: "Auteur",
DlgDocMeCopy		: "Copyright",
DlgDocPreview		: "Voorbeeld",

// Templates Dialog
Templates			: "Sjablonen",
DlgTemplatesTitle	: "Inhoud sjabonen",
DlgTemplatesSelMsg	: "Selecteer het sjabloon dat in de editor geopend moet worden (de actuele inhoud gaat verloren):",
DlgTemplatesLoading	: "Bezig met laden sjabonen. Even geduld alstublieft...",
DlgTemplatesNoTpl	: "(Geen sjablonen gedefinieerd)",

// About Dialog
DlgAboutAboutTab	: "Over",
DlgAboutBrowserInfoTab	: "Browserinformatie",
DlgAboutLicenseTab	: "Licentie",
DlgAboutVersion		: "Versie",
DlgAboutLicense		: "Gelicenceerd onder de condities van het GNU Lesser General Public License",
DlgAboutInfo		: "Voor meer informatie ga naar "
}