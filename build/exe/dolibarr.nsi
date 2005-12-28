; dolibarr.nsi
;

!include "MUI.nsh"


!define MUI_PROD "Dolibarr" ;Define your own software name here
!define MUI_PRODUCT "Dolibarr 2.0.0 beta1" ;Define your own uninstall software name here
!define MUI_VERSION_DOT "2.0.0-beta1" ;Define your own software version here
!define MUI_PUBLISHER "Rodolphe Quiedeville, Laurent Destailleur"
!define MUI_URL "http://dolibarr.com"
!define MUI_COMMENTS "Thanks for using Dolibarr"
!define MUI_HELPLINK "http://dolibarr.com"
!define MUI_URLUPDATE "http://dolibarr.com"


;--------------------------------
;Configuration

  ;General
  Name "Dolibarr"
  OutFile "dolibarr-${MUI_VERSION_DOT}.exe"
  Icon "..\..\doc\images\dolibarr.ico"
  UninstallIcon "..\..\doc\images\dolibarr.ico"
  !define MUI_ICON "..\..\doc\images\dolibarr.ico"
  !define MUI_UNICON "..\..\doc\images\dolibarr.ico"

  BrandingText ""
;  ShowInstDetails nevershow
  
  ;Set install dir
  InstallDir "$PROGRAMFILES\${MUI_PROD}"
  
  ;Get install folder from registry if available
  InstallDirRegKey HKCU "Software\${MUI_PROD}" ""

  CompletedText 'Dolibarr ${MUI_VERSION_DOT} setup completed.'



;--------------------------------
;Interface Settings

  !define MUI_ABORTWARNING


;--------------------------------
;Language Selection Dialog Settings

  ;Recupere la langue choisie pour la dernière installation
  !define MUI_LANGDLL_REGISTRY_ROOT "HKCU" 
  !define MUI_LANGDLL_REGISTRY_KEY "Software\${MUI_PROD}" 
  !define MUI_LANGDLL_REGISTRY_VALUENAME "Installer Language"


;--------------------------------
;Pages

;  !define MUI_SPECIALBITMAP "..\..\build\exe\dolibarr_bitmap1.bmp"
;  !define MUI_HEADERBITMAP "..\..\build\exe\dolibarr_bitmap2.bmp"
  !define MUI_SPECIALBITMAP "..\..\build\exe\dolibarr_bitmap1.bmp"
  !define MUI_HEADERBITMAP "..\..\build\exe\dolibarr_bitmap2.bmp"

  !insertmacro MUI_PAGE_WELCOME
  !insertmacro MUI_PAGE_LICENSE "..\..\COPYING"
;  !insertmacro MUI_PAGE_COMPONENTS
  !insertmacro MUI_PAGE_DIRECTORY
  !insertmacro MUI_PAGE_INSTFILES
  
  !insertmacro MUI_UNPAGE_CONFIRM
  !insertmacro MUI_UNPAGE_INSTFILES


;--------------------------------
;Languages
 
  !insertmacro MUI_LANGUAGE "English"
  !insertmacro MUI_LANGUAGE "French"

  
;--------------------------------
;Reserve Files
  
  ;These files should be inserted before other files in the data block
  ;Keep these lines before any File command
  ;Only for solid compression (by default, solid compression is enabled for BZIP2 and LZMA)
  
  !insertmacro MUI_RESERVEFILE_LANGDLL

  
;--------------------------------
;Language Strings

  ;Header
  LangString PERLCHECK_TITLE ${LANG_ENGLISH} "Perl check"
  LangString PERLCHECK_SUBTITLE ${LANG_ENGLISH} "Check if a working Perl interpreter can be found"
  LangString SETUP_TITLE ${LANG_ENGLISH} "Setup"
  LangString SETUP_SUBTITLE ${LANG_ENGLISH} "Dolibarr setups"

  ;Description
  LangString Dolibarr ${LANG_ENGLISH} "Dolibarr"
  LangString DESC_dolibarr ${LANG_ENGLISH} "dolibarr main files"

  LangString Dolibarr ${LANG_FRENCH} "Dolibarr"
  LangString DESC_dolibarr ${LANG_FRENCH} "Fichiers Dolibarr"


;--------------------------------
;Reserve Files
  
  ;Things that need to be extracted on first (keep these lines before any File command!)
  ;Only useful for BZIP2 compression
;  !insertmacro MUI_RESERVEFILE_WELCOMEFINISHPAGE
;  !insertmacro MUI_RESERVEFILE_INSTALLOPTION ;InstallOptions
;  !insertmacro MUI_RESERVEFILE_LANGDLL ;LangDLL (language selection dialog)





;--------------------------------
;Installer Sections

; Check for a Perl interpreter
Section "CheckPerl"
    !insertmacro MUI_HEADER_TEXT "$(PERLCHECK_TITLE)" "$(PERLCHECK_SUBTITLE)"
CHECKPERL:
	SearchPath $1 "perl.exe"
	IfErrors NOPERL PERL
NOPERL:
	MessageBox MB_ABORTRETRYIGNORE "The installer did not find any Perl interpreter in your PATH.$\r$\ndolibarr can't work without Perl. You must install one to use dolibarr (For example the free Perl found at http://activestate.com).$\r$\nContinue setup anyway ?" IDABORT ABORT IDRETRY CHECKPERL
PERL:
	GOTO NOABORT
ABORT:
	Abort "Dolibarr ${MUI_VERSION_DOT} setup has been canceled"
NOABORT:
SectionEnd


; Copy the files into install directory
Section "Dolibarr" Dolibarr

	SetOutPath $INSTDIR
	File "..\..\*"
	File /r "..\..\doc"
	File /r "..\..\htdocs"
	File /r "..\..\misc"
	File /r "..\..\mysql"
	File /r "..\..\pgsql"
	File /r "..\..\scripts"
	
	;Store install folder
    WriteRegStr HKCU "Software\${MUI_PROD}" "" $INSTDIR

	;Write uninstall entries
    WriteRegStr HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\${MUI_PROD}" "DisplayName" "${MUI_PRODUCT}"
    WriteRegStr HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\${MUI_PROD}" "UninstallString" "$INSTDIR/uninstall.exe"
    WriteRegStr HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\${MUI_PROD}" "Publisher" "${MUI_PUBLISHER}"

    WriteRegStr HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\${MUI_PROD}" "URLInfoAbout" "${MUI_URL}"
    WriteRegStr HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\${MUI_PROD}" "Comments" "${MUI_COMMENTS}"
    WriteRegStr HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\${MUI_PROD}" "HelpLink" "${MUI_HELPLINK}"
    WriteRegStr HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\${MUI_PROD}" "URLUpdateInfo" "${MUI_URLUPDATE}"
    WriteRegStr HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\${MUI_PROD}" "DisplayVersion" "${MUI_VERSION_DOT}"

	;Create uninstaller
	WriteUninstaller "uninstall.exe"

SectionEnd


Section "Clean CVS files" CvsClean

    RMDir /r "$INSTDIR\CVS"
    RMDir /r "$INSTDIR\doc\CVS"
    RMDir /r "$INSTDIR\doc\dev\CVS"
    RMDir /r "$INSTDIR\doc\dev\dbmodel\CVS"
    RMDir /r "$INSTDIR\doc\dev\php\CVS"
    RMDir /r "$INSTDIR\doc\dev\php\html\CVS"
    RMDir /r "$INSTDIR\doc\dev\php\latex\CVS"
    RMDir /r "$INSTDIR\doc\images\CVS"
    RMDir /r "$INSTDIR\doc\install\CVS"
    RMDir /r "$INSTDIR\doc\user\CVS"
    RMDir /r "$INSTDIR\htdocs\adherents\cartes\CVS"
    RMDir /r "$INSTDIR\htdocs\adherents\CVS"
    RMDir /r "$INSTDIR\htdocs\admin\CVS"
    RMDir /r "$INSTDIR\htdocs\admin\system\CVS"
    RMDir /r "$INSTDIR\htdocs\admin\update\CVS"
    RMDir /r "$INSTDIR\htdocs\bookmarks\CVS"
    RMDir /r "$INSTDIR\htdocs\boutique\auteur\CVS"
    RMDir /r "$INSTDIR\htdocs\boutique\client\CVS"
    RMDir /r "$INSTDIR\htdocs\boutique\commande\CVS"
    RMDir /r "$INSTDIR\htdocs\boutique\CVS"
    RMDir /r "$INSTDIR\htdocs\boutique\editeur\CVS"
    RMDir /r "$INSTDIR\htdocs\boutique\livre\CVS"
    RMDir /r "$INSTDIR\htdocs\boutique\newsletter\CVS"
    RMDir /r "$INSTDIR\htdocs\boutique\notification\CVS"
    RMDir /r "$INSTDIR\htdocs\categories\CVS"
    RMDir /r "$INSTDIR\htdocs\comm\action\CVS"
    RMDir /r "$INSTDIR\htdocs\comm\action\rapport\CVS"
    RMDir /r "$INSTDIR\htdocs\comm\CVS"
    RMDir /r "$INSTDIR\htdocs\comm\mailing\CVS"
    RMDir /r "$INSTDIR\htdocs\comm\propal\CVS"
    RMDir /r "$INSTDIR\htdocs\comm\propal\stats\CVS"
    RMDir /r "$INSTDIR\htdocs\comm\prospect\CVS"
    RMDir /r "$INSTDIR\htdocs\commande\CVS"
    RMDir /r "$INSTDIR\htdocs\commande\stats\CVS"
    RMDir /r "$INSTDIR\htdocs\compta\bank\CVS"
    RMDir /r "$INSTDIR\htdocs\compta\caisse\CVS"
    RMDir /r "$INSTDIR\htdocs\compta\charges\CVS"
    RMDir /r "$INSTDIR\htdocs\compta\CVS"
    RMDir /r "$INSTDIR\htdocs\compta\commande\CVS"
    RMDir /r "$INSTDIR\htdocs\compta\deplacement\CVS"
    RMDir /r "$INSTDIR\htdocs\compta\dons\CVS"
    RMDir /r "$INSTDIR\htdocs\compta\dons\formulaire\CVS"
    RMDir /r "$INSTDIR\htdocs\compta\export\CVS"
    RMDir /r "$INSTDIR\htdocs\compta\export\modules\CVS"
    RMDir /r "$INSTDIR\htdocs\compta\facture\CVS"
    RMDir /r "$INSTDIR\htdocs\compta\facture\stats\CVS"
    RMDir /r "$INSTDIR\htdocs\compta\paiement\CVS"
    RMDir /r "$INSTDIR\htdocs\compta\param\CVS"
    RMDir /r "$INSTDIR\htdocs\compta\param\comptes\CVS"
    RMDir /r "$INSTDIR\htdocs\compta\prelevement\CVS"
    RMDir /r "$INSTDIR\htdocs\compta\resultat\CVS"
    RMDir /r "$INSTDIR\htdocs\compta\sociales\CVS"
    RMDir /r "$INSTDIR\htdocs\compta\stats\CVS"
    RMDir /r "$INSTDIR\htdocs\compta\tva\CVS"
    RMDir /r "$INSTDIR\htdocs\compta\ventilation\CVS"
    RMDir /r "$INSTDIR\htdocs\compta\ventilation\fournisseur\CVS"
    RMDir /r "$INSTDIR\htdocs\compta\voyage\CVS"
    RMDir /r "$INSTDIR\htdocs\conf\CVS"
    RMDir /r "$INSTDIR\htdocs\contact\CVS"
    RMDir /r "$INSTDIR\htdocs\contrat\CVS"
    RMDir /r "$INSTDIR\htdocs\CVS"
    RMDir /r "$INSTDIR\htdocs\domain\CVS"
    RMDir /r "$INSTDIR\htdocs\dossier\CVS"
    RMDir /r "$INSTDIR\htdocs\dossier\client\CVS"
    RMDir /r "$INSTDIR\htdocs\energie\CVS"
    RMDir /r "$INSTDIR\htdocs\expedition\CVS"
    RMDir /r "$INSTDIR\htdocs\expedition\mods\CVS"
    RMDir /r "$INSTDIR\htdocs\expedition\mods\pdf\CVS"
    RMDir /r "$INSTDIR\htdocs\expedition\stats\CVS"
    RMDir /r "$INSTDIR\htdocs\exports\CVS"
    RMDir /r "$INSTDIR\htdocs\fichinter\CVS"
    RMDir /r "$INSTDIR\htdocs\fourn\CVS"
    RMDir /r "$INSTDIR\htdocs\fourn\commande\CVS"
    RMDir /r "$INSTDIR\htdocs\fourn\facture\CVS"
    RMDir /r "$INSTDIR\htdocs\fourn\paiement\CVS"
    RMDir /r "$INSTDIR\htdocs\fourn\product\CVS"
    RMDir /r "$INSTDIR\htdocs\includes\boxes\CVS"
    RMDir /r "$INSTDIR\htdocs\includes\CVS"
    RMDir /r "$INSTDIR\htdocs\includes\fpdf\CVS"
    RMDir /r "$INSTDIR\htdocs\includes\fpdf\code39ext\CVS"
    RMDir /r "$INSTDIR\htdocs\includes\fpdf\font\CVS"
    RMDir /r "$INSTDIR\htdocs\includes\fpdf\fpdf\CVS"
    RMDir /r "$INSTDIR\htdocs\includes\fpdf\fpdf\font\CVS"
    RMDir /r "$INSTDIR\htdocs\includes\fpdf\i25\CVS"
    RMDir /r "$INSTDIR\htdocs\includes\magpierss\CVS"
    RMDir /r "$INSTDIR\htdocs\includes\magpierss\extlib\CVS"
    RMDir /r "$INSTDIR\htdocs\includes\menus\CVS"
    RMDir /r "$INSTDIR\htdocs\includes\menus\barre_top\CVS"
    RMDir /r "$INSTDIR\htdocs\includes\menus\barre_left\CVS"
    RMDir /r "$INSTDIR\htdocs\includes\modules\CVS"
    RMDir /r "$INSTDIR\htdocs\includes\modules\commande\CVS"
    RMDir /r "$INSTDIR\htdocs\includes\modules\dons\CVS"
    RMDir /r "$INSTDIR\htdocs\includes\modules\expedition\CVS"
    RMDir /r "$INSTDIR\htdocs\includes\modules\facture\CVS"
    RMDir /r "$INSTDIR\htdocs\includes\modules\facture\deneb\CVS"
    RMDir /r "$INSTDIR\htdocs\includes\modules\facture\janus\CVS"
    RMDir /r "$INSTDIR\htdocs\includes\modules\facture\jupiter\CVS"
    RMDir /r "$INSTDIR\htdocs\includes\modules\facture\lune\CVS"
    RMDir /r "$INSTDIR\htdocs\includes\modules\facture\mars\CVS"
    RMDir /r "$INSTDIR\htdocs\includes\modules\facture\mercure\CVS"
    RMDir /r "$INSTDIR\htdocs\includes\modules\facture\neptune\CVS"
    RMDir /r "$INSTDIR\htdocs\includes\modules\facture\pluton\CVS"
    RMDir /r "$INSTDIR\htdocs\includes\modules\facture\saturne\CVS"
    RMDir /r "$INSTDIR\htdocs\includes\modules\facture\terre\CVS"
    RMDir /r "$INSTDIR\htdocs\includes\modules\facture\uranus\CVS"
    RMDir /r "$INSTDIR\htdocs\includes\modules\facture\venus\CVS"
    RMDir /r "$INSTDIR\htdocs\includes\modules\fichinter\CVS"
    RMDir /r "$INSTDIR\htdocs\includes\modules\mailings\CVS"
    RMDir /r "$INSTDIR\htdocs\includes\modules\propale\CVS"
    RMDir /r "$INSTDIR\htdocs\includes\modules\rapport\CVS"
    RMDir /r "$INSTDIR\htdocs\includes\modules\societe\CVS"
    RMDir /r "$INSTDIR\htdocs\includes\pear\Auth\Container\CVS"
    RMDir /r "$INSTDIR\htdocs\includes\pear\Auth\CVS"
    RMDir /r "$INSTDIR\htdocs\includes\pear\CVS"
    RMDir /r "$INSTDIR\htdocs\includes\pear\DB\CVS"
    RMDir /r "$INSTDIR\htdocs\includes\phplot\CVS"
    RMDir /r "$INSTDIR\htdocs\includes\php_writeexcel\CVS"
    RMDir /r "$INSTDIR\htdocs\includes\triggers\CVS"
    RMDir /r "$INSTDIR\htdocs\install\CVS"
    RMDir /r "$INSTDIR\htdocs\install\doc\CVS"
    RMDir /r "$INSTDIR\htdocs\langs\CVS"
    RMDir /r "$INSTDIR\htdocs\langs\en_US\CVS"
    RMDir /r "$INSTDIR\htdocs\langs\en_US\html\CVS"
    RMDir /r "$INSTDIR\htdocs\langs\fr_BE\CVS"
    RMDir /r "$INSTDIR\htdocs\langs\fr_BE\html\CVS"
    RMDir /r "$INSTDIR\htdocs\langs\fr_FR\CVS"
    RMDir /r "$INSTDIR\htdocs\langs\fr_FR\html\CVS"
    RMDir /r "$INSTDIR\htdocs\langs\nl_BE\CVS"
    RMDir /r "$INSTDIR\htdocs\langs\nl_BE\html\CVS"
    RMDir /r "$INSTDIR\htdocs\langs\pt_PT\CVS"
    RMDir /r "$INSTDIR\htdocs\langs\pt_PT\html\CVS"
    RMDir /r "$INSTDIR\htdocs\lib\CVS"
    RMDir /r "$INSTDIR\htdocs\lib\jabber\CVS"
    RMDir /r "$INSTDIR\htdocs\lib\vcard\CVS"
    RMDir /r "$INSTDIR\htdocs\lolix\CVS"
    RMDir /r "$INSTDIR\htdocs\lolix\cv\CVS"
    RMDir /r "$INSTDIR\htdocs\lolix\societe\CVS"
    RMDir /r "$INSTDIR\htdocs\postnuke\articles\CVS"
    RMDir /r "$INSTDIR\htdocs\postnuke\CVS"
    RMDir /r "$INSTDIR\htdocs\product\album\CVS"
    RMDir /r "$INSTDIR\htdocs\product\categorie\CVS"
    RMDir /r "$INSTDIR\htdocs\product\concert\CVS"
    RMDir /r "$INSTDIR\htdocs\product\critiques\CVS"
    RMDir /r "$INSTDIR\htdocs\product\CVS"
    RMDir /r "$INSTDIR\htdocs\product\groupart\CVS"
    RMDir /r "$INSTDIR\htdocs\product\promotion\CVS"
    RMDir /r "$INSTDIR\htdocs\product\stats\CVS"
    RMDir /r "$INSTDIR\htdocs\product\stock\CVS"
    RMDir /r "$INSTDIR\htdocs\projet\CVS"
    RMDir /r "$INSTDIR\htdocs\projet\activity\CVS"
    RMDir /r "$INSTDIR\htdocs\projet\tasks\CVS"
    RMDir /r "$INSTDIR\htdocs\public\adherents\CVS"
    RMDir /r "$INSTDIR\htdocs\public\CVS"
    RMDir /r "$INSTDIR\htdocs\public\dons\CVS"
    RMDir /r "$INSTDIR\htdocs\rapport\CVS"
    RMDir /r "$INSTDIR\htdocs\societe\CVS"
    RMDir /r "$INSTDIR\htdocs\societe\notify\CVS"
    RMDir /r "$INSTDIR\htdocs\telephonie\adsl\CVS"
    RMDir /r "$INSTDIR\htdocs\telephonie\ca\CVS"
    RMDir /r "$INSTDIR\htdocs\telephonie\client\CVS"
    RMDir /r "$INSTDIR\htdocs\telephonie\config\concurrents\CVS"
    RMDir /r "$INSTDIR\htdocs\telephonie\config\CVS"
    RMDir /r "$INSTDIR\htdocs\telephonie\contrat\CVS"
    RMDir /r "$INSTDIR\htdocs\telephonie\distributeurs\CVS"
    RMDir /r "$INSTDIR\htdocs\telephonie\distributeurs\comm\CVS"
    RMDir /r "$INSTDIR\htdocs\telephonie\facture\CVS"
    RMDir /r "$INSTDIR\htdocs\telephonie\fournisseur\commande\CVS"
    RMDir /r "$INSTDIR\htdocs\telephonie\fournisseur\CVS"
    RMDir /r "$INSTDIR\htdocs\telephonie\ligne\commande\retour\CVS"
    RMDir /r "$INSTDIR\htdocs\telephonie\ligne\commande\CVS"
    RMDir /r "$INSTDIR\htdocs\telephonie\ligne\CVS"
    RMDir /r "$INSTDIR\htdocs\telephonie\pdf\CVS"
    RMDir /r "$INSTDIR\htdocs\telephonie\script\CVS"
    RMDir /r "$INSTDIR\htdocs\telephonie\script\tools\CVS"
    RMDir /r "$INSTDIR\htdocs\telephonie\service\CVS"
    RMDir /r "$INSTDIR\htdocs\telephonie\service\modules\CVS"
    RMDir /r "$INSTDIR\htdocs\telephonie\sql\CVS"
    RMDir /r "$INSTDIR\htdocs\telephonie\stats\clients\CVS"
    RMDir /r "$INSTDIR\htdocs\telephonie\stats\commerciaux\groupes\CVS"
    RMDir /r "$INSTDIR\htdocs\telephonie\stats\commerciaux\CVS"
    RMDir /r "$INSTDIR\htdocs\telephonie\stats\communications\CVS"
    RMDir /r "$INSTDIR\htdocs\telephonie\stats\consultation\CVS"
    RMDir /r "$INSTDIR\htdocs\telephonie\stats\contrats\CVS"
    RMDir /r "$INSTDIR\htdocs\telephonie\stats\destinations\CVS"
    RMDir /r "$INSTDIR\htdocs\telephonie\stats\distributeurs\CVS"
    RMDir /r "$INSTDIR\htdocs\telephonie\stats\factures\CVS"
    RMDir /r "$INSTDIR\htdocs\telephonie\stats\fournisseurs\CVS"
    RMDir /r "$INSTDIR\htdocs\telephonie\stats\graph\CVS"
    RMDir /r "$INSTDIR\htdocs\telephonie\stats\lignes\CVS"
    RMDir /r "$INSTDIR\htdocs\telephonie\stats\CVS"
    RMDir /r "$INSTDIR\htdocs\telephonie\tarifs\CVS"
    RMDir /r "$INSTDIR\htdocs\telephonie\CVS"
    RMDir /r "$INSTDIR\htdocs\theme\CVS"
    RMDir /r "$INSTDIR\htdocs\theme\dev\CVS"
    RMDir /r "$INSTDIR\htdocs\theme\dev\img\CVS"
    RMDir /r "$INSTDIR\htdocs\theme\dolibarr\CVS"
    RMDir /r "$INSTDIR\htdocs\theme\dolibarr\img\CVS"
    RMDir /r "$INSTDIR\htdocs\theme\eldy\CVS"
    RMDir /r "$INSTDIR\htdocs\theme\eldy\img\CVS"
    RMDir /r "$INSTDIR\htdocs\theme\freelug\CVS"
    RMDir /r "$INSTDIR\htdocs\theme\freelug\img\CVS"
    RMDir /r "$INSTDIR\htdocs\theme\rodolphe\CVS"
    RMDir /r "$INSTDIR\htdocs\theme\yellow\CVS"
    RMDir /r "$INSTDIR\htdocs\theme\yellow\img\CVS"
    RMDir /r "$INSTDIR\htdocs\user\CVS"
    RMDir /r "$INSTDIR\htdocs\user\admin\CVS"
    RMDir /r "$INSTDIR\htdocs\user\group\CVS"
    RMDir /r "$INSTDIR\htdocs\user\modules\CVS"
    RMDir /r "$INSTDIR\misc\CVS"
    RMDir /r "$INSTDIR\misc\purgedemo\CVS"
    RMDir /r "$INSTDIR\mysql\CVS"
    RMDir /r "$INSTDIR\mysql\data\CVS"
    RMDir /r "$INSTDIR\mysql\data\dev\CVS"
    RMDir /r "$INSTDIR\mysql\migration\CVS"
    RMDir /r "$INSTDIR\mysql\tables\CVS"
    RMDir /r "$INSTDIR\pgsql\CVS"
    RMDir /r "$INSTDIR\pgsql\data\CVS"
    RMDir /r "$INSTDIR\pgsql\functions\CVS"
    RMDir /r "$INSTDIR\pgsql\tables\CVS"
    RMDir /r "$INSTDIR\scripts\adherents\CVS"
    RMDir /r "$INSTDIR\scripts\adherents\mailman\CVS"
    RMDir /r "$INSTDIR\scripts\banque\CVS"
    RMDir /r "$INSTDIR\scripts\energie\CVS"
    RMDir /r "$INSTDIR\scripts\lolix\CVS"
    RMDir /r "$INSTDIR\scripts\CVS"
    

SectionEnd


; Run setup script
;Section "Create config file" Setup
;    !insertmacro MUI_HEADER_TEXT "$(SETUP_TITLE)" "$(SETUP_SUBTITLE)"
;	SetOutPath $INSTDIR
;	StrLen $2 $1
;	IntCmpU $2 0 NOCONFIGURE
;	ExecWait '"$1" "$INSTDIR\tools\configure.pl"' $3
;NOCONFIGURE:
;	ExecShell open $INSTDIR\docs\dolibarr_setup.html SW_SHOWNORMAL 
;	BringToFront
;SectionEnd



;--------------------------------
;Descriptions

!insertmacro MUI_FUNCTION_DESCRIPTION_BEGIN
  !insertmacro MUI_DESCRIPTION_TEXT ${Dolibarr} $(DESC_Dolibarr)
!insertmacro MUI_FUNCTION_DESCRIPTION_END
 


;--------------------------------
;Uninstaller Section

Section "Uninstall"

  DeleteRegKey /ifempty HKCU "Software\${MUI_PROD}"

  Delete "$INSTDIR\Uninstall.exe"

  RMDir /r "$INSTDIR"
  
  DeleteRegKey /ifempty HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\${MUI_PROD}"

SectionEnd




!define MUI_FINISHPAGE
