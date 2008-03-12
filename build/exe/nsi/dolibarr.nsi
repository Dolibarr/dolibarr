; dolibarr.nsi
;

;--------------------------------
;Include Modern UI

!include "MUI.nsh"


;--------------------------------
;Configuration

!define MUI_PROD "Dolibarr" ;Define your own software name here
!define MUI_PRODUCT "Dolibarr 2.1" ;Define your own uninstall software name here
!define MUI_VERSION_DOT "2.1"      ;Define your own software version here
!define MUI_PUBLISHER "Rodolphe Quiedeville, Laurent Destailleur"
!define MUI_URL "http://www.dolibarr.org"
!define MUI_COMMENTS "Thanks for using Dolibarr"
!define MUI_HELPLINK "http://www.dolibarr.org"
!define MUI_URLUPDATE "http://www.dolibarr.org"


;!define MUI_HEADERIMAGE
;!define MUI_HEADERIMAGE_BITMAP "..\..\build\exe\dolibarr_bitmap1.bmp"


;General
Name "Dolibarr"
OutFile "dolibarr-${MUI_VERSION_DOT}.exe"
Icon "..\..\doc\images\dolibarr.ico"
UninstallIcon "..\..\doc\images\dolibarr.ico"
!define MUI_ICON "..\..\doc\images\dolibarr.ico"
!define MUI_UNICON "..\..\doc\images\dolibarr.ico"

BrandingText ""
;ShowInstDetails nevershow

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
  LangString PHPCHECK_TITLE ${LANG_ENGLISH} "PHP check"
  LangString PHPCHECK_SUBTITLE ${LANG_ENGLISH} "Check if a working PHP interpreter can be found"

  LangString PHPCHECK_TITLE ${LANG_FRENCH} "Verification PHP"
  LangString PHPCHECK_SUBTITLE ${LANG_FRENCH} "Verification si un interpreteur PHP operationnel peut etre trouvé"

  LangString SETUP_TITLE ${LANG_ENGLISH} "Setup"
  LangString SETUP_SUBTITLE ${LANG_ENGLISH} "Dolibarr files copying"

  LangString SETUP_TITLE ${LANG_FRENCH} "Installation"
  LangString SETUP_SUBTITLE ${LANG_FRENCH} "Installation des fichiers Dolibarr"

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



; Check for a PHP interpreter
Section "CheckPHP"

    !insertmacro MUI_HEADER_TEXT "$(PHPCHECK_TITLE)" "$(PHPCHECK_SUBTITLE)"
CHECKPHP:
	SearchPath $1 "php.exe"
	IfErrors NOPHP PHP
NOPHP:
	MessageBox MB_ABORTRETRYIGNORE "The installer did not find any PHP interpreter in your PATH.$\r$\ndolibarr can't work without PHP. You must install a web server that support PHP (For example the free Apache web server found at http://www.apache.org).$\r$\nContinue setup anyway ?" IDABORT ABORT IDRETRY CHECKPHP
PHP:
	GOTO NOABORT
ABORT:
	Abort "Dolibarr ${MUI_VERSION_DOT} setup has been canceled"
NOABORT:

SectionEnd



; Change page to show setup label
Section "SetupDolibarr"
	!insertmacro MUI_HEADER_TEXT "$(SETUP_TITLE)" "$(SETUP_SUBTITLE)"

BgImage::AddImage /NOUNLOAD "..\..\build\exe\dolibarr_bitmap1.bmp" 50 150


SectionEnd



; Copy the files into install directory
Section "Dolibarr" Dolibarr

	SetOutPath $INSTDIR
	File /x CVS /x .cvsignore /x Thumbs.db "..\..\*"
	File /r /x CVS /x .cvsignore /x Thumbs.db "..\..\doc"
	File /r /x CVS /x .cvsignore /x Thumbs.db "..\..\htdocs"
	File /r /x CVS /x .cvsignore /x Thumbs.db "..\..\mysql"
	File /r /x CVS /x .cvsignore /x Thumbs.db "..\..\pgsql"
	File /r /x CVS /x .cvsignore /x Thumbs.db "..\..\scripts"
	
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



Section "Clean files after install" CleanFiles

    RMDir /r "$INSTDIR\xxx"

SectionEnd



; Run setup script
;Section "Configure Apache Web server" Setup
;
;    !insertmacro MUI_HEADER_TEXT "$(SETUP_TITLE)" "$(SETUP_SUBTITLE)"
;	SetOutPath $INSTDIR
;	StrLen $2 $1
;	IntCmpU $2 0 NOCONFIGURE
;	ExecWait '"$1" "$INSTDIR\script\configure_apache.php"' $3
;NOCONFIGURE:
;	ExecShell open $INSTDIR\docs\dolibarr_setup.html SW_SHOWNORMAL 
;	BringToFront
;
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
