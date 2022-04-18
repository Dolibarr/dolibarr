
[CustomMessages]

NameAndVersion=%1 Version %2
AdditionalIcons=Zusätzliche Symbole:
CreateDesktopIcon=&Desktop-Symbol erstellen
CreateQuickLaunchIcon=Symbol in der Schnellstartleiste erstellen
ProgramOnTheWeb=%1 im Internet
UninstallProgram=%1 entfernen
LaunchProgram=%1 starten
AssocFileExtension=&Registriere %1 mit der %2-Dateierweiterung
AssocingFileExtension=%1 wird mit der %2-Dateierweiterung registriert...


YouWillInstallDoliWamp=Sie installieren DoliWamp (also Dolibarr + alle erforderliche Software von Drittanbietern wie Apache, MySQL und PHP) auf Ihrem Computer.
ThisAssistantInstallOrUpgrade=WARNUNG: Die Verwendung eines auf einem lokalen Computer installierten ERP-CRM kann gefährlich sein: Wenn Ihr Computer ausfällt, können Sie alle Ihre Daten verlieren. Tun Sie dies, wenn Sie bereit sind, das Backup selbst ernsthaft zu verwalten. Wenn nicht, verwenden Sie stattdessen eine Installation in Saas (siehe https://saas.dolibarr.org).
IfYouHaveTechnicalKnowledge=Wenn Sie über technische Kenntnisse verfügen und Apache, MySQL und PHP selbst verwalten möchten, sollten Sie diesen Assistenten nicht verwenden und eine manuelle Installation von Dolibarr auf Ihrem vorhandenen Server mit Apache, MySQL und PHP durchführen.
ButIfYouLook=Aber wenn Sie auf Ihrem lokalen Computer nach einer automatischen Einrichtung suchen, sind Sie auf dem besten Weg ...
DoYouWantToStart=Möchten Sie den Installationsprozess starten?

TechnicalParameters=technische Parameter
IfFirstInstall=Geben Sie bei der Erstinstallation einige technische Parameter an. Wenn Sie nicht verstehen, sich nicht sicher sind oder ein Upgrade durchführen, belassen Sie einfach die Standardwerte.

; WARNING !!! STRINGS HERE MUST BE LOWER THAN 60 CHARACTERS 
SMTPServer=SMTP Server (your own or ISP SMTP server, first install only) :
ApachePort=Apache Port (first install only, Standard ist 80) :
MySqlPort=MySQL Port (first install only, Standard ist 3306) :
MySqlPassword=MySQL Server und Datenbank Passwort für root (first install only):

FailedToDeleteLock=Fehler beim Löschen der Datei %1/www/dolibarr/install.lock. Sie können die Warnung ignorieren, müssen sie jedoch möglicherweise später manuell entfernen, wenn Sie dazu aufgefordert werden. Klicken Sie auf OK, um fortzufahren ...

PortAlreadyInUse=Port %1 scheint bereits verwendet zu werden. Sie sollten zurückgehen und einen anderen Wert für %2 Port wählen.  Auswahl abbrechen und einen anderen Wert wählen ? 

FirefoxDetected=Firefox wurde auf Ihrem Computer erkannt. Möchten Sie ihn als Standardbrowser für Dolibarr verwenden?
ChromeDetected=Chrome wurde auf Ihrem Computer erkannt. Möchten Sie ihn als Standardbrowser für Dolibarr verwenden?
ChooseDefaultBrowser=Bitte wählen Sie Ihren Standardbrowser (iexplore.exe, firefox.exe, chrome.exe, MicrosoftEdge.exe...). Wenn Sie sich nicht sicher sind, klicken Sie einfach auf Öffnen:

LaunchNow=Starten Sie jetzt Dolibarr

ProgramHasBeenRemoved=Die Dolibarr-Programmdateien wurden entfernt. Alle Ihre Daten befinden sich jedoch noch im Verzeichnis %1. Für eine vollständige Deinstallation, müssen Sie dieses Verzeichnis manuell entfernen.
DoliWampWillStartApacheMysql=Die DoliWamp-Installation wird nun starten oder Apache und MySQL neu starten. Dies kann nach dieser Bestätigung einige Sekunden bis eine Minute dauern. Wollen Sie mit der Installation oder Aktualisierung des von Dolibarr benötigten Web- und Datenbankservers starten ?

OldVersionFoundAndMoveInNew=Eine alte Datenbankversion wurde gefunden und verschoben, um von der neuen Dolibarr-Version verwendet zu werden.
OldVersionFoundButFailedToMoveInNew=Eine alte Datenbankversion wurde gefunden, konnte jedoch nicht verschoben werden, um mit der neuen Dolibarr-Version verwendet zu werden.

DLLMissing=Your Windows installation is missing The "Micrsoft Visual C++ Redistributable for Visual Studio 2012" component. Please install the 32-bit version (vcredist_x86.exe) first (you can find it at https://www.microsoft.com/en-us/download/) and restart DoliWamp installation/upgrade after.
ContinueAnyway=Fahren Sie trotzdem fort (der Installationsvorgang kann ohne diese Voraussetzung fehlschlagen).
