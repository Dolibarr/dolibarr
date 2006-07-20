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
 * File Name: el.js
 * 	Greek language file.
 * 
 * File Authors:
 * 		Spyros Barbatos (sbarbatos{at}users.sourceforge.net)
 */

var FCKLang =
{
// Language direction : "ltr" (left to right) or "rtl" (right to left).
Dir					: "ltr",

ToolbarCollapse		: "Απόκρυψη Μπάρας Εργαλείων",
ToolbarExpand		: "Εμφάνιση Μπάρας Εργαλείων",

// Toolbar Items and Context Menu
Save				: "Αποθήκευση",
NewPage				: "Νέα Σελίδα",
Preview				: "Προεπισκόπιση",
Cut					: "Αποκοπή",
Copy				: "Αντιγραφή",
Paste				: "Επικόλληση",
PasteText			: "Επικόλληση (απλό κείμενο)",
PasteWord			: "Επικόλληση από το Word",
Print				: "Εκτύπωση",
SelectAll			: "Επιλογή όλων",
RemoveFormat		: "Αφαίρεση Μορφοποίησης",
InsertLinkLbl		: "Σύνδεσμος (Link)",
InsertLink			: "Εισαγωγή/Μεταβολή Συνδέσμου (Link)",
RemoveLink			: "Αφαίρεση Συνδέσμου (Link)",
Anchor				: "Insert/Edit Anchor",	//MISSING
InsertImageLbl		: "Εικόνα",
InsertImage			: "Εισαγωγή/Μεταβολή Εικόνας",
InsertFlashLbl		: "Flash",	//MISSING
InsertFlash			: "Insert/Edit Flash",	//MISSING
InsertTableLbl		: "Πίνακας",
InsertTable			: "Εισαγωγή/Μεταβολή Πίνακα",
InsertLineLbl		: "Γραμμή",
InsertLine			: "Εισαγωγή Οριζόντιας Γραμμής",
InsertSpecialCharLbl: "Ειδικό Σύμβολο",
InsertSpecialChar	: "Εισαγωγή Ειδικού Συμβόλου",
InsertSmileyLbl		: "Smiley",
InsertSmiley		: "Εισαγωγή Smiley",
About				: "Περί του FCKeditor",
Bold				: "Έντονα",
Italic				: "Πλάγια",
Underline			: "Υπογράμμιση",
StrikeThrough		: "Διαγράμμιση",
Subscript			: "Δείκτης",
Superscript			: "Εκθέτης",
LeftJustify			: "Στοίχιση Αριστερά",
CenterJustify		: "Στοίχιση στο Κέντρο",
RightJustify		: "Στοίχιση Δεξιά",
BlockJustify		: "Πλήρης Στοίχιση (Block)",
DecreaseIndent		: "Μείωση Εσοχής",
IncreaseIndent		: "Αύξηση Εσοχής",
Undo				: "Undo",
Redo				: "Redo",
NumberedListLbl		: "Λίστα με Αριθμούς",
NumberedList		: "Εισαγωγή/Διαγραφή Λίστας με Αριθμούς",
BulletedListLbl		: "Λίστα με Bullets",
BulletedList		: "Εισαγωγή/Διαγραφή Λίστας με Bullets",
ShowTableBorders	: "Προβολή Ορίων Πίνακα",
ShowDetails			: "Προβολή Λεπτομερειών",
Style				: "Style",
FontFormat			: "Μορφή Γραμματοσειράς",
Font				: "Γραμματοσειρά",
FontSize			: "Μέγεθος",
TextColor			: "Χρώμα Γραμμάτων",
BGColor				: "Χρώμα Υποβάθρου",
Source				: "HTML κώδικας",
Find				: "Αναζήτηση",
Replace				: "Αντικατάσταση",
SpellCheck			: "Check Spelling",	//MISSING
UniversalKeyboard	: "Universal Keyboard",	//MISSING
PageBreakLbl		: "Page Break",	//MISSING
PageBreak			: "Insert Page Break",	//MISSING

Form			: "Form",	//MISSING
Checkbox		: "Checkbox",	//MISSING
RadioButton		: "Radio Button",	//MISSING
TextField		: "Text Field",	//MISSING
Textarea		: "Textarea",	//MISSING
HiddenField		: "Hidden Field",	//MISSING
Button			: "Button",	//MISSING
SelectionField	: "Selection Field",	//MISSING
ImageButton		: "Image Button",	//MISSING

FitWindow		: "Maximize the editor size",	//MISSING

// Context Menu
EditLink			: "Μεταβολή Συνδέσμου (Link)",
CellCM				: "Cell",	//MISSING
RowCM				: "Row",	//MISSING
ColumnCM			: "Column",	//MISSING
InsertRow			: "Εισαγωγή Γραμμής",
DeleteRows			: "Διαγραφή Γραμμών",
InsertColumn		: "Εισαγωγή Κολώνας",
DeleteColumns		: "Διαγραφή Κολωνών",
InsertCell			: "Εισαγωγή Κελιού",
DeleteCells			: "Διαγραφή Κελιών",
MergeCells			: "Ενοποίηση Κελιών",
SplitCell			: "Διαχωρισμός Κελιού",
TableDelete			: "Delete Table",	//MISSING
CellProperties		: "Ιδιότητες Κελιού",
TableProperties		: "Ιδιότητες Πίνακα",
ImageProperties		: "Ιδιότητες Εικόνας",
FlashProperties		: "Flash Properties",	//MISSING

AnchorProp			: "Anchor Properties",	//MISSING
ButtonProp			: "Button Properties",	//MISSING
CheckboxProp		: "Checkbox Properties",	//MISSING
HiddenFieldProp		: "Hidden Field Properties",	//MISSING
RadioButtonProp		: "Radio Button Properties",	//MISSING
ImageButtonProp		: "Image Button Properties",	//MISSING
TextFieldProp		: "Text Field Properties",	//MISSING
SelectionFieldProp	: "Selection Field Properties",	//MISSING
TextareaProp		: "Textarea Properties",	//MISSING
FormProp			: "Form Properties",	//MISSING

FontFormats			: "Normal;Formatted;Address;Heading 1;Heading 2;Heading 3;Heading 4;Heading 5;Heading 6",

// Alerts and Messages
ProcessingXHTML		: "Επεξεργασία XHTML. Παρακαλώ περιμένετε...",
Done				: "Έτοιμο",
PasteWordConfirm	: "Το κείμενο που θέλετε να επικολήσετε, φαίνεται πως προέρχεται από το Word. Θέλετε να καθαριστεί πριν επικοληθεί;",
NotCompatiblePaste	: "Αυτή η επιλογή είναι διαθέσιμη στον Internet Explorer έκδοση 5.5+. Θέλετε να γίνει η επικόλληση χωρίς καθαρισμό;",
UnknownToolbarItem	: "Άγνωστο αντικείμενο της μπάρας εργαλείων \"%1\"",
UnknownCommand		: "Άγνωστή εντολή \"%1\"",
NotImplemented		: "Η εντολή δεν έχει ενεργοποιηθεί",
UnknownToolbarSet	: "Η μπάρα εργαλείων \"%1\" δεν υπάρχει",
NoActiveX			: "Your browser's security settings could limit some features of the editor. You must enable the option \"Run ActiveX controls and plug-ins\". You may experience errors and notice missing features.",	//MISSING
BrowseServerBlocked : "The resources browser could not be opened. Make sure that all popup blockers are disabled.",	//MISSING
DialogBlocked		: "It was not possible to open the dialog window. Make sure all popup blockers are disabled.",	//MISSING

// Dialogs
DlgBtnOK			: "OK",
DlgBtnCancel		: "Ακύρωση",
DlgBtnClose			: "Κλείσιμο",
DlgBtnBrowseServer	: "Browse Server",	//MISSING
DlgAdvancedTag		: "Για προχωρημένους",
DlgOpOther			: "<Other>",	//MISSING
DlgInfoTab			: "Info",	//MISSING
DlgAlertUrl			: "Please insert the URL",	//MISSING

// General Dialogs Labels
DlgGenNotSet		: "<χωρίς>",
DlgGenId			: "Id",
DlgGenLangDir		: "Κατεύθυνση κειμένου",
DlgGenLangDirLtr	: "Αριστερά προς Δεξιά (LTR)",
DlgGenLangDirRtl	: "Δεξιά προς Αριστερά (RTL)",
DlgGenLangCode		: "Κωδικός Γλώσσας",
DlgGenAccessKey		: "Συντόμευση (Access Key)",
DlgGenName			: "Name",
DlgGenTabIndex		: "Tab Index",
DlgGenLongDescr		: "Long Description URL",
DlgGenClass			: "Stylesheet Classes",
DlgGenTitle			: "Advisory Title",
DlgGenContType		: "Advisory Content Type",
DlgGenLinkCharset	: "Linked Resource Charset",
DlgGenStyle			: "Style",

// Image Dialog
DlgImgTitle			: "Ιδιότητες Εικόνας",
DlgImgInfoTab		: "Πληροφορίες Εικόνας",
DlgImgBtnUpload		: "Αποστολή στον Διακομιστή",
DlgImgURL			: "URL",
DlgImgUpload		: "Αποστολή",
DlgImgAlt			: "Εναλλακτικό Κείμενο (ALT)",
DlgImgWidth			: "Πλάτος",
DlgImgHeight		: "Ύψος",
DlgImgLockRatio		: "Κλείδωμα Αναλογίας",
DlgBtnResetSize		: "Επαναφορά Αρχικού Μεγέθους",
DlgImgBorder		: "Περιθώριο",
DlgImgHSpace		: "Οριζόντιος Χώρος (HSpace)",
DlgImgVSpace		: "Κάθετος Χώρος (VSpace)",
DlgImgAlign			: "Ευθυγράμμιση (Align)",
DlgImgAlignLeft		: "Αριστερά",
DlgImgAlignAbsBottom: "Απόλυτα Κάτω (Abs Bottom)",
DlgImgAlignAbsMiddle: "Απόλυτα στη Μέση (Abs Middle)",
DlgImgAlignBaseline	: "Γραμμή Βάσης (Baseline)",
DlgImgAlignBottom	: "Κάτω (Bottom)",
DlgImgAlignMiddle	: "Μέση (Middle)",
DlgImgAlignRight	: "Δεξιά (Right)",
DlgImgAlignTextTop	: "Κορυφή Κειμένου (Text Top)",
DlgImgAlignTop		: "Πάνω (Top)",
DlgImgPreview		: "Προεπισκόπιση",
DlgImgAlertUrl		: "Εισάγετε την τοποθεσία (URL) της εικόνας",
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
DlgLnkWindowTitle	: "Υπερσύνδεσμος (Link)",
DlgLnkInfoTab		: "Link",
DlgLnkTargetTab		: "Παράθυρο Στόχος (Target)",

DlgLnkType			: "Τύπος Υπερσυνδέσμου (Link)",
DlgLnkTypeURL		: "URL",
DlgLnkTypeAnchor	: "Anchor in this page",
DlgLnkTypeEMail		: "E-Mail",
DlgLnkProto			: "Protocol",
DlgLnkProtoOther	: "<άλλο>",
DlgLnkURL			: "URL",
DlgLnkAnchorSel		: "Επιλέξτε ένα Anchor",
DlgLnkAnchorByName	: "Βάσει του Ονόματος (Name)του Anchor",
DlgLnkAnchorById	: "Βάσει του Element Id",
DlgLnkNoAnchors		: "<Δεν υπάρχουν Anchors στο κείμενο>",
DlgLnkEMail			: "Διεύθυνση Ηλεκτρονικού Ταχυδρομείου",
DlgLnkEMailSubject	: "Θέμα Μηνύματος",
DlgLnkEMailBody		: "Κείμενο Μηνύματος",
DlgLnkUpload		: "Αποστολή",
DlgLnkBtnUpload		: "Αποστολή στον Διακομιστή",

DlgLnkTarget		: "Παράθυρο Στόχος (Target)",
DlgLnkTargetFrame	: "<frame>",
DlgLnkTargetPopup	: "<popup window>",
DlgLnkTargetBlank	: "Νέο Παράθυρο (_blank)",
DlgLnkTargetParent	: "Γονικό Παράθυρο (_parent)",
DlgLnkTargetSelf	: "Ίδιο Παράθυρο (_self)",
DlgLnkTargetTop		: "Ανώτατο Παράθυρο (_top)",
DlgLnkTargetFrameName	: "Target Frame Name",	//MISSING
DlgLnkPopWinName	: "Όνομα Popup Window",
DlgLnkPopWinFeat	: "Επιλογές Popup Window",
DlgLnkPopResize		: "Με αλλαγή Μεγέθους",
DlgLnkPopLocation	: "Μπάρα Τοποθεσίας",
DlgLnkPopMenu		: "Μπάρα Menu",
DlgLnkPopScroll		: "Μπάρες Κύλισης",
DlgLnkPopStatus		: "Μπάρα Status",
DlgLnkPopToolbar	: "Μπάρα Εργαλείων",
DlgLnkPopFullScrn	: "Ολόκληρη η Οθόνη (IE)",
DlgLnkPopDependent	: "Dependent (Netscape)",
DlgLnkPopWidth		: "Πλάτος",
DlgLnkPopHeight		: "Ύψος",
DlgLnkPopLeft		: "Τοποθεσία Αριστερής Άκρης",
DlgLnkPopTop		: "Τοποθεσία Πάνω Άκρης",

DlnLnkMsgNoUrl		: "Εισάγετε την τοποθεσία (URL) του υπερσυνδέσμου (Link)",
DlnLnkMsgNoEMail	: "Εισάγετε την διεύθυνση ηλεκτρονικού ταχυδρομείου",
DlnLnkMsgNoAnchor	: "Επιλέξτε ένα Anchor",

// Color Dialog
DlgColorTitle		: "Επιλογή χρώματος",
DlgColorBtnClear	: "Καθαρισμός",
DlgColorHighlight	: "Προεπισκόπιση",
DlgColorSelected	: "Επιλεγμένο",

// Smiley Dialog
DlgSmileyTitle		: "Επιλέξτε ένα Smiley",

// Special Character Dialog
DlgSpecialCharTitle	: "Επιλέξτε ένα Ειδικό Σύμβολο",

// Table Dialog
DlgTableTitle		: "Ιδιότητες Πίνακα",
DlgTableRows		: "Γραμμές",
DlgTableColumns		: "Κολώνες",
DlgTableBorder		: "Μέγεθος Περιθωρίου",
DlgTableAlign		: "Στοίχιση",
DlgTableAlignNotSet	: "<χωρίς>",
DlgTableAlignLeft	: "Αριστερά",
DlgTableAlignCenter	: "Κέντρο",
DlgTableAlignRight	: "Δεξιά",
DlgTableWidth		: "Πλάτος",
DlgTableWidthPx		: "pixels",
DlgTableWidthPc		: "\%",
DlgTableHeight		: "Ύψος",
DlgTableCellSpace	: "Cell spacing",
DlgTableCellPad		: "Cell padding",
DlgTableCaption		: "Υπέρτιτλος",
DlgTableSummary		: "Summary",	//MISSING

// Table Cell Dialog
DlgCellTitle		: "Ιδιότητες Κελιού",
DlgCellWidth		: "Πλάτος",
DlgCellWidthPx		: "pixels",
DlgCellWidthPc		: "\%",
DlgCellHeight		: "Ύψος",
DlgCellWordWrap		: "Με αλλαγή γραμμής",
DlgCellWordWrapNotSet	: "<χωρίς>",
DlgCellWordWrapYes	: "Ναι",
DlgCellWordWrapNo	: "Όχι",
DlgCellHorAlign		: "Οριζόντια Στοίχιση",
DlgCellHorAlignNotSet	: "<χωρίς>",
DlgCellHorAlignLeft	: "Αριστερά",
DlgCellHorAlignCenter	: "Κέντρο",
DlgCellHorAlignRight: "Δεξιά",
DlgCellVerAlign		: "Κάθετη Στοίχιση",
DlgCellVerAlignNotSet	: "<χωρίς>",
DlgCellVerAlignTop	: "Πάνω (Top)",
DlgCellVerAlignMiddle	: "Μέση (Middle)",
DlgCellVerAlignBottom	: "Κάτω (Bottom)",
DlgCellVerAlignBaseline	: "Γραμμή Βάσης (Baseline)",
DlgCellRowSpan		: "Αριθμός Γραμμών (Rows Span)",
DlgCellCollSpan		: "Αριθμός Κολωνών (Columns Span)",
DlgCellBackColor	: "Χρώμα Υποβάθρου",
DlgCellBorderColor	: "Χρώμα Περιθωρίου",
DlgCellBtnSelect	: "Επιλογή...",

// Find Dialog
DlgFindTitle		: "Αναζήτηση",
DlgFindFindBtn		: "Αναζήτηση",
DlgFindNotFoundMsg	: "Το κείμενο δεν βρέθηκε.",

// Replace Dialog
DlgReplaceTitle			: "Αντικατάσταση",
DlgReplaceFindLbl		: "Αναζήτηση:",
DlgReplaceReplaceLbl	: "Αντικατάσταση με:",
DlgReplaceCaseChk		: "Έλεγχος πεζών/κεφαλαίων",
DlgReplaceReplaceBtn	: "Αντικατάσταση",
DlgReplaceReplAllBtn	: "Αντικατάσταση Όλων",
DlgReplaceWordChk		: "Εύρεση πλήρους λέξης",

// Paste Operations / Dialog
PasteErrorPaste	: "Οι ρυθμίσεις ασφαλείας του φυλλομετρητή σας δεν επιτρέπουν την επιλεγμένη εργασία επικόλλησης. Χρησιμοποιείστε το πληκτρολόγιο (Ctrl+V).",
PasteErrorCut	: "Οι ρυθμίσεις ασφαλείας του φυλλομετρητή σας δεν επιτρέπουν την επιλεγμένη εργασία αποκοπής. Χρησιμοποιείστε το πληκτρολόγιο (Ctrl+X).",
PasteErrorCopy	: "Οι ρυθμίσεις ασφαλείας του φυλλομετρητή σας δεν επιτρέπουν την επιλεγμένη εργασία αντιγραφής. Χρησιμοποιείστε το πληκτρολόγιο (Ctrl+C).",

PasteAsText		: "Επικόλληση ως Απλό Κείμενο",
PasteFromWord	: "Επικόλληση από το Word",

DlgPasteMsg2	: "Please paste inside the following box using the keyboard (<STRONG>Ctrl+V</STRONG>) and hit <STRONG>OK</STRONG>.",	//MISSING
DlgPasteIgnoreFont		: "Ignore Font Face definitions",	//MISSING
DlgPasteRemoveStyles	: "Remove Styles definitions",	//MISSING
DlgPasteCleanBox		: "Clean Up Box",	//MISSING

// Color Picker
ColorAutomatic	: "Αυτόματο",
ColorMoreColors	: "Περισσότερα χρώματα...",

// Document Properties
DocProps		: "Document Properties",	//MISSING

// Anchor Dialog
DlgAnchorTitle		: "Anchor Properties",	//MISSING
DlgAnchorName		: "Anchor Name",	//MISSING
DlgAnchorErrorName	: "Please type the anchor name",	//MISSING

// Speller Pages Dialog
DlgSpellNotInDic		: "Not in dictionary",	//MISSING
DlgSpellChangeTo		: "Change to",	//MISSING
DlgSpellBtnIgnore		: "Ignore",	//MISSING
DlgSpellBtnIgnoreAll	: "Ignore All",	//MISSING
DlgSpellBtnReplace		: "Replace",	//MISSING
DlgSpellBtnReplaceAll	: "Replace All",	//MISSING
DlgSpellBtnUndo			: "Undo",	//MISSING
DlgSpellNoSuggestions	: "- No suggestions -",	//MISSING
DlgSpellProgress		: "Spell check in progress...",	//MISSING
DlgSpellNoMispell		: "Spell check complete: No misspellings found",	//MISSING
DlgSpellNoChanges		: "Spell check complete: No words changed",	//MISSING
DlgSpellOneChange		: "Spell check complete: One word changed",	//MISSING
DlgSpellManyChanges		: "Spell check complete: %1 words changed",	//MISSING

IeSpellDownload			: "Spell checker not installed. Do you want to download it now?",	//MISSING

// Button Dialog
DlgButtonText	: "Text (Value)",	//MISSING
DlgButtonType	: "Type",	//MISSING

// Checkbox and Radio Button Dialogs
DlgCheckboxName		: "Name",	//MISSING
DlgCheckboxValue	: "Value",	//MISSING
DlgCheckboxSelected	: "Selected",	//MISSING

// Form Dialog
DlgFormName		: "Name",	//MISSING
DlgFormAction	: "Action",	//MISSING
DlgFormMethod	: "Method",	//MISSING

// Select Field Dialog
DlgSelectName		: "Name",	//MISSING
DlgSelectValue		: "Value",	//MISSING
DlgSelectSize		: "Size",	//MISSING
DlgSelectLines		: "lines",	//MISSING
DlgSelectChkMulti	: "Allow multiple selections",	//MISSING
DlgSelectOpAvail	: "Available Options",	//MISSING
DlgSelectOpText		: "Text",	//MISSING
DlgSelectOpValue	: "Value",	//MISSING
DlgSelectBtnAdd		: "Add",	//MISSING
DlgSelectBtnModify	: "Modify",	//MISSING
DlgSelectBtnUp		: "Up",	//MISSING
DlgSelectBtnDown	: "Down",	//MISSING
DlgSelectBtnSetValue : "Set as selected value",	//MISSING
DlgSelectBtnDelete	: "Delete",	//MISSING

// Textarea Dialog
DlgTextareaName	: "Name",	//MISSING
DlgTextareaCols	: "Columns",	//MISSING
DlgTextareaRows	: "Rows",	//MISSING

// Text Field Dialog
DlgTextName			: "Name",	//MISSING
DlgTextValue		: "Value",	//MISSING
DlgTextCharWidth	: "Character Width",	//MISSING
DlgTextMaxChars		: "Maximum Characters",	//MISSING
DlgTextType			: "Type",	//MISSING
DlgTextTypeText		: "Text",	//MISSING
DlgTextTypePass		: "Password",	//MISSING

// Hidden Field Dialog
DlgHiddenName	: "Name",	//MISSING
DlgHiddenValue	: "Value",	//MISSING

// Bulleted List Dialog
BulletedListProp	: "Bulleted List Properties",	//MISSING
NumberedListProp	: "Numbered List Properties",	//MISSING
DlgLstType			: "Type",	//MISSING
DlgLstTypeCircle	: "Circle",	//MISSING
DlgLstTypeDisc		: "Disc",	//MISSING
DlgLstTypeSquare	: "Square",	//MISSING
DlgLstTypeNumbers	: "Numbers (1, 2, 3)",	//MISSING
DlgLstTypeLCase		: "Lowercase Letters (a, b, c)",	//MISSING
DlgLstTypeUCase		: "Uppercase Letters (A, B, C)",	//MISSING
DlgLstTypeSRoman	: "Small Roman Numerals (i, ii, iii)",	//MISSING
DlgLstTypeLRoman	: "Large Roman Numerals (I, II, III)",	//MISSING

// Document Properties Dialog
DlgDocGeneralTab	: "General",	//MISSING
DlgDocBackTab		: "Background",	//MISSING
DlgDocColorsTab		: "Colors and Margins",	//MISSING
DlgDocMetaTab		: "Meta Data",	//MISSING

DlgDocPageTitle		: "Page Title",	//MISSING
DlgDocLangDir		: "Language Direction",	//MISSING
DlgDocLangDirLTR	: "Left to Right (LTR)",	//MISSING
DlgDocLangDirRTL	: "Right to Left (RTL)",	//MISSING
DlgDocLangCode		: "Language Code",	//MISSING
DlgDocCharSet		: "Character Set Encoding",	//MISSING
DlgDocCharSetOther	: "Other Character Set Encoding",	//MISSING

DlgDocDocType		: "Document Type Heading",	//MISSING
DlgDocDocTypeOther	: "Other Document Type Heading",	//MISSING
DlgDocIncXHTML		: "Include XHTML Declarations",	//MISSING
DlgDocBgColor		: "Background Color",	//MISSING
DlgDocBgImage		: "Background Image URL",	//MISSING
DlgDocBgNoScroll	: "Nonscrolling Background",	//MISSING
DlgDocCText			: "Text",	//MISSING
DlgDocCLink			: "Link",	//MISSING
DlgDocCVisited		: "Visited Link",	//MISSING
DlgDocCActive		: "Active Link",	//MISSING
DlgDocMargins		: "Page Margins",	//MISSING
DlgDocMaTop			: "Top",	//MISSING
DlgDocMaLeft		: "Left",	//MISSING
DlgDocMaRight		: "Right",	//MISSING
DlgDocMaBottom		: "Bottom",	//MISSING
DlgDocMeIndex		: "Document Indexing Keywords (comma separated)",	//MISSING
DlgDocMeDescr		: "Document Description",	//MISSING
DlgDocMeAuthor		: "Author",	//MISSING
DlgDocMeCopy		: "Copyright",	//MISSING
DlgDocPreview		: "Preview",	//MISSING

// Templates Dialog
Templates			: "Templates",	//MISSING
DlgTemplatesTitle	: "Content Templates",	//MISSING
DlgTemplatesSelMsg	: "Please select the template to open in the editor<br>(the actual contents will be lost):",	//MISSING
DlgTemplatesLoading	: "Loading templates list. Please wait...",	//MISSING
DlgTemplatesNoTpl	: "(No templates defined)",	//MISSING

// About Dialog
DlgAboutAboutTab	: "About",	//MISSING
DlgAboutBrowserInfoTab	: "Browser Info",	//MISSING
DlgAboutLicenseTab	: "License",	//MISSING
DlgAboutVersion		: "έκδοση",
DlgAboutLicense		: "Άδεια χρήσης υπό τους όρους της GNU Lesser General Public License",
DlgAboutInfo		: "Για περισσότερες πληροφορίες"
}