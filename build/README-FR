README (French)
################################
Building packages
################################

Les sous repertoires du repertoire "build" contiennent tous les fichiers
requis pour packager Dolibarr de maniere automatisee.
On trouve plusieurs outils:

- Pour construire un package Dolibarr complet, il suffit de
> Editer la version dans le fichier makepack-dolibarr.pl  
> Lancer la commande perl makepack-dolibarr.pl

- Pour construire un package d'une traduction, il suffit de lancer le script
> perl makepack-dolibarrlang.pl

- Pour construire un package d'un theme, il suffit de lancer le script
> perl makepack-dolibarrtheme.pl

- Pour construire un package d'un module, il suffit de lancer le script
> perl makepack-dolibarrmodule.pl

- Pour construire un package DoliWamp autoexe:
> Installer InnoSetup (http://www.jrsoftware.org)
> Installer WampServer dans "C:\Program Files\Wamp" (http://www.wampserver.com)
> Installer les addon WampServer afin d'y mettre les versions:
   Apache2.2.6, Mysql5.0.45, Php5.2.5
> Modifier dans le fichier build/exe/doliwamp.iss la variable SourceDir
  afin d'y mettre le repository Dolibarr.
> Modifier AppVerName et OutputBaseFilename.
> Lancer innosetup, ouvrir le fichier build/exe/doliwamp.iss et cliquer sur
  le bouton "Compile". Le fichier .exe sera fabrique dans le repertoire build.

- Pour generer la documentation developpeur, lancer le script
> perl dolybarr-doxygen-build.pl


Note: 

Le repertoire build et tout ce qu'il contient n'est absolument pas requis
pour faire fonctionner Dolibarr. Ils ne servent qu'a la generation des
packages. Certains packages, une fois construit, n'incluent par le repertoire
"build".

On trouve dans le repertoire "build", les sous-repertoires utilises par
l'outil makepack-dolibarr.pl:

* deb:
Fichier de config pour construire un package Debian.

* rpm:
Fichier de config pour construire un package Redhat ou Mandrake.

* tgz:
Fichier de config pour construire un package tgz.

* exe:
Fichier de config pour construire un package exe pour Windows des sources 
ou pour construire l'assistant d'installation complet DoliWamp.

* zip:
Fichier de config pour construire un package zip.

* live:
Fichier pour fabriquer un live CD de demo de Dolibarr.

* patch:
Fichier exemple de generation de fichier patch pour diffusion d'une
modification de Dolibarr.

* doap:
Fichier descriptif DOAP pour promouvoir/decrire la version de Dolibarr.

* pad:
Fichier descriptif PAD pour promouvoir/decrire la version de Dolibarr.

* dmg:
Fichier de config pour construire un package dmg DoliMamp pour Mac OS X
