; dolibarr.nsi
;

!define MUI_PRODUCT "Dolibarr" ;Define your own software name here
!define MUI_VERSION "2.0" ;Define your own software version here
!define MUI_VERSION_NODOT "20" ;Define your own software version here
!define MUI_PUBLISHER "Rodolphe Quiedeville, Laurent Destailleur"
!define MUI_URL "http://dolibarr.com"


!include "MUI.nsh"

;--------------------------------
;Configuration

  ;General
  OutFile "dolibarr-${MUI_VERSION_NODOT}.exe"
  Icon "C:\temp\buildroot\dolibarr-${MUI_VERSION}\docs\images\dolibarr.ico"
  UninstallIcon "C:\temp\buildroot\dolibarr-${MUI_VERSION}\docs\images\dolibarr.ico"
  !define MUI_ICON "C:\temp\buildroot\dolibarr-${MUI_VERSION}\docs\images\dolibarr.ico"
  !define MUI_UNICON "C:\temp\buildroot\dolibarr-${MUI_VERSION}\docs\images\dolibarr.ico"

  BrandingText ""
;  ShowInstDetails nevershow
  CompletedText 'Read opened dolibarr ${MUI_VERSION} documentation page to continue setup process.'

  ;Set install dir
  InstallDir "$PROGRAMFILES\${MUI_PRODUCT}"
  
  ;Get install folder from registry if available
  InstallDirRegKey HKCU "Software\${MUI_PRODUCT}" ""




;--------------------------------
;Modern UI Configuration

  !define MUI_WELCOMEPAGE

  !define MUI_LICENSEPAGE

;  !define MUI_COMPONENTSPAGE

  !define MUI_DIRECTORYPAGE
 
  !define MUI_ABORTWARNING
  
  !define MUI_UNINSTALLER

  !define MUI_UNCONFIRMPAGE

;  !define MUI_SPECIALBITMAP "C:\Mes Developpements\dolibarr\build\exe\dolibarr_bitmap1.bmp"
;  !define MUI_HEADERBITMAP "C:\Mes Developpements\dolibarr\build\exe\dolibarr_bitmap2.bmp"
  !define MUI_SPECIALBITMAP ".\dolibarr_bitmap1.bmp"
  !define MUI_HEADERBITMAP ".\dolibarr_bitmap2.bmp"

;--------------------------------
;Languages
 
  !insertmacro MUI_LANGUAGE "English"
  
;--------------------------------
;Language Strings

  ;Header
  LangString PERLCHECK_TITLE ${LANG_ENGLISH} "Perl check"
  LangString PERLCHECK_SUBTITLE ${LANG_ENGLISH} "Check if a working Perl interpreter can be found"
  LangString SETUP_TITLE ${LANG_ENGLISH} "Setup"
  LangString SETUP_SUBTITLE ${LANG_ENGLISH} "Building dolibarr config files"

  ;Description
  LangString DESC_dolibarr ${LANG_ENGLISH} "dolibarr main files"


;--------------------------------
;Data
  
  LicenseData "C:\temp\buildroot\dolibarr-${MUI_VERSION}\COPYRIGHT"

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
	Abort "dolibarr ${MUI_VERSION} setup has been canceled"
NOABORT:
SectionEnd


; Copy the files into install directory
Section "dolibarr" dolibarr

	SetOutPath $INSTDIR
	File /r "C:\temp\buildroot\dolibarr-${MUI_VERSION}\*"
	
	;Store install folder
    WriteRegStr HKCU "Software\${MUI_PRODUCT}" "" $INSTDIR

	;Write uninstall entries
    WriteRegStr HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\${MUI_PRODUCT}" "DisplayName" "${MUI_PRODUCT}"
    WriteRegStr HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\${MUI_PRODUCT}" "UninstallString" "$INSTDIR/uninstall.exe"
    WriteRegStr HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\${MUI_PRODUCT}" "Publisher" "${MUI_PUBLISHER}"

    WriteRegStr HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\${MUI_PRODUCT}" "URLInfoAbout" "${MUI_URL}"
    WriteRegStr HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\${MUI_PRODUCT}" "Comments" "${MUI_COMMENTS}"
    WriteRegStr HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\${MUI_PRODUCT}" "HelpLink" "${MUI_HELPLINK}"
    WriteRegStr HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\${MUI_PRODUCT}" "URLUpdateInfo" "${MUI_URLUPDATE}"
    WriteRegStr HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\${MUI_PRODUCT}" "DisplayVersion" "${MUI_VERSION}"

	;Create uninstaller
	WriteUninstaller "uninstall.exe"

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

!insertmacro MUI_FUNCTIONS_DESCRIPTION_BEGIN
  !insertmacro MUI_DESCRIPTION_TEXT ${dolibarr} $(DESC_dolibarr)
!insertmacro MUI_FUNCTIONS_DESCRIPTION_END
 


;--------------------------------
;Uninstaller Section

Section "Uninstall"

  DeleteRegKey /ifempty HKCU "Software\${MUI_PRODUCT}"

  Delete "$INSTDIR\Uninstall.exe"

  RMDir /r "$INSTDIR"

SectionEnd




!define MUI_FINISHPAGE
