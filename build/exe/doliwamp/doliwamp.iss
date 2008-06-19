; ----- DoliWamp.iss ---------------------------------------------------------------------
; Script to build an auto installer for Dolibarr.
; Idea from WampServer 2 (http://www.wampserver.com)
;----------------------------------------------------------------------------------------
; You must edit some path in this file to build an exe.
; WARNING: Be sure that user files for Mysql data used to build
; package contains only user root with no password.
; For this, you can edit mysql.user table for a database to keep
; only root user with no password, stop server and catch
; files user.MY* to put them in data sources.
;
; Version: $Id$
;----------------------------------------------------------------------------------------


[Setup]
; ----- Change this -----
AppName=DoliWamp
; DoliWamp x.x or DoliWamp x.x-dev
AppVerName=DoliWamp 2.4-dev
; DoliWamp x.x or DoliWamp x.x-dev
OutputBaseFilename=DoliWamp 2.4-dev
; Define full path from wich all relative path are defined
; You must modify this to put here your dolibarr root directory
SourceDir=E:\Mes Developpements\dolibarr
; ----- End of change
AppId=doliwamp
AppPublisher=NLTechno
AppPublisherURL=http://www.nltechno.com
AppSupportURL=http://www.dolibarr.org
AppUpdatesURL=http://www.dolibarr.org
AppComments=DoliWamp includes Dolibarr, Apache, PHP and Mysql softwares.
AppCopyright=Copyright (C) 2008 Laurent Destailleur, NLTechno
DefaultDirName=c:\dolibarr
DefaultGroupName=Dolibarr
LicenseFile=COPYING
;Compression=none
Compression=lzma
SolidCompression=yes
WizardImageFile=build\exe\doliwamp\doliwamp.bmp
WizardSmallImageFile=build\exe\doliwamp\doliwampsmall.bmp
SetupIconFile=doc\images\dolibarr.ico
PrivilegesRequired=poweruser
DisableProgramGroupPage=yes
ChangesEnvironment=no
CreateUninstallRegKey=yes
;UninstallDisplayIcon={app}\bidon
OutputDir=build

[Tasks]
;Name: "autostart"; Description: "Automatically launch DoliWamp server on startup. If you check this option, Services will be installed as automatic. Otherwise, services will be installed as manual and will start and stop with the service manager."; GroupDescription: "Auto Start:" ;Flags: unchecked;
Name: quicklaunchicon; Description: "Create a &Quick Launch icon"; GroupDescription: "Additional icons:"; Flags: unchecked
Name: "desktopicon"; Description: "Create a &Desktop icon"; GroupDescription: "Additional icons:"; Flags: unchecked

[Dirs]
Name: "{app}\logs"
Name: "{app}\tmp"
Name: "{app}\dolibarr_documents"
Name: "{app}\bin\apache\apache2.2.6\logs"

[Files]
; Stop/start
Source: "build\exe\doliwamp\stopdoliwamp.bat"; DestDir: "{app}\"; Flags: ignoreversion; AfterInstall: close()
Source: "build\exe\doliwamp\startdoliwamp.bat"; DestDir: "{app}\"; Flags: ignoreversion;
Source: "build\exe\doliwamp\removefiles.bat"; DestDir: "{app}\"; Flags: ignoreversion;
Source: "build\exe\doliwamp\rundoliwamp.bat.install"; DestDir: "{app}\"; Flags: ignoreversion;
Source: "build\exe\doliwamp\install_services.bat.install"; DestDir: "{app}\"; Flags: ignoreversion;
Source: "build\exe\doliwamp\uninstall_services.bat.install"; DestDir: "{app}\"; Flags: ignoreversion;
Source: "build\exe\doliwamp\mysqlinitpassword.bat.install"; DestDir: "{app}\"; Flags: ignoreversion;
Source: "build\exe\doliwamp\mysqltestinstall.bat.install"; DestDir: "{app}\"; Flags: ignoreversion;
; PhpMyAdmin, Apache, Php, Mysql
; Put here path of Wampserver applications
Source: "C:\Program Files\Wamp\apps\phpmyadmin2.10.1\*.*"; DestDir: "{app}\apps\phpmyadmin2.10.1"; Flags: ignoreversion recursesubdirs; Excludes: "config.inc.php,wampserver.conf,*.log,*_log"
Source: "C:\Program Files\Wamp\bin\apache\apache2.2.6\*.*"; DestDir: "{app}\bin\apache\apache2.2.6"; Flags: ignoreversion recursesubdirs; Excludes: "php.ini,httpd.conf,wampserver.conf,*.log,*_log"
Source: "C:\Program Files\Wamp\bin\php\php5.2.5\*.*"; DestDir: "{app}\bin\php\php5.2.5"; Flags: ignoreversion recursesubdirs; Excludes: "php.ini,phpForApache.ini,wampserver.conf,*.log,*_log"
Source: "C:\Program Files\Wamp\bin\mysql\mysql5.0.45\*.*"; DestDir: "{app}\bin\mysql\mysql5.0.45"; Flags: ignoreversion recursesubdirs; Excludes: "my.ini,data\*,wampserver.conf,*.log,*_log"
; Mysql data files (does not overwrite if exists)
Source: "build\exe\doliwamp\mysql\*.*"; DestDir: "{app}\bin\mysql\mysql5.0.45\data\mysql"; Flags: onlyifdoesntexist ignoreversion recursesubdirs; Excludes: ".cvsignore,.project,CVS\*,Thumbs.db"
; Dolibarr
Source: "external-libs\*.*"; DestDir: "{app}\www\dolibarr\external-libs"; Flags: ignoreversion recursesubdirs; Excludes: ".cvsignore,.project,CVS\*,Thumbs.db"
Source: "htdocs\*.*"; DestDir: "{app}\www\dolibarr\htdocs"; Flags: ignoreversion recursesubdirs; Excludes: ".cvsignore,.project,CVS\*,Thumbs.db,telephonie\*,*\conf.php,*\install.forced.php,*\modDocument.class.php,*\modTelephonie.class.php,*\modEnergie.class.php,*\interface_modEditeur_Editeur.class.php*,*\rodolphe"
Source: "doc\*.*"; DestDir: "{app}\www\dolibarr\doc"; Flags: ignoreversion recursesubdirs; Excludes: ".cvsignore,.project,CVS\*,Thumbs.db,wiki\*,plaquette\*,dev\*"
Source: "dev\*.*"; DestDir: "{app}\www\dolibarr\dev"; Flags: ignoreversion recursesubdirs; Excludes: ".cvsignore,.project,CVS\*,Thumbs.db"
Source: "mysql\*.*"; DestDir: "{app}\www\dolibarr\mysql"; Flags: ignoreversion recursesubdirs; Excludes: ".cvsignore,.project,CVS\*,Thumbs.db"
Source: "scripts\*.*"; DestDir: "{app}\www\dolibarr\scripts"; Flags: ignoreversion recursesubdirs; Excludes: ".cvsignore,.project,CVS\*,Thumbs.db"
Source: "*.*"; DestDir: "{app}\www\dolibarr"; Flags: ignoreversion; Excludes: ".cvsignore,.project,CVS\*,Thumbs.db"
; Config files
Source: "build\exe\doliwamp\phpmyadmin.conf.install"; DestDir: "{app}\alias"; Flags: ignoreversion;
Source: "build\exe\doliwamp\dolibarr.conf.install"; DestDir: "{app}\alias"; Flags: ignoreversion;
Source: "build\exe\doliwamp\config.inc.php.install"; DestDir: "{app}\apps\phpmyadmin2.10.1"; Flags: ignoreversion;
Source: "build\exe\doliwamp\httpd.conf.install"; DestDir: "{app}\bin\apache\apache2.2.6\conf"; Flags: ignoreversion;
Source: "build\exe\doliwamp\my.ini.install"; DestDir: "{app}\bin\mysql\mysql5.0.45"; Flags: ignoreversion;
Source: "build\exe\doliwamp\php.ini.install"; DestDir: "{app}\bin\php\php5.2.5"; Flags: ignoreversion;
Source: "build\exe\doliwamp\index.php.install"; DestDir: "{app}\www"; Flags: ignoreversion;
Source: "build\exe\doliwamp\install.forced.php.install"; DestDir: "{app}\www\dolibarr\htdocs\install"; Flags: ignoreversion;
; Licence
Source: "COPYRIGHT"; DestDir: "{app}"; Flags: ignoreversion;



[Icons]
Name: "{group}\Dolibarr"; Filename: "{app}\rundoliwamp.bat"; WorkingDir: "{app}"; IconFilename: {app}\www\dolibarr\doc\images\dolibarr.ico
Name: "{group}\Start DoliWamp server"; Filename: "{app}\startdoliwamp.bat"; WorkingDir: "{app}"; IconFilename: {app}\www\dolibarr\doc\images\doliwampon.ico
Name: "{group}\Stop DoliWamp server"; Filename: "{app}\stopdoliwamp.bat"; WorkingDir: "{app}"; IconFilename: {app}\www\dolibarr\doc\images\doliwampoff.ico
Name: "{group}\Uninstall DoliWamp"; Filename: "{app}\unins000.exe"; WorkingDir: "{app}"; IconFilename: {app}\uninstall_services.bat
Name: "{userappdata}\Microsoft\Internet Explorer\Quick Launch\Dolibarr"; WorkingDir: "{app}"; Filename: "{app}\rundoliwamp.bat"; Tasks: quicklaunchicon; IconFilename: {app}\www\dolibarr\doc\images\dolibarr.ico
Name: "{userdesktop}\Dolibarr"; Filename: "{app}\rundoliwamp.bat"; WorkingDir: "{app}"; Tasks: desktopicon; IconFilename: {app}\www\dolibarr\doc\images\dolibarr.ico
;Start of servers fromstartup menu disabled as services are auto
;Name: "{userstartup}\DoliWamp server"; Filename: "{app}\startdoliwamp.bat"; WorkingDir: "{app}"; Flags: runminimized; IconFilename: {app}\www\dolibarr\doc\images\dolibarr.ico


[Code]

//variables globales
var phpVersion: String;
var apacheVersion: String;
var path: String;
var pathWithSlashes: String;
var Page: TInputQueryWizardPage;

var smtpServer: String;
var apachePort: String;
var mysqlPort: String;
var newPassword: String;

var srcFile: String;
var destFile: String;
var srcContents: String;
var browser: String;
var winPath: String;
var mysqlVersion: String;
var wampserverVersion: String;
var phpmyadminVersion: String;
var sqlitemanagerVersion: String;
var tmp: String;
var phpDllCopy: String;
var batFile: String;

var mysmtp: String;
var myporta: String;
var myport: String;
var mypass: String;

var firstinstall: Boolean;
var value: String;


//-----------------------------------------------

//procedures lancées au début de l'installation

function InitializeSetup(): Boolean;
begin
  Result := MsgBox('You will install or upgrade DoliWamp (Apache+Mysql+PHP+Dolibarr) on your computer.' #13#13 'This setup install Dolibarr and third party softwares (Apache, Mysql and PHP) configured for a Dolibarr usage.' #13#13 'If you want to share your Apache, Mysql and PHP with other projects than Dolibarr, it is recommended to make ' #13 'a manual installation of Dolibarr on your own Apache, Mysql and PHP installation.' #13#13 'Do you want to continue install ?', mbConfirmation, MB_YESNO) = idYes;
end;

procedure InitializeWizard();
begin
  //version des applis, à modifier pour chaque version de WampServer 2
  apacheVersion := '2.2.6';
  phpVersion := '5.2.5' ;
  mysqlVersion := '5.0.45';
  wampserverVersion := '2.0';
  phpmyadminVersion := '2.10.1';
  sqlitemanagerVersion := '1.2.0';

  smtpServer := 'localhost';
  apachePort := '81';
  mysqlPort := '3307';
  newPassword := 'changeme';

  firstinstall := true;


  //LoadStringFromFile (srcFile, srcContents);
  //posvalue=Pos('$dolibarr_main_db_port=', srcFile);

  if RegQueryStringValue(HKEY_LOCAL_MACHINE, 'SOFTWARE\NLTechno\DoliWamp','smtpServer', value) then
  begin
      if value <> '' then smtpServer:=value;
  end
  if RegQueryStringValue(HKEY_LOCAL_MACHINE, 'SOFTWARE\NLTechno\DoliWamp','apachePort', value) then
  begin
      if value <> '' then apachePort:=value;
  end
  if RegQueryStringValue(HKEY_LOCAL_MACHINE, 'SOFTWARE\NLTechno\DoliWamp','mysqlPort', value) then
  begin
      if value <> '' then mysqlPort:=value;
  end
  if RegQueryStringValue(HKEY_LOCAL_MACHINE, 'SOFTWARE\NLTechno\DoliWamp','newPassword', value) then
  begin
      if value <> '' then newPassword:=value;
  end


  // Create a page wpInstalling
  Page := CreateInputQueryPage(wpInstalling,
  'Technical parameters', '',
  'Please specify some technical parameters. If you don t understand or ' #13 'are not sure, just leave the default values.');

  // TODO Add control differently if first install or update
  Page.Add('SMTP server (your own or ISP SMTP server) :', False);
  Page.Add('Apache port (common choice is 80) :', False);
  Page.Add('Mysql port (common choice is 3306) :', False);
  Page.Add('Mysql server and database password you want for root :', False);

  // Valeurs par defaut
  Page.Values[0] := smtpServer;
  Page.Values[1] := apachePort;
  Page.Values[2] := mysqlPort;
  Page.Values[3] := newPassword;

end;


//-----------------------------------------------

//procedure qui ferme les services (si ils existent)

procedure close();
var myResult: Integer;
begin
path := ExpandConstant('{app}');
winPath := ExpandConstant('{win}');

pathWithSlashes := path;
StringChange (pathWithSlashes, '\','/');

batFile := path+'\stopdoliwamp.bat';
Exec(batFile, '',path+'\', SW_HIDE, ewWaitUntilTerminated, myResult);
end;




//-----------------------------------------------------------
//

function NextButtonClick(CurPageID: Integer): Boolean;
var myResult: Integer;
begin

  //MsgBox(''+CurPageID,mbConfirmation,MB_YESNO);

  if CurPageID = Page.ID then
  begin

    // Check if parameters already defined in conf.php file
    srcFile := pathWithSlashes+'/www/dolibarr/htdocs/conf/conf.php';
    if not FileExists (srcFile) then
    begin
        firstinstall := true;

        mysmtp  := Page.Values[0];
        myporta := Page.Values[1];
        myport  := Page.Values[2];
        mypass  := Page.Values[3];
    end
    else
    begin
        mysmtp  := smtpServer;
        myporta := apachePort;
        myport  := mysqlPort;
        mypass  := newPassword;
    end
    
    // Save parameters to registry
    RegWriteStringValue(HKEY_LOCAL_MACHINE, 'SOFTWARE\NLTechno\DoliWamp', 'smtpServer',  mysmtp);
    RegWriteStringValue(HKEY_LOCAL_MACHINE, 'SOFTWARE\NLTechno\DoliWamp', 'apachePort',  myporta);
    RegWriteStringValue(HKEY_LOCAL_MACHINE, 'SOFTWARE\NLTechno\DoliWamp', 'mysqlPort',   myport);
    RegWriteStringValue(HKEY_LOCAL_MACHINE, 'SOFTWARE\NLTechno\DoliWamp', 'newPassword', mypass);


    //----------------------------------------------
    // Rename file c:/windows/php.ini (we don't want it)
    //----------------------------------------------

    if FileExists ('c:/windows/php.ini') then
    begin
      if MsgBox('A previous c:/windows/php.ini file has been detected in your Windows directory. Do you want DoliWamp to rename it to php_old.ini to avoid conflicts ?',mbConfirmation,MB_YESNO) = IDYES then
      begin
        RenameFile('c:/windows/php.ini','c:/windows/php_old.ini');
      end
    end
    if FileExists ('c:/winnt/php.ini') then
    begin
      if MsgBox('A previous c:/winnt/php.ini file has been detected in your Windows directory. Do you want DoliWamp to rename it to php_old.ini to avoid conflicts ?',mbConfirmation,MB_YESNO) = IDYES then
      begin
        RenameFile('c:/winnt/php.ini','c:/winnt/php_old.ini');
      end
    end



    //----------------------------------------------
    // rundoliwamp.bat
    //----------------------------------------------

    destFile := pathWithSlashes+'/rundoliwamp.bat';
    srcFile := pathWithSlashes+'/rundoliwamp.bat.install';

    if not FileExists (destFile) and FileExists(srcFile) then
    begin
      //navigateur
      browser := 'explorer.exe';
      if FileExists ('C:/Program Files/Mozilla Firefox/firefox.exe')  then
      begin
        if MsgBox('Firefox has been detected on your computer. Would you like to use it as the default browser with Dolibarr ?',mbConfirmation,MB_YESNO) = IDYES then
        begin
          browser := 'C:/Program Files/Mozilla Firefox/firefox.exe';
        end
      end
      if browser = 'explorer.exe' then
      begin
        GetOpenFileName('Please choose your default browser. If you are not sure, just click Open :', browser, winPath,'exe files (*.exe)|*.exe|All files (*.*)|*.*' ,'exe');
      end

      LoadStringFromFile (srcFile, srcContents);
      StringChange (srcContents, 'WAMPBROWSER', browser);
      StringChange (srcContents, 'WAMPAPACHEPORT', myporta);
      SaveStringToFile(destFile,srcContents, False);
    end




    //----------------------------------------------
    // Fichier alias phpmyadmin
    //----------------------------------------------

    destFile := pathWithSlashes+'/alias/phpmyadmin.conf';
    srcFile := pathWithSlashes+'/alias/phpmyadmin.conf.install';

    if not FileExists (destFile) and FileExists(srcFile) then
    begin
      LoadStringFromFile (srcFile, srcContents);

      //installDir et version de phpmyadmin
      StringChange (srcContents, 'WAMPROOT', pathWithSlashes);
      StringChange (srcContents, 'WAMPPHPMYADMINVERSION', phpmyadminVersion);

      SaveStringToFile(destFile,srcContents, False);
    end
    DeleteFile(srcFile);



    //----------------------------------------------
    // Fichier alias dolibarr
    //----------------------------------------------

    destFile := pathWithSlashes+'/alias/dolibarr.conf';
    srcFile := pathWithSlashes+'/alias/dolibarr.conf.install';

    if not FileExists (destFile) and FileExists(srcFile) then
    begin
      LoadStringFromFile (srcFile, srcContents);

      StringChange (srcContents, 'WAMPROOT', pathWithSlashes);
      StringChange (srcContents, 'WAMPMYSQLNEWPASSWORD', mypass);

      SaveStringToFile(destFile, srcContents, False);
    end
    DeleteFile(srcFile);




    //----------------------------------------------
    // Fichier de configuration de phpmyadmin
    //----------------------------------------------

    destFile := pathWithSlashes+'/apps/phpmyadmin'+phpmyadminVersion+'/config.inc.php';
    srcFile := pathWithSlashes+'/apps/phpmyadmin'+phpmyadminVersion+'/config.inc.php.install';

    if not FileExists (destFile) then
    begin
      // si un fichier existe pour une version precedente de phpmyadmin, on le recupere
      if FileExists (pathWithSlashes+'/apps/phpmyadmin'+tmp+'/config.inc.php') then
      begin
        LoadStringFromFile (pathWithSlashes+'/apps/phpmyadmin'+tmp+'/config.inc.php', srcContents);
        StringChange (srcContents, 'WAMPMYSQLNEWPASSWORD', mypass);
        SaveStringToFile(destFile,srcContents, False);
      end
      else
      begin
        // sinon on prends le fichier par defaut
        LoadStringFromFile (srcFile, srcContents);
        StringChange (srcContents, 'WAMPMYSQLNEWPASSWORD', mypass);
        SaveStringToFile(destFile,srcContents, False);
      end
    end



    //----------------------------------------------
    // Fichier httpd.conf
    //----------------------------------------------

    destFile := pathWithSlashes+'/bin/apache/apache'+apacheVersion+'/conf/httpd.conf';
    srcFile := pathWithSlashes+'/bin/apache/apache'+apacheVersion+'/conf/httpd.conf.install';

    if not FileExists (destFile) then
    begin
      LoadStringFromFile (srcFile, srcContents);

      //installDir et version de php
      StringChange (srcContents, 'WAMPROOT', pathWithSlashes);
      StringChange (srcContents, 'WAMPPHPVERSION', phpVersion);
      StringChange (srcContents, 'WAMPAPACHEPORT', myporta);

      SaveStringToFile(destFile,srcContents, False);
    end




    //----------------------------------------------
    // Fichier my.ini
    //----------------------------------------------

    destFile := pathWithSlashes+'/bin/mysql/mysql'+mysqlVersion+'/my.ini';
    srcFile := pathWithSlashes+'/bin/mysql/mysql'+mysqlVersion+'/my.ini.install';

    if not FileExists (destFile) then
    begin
      LoadStringFromFile (srcFile, srcContents);

      //installDir et version de php
      StringChange (srcContents, 'WAMPROOT', pathWithSlashes);
      StringChange (srcContents, 'WAMPMYSQLPORT', myport);

      SaveStringToFile(destFile,srcContents, False);
    end




    //----------------------------------------------
    // Fichier index.php
    //----------------------------------------------

    destFile := pathWithSlashes+'/www/index.php';
    srcFile := pathWithSlashes+'/www/index.php.install';

    if not FileExists (destFile) then
    begin
      LoadStringFromFile (srcFile, srcContents);
      StringChange (srcContents, 'WAMPPHPVERSION', phpVersion);
      StringChange (srcContents, 'WAMPMYSQLVERSION', mysqlVersion);
      StringChange (srcContents, 'WAMPAPACHEVERSION', apacheVersion);
      StringChange (srcContents, 'WAMPAPACHEPORT', myporta);
      SaveStringToFile(destFile, srcContents, False);
    end
    else
    begin
      RenameFile(destFile, destFile+'.old');
      LoadStringFromFile (srcFile, srcContents);
      StringChange (srcContents, 'WAMPPHPVERSION', phpVersion);
      StringChange (srcContents, 'WAMPMYSQLVERSION', mysqlVersion);
      StringChange (srcContents, 'WAMPAPACHEVERSION', apacheVersion);
      StringChange (srcContents, 'WAMPAPACHEPORT', myporta);
      SaveStringToFile(destFile, srcContents, False);
    end





    //----------------------------------------------
    // Fichier dolibarr parametres predefins install web
    //----------------------------------------------

    destFile := pathWithSlashes+'/www/dolibarr/htdocs/install/install.forced.php';
    srcFile := pathWithSlashes+'/www/dolibarr/htdocs/install/install.forced.php.install';

    if not FileExists (destFile) then
    begin
      LoadStringFromFile (srcFile, srcContents);

      StringChange (srcContents, 'WAMPROOT', pathWithSlashes);
      StringChange (srcContents, 'WAMPMYSQLPORT', myport);
      StringChange (srcContents, 'WAMPMYSQLNEWPASSWORD', mypass);

      SaveStringToFile(destFile,srcContents, False);
    end



    //----------------------------------------------
    // Fichier install_services.bat
    //----------------------------------------------

    destFile := pathWithSlashes+'/install_services.bat';
    srcFile := pathWithSlashes+'/install_services.bat.install';

    if not FileExists (destFile) then
    begin
      LoadStringFromFile (srcFile, srcContents);

      //version de apache et mysql
      StringChange (srcContents, 'WAMPMYSQLVERSION', mysqlVersion);
      StringChange (srcContents, 'WAMPAPACHEVERSION', apacheVersion);

      SaveStringToFile(destFile,srcContents, False);
    end



    //----------------------------------------------
    // Fichier install_services_auto.bat
    //----------------------------------------------

    destFile := pathWithSlashes+'/install_services_auto.bat';
    srcFile := pathWithSlashes+'/install_services_auto.bat.install';

    if not FileExists (destFile) and FileExists (srcFile) then
    begin
      LoadStringFromFile (srcFile, srcContents);

      //version de apache et mysql
      StringChange (srcContents, 'WAMPMYSQLVERSION', mysqlVersion);
      StringChange (srcContents, 'WAMPAPACHEVERSION', apacheVersion);

      SaveStringToFile(destFile,srcContents, False);
    end




    //----------------------------------------------
    // Fichier uninstall_services.bat
    //----------------------------------------------

    destFile := pathWithSlashes+'/uninstall_services.bat';
    srcFile := pathWithSlashes+'/uninstall_services.bat.install';

    if not FileExists (destFile) then
    begin
      LoadStringFromFile (srcFile, srcContents);

      //version de apache et mysql
      StringChange (srcContents, 'WAMPMYSQLVERSION', mysqlVersion);
      StringChange (srcContents, 'WAMPAPACHEVERSION', apacheVersion);

      SaveStringToFile(destFile,srcContents, False);
    end



    //----------------------------------------------
    // Fichier mysqlinitpassword.bat
    //----------------------------------------------

    destFile := pathWithSlashes+'/mysqlinitpassword.bat';
    srcFile := pathWithSlashes+'/mysqlinitpassword.bat.install';

    if not FileExists (destFile) and FileExists (srcFile) then
    begin
      LoadStringFromFile (srcFile, srcContents);

      //version de apache et mysql
      StringChange (srcContents, 'WAMPMYSQLVERSION', mysqlVersion);
      StringChange (srcContents, 'WAMPMYSQLPORT', myport);
      StringChange (srcContents, 'WAMPMYSQLNEWPASSWORD', mypass);

      SaveStringToFile(destFile,srcContents, False);
    end


    //----------------------------------------------
    // Fichier mysqltestinstall.bat
    //----------------------------------------------

    destFile := pathWithSlashes+'/mysqltestinstall.bat';
    srcFile := pathWithSlashes+'/mysqltestinstall.bat.install';

    if not FileExists (destFile) and FileExists (srcFile) then
    begin
      LoadStringFromFile (srcFile, srcContents);

      //version de apache et mysql
      StringChange (srcContents, 'WAMPMYSQLVERSION', mysqlVersion);
      StringChange (srcContents, 'WAMPMYSQLPORT', myport);

      SaveStringToFile(destFile,srcContents, False);
    end




    //----------------------------------------------
    // fichier php.ini dans php
    //----------------------------------------------

    destFile := pathWithSlashes+'/bin/php/php'+phpVersion+'/php.ini';
    srcFile := pathWithSlashes+'/bin/php/php'+phpVersion+'/php.ini.install';

    if not FileExists (destFile) then
    begin
      LoadStringFromFile (srcFile, srcContents);
      StringChange (srcContents, 'WAMPROOT', pathWithSlashes);
      StringChange (srcContents, 'WAMPSMTP', mysmtp);
      SaveStringToFile(destFile,srcContents, False);
    end

    //----------------------------------------------
    // fichier php.ini dans apache
    //----------------------------------------------

    destFile := pathWithSlashes+'/bin/apache/apache'+apacheVersion+'/bin/php.ini';
    srcFile := pathWithSlashes+'/bin/php/php'+phpVersion+'/php.ini.install';

    if not FileExists (destFile) then
    begin
      LoadStringFromFile (srcFile, srcContents);
      StringChange (srcContents, 'WAMPROOT', pathWithSlashes);
      StringChange (srcContents, 'WAMPSMTP', mysmtp);
      SaveStringToFile(destFile,srcContents, False);
    end



    // This must be in if curpage.id = page.id, otherwise it is executed after each Next button

  //----------------------------------------------
  // copie des dll de php vers apache
  //----------------------------------------------

  phpDllCopy := 'fdftk.dll';
  filecopy (pathWithSlashes+'/bin/php/php'+phpVersion+'/'+phpDllCopy, pathWithSlashes+'/bin/apache/apache'+apacheVersion+'/bin/'+phpDllCopy, False);
  phpDllCopy := 'fribidi.dll';
  filecopy (pathWithSlashes+'/bin/php/php'+phpVersion+'/'+phpDllCopy, pathWithSlashes+'/bin/apache/apache'+apacheVersion+'/bin/'+phpDllCopy, False);
  phpDllCopy := 'gds32.dll';
  filecopy (pathWithSlashes+'/bin/php/php'+phpVersion+'/'+phpDllCopy, pathWithSlashes+'/bin/apache/apache'+apacheVersion+'/bin/'+phpDllCopy, False);
  phpDllCopy := 'libeay32.dll';
  filecopy (pathWithSlashes+'/bin/php/php'+phpVersion+'/'+phpDllCopy, pathWithSlashes+'/bin/apache/apache'+apacheVersion+'/bin/'+phpDllCopy, False);
  phpDllCopy := 'libmhash.dll';
  filecopy (pathWithSlashes+'/bin/php/php'+phpVersion+'/'+phpDllCopy, pathWithSlashes+'/bin/apache/apache'+apacheVersion+'/bin/'+phpDllCopy, False);
  phpDllCopy := 'libmysql.dll';
  filecopy (pathWithSlashes+'/bin/php/php'+phpVersion+'/'+phpDllCopy, pathWithSlashes+'/bin/apache/apache'+apacheVersion+'/bin/'+phpDllCopy, False);
  phpDllCopy := 'msql.dll';
  filecopy (pathWithSlashes+'/bin/php/php'+phpVersion+'/'+phpDllCopy, pathWithSlashes+'/bin/apache/apache'+apacheVersion+'/bin/'+phpDllCopy, False);
  phpDllCopy := 'libmysqli.dll';
  filecopy (pathWithSlashes+'/bin/php/php'+phpVersion+'/'+phpDllCopy, pathWithSlashes+'/bin/apache/apache'+apacheVersion+'/bin/'+phpDllCopy, False);
  phpDllCopy := 'ntwdblib.dll';
  filecopy (pathWithSlashes+'/bin/php/php'+phpVersion+'/'+phpDllCopy, pathWithSlashes+'/bin/apache/apache'+apacheVersion+'/bin/'+phpDllCopy, False);
  phpDllCopy := 'php5activescript.dll';
  filecopy (pathWithSlashes+'/bin/php/php'+phpVersion+'/'+phpDllCopy, pathWithSlashes+'/bin/apache/apache'+apacheVersion+'/bin/'+phpDllCopy, False);
  phpDllCopy := 'php5isapi.dll';
  filecopy (pathWithSlashes+'/bin/php/php'+phpVersion+'/'+phpDllCopy, pathWithSlashes+'/bin/apache/apache'+apacheVersion+'/bin/'+phpDllCopy, False);
  phpDllCopy := 'php5nsapi.dll';
  filecopy (pathWithSlashes+'/bin/php/php'+phpVersion+'/'+phpDllCopy, pathWithSlashes+'/bin/apache/apache'+apacheVersion+'/bin/'+phpDllCopy, False);
  phpDllCopy := 'ssleay32.dll';
  filecopy (pathWithSlashes+'/bin/php/php'+phpVersion+'/'+phpDllCopy, pathWithSlashes+'/bin/apache/apache'+apacheVersion+'/bin/'+phpDllCopy, False);
  phpDllCopy := 'yaz.dll';
  filecopy (pathWithSlashes+'/bin/php/php'+phpVersion+'/'+phpDllCopy, pathWithSlashes+'/bin/apache/apache'+apacheVersion+'/bin/'+phpDllCopy, False);
  phpDllCopy := 'libmcrypt.dll';
  filecopy (pathWithSlashes+'/bin/php/php'+phpVersion+'/'+phpDllCopy, pathWithSlashes+'/bin/apache/apache'+apacheVersion+'/bin/'+phpDllCopy, False);
  phpDllCopy := 'php5ts.dll';
  filecopy (pathWithSlashes+'/bin/php/php'+phpVersion+'/'+phpDllCopy, pathWithSlashes+'/bin/apache/apache'+apacheVersion+'/bin/'+phpDllCopy, False);


    // Remove lock file
    DeleteFile(pathWithSlashes+'/www/dolibarr/install.lock');


    MsgBox('DoliWamp installer will now start Apache and Mysql, this may last from several seconds to one minute after this confirmation.',mbInformation,MB_OK)


    // Install services
    batFile := path+'\uninstall_services.bat';
    Exec(batFile, '',path+'\', SW_HIDE, ewWaitUntilTerminated, myResult);
    batFile := path+'\install_services.bat';
    Exec(batFile, '',path+'\', SW_HIDE, ewWaitUntilTerminated, myResult);

    // Stard services
    batFile := path+'\startdoliwamp.bat';
    Exec(batFile, '',path+'\', SW_HIDE, ewWaitUntilTerminated, myResult);

    // Change mysql password
    batFile := path+'\mysqlinitpassword.bat';
    Exec(batFile, '',path+'\', SW_HIDE, ewWaitUntilTerminated, myResult);

    // Remove dangerous files
    batFile := path+'\removefiles.bat';
    Exec(batFile, '',path+'\', SW_HIDE, ewWaitUntilTerminated, myResult);

  end


  Result := True;
end;





//-----------------------------------------------

//procedure lancée à la fin de l'installation, elle supprime les fichiers d'installation

procedure DeinitializeSetup();
begin
//  DeleteFile(path+'\install_services.bat');
//  DeleteFile(path+'\install_services_auto.bat');
end;



function InitializeUninstall(): Boolean;
begin
    Result := RegDeleteValue(HKEY_LOCAL_MACHINE, 'SOFTWARE\NLTechno\DoliWamp','smtpServer');
    Result := RegDeleteValue(HKEY_LOCAL_MACHINE, 'SOFTWARE\NLTechno\DoliWamp','apachePort');
    Result := RegDeleteValue(HKEY_LOCAL_MACHINE, 'SOFTWARE\NLTechno\DoliWamp','mysqlPort');
    Result := RegDeleteValue(HKEY_LOCAL_MACHINE, 'SOFTWARE\NLTechno\DoliWamp','newPassword');
    Result := RegDeleteKeyIncludingSubkeys(HKEY_LOCAL_MACHINE, 'SOFTWARE\NLTechno\DoliWamp');
end;



[Run]
; Launch Dolibarr in browser. This is run after Wizard because of postinstall flag
Filename: "{app}\rundoliwamp.bat"; Description: "Launch Dolibarr now"; Flags: shellexec postinstall skipifsilent runhidden


[UninstallDelete]
Type: files; Name: "{app}\*.*"
Type: files; Name: "{app}\bin\mysql\mysql5.0.45\*.*"
Type: filesandordirs; Name: "{app}\alias"
Type: filesandordirs; Name: "{app}\apps"
Type: filesandordirs; Name: "{app}\bin\apache"
Type: filesandordirs; Name: "{app}\bin\php"
Type: filesandordirs; Name: "{app}\help"
Type: filesandordirs; Name: "{app}\lang"
Type: filesandordirs; Name: "{app}\logs"
Type: filesandordirs; Name: "{app}\scripts"
Type: filesandordirs; Name: "{app}\tmp"
Type: filesandordirs; Name: "{app}\www\dolibarr"


[UninstallRun]
Filename: "{app}\uninstall_services.bat"; Flags: runhidden

