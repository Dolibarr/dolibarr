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
 * 		Vangelis Bibakis (bibakisv[-a-t-]yahoo.com)
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
Anchor				: "Εισαγωγή/επεξεργασία Anchor",
InsertImageLbl		: "Εικόνα",
InsertImage			: "Εισαγωγή/Μεταβολή Εικόνας",
InsertFlashLbl		: "Εισαγωγή Flash",
InsertFlash			: "Εισαγωγή/επεξεργασία Flash",
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
Undo				: "Αναίρεση",
Redo				: "Επαναφορά",
NumberedListLbl		: "Λίστα με Αριθμούς",
NumberedList		: "Εισαγωγή/Διαγραφή Λίστας με Αριθμούς",
BulletedListLbl		: "Λίστα με Bullets",
BulletedList		: "Εισαγωγή/Διαγραφή Λίστας με Bullets",
ShowTableBorders	: "Προβολή Ορίων Πίνακα",
ShowDetails			: "Προβολή Λεπτομερειών",
Style				: "Στυλ",
FontFormat			: "Μορφή Γραμματοσειράς",
Font				: "Γραμματοσειρά",
FontSize			: "Μέγεθος",
TextColor			: "Χρώμα Γραμμάτων",
BGColor				: "Χρώμα Υποβάθρου",
Source				: "HTML κώδικας",
Find				: "Αναζήτηση",
Replace				: "Αντικατάσταση",
SpellCheck			: "Ορθογραφικός έλεγχος",
UniversalKeyboard	: "Διεθνής πληκτρολόγιο",
PageBreakLbl		: "Τέλος σελίδας",
PageBreak			: "Εισαγωγή τέλους σελίδας",

Form			: "Φόρμα",
Checkbox		: "Κουτί επιλογής",
RadioButton		: "Κουμπί Radio",
TextField		: "Πεδίο κειμένου",
Textarea		: "Περιοχή κειμένου",
HiddenField		: "Κρυφό πεδίο",
Button			: "Κουμπί",
SelectionField	: "Πεδίο επιλογής",
ImageButton		: "Κουμπί εικόνας",

FitWindow		: "Μεγιστοποίηση προγράμματος",

// Context Menu
EditLink			: "Μεταβολή Συνδέσμου (Link)",
CellCM				: "Κελί",
RowCM				: "Σειρά",
ColumnCM			: "Στήλη",
InsertRow			: "Εισαγωγή Γραμμής",
DeleteRows			: "Διαγραφή Γραμμών",
InsertColumn		: "Εισαγωγή Κολώνας",
DeleteColumns		: "Διαγραφή Κολωνών",
InsertCell			: "Εισαγωγή Κελιού",
DeleteCells			: "Διαγραφή Κελιών",
MergeCells			: "Ενοποίηση Κελιών",
SplitCell			: "Διαχωρισμός Κελιού",
TableDelete			: "Διαγραφή πίνακα",
CellProperties		: "Ιδιότητες Κελιού",
TableProperties		: "Ιδιότητες Πίνακα",
ImageProperties		: "Ιδιότητες Εικόνας",
FlashProperties		: "Ιδιότητες Flash",

AnchorProp			: "Ιδιότητες άγκυρας",
ButtonProp			: "Ιδιότητες κουμπιού",
CheckboxProp		: "Ιδιότητες κουμπιού επιλογής",
HiddenFieldProp		: "Ιδιότητες κρυφού πεδίου",
RadioButtonProp		: "Ιδιότητες κουμπιού radio",
ImageButtonProp		: "Ιδιότητες κουμπιού εικόνας",
TextFieldProp		: "Ιδιότητες πεδίου κειμένου",
SelectionFieldProp	: "Ιδιότητες πεδίου επιλογής",
TextareaProp		: "Ιδιότητες περιοχής κειμένου",
FormProp			: "Ιδιότητες φόρμας",

FontFormats			: "Κανονικό;Μορφοποιημένο;Διεύθυνση;Επικεφαλίδα 1;Επικεφαλίδα 2;Επικεφαλίδα 3;Επικεφαλίδα 4;Επικεφαλίδα 5;Επικεφαλίδα 6",

// Alerts and Messages
ProcessingXHTML		: "Επεξεργασία XHTML. Παρακαλώ περιμένετε...",
Done				: "Έτοιμο",
PasteWordConfirm	: "Το κείμενο που θέλετε να επικολήσετε, φαίνεται πως προέρχεται από το Word. Θέλετε να καθαριστεί πριν επικοληθεί;",
NotCompatiblePaste	: "Αυτή η επιλογή είναι διαθέσιμη στον Internet Explorer έκδοση 5.5+. Θέλετε να γίνει η επικόλληση χωρίς καθαρισμό;",
UnknownToolbarItem	: "Άγνωστο αντικείμενο της μπάρας εργαλείων \"%1\"",
UnknownCommand		: "Άγνωστή εντολή \"%1\"",
NotImplemented		: "Η εντολή δεν έχει ενεργοποιηθεί",
UnknownToolbarSet	: "Η μπάρα εργαλείων \"%1\" δεν υπάρχει",
NoActiveX			: "Οι ρυθμίσεις ασφαλείας του browser σας μπορεί να περιορίσουν κάποιες ρυθμίσεις του προγράμματος. Χρειάζεται να ενεργοποιήσετε την επιλογή \"Run ActiveX controls and plug-ins\". Ίσως παρουσιαστούν λάθη και παρατηρήσετε ελειπείς λειτουργίες.",
BrowseServerBlocked : "Οι πόροι του browser σας δεν είναι προσπελάσιμοι. Σιγουρευτείτε ότι δεν υπάρχουν ενεργοί popup blockers.",
DialogBlocked		: "Δεν ήταν δυνατό να ανοίξει το παράθυρο διαλόγου. Σιγουρευτείτε ότι δεν υπάρχουν ενεργοί popup blockers.",

// Dialogs
DlgBtnOK			: "OK",
DlgBtnCancel		: "Ακύρωση",
DlgBtnClose			: "Κλείσιμο",
DlgBtnBrowseServer	: "Εξερεύνηση διακομιστή",
DlgAdvancedTag		: "Για προχωρημένους",
DlgOpOther			: "<Άλλα>",
DlgInfoTab			: "Πληροφορίες",
DlgAlertUrl			: "Παρακαλώ εισάγετε URL",

// General Dialogs Labels
DlgGenNotSet		: "<χωρίς>",
DlgGenId			: "Id",
DlgGenLangDir		: "Κατεύθυνση κειμένου",
DlgGenLangDirLtr	: "Αριστερά προς Δεξιά (LTR)",
DlgGenLangDirRtl	: "Δεξιά προς Αριστερά (RTL)",
DlgGenLangCode		: "Κωδικός Γλώσσας",
DlgGenAccessKey		: "Συντόμευση (Access Key)",
DlgGenName			: "Όνομα",
DlgGenTabIndex		: "Tab Index",
DlgGenLongDescr		: "Αναλυτική περιγραφή URL",
DlgGenClass			: "Stylesheet Classes",
DlgGenTitle			: "Συμβουλευτικός τίτλος",
DlgGenContType		: "Συμβουλευτικός τίτλος περιεχομένου",
DlgGenLinkCharset	: "Linked Resource Charset",
DlgGenStyle			: "Στύλ",

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
DlgImgLinkTab		: "Σύνδεσμος",

// Flash Dialog
DlgFlashTitle		: "Ιδιότητες flash",
DlgFlashChkPlay		: "Αυτόματη έναρξη",
DlgFlashChkLoop		: "Επανάληψη",
DlgFlashChkMenu		: "Ενεργοποίηση Flash Menu",
DlgFlashScale		: "Κλίμακα",
DlgFlashScaleAll	: "Εμφάνιση όλων",
DlgFlashScaleNoBorder	: "Χωρίς όρια",
DlgFlashScaleFit	: "Ακριβής εφαρμογή",

// Link Dialog
DlgLnkWindowTitle	: "Σύνδεσμος (Link)",
DlgLnkInfoTab		: "Link",
DlgLnkTargetTab		: "Παράθυρο Στόχος (Target)",

DlgLnkType			: "Τύπος συνδέσμου (Link)",
DlgLnkTypeURL		: "URL",
DlgLnkTypeAnchor	: "Άγκυρα σε αυτή τη σελίδα",
DlgLnkTypeEMail		: "E-Mail",
DlgLnkProto			: "Προτόκολο",
DlgLnkProtoOther	: "<άλλο>",
DlgLnkURL			: "URL",
DlgLnkAnchorSel		: "Επιλέξτε μια άγκυρα",
DlgLnkAnchorByName	: "Βάσει του Ονόματος (Name) της άγκυρας",
DlgLnkAnchorById	: "Βάσει του Element Id",
DlgLnkNoAnchors		: "<Δεν υπάρχουν άγκυρες στο κείμενο>",
DlgLnkEMail			: "Διεύθυνση Ηλεκτρονικού Ταχυδρομείου",
DlgLnkEMailSubject	: "Θέμα Μηνύματος",
DlgLnkEMailBody		: "Κείμενο Μηνύματος",
DlgLnkUpload		: "Αποστολή",
DlgLnkBtnUpload		: "Αποστολή στον Διακομιστή",

DlgLnkTarget		: "Παράθυρο Στόχος (Target)",
DlgLnkTargetFrame	: "<πλαίσιο>",
DlgLnkTargetPopup	: "<παράθυρο popup>",
DlgLnkTargetBlank	: "Νέο Παράθυρο (_blank)",
DlgLnkTargetParent	: "Γονικό Παράθυρο (_parent)",
DlgLnkTargetSelf	: "Ίδιο Παράθυρο (_self)",
DlgLnkTargetTop		: "Ανώτατο Παράθυρο (_top)",
DlgLnkTargetFrameName	: "Όνομα πλαισίου στόχου",
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
DlgTableCellSpace	: "Απόσταση κελιών",
DlgTableCellPad		: "Γέμισμα κελιών",
DlgTableCaption		: "Υπέρτιτλος",
DlgTableSummary		: "Περίληψη",

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

DlgPasteMsg2	: "Παρακαλώ επικολήστε στο ακόλουθο κουτί χρησιμοποιόντας το πληκτρολόγιο (<STRONG>Ctrl+V</STRONG>) και πατήστε <STRONG>OK</STRONG>.",
DlgPasteIgnoreFont		: "Αγνόηση προδιαγραφών γραμματοσειράς",
DlgPasteRemoveStyles	: "Αφαίρεση προδιαγραφών στύλ",
DlgPasteCleanBox		: "Κουτί εκαθάρισης",

// Color Picker
ColorAutomatic	: "Αυτόματο",
ColorMoreColors	: "Περισσότερα χρώματα...",

// Document Properties
DocProps		: "Ιδιότητες εγγράφου",

// Anchor Dialog
DlgAnchorTitle		: "Ιδιότητες άγκυρας",
DlgAnchorName		: "Όνομα άγκυρας",
DlgAnchorErrorName	: "Παρακαλούμε εισάγετε όνομα άγκυρας",

// Speller Pages Dialog
DlgSpellNotInDic		: "Δεν υπάρχει στο λεξικό",
DlgSpellChangeTo		: "Αλλαγή σε",
DlgSpellBtnIgnore		: "Αγνόηση",
DlgSpellBtnIgnoreAll	: "Αγνόηση όλων",
DlgSpellBtnReplace		: "Αντικατάσταση",
DlgSpellBtnReplaceAll	: "Αντικατάσταση όλων",
DlgSpellBtnUndo			: "Αναίρεση",
DlgSpellNoSuggestions	: "- Δεν υπάρχουν προτάσεις -",
DlgSpellProgress		: "Ορθογραφικός έλεγχος σε εξέλιξη...",
DlgSpellNoMispell		: "Ο ορθογραφικός έλεγχος ολοκληρώθηκε: Δεν βρέθηκαν λάθη",
DlgSpellNoChanges		: "Ο ορθογραφικός έλεγχος ολοκληρώθηκε: Δεν άλλαξαν λέξεις",
DlgSpellOneChange		: "Ο ορθογραφικός έλεγχος ολοκληρώθηκε: Μια λέξη άλλαξε",
DlgSpellManyChanges		: "Ο ορθογραφικός έλεγχος ολοκληρώθηκε: %1 λέξεις άλλαξαν",

IeSpellDownload			: "Δεν υπάρχει εγκατεστημένος ορθογράφος. Θέλετε να τον κατεβάσετε τώρα;",

// Button Dialog
DlgButtonText	: "Κείμενο (Τιμή)",
DlgButtonType	: "Τύπος",

// Checkbox and Radio Button Dialogs
DlgCheckboxName		: "Όνομα",
DlgCheckboxValue	: "Τιμή",
DlgCheckboxSelected	: "Επιλεγμένο",

// Form Dialog
DlgFormName		: "Όνομα",
DlgFormAction	: "Δράση",
DlgFormMethod	: "Μάθοδος",

// Select Field Dialog
DlgSelectName		: "Όνομα",
DlgSelectValue		: "Τιμή",
DlgSelectSize		: "Μέγεθος",
DlgSelectLines		: "γραμμές",
DlgSelectChkMulti	: "Πολλαπλές επιλογές",
DlgSelectOpAvail	: "Διαθέσιμες επιλογές",
DlgSelectOpText		: "Κείμενο",
DlgSelectOpValue	: "Τιμή",
DlgSelectBtnAdd		: "Προσθήκη",
DlgSelectBtnModify	: "Αλλαγή",
DlgSelectBtnUp		: "Πάνω",
DlgSelectBtnDown	: "Κάτω",
DlgSelectBtnSetValue : "Προεπιλεγμένη επιλογή",
DlgSelectBtnDelete	: "Διαγραφή",

// Textarea Dialog
DlgTextareaName	: "Όνομα",
DlgTextareaCols	: "Στήλες",
DlgTextareaRows	: "Σειρές",

// Text Field Dialog
DlgTextName			: "Όνομα",
DlgTextValue		: "Τιμή",
DlgTextCharWidth	: "Μήκος χαρακτήρων",
DlgTextMaxChars		: "Μέγιστοι χαρακτήρες",
DlgTextType			: "Τύπος",
DlgTextTypeText		: "Κείμενο",
DlgTextTypePass		: "Κωδικός",

// Hidden Field Dialog
DlgHiddenName	: "Όνομα",
DlgHiddenValue	: "Τιμή",

// Bulleted List Dialog
BulletedListProp	: "Ιδιότητες λίστας Bulleted",
NumberedListProp	: "Ιδιότητες αριθμημένης λίστας ",
DlgLstType			: "Τύπος",
DlgLstTypeCircle	: "Κύκλος",
DlgLstTypeDisc		: "Δίσκος",
DlgLstTypeSquare	: "Τετράγωνο",
DlgLstTypeNumbers	: "Αριθμοί (1, 2, 3)",
DlgLstTypeLCase		: "Πεζά γράμματα (a, b, c)",
DlgLstTypeUCase		: "Κεφαλαία γράμματα (A, B, C)",
DlgLstTypeSRoman	: "Μικρά λατινικά αριθμητικά (i, ii, iii)",
DlgLstTypeLRoman	: "Μεγάλα λατινικά αριθμητικά (I, II, III)",

// Document Properties Dialog
DlgDocGeneralTab	: "Γενικά",
DlgDocBackTab		: "Φόντο",
DlgDocColorsTab		: "Χρώματα και περιθώρια",
DlgDocMetaTab		: "Δεδομένα Meta",

DlgDocPageTitle		: "Τίτλος σελίδας",
DlgDocLangDir		: "Κατεύθυνση γραφής",
DlgDocLangDirLTR	: "αριστερά προς δεξιά (LTR)",
DlgDocLangDirRTL	: "δεξιά προς αριστερά (RTL)",
DlgDocLangCode		: "Κωδικός γλώσσας",
DlgDocCharSet		: "Κωδικοποίηση χαρακτήρων",
DlgDocCharSetOther	: "Άλλη κωδικοποίηση χαρακτήρων",

DlgDocDocType		: "Επικεφαλίδα τύπου εγγράφου",
DlgDocDocTypeOther	: "Άλλη επικεφαλίδα τύπου εγγράφου",
DlgDocIncXHTML		: "Να συμπεριληφθούν οι δηλώσεις XHTML",
DlgDocBgColor		: "Χρώμα φόντου",
DlgDocBgImage		: "Διεύθυνση εικόνας φόντου",
DlgDocBgNoScroll	: "Φόντο χωρίς κύλιση",
DlgDocCText			: "Κείμενο",
DlgDocCLink			: "Σύνδεσμος",
DlgDocCVisited		: "Σύνδεσμος που έχει επισκευθεί",
DlgDocCActive		: "Ενεργός σύνδεσμος",
DlgDocMargins		: "Περιθώρια σελίδας",
DlgDocMaTop			: "Κορυφή",
DlgDocMaLeft		: "Αριστερά",
DlgDocMaRight		: "Δεξιά",
DlgDocMaBottom		: "Κάτω",
DlgDocMeIndex		: "Λέξεις κλειδιά δείκτες εγγράφου (διαχωρισμός με κόμμα)",
DlgDocMeDescr		: "Περιγραφή εγγράφου",
DlgDocMeAuthor		: "Συγγραφέας",
DlgDocMeCopy		: "Πνευματικά δικαιώματα",
DlgDocPreview		: "Προεπισκόπηση",

// Templates Dialog
Templates			: "Πρότυπα",
DlgTemplatesTitle	: "Πρότυπα περιεχομένου",
DlgTemplatesSelMsg	: "Παρακαλώ επιλέξτε πρότυπο για εισαγωγή στο πρόγραμμα<br>(τα υπάρχοντα περιεχόμενα θα χαθούν):",
DlgTemplatesLoading	: "Φόρτωση καταλόγου προτύπων. Παρακαλώ περιμένετε...",
DlgTemplatesNoTpl	: "(Δεν έχουν καθοριστεί πρότυπα)",

// About Dialog
DlgAboutAboutTab	: "Σχετικά",
DlgAboutBrowserInfoTab	: "Πληροφορίες Browser",
DlgAboutLicenseTab	: "Άδεια",
DlgAboutVersion		: "έκδοση",
DlgAboutLicense		: "Άδεια χρήσης υπό τους όρους της GNU Lesser General Public License",
DlgAboutInfo		: "Για περισσότερες πληροφορίες"
}