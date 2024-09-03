
[CustomMessages]

NameAndVersion=%1 versión %2
AdditionalIcons=Iconos adicionales:
CreateDesktopIcon=Crear un icono en el &escritorio
CreateQuickLaunchIcon=Crear un icono de Inicio Rápido
ProgramOnTheWeb=%1 en la Web
UninstallProgram=Desinstalar %1
LaunchProgram=Ejecutar %1
AssocFileExtension=&Asociar %1 con la extensión de archivo %2
AssocingFileExtension=Asociando %1 con la extensión de archivo %2...

YouWillInstallDoliWamp=Va a instalar DoliWamp (Dolibarr y otro software como Apache, Mysql y PHP) en su ordenador.
ThisAssistantInstallOrUpgrade=ALERTA: Utilizar un ERP CRM instalado en un ordenador en local puede ser peligroso: si el ordenador se estropea, puede perder todos sus datos. Hágalo si está preparado para autogestionar sus copias de seguridad. Si no, puede utilizar una instalacion Saas (puede ver https://saas.dolibarr.org).
IfYouHaveTechnicalKnowledge=Si tiene conocimientos técnicos y necesita usar su Apache, Mysql y PHP con otras aplicaciones aparte de Dolibarr, no debería usar este asistente, debería realizar una instalación manual de Dolibarr sobre un Apache, Mysql y PHP existente.
ButIfYouLook=Pero si busca una instalación automática en tu propio ordenador, se encuentra en el buen camino...
DoYouWantToStart=¿Quiere iniciar el proceso de instalación?

TechnicalParameters=Parámetros técnicos
IfFirstInstall=Si se trata de la primera instalación, deberá especificar algunos parámetros técnicos. Si no los entiende, no está seguro o va a proceder a una actualización, deje los campos con los valores propuestos por defecto.

; WARNING !!! STRINGS HERE MUST BE LOWER THAN 60 CHARACTERS
SMTPServer=Servidor SMTP (propio o su ISP, sólo primera instalación) :
ApachePort=Puerto Apache (sólo primera instalación, normalmente el 80) :
MySqlPort=Puerto Mysql (sólo primera instalación, normalmente el 3306) :
MySqlPassword=Contraseña del servidor y la base de datos MySQL de root (sólo primera instalación):

FailedToDeleteLock=Error en la eliminación del archivo %1/www/dolibarr/install.lock. Puede ignorar el aviso pero es posible que deba eliminarlo manualmente más tarde. En este caso, será informado. Haga clic en OK para continuar...

PortAlreadyInUse=Parece que el puerto %1 ya esta siendo usado. Se recomienda cancelar, volver atras y especificar otro valor para el puerto %2. ¿Cancelar y escojer otro valor?

FirefoxDetected=Se ha detectado Firefox en su ordenador. Desea activarlo por defecto como navegador para Dolibarr ?
ChromeDetected=Se ha detectado Chrome en su ordenador. Desea activarlo por defecto como navegador para Dolibarr ?
ChooseDefaultBrowser=Escoja su navegador por defecto (iexplore.exe, firefox.exe, chrome.exe, MicrosoftEdge.exe...). Si no está seguro, simplementa haga clic en Abrir :

LaunchNow=Lanzar ahora Dolibarr

ProgramHasBeenRemoved=Los archivos del programa Dolibarr han sido eliminados. Sin embargo todos sus archivos de datos se encuentran todavía en el directorio %1. Deberá eliminar este directorio manualmente para una desinstalación completa.

DoliWampWillStartApacheMysql=El instalador DoliWamp intentará iniciar o reiniciar Apache y MySQL, esto puede durar desde varios segundos a un minuto después de la confirmación. ¿Iniciar la instalación o actualización de los servidores Web y bases de datos requeridas por Dolibarr?

OldVersionFoundAndMoveInNew=Se ha encontrado una versión antigua de base de datos y ha sido movida para ser utilizada por la nueva versión de Dolibarr
OldVersionFoundButFailedToMoveInNew=Se ha encontrado una versión antigua de base de datos, pero no se pudo mover para ser utilizada por la nueva versión de Dolibarr
 	  	 
DLLMissing=Su instalación Windows no tiene el componente "Microsoft Visual C++ Redistributable for Visual Studio 2017". Instale primero la versión de 32-bit (vcredist_x86.exe) (puedes encontrarlo en https://www.microsoft.com/en-us/download/) y reiniciar después la instalación/actualización de DoliWamp.
ContinueAnyway=Continua igualmente (el proceso de instalación podría fallar sin este prerequisito)
