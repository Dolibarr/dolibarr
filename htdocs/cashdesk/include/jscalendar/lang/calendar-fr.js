// ** I18N

// Calendar FR language
// Author: Jérémie Ollivier, <>
// Encoding: any
// Distributed under the same terms as the calendar itself.

// For translators: please use UTF-8 if possible.  We strongly believe that
// Unicode is the answer to a real internationalized world.  Also please
// include your contact information in the header, as can be seen above.

// full day names
Calendar._DN = new Array
("Dimanche",
 "Lundi",
 "Mardi",
 "Mercredi",
 "Jeudi",
 "Vendredi",
 "Samedi",
 "Dimanche");

// Please note that the following array of short day names (and the same goes
// for short month names, _SMN) isn't absolutely necessary.  We give it here
// for exemplification on how one can customize the short day names, but if
// they are simply the first N letters of the full name you can simply say:
//
//   Calendar._SDN_len = N; // short day name length
//   Calendar._SMN_len = N; // short month name length
//
// If N = 3 then this is not needed either since we assume a value of 3 if not
// present, to be compatible with translation files that were written before
// this feature.

// short day names
Calendar._SDN = new Array
("Dim",
 "Lun",
 "Mar",
 "Mer",
 "Jeu",
 "Ven",
 "Sam",
 "Dim");

// First day of the week. "0" means display Sunday first, "1" means display
// Monday first, etc.
Calendar._FD = 0;

// full month names
Calendar._MN = new Array
("Janvier",
 "Février",
 "Mars",
 "Avril",
 "Mai",
 "Juin",
 "Juillet",
 "Août",
 "Septembre",
 "Octobre",
 "Novembre",
 "Décembre");

// short month names
Calendar._SMN = new Array
("Jan",
 "Fev",
 "Mar",
 "Avr",
 "Mai",
 "Jui",
 "Jul",
 "Aou",
 "Sep",
 "Oct",
 "Nov",
 "Dec");

// tooltips
Calendar._TT = {};
Calendar._TT["INFO"] = "Aide";

Calendar._TT["ABOUT"] =
"Sélection de la date :\n\n" +
"- Utilisez les boutons \xab et \xbb pour sélectionner l'année\n" +
"- Utilisez les boutons " + String.fromCharCode(0x2039) + " et " + String.fromCharCode(0x203a) + " pour sélectionner le mois\n" +
"- Pour une sélection plus rapide, effectuez un clic prolongé sur un des boutons ci-dessus ";
Calendar._TT["ABOUT_TIME"] = "\n\n" +
"Sélection de l'heure :\n" +
"- Click on any of the time parts to increase it\n" +
"- or Shift-click to decrease it\n" +
"- or click and drag for faster selection.";

Calendar._TT["PREV_YEAR"] = "Année préc. (maintenir pour le menu)";
Calendar._TT["PREV_MONTH"] = "Mois suiv. (maintenir pour le menu)";
Calendar._TT["GO_TODAY"] = "Aujourd'hui";
Calendar._TT["NEXT_MONTH"] = "Mois suiv. (maintenir pour le menu)";
Calendar._TT["NEXT_YEAR"] = "Année suiv. (maintenir pour le menu)";
Calendar._TT["SEL_DATE"] = "Choisissez une date";
Calendar._TT["DRAG_TO_MOVE"] = "Déplacer";
Calendar._TT["PART_TODAY"] = " (Aujourd'hui)";

// the following is to inform that "%s" is to be the first day of week
// %s will be replaced with the day name.
Calendar._TT["DAY_FIRST"] = "Afficher %s en premier";

// This may be locale-dependent.  It specifies the week-end days, as an array
// of comma-separated numbers.  The numbers are from 0 to 6: 0 means Sunday, 1
// means Monday, etc.
Calendar._TT["WEEKEND"] = "0,6";

Calendar._TT["CLOSE"] = "Fermer";
Calendar._TT["TODAY"] = "Aujourd'hui";
Calendar._TT["TIME_PART"] = "Faire glisser la souris pour modifier";

// date formats
Calendar._TT["DEF_DATE_FORMAT"] = "%Y-%m-%d";
Calendar._TT["TT_DATE_FORMAT"] = "%A %e %B";

Calendar._TT["WK"] = "Sem";
Calendar._TT["TIME"] = "Heure :";
