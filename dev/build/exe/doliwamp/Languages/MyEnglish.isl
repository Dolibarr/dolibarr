
[CustomMessages]

NameAndVersion=%1 version %2
AdditionalIcons=Additional icons:
CreateDesktopIcon=Create a &desktop icon
CreateQuickLaunchIcon=Create a &Quick Launch icon
ProgramOnTheWeb=%1 on the Web
UninstallProgram=Uninstall %1
LaunchProgram=Launch %1
AssocFileExtension=&Associate %1 with the %2 file extension
AssocingFileExtension=Associating %1 with the %2 file extension...

YouWillInstallDoliWamp=You will install DoliWamp (so Dolibarr plus all required third-party software like Apache, MySQL and PHP) on your computer.
ThisAssistantInstallOrUpgrade=WARNING: Using an ERP CRM installed on a local computer can be dangerous: if your computer breaks down, you can lose all your data. Do this if you are ready to manage backups yourself seriously. If not, use an installation in SaaS instead (see https://saas.dolibarr.org).
IfYouHaveTechnicalKnowledge=Moreover, if you have technical knowledge and want to manage Apache, MySQL and PHP yourself, you should not use this assistant and instead make a manual installation of Dolibarr on your existing server with Apache, MySQL and PHP.
ButIfYouLook=But if you are looking for an automatic setup on your local computer, you're on the right path...
DoYouWantToStart=Do you want to start the installation process?

TechnicalParameters=Technical parameters
IfFirstInstall=If this is the first install, please specify some technical parameters. If you don't understand, are not sure, or are doing an upgrade, just keep the default values.

; WARNING !!! STRINGS HERE MUST BE LOWER THAN 60 CHARACTERS
SMTPServer=SMTP server (your own or ISP SMTP server, first install only):
ApachePort=Apache port (first install only, common choice is 80):
MySqlPort=MySQL port (first install only, common choice is 3306):
MySqlPassword=MySQL server and database password you want for root (first install only):

FailedToDeleteLock=Failed to delete the file %1/www/dolibarr/install.lock. You can ignore this warning but you may have to remove the file manually later when asked. Click OK to continue...

PortAlreadyInUse=Port %1 seems to already be in use. You should cancel to go back and choose another value for %2 port. Cancel choice and choose another value?

FirefoxDetected=Firefox has been detected on your computer. Would you like to use it as the default browser for Dolibarr?
ChromeDetected=Chrome has been detected on your computer. Would you like to use it as the default browser for Dolibarr?
MicrosoftEdgeDetected=Microsoft Edge has been detected on your computer. Would you like to use it as the default browser for Dolibarr?
ChooseDefaultBrowser=Please choose your default browser (iexplore.exe, firefox.exe, chrome.exe, MicrosoftEdge.exe...). If you are not sure, just click Open:

LaunchNow=Launch Dolibarr now

ProgramHasBeenRemoved=Dolibarr's program files have been removed. However, all your data files are still in directory %1. You must remove this directory manually for a complete uninstall.

DoliWampWillStartApacheMysql=DoliWamp installer will now start or restart Apache and MySQL. This may take from several seconds to one minute. Start to install or upgrade the web and database server required by Dolibarr?

OldVersionFoundAndMoveInNew=An old database version has been found and moved to be used by the new Dolibarr version
OldVersionFoundButFailedToMoveInNew=An old database version has been found but could not be moved to be used with the new Dolibarr version

DLLMissing=Your Windows installation is missing The "Microsoft Visual C++ Redistributable for Visual Studio 2017" component. Please install the 32-bit version (vcredist_x86.exe) first (you can find it at https://learn.microsoft.com/en-US/cpp/windows/latest-supported-vc-redist?view=msvc-170) and restart DoliWamp installation/upgrade after.
ContinueAnyway=Continue anyway (install process may fail without this prerequisite)
