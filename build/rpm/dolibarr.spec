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
#Source: http://sourceforge.net/projects/%{name}/files/Dolibarr%20ERP-CRM/%{version}/%{name}-%{version}.tgz/download
Source: /usr/src/RPM/SOURCES/%{name}-%{version}.tgz
#BuildArch: noarch
#BuildArchitectures: noarch
BuildRoot: /tmp/%{name}-buildroot
#Icon: dolibarr_logo1.gif

# For Mandrake
Group: Networking/WWW
# For all other distrib
Group: Applications/Internet

Requires: mysql-server mysql httpd php php-cli php-gd php-ldap php-imap php-mysql 
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
#mkdir -p $RPM_BUILD_ROOT/%{_sysconfdir}/dolibarr
#mkdir -p $RPM_BUILD_ROOT/%{_sysconfdir}/cron.daily

mkdir -p $RPM_BUILD_ROOT%{_datadir}/pixmaps
cp doc/images/dolibarr_48x48.png $RPM_BUILD_ROOT%{_datadir}/pixmaps/dolibarr.png
mkdir -p $RPM_BUILD_ROOT%{_datadir}/applications
cp build/rpm/dolibarr.desktop $RPM_BUILD_ROOT/%{_datadir}/applications/dolibarr.desktop

install -m 444 README  $RPM_BUILD_ROOT/var/www/dolibarr/README
install -m 444 COPYRIGHT  $RPM_BUILD_ROOT/var/www/dolibarr/COPYRIGHT
cp -pr build $RPM_BUILD_ROOT/var/www/dolibarr
cp -pr doc $RPM_BUILD_ROOT/var/www/dolibarr
cp -pr htdocs $RPM_BUILD_ROOT/var/www/dolibarr
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


#---- post (after install)
%post
%update_menus

# Dolibarr files are stored into /var/www
export targetdir='/var/www/dolibarr'
# Dolibarr uploaded files and generated documents are stored into /usr/share/dolibarr/documents 
export docdir='/usr/share/dolibarr/documents'


# Create empty directory for uploaded files and generated documents 
echo Create document directory $docdir
mkdir -p $docdir

# Create install.forced.php into Dolibarr install directory
fileorig="$targetdir/build/rpm/install.forced.php.install"
config="$targetdir/htdocs/install/install.forced.php"
superuserlogin=''
superuserpassword=''
if [ -f %{_sysconfdir}/mysql/debian.cnf ] ; then
    # Load superuser login and pass
    superuserlogin=$(/bin/grep --max-count=1 "user" %{_sysconfdir}/mysql/debian.cnf | /bin/sed -e 's/^user[ =]*//g')
    superuserpassword=$(/bin/grep --max-count=1 "password" %{_sysconfdir}/mysql/debian.cnf | /bin/sed -e 's/^password[ =]*//g')
fi
echo Mysql superuser found to use is $superuserlogin
if [ -z "$superuserlogin" ] ; then
    cat $fileorig | sed -e 's/__SUPERUSERLOGIN__/root/g' | sed -e 's/__SUPERUSERPASSWORD__//g' > $config
else
    cat $fileorig | sed -e 's/__SUPERUSERLOGIN__/'$superuserlogin'/g' | sed -e 's/__SUPERUSERPASSWORD__/'$superuserpassword'/g' > $config
fi

# Create a config file %{_sysconfdir}/dolibarr/apache.conf
if [ ! -f %{_sysconfdir}/dolibarr/apache.conf ]; then
  	 echo Create dolibarr web server config file %{_sysconfdir}/dolibarr/apache.conf
     mkdir -p %{_sysconfdir}/dolibarr
     cp $targetdir/build/rpm/httpd-dolibarr.conf %{_sysconfdir}/dolibarr/apache.conf
     chmod a-x %{_sysconfdir}/dolibarr/apache.conf
     chmod go-w %{_sysconfdir}/dolibarr/apache.conf
fi


# Detect OS
os='fedora-redhat';
if [ -d %{_sysconfdir}/httpd/conf.d ]; then
    export os='fedora-redhat';
    export conffile="%{_sysconfdir}/httpd/conf.d/dolibarr.conf"
    export apacheuser='apache';
    export apachegroup='apache';
fi
if [ -d %{_sysconfdir}/apache2/conf.d -a `grep wwwrun /etc/passwd` ]; then
    export os='opensuse';
    export conffile="%{_sysconfdir}/apache2/conf.d/dolibarr.conf"
    export apacheuser='wwwrun';
    export apachegroup='wwwrun';
fi
if [ -d %{_sysconfdir}/apache2/conf.d -a `grep www-data /etc/passwd` ]; then
    export os='ubuntu-debian';
    export conffile="%{_sysconfdir}/apache2/conf.d/dolibarr.conf"
    export apacheuser='www-data';
    export apachegroup='www-data';
fi
echo OS detected: $os

# Create a config link dolibarr.conf for Fedora or Redhat
if [ ! -f $conffile ]; then
    echo Create dolibarr web server config link $conffile
    ln -fs /etc/dolibarr/apache.conf $conffile
fi

# Set permissions
echo Set permission to $apacheuser:$apachegroup on $targetdir
chown -R $apacheuser:$apachegroup $targetdir
chmod -R a-w $targetdir

echo Set permission to $apacheuser:$apachegroup on $docdir
chown -R $apacheuser:$apachegroup $docdir
chmod -R o-w $docdir

# Create empty conf.php file for web installer
if [ ! -s $targetdir/htdocs/conf/conf.php ]; then
    echo Create empty Dolibarr conf.php file
    touch $targetdir/htdocs/conf/conf.php
    chown $apacheuser:$apachegroup $targetdir/htdocs/conf/conf.php
    chmod ug+rw $targetdir/htdocs/conf/conf.php
fi

if [ "x$os" = "xfedora-redhat" -a -s /usr/bin/chcon ]; then
    echo Set SELinux permissions
    # Warning: chcon seems not cumulative 
    #chcon -R -h -t httpd_sys_content_t $targetdir
    #chcon -R -h -t httpd_sys_content_t $docdir
    chcon -R -h -t httpd_sys_script_rw_t $targetdir
    chcon -R -h -t httpd_sys_script_rw_t $docdir
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
echo To finish installation and use Dolibarr, call the following
echo page from your web browser:  
echo http://localhost/dolibarr/
echo



#---- postun (after uninstall)
%postun
%clean_menus

# Detect OS
os='fedora-redhat';
if [ -d %{_sysconfdir}/httpd/conf.d ]; then
    export os='fedora-redhat';
    export conffile="%{_sysconfdir}/httpd/conf.d/dolibarr.conf"
    export apacheuser='apache';
    export apachegroup='apache';
fi
if [ -d %{_sysconfdir}/apache2/conf.d -a `grep wwwrun /etc/passwd` ]; then
    export os='opensuse';
    export conffile="%{_sysconfdir}/apache2/conf.d/dolibarr.conf"
    export apacheuser='wwwrun';
    export apachegroup='wwwrun';
fi
if [ -d %{_sysconfdir}/apache2/conf.d -a `grep www-data /etc/passwd` ]; then
    export os='ubuntu-debian';
    export conffile="%{_sysconfdir}/apache2/conf.d/dolibarr.conf"
    export apacheuser='www-data';
    export apachegroup='www-data';
fi
echo OS detected: $os

# Dolibarr files are stored into /var/www
export targetdir='/var/www/dolibarr'
# Dolibarr uploaded files and generated documents are stored into /usr/share/dolibarr/documents 
export docdir='/usr/share/dolibarr/documents'

if [ -f $conffile ] ;
then
    echo Delete apache config file for Dolibarr
    rm -f $conffile
    status=purge
fi

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

rm -rf /etc/dolibarr
rm -rf $targetdir/htdocs/conf
rm -rf $targetdir/htdocs/install


%changelog
