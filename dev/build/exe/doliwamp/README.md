# DOLIWAMP, THE DOLIBARR INSTALLER FOR WINDOWS

DoliWamp is a special all in one package installer for Windows (Dolibarr+Mysql+Apache+PHP). 
It's a dedicated Dolibarr version for Windows newbies with no technical knowledge. This package will install or upgrade Dolibarr but also all prerequisites like the web server, and the database in one auto-install process.

If you have technical knowledge in web administration and plan to share your server instance (Apache, Mysql or PHP) with other projects than Dolibarr, or want to use Dolibarr with a different technical layer (PostgreSQL instead of MariaDB for exemple), you should not use this auto-installer assistant and make a manual installation of Dolibarr on your existing server by downloading the standard package (the .zip file).


This directory contains files used by *makepack-dolibarr.pl* script to build DoliWamp, the all-in-one .EXE package of Dolibarr, ready to be distributed on Windows (See file ../../makepack-howto.md)
The build of .exe files need to have some windows executable files already installed (Apache, MariaDb). The package to install to get this files are defined into the file *doliwamp.iss* (search line starting with "; Value OK:")
