#---------------------------------------------------------
# Spec file to build a rpm file
#
# This is an example to build a rpm file. You can use this 
# file to build a package for your own distributions and 
# edit it if you need to match your rules.
# --------------------------------------------------------
%define name dolibarr
%define version	__VERSION__
%define release __RELEASE__

Name: %{name}
Version: %{version}
Release: %{release}
Summary: Dolibarr

License: GPL
Packager: Laurent Destailleur (Eldy) <eldy@users.sourceforge.net>
Vendor: Dolibarr dev team

URL: http://www.%{name}.org
Source: /usr/src/RPM/SOURCES/%{name}-%{version}.tgz
BuildArch: noarch
#BuildArchitectures: noarch
BuildRoot: /tmp/%{name}-buildroot
#Icon: dolibarr_logo1.gif

# For Mandriva-Mageia
Group: Networking/WWW
# For all other distrib
Group: Applications/Internet

# Requires can use lua to be defined dynamically (but still at build time) 
# %{lua: if posix.access("/aaa") then print("Requires: bidon1 mysql-server mysql httpd php php-cli php-gd php-ldap php-imap php-mysql") end }

# Requires for Fedora-Redhat
Requires: mysql-server mysql httpd php php-cli php-gd php-ldap php-imap php-mysql 
# Requires for OpenSuse
#Requires: mysql-community-server mysql-community-server-client apache2 apache2-mod_php5 php5 php5-gd php5-ldap php5-imap php5-mysql php5-openssl 
# Requires for Mandriva-Mageia
#Requires: mysql mysql-client apache-base apache-mod_php php-cgi php-cli php-bz2 php-gd php-ldap php-imap php-mysqli php-openssl 

#Requires(pre):
#Requires(postun):

# Set yes to build test package, no for release (this disable need of /usr/bin/php not found by OpenSuse)
AutoReqProv: no


%description
Dolibarr ERP & CRM is an easy to use open source/free software for small  
and medium companies, foundations or freelances. It includes different 
features for Enterprise Resource Planning (ERP) and Customer Relationship 
Management (CRM) but also for different other activities.
Dolibarr was designed to provide only features you need and be easy to 
use.

%description -l es
Dolibarr ERP y CRM es un software open source/gratis para pequeñas y
medianas empresas, asociaciones o autónomos. Incluye diferentes
funcionalidades para la Planificación de Recursos Empresariales (ERP) y
Gestión de la Relación con los Clientes (CRM) así como para para otras
diferentes actividades. Dolibarr ha sido diseñado para suministrarle
solamente las funcionalidades que necesita y haciendo hincapié en su
facilidad de uso.
    
%description -l fr
Dolibarr ERP & CRM est un logiciel de gestion de PME/PMI, autoentrepreneurs, 
artisans ou associations. Il permet de gérer vos clients, prospect, 
fournisseurs, devis, factures, comptes bancaires, agenda, campagne emailings
et bien d'autres choses dans une interface pensée pour la simplicité.

%description -l it
Dolibarr è un programma gestionale open source e gratuito per piccole e medie
imprese, fondazioni e liberi professionisti. Include varie funzionalità per
Enterprise Resource Planning e gestione dei clienti (CRM), ma anche ulteriori
attività. Dolibar è progettato per poter fornire solo ciò di cui hai bisogno 
ed essere facile da usare.
Dolibar è completamente web-based, progettato per poter fornire solo ciò di 
cui hai bisogno ed essere facile da usare.



#---- prep
%prep
echo Building %{name}-%{version}-%{release}
%setup -q


#---- build
%build
# Nothing to build


#---- install
%install
rm -rf $RPM_BUILD_ROOT

mkdir -p $RPM_BUILD_ROOT/var/www/dolibarr/build
mkdir -p $RPM_BUILD_ROOT/var/www/dolibarr/doc
mkdir -p $RPM_BUILD_ROOT/var/www/dolibarr/htdocs
mkdir -p $RPM_BUILD_ROOT/var/www/dolibarr/scripts

mkdir -p $RPM_BUILD_ROOT%{_datadir}/pixmaps
cp doc/images/dolibarr_48x48.png $RPM_BUILD_ROOT%{_datadir}/pixmaps/dolibarr.png
mkdir -p $RPM_BUILD_ROOT%{_datadir}/applications
cp build/rpm/dolibarr.desktop    $RPM_BUILD_ROOT/%{_datadir}/applications/dolibarr.desktop

install -m 444 README     $RPM_BUILD_ROOT/var/www/dolibarr/README
install -m 444 COPYRIGHT  $RPM_BUILD_ROOT/var/www/dolibarr/COPYRIGHT
cp -pr build   $RPM_BUILD_ROOT/var/www/dolibarr
cp -pr doc     $RPM_BUILD_ROOT/var/www/dolibarr
cp -pr htdocs  $RPM_BUILD_ROOT/var/www/dolibarr
cp -pr scripts $RPM_BUILD_ROOT/var/www/dolibarr


#---- clean
%clean
rm -rf $RPM_BUILD_ROOT


#---- files
%files
%defattr(-,root,root)
%doc README
%doc COPYRIGHT
%doc /var/www/dolibarr/doc/*
%dir /var/www/dolibarr/build
%dir /var/www/dolibarr/htdocs
%dir /var/www/dolibarr/scripts
%_datadir/pixmaps/*
%_datadir/applications/%{name}.desktop
/var/www/dolibarr/build/*
/var/www/dolibarr/htdocs/*
/var/www/dolibarr/scripts/*
/var/www/dolibarr/README
/var/www/dolibarr/COPYRIGHT
#%config /var/www/dolibarr/htdocs/conf/conf.php


#---- post (after unzip during install)
%post
#%update_menus	# Does not exists on fedora nor mandriva


# Define vars
# Dolibarr files are stored into /var/www
export targetdir='/var/www/dolibarr'
# Dolibarr uploaded files and generated documents will be stored into docdir 
#export docdir="/var/lib/dolibarr/documents"
export docdir="/usr/share/dolibarr/documents"
export installfileorig="$targetdir/build/rpm/install.forced.php.install"
export installconfig="%{_sysconfdir}/dolibarr/install.forced.php"
export apachefileorig="$targetdir/build/rpm/httpd-dolibarr.conf"
export apacheconfig="%{_sysconfdir}/dolibarr/apache.conf"
#config="/usr/share/dolibarr/htdocs/conf/conf.php"
config="%{_sysconfdir}/dolibarr/conf.php"
lockfile="/usr/share/dolibarr/install.lock"


# Detect OS
os='unknown';
if [ -d %{_sysconfdir}/httpd/conf.d ]; then
    export os='fedora-redhat';
    export apachelink="%{_sysconfdir}/httpd/conf.d/dolibarr.conf"
    export apacheuser='apache';
    export apachegroup='apache';
fi
if [ -d %{_sysconfdir}/apache2/conf.d -a `grep ^wwwrun /etc/passwd | wc -l` -ge 1 ]; then
    export os='opensuse';
    export apachelink="%{_sysconfdir}/apache2/conf.d/dolibarr.conf"
    export apacheuser='wwwrun';
    export apachegroup='www';
fi
if [ -d %{_sysconfdir}/httpd/conf.d -a `grep -i "^mageia\|mandriva" /etc/issue | wc -l` -ge 1 ]; then
    export os='mageia-mandriva';
    export apachelink="%{_sysconfdir}/httpd/conf.d/dolibarr.conf"
    export apacheuser='apache';
    export apachegroup='apache';
fi
if [ -d %{_sysconfdir}/apache2/conf.d -a `grep ^www-data /etc/passwd | wc -l` -ge 1 ]; then
    export os='ubuntu-debian';
    export apachelink="%{_sysconfdir}/apache2/conf.d/dolibarr.conf"
    export apacheuser='www-data';
    export apachegroup='www-data';
fi
echo OS detected: $os


# Create empty directory for uploaded files and generated documents 
echo Create document directory $docdir
mkdir -p $docdir
mkdir -p %{_sysconfdir}/dolibarr

# Create install.forced.php into Dolibarr install directory
superuserlogin=''
superuserpassword=''
if [ -f %{_sysconfdir}/mysql/debian.cnf ] ; then
    # Load superuser login and pass
    superuserlogin=$(/bin/grep --max-count=1 "user" %{_sysconfdir}/mysql/debian.cnf | /bin/sed -e 's/^user[ =]*//g')
    superuserpassword=$(/bin/grep --max-count=1 "password" %{_sysconfdir}/mysql/debian.cnf | /bin/sed -e 's/^password[ =]*//g')
fi
echo Mysql superuser found to use is $superuserlogin
if [ -z "$superuserlogin" ] ; then
    cat $installfileorig | sed -e 's/__SUPERUSERLOGIN__/root/g' | sed -e 's/__SUPERUSERPASSWORD__//g' > $installconfig
else
    cat $installfileorig | sed -e 's/__SUPERUSERLOGIN__/'$superuserlogin'/g' | sed -e 's/__SUPERUSERPASSWORD__/'$superuserpassword'/g' > $installconfig
fi
chown -R root:$apachegroup $installconfig
chmod -R 660 $installconfig

# Create an empty conf.php with permission to web server
if [ ! -f $config ]
then 
	echo Create empty file $config		
	touch $config
	chown -R root:$apachegroup $config
	chmod -R 660 $config
fi

# Create a config file $apacheconfig
if [ ! -f $apacheconfig ]; then
  	 echo Create dolibarr web server config file $apacheconfig
     cp $apachefileorig $apacheconfig
     chmod a-x $apacheconfig
     chmod go-w $apacheconfig
fi

# Create a config link dolibarr.conf for Fedora or Redhat
if [ ! -f $apachelink ]; then
    echo Create dolibarr web server config link $apachelink
    ln -fs $apacheconfig $apachelink
fi

# Set permissions
echo Set permission to $apacheuser:$apachegroup on $targetdir
chown -R $apacheuser:$apachegroup $targetdir
chmod -R a-w $targetdir
chmod u+w $targetdir

echo Set permission to $apacheuser:$apachegroup on $docdir
chown -R $apacheuser:$apachegroup $docdir
chmod -R o-w $docdir

# Set SE Linux on OS SE is enabled
if [ "x$os" = "xfedora-redhat" -a -s /usr/bin/chcon ]; then
    echo Set SELinux permissions
    # Warning: chcon seems not cumulative 
    #chcon -R -h -t httpd_sys_content_t $targetdir
    #chcon -R -h -t httpd_sys_content_t $docdir
    chcon -R -h -t httpd_sys_script_rw_t $targetdir
    chcon -R -h -t httpd_sys_script_rw_t $docdir
    chcon -R -h -t httpd_sys_script_rw_t %{_sysconfdir}/dolibarr
    #chcon -R -h -t httpd_sys_script_exec_t $targetdir
fi

# Restart web server
echo Restart web server
if [ -f %{_sysconfdir}/init.d/httpd ]; then
    %{_sysconfdir}/init.d/httpd restart
fi
if [ -f %{_sysconfdir}/init.d/apache2 ]; then
    %{_sysconfdir}/init.d/apache2 restart
fi

# Show result
echo
echo "----- Dolibarr %version - (c) Dolibarr dev team -----"
echo "Dolibarr files are now installed (into /var/www/dolibarr)."
echo "To finish installation and use Dolibarr, click on ne menu" 
echo "entry Dolibarr ERP-CRM or call the following page from your"
echo "web browser:"  
echo "http://localhost/dolibarr/"
echo "--------------------------------------------------"
echo


#---- postun (after uninstall)
%postun
#%clean_menus	# Does not exists on fedora nor mandriva


# Define vars
# Dolibarr files are stored into targetdir
export targetdir='/var/www/dolibarr'
# Dolibarr uploaded files and generated documents will be stored into docdir 
#export docdir="/var/lib/dolibarr/documents"
export docdir="/usr/share/dolibarr/documents"
export installfileorig="$targetdir/build/rpm/install.forced.php.install"
export installconfig="%{_sysconfdir}/dolibarr/install.forced.php"
export apachefileorig="$targetdir/build/rpm/httpd-dolibarr.conf"
export apacheconfig="%{_sysconfdir}/dolibarr/apache.conf"
#config="/usr/share/dolibarr/htdocs/conf/conf.php"
config="%{_sysconfdir}/dolibarr/conf.php"
lockfile="$targetdir/install.lock"


# Detect OS
os='unknown';
if [ -d %{_sysconfdir}/httpd/conf.d ]; then
    export os='fedora-redhat';
    export apachelink="%{_sysconfdir}/httpd/conf.d/dolibarr.conf"
    export apacheuser='apache';
    export apachegroup='apache';
fi
if [ -d %{_sysconfdir}/apache2/conf.d -a `grep ^wwwrun /etc/passwd | wc -l` -ge 1 ]; then
    export os='opensuse';
    export apachelink="%{_sysconfdir}/apache2/conf.d/dolibarr.conf"
    export apacheuser='wwwrun';
    export apachegroup='www';
fi
if [ -d %{_sysconfdir}/httpd/conf.d -a `grep -i "^mageia\|mandriva" /etc/issue | wc -l` -ge 1 ]; then
    export os='mageia-mandriva';
    export apachelink="%{_sysconfdir}/httpd/conf.d/dolibarr.conf"
    export apacheuser='apache';
    export apachegroup='apache';
fi
if [ -d %{_sysconfdir}/apache2/conf.d -a `grep ^www-data /etc/passwd | wc -l` -ge 1 ]; then
    export os='ubuntu-debian';
    export apachelink="%{_sysconfdir}/apache2/conf.d/dolibarr.conf"
    export apacheuser='www-data';
    export apachegroup='www-data';
fi
echo OS detected: $os

# Remove apache link
if [ -f $apachelink ] ;
then
    echo Delete apache config link for Dolibarr
    rm -f $apachelink
    status=purge
fi

# Restart web servers if required
if [ "x$status" = "xpurge" ] ;
then
    # Restart web server
    echo Restart web server
    if [ -f %{_sysconfdir}/init.d/httpd ]; then
        %{_sysconfdir}/init.d/httpd restart
    fi
    if [ -f %{_sysconfdir}/init.d/apache2 ]; then
        %{_sysconfdir}/init.d/apache2 restart
    fi
fi

# Removed dirs after apache restart
echo Removed remaining $apacheconfig
rm -f $apacheconfig
echo Removed remaining $config
rm -f $config
echo Removed remaining $installconfig
rm -f $installconfig
echo Removed remaining $lockfile
rm -f $lockfile
echo Removed remaining dir $targetdir/doc
rmdir $targetdir/doc >/dev/null 2>&1
echo Removed remaining dir $targetdir/htdocs
rmdir $targetdir/htdocs >/dev/null 2>&1

%changelog
