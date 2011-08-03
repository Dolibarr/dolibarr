#---------------------------------------------------------
# Spec file to build a rpm file
#
# This is an example to build a rpm file. You can use this 
# file to build a package for your own distributions and 
# edit it if you need to match your rules.
# --------------------------------------------------------

#%define is_mandrake %(test -e /etc/mandrake-release && echo 1 || echo 0)
#%define is_suse %(test -e /etc/SuSE-release && echo 1 || echo 0)
#%define is_fedora %(test -e /etc/fedora-release && echo 1 || echo 0)

%define name dolibarr
%define version	__VERSION__
%define release __RELEASE__

Name: %{name}
Version: %{version}
Release: %{release}
Summary: ERP and CRM software for small and medium companies or foundations 
Summary(es): Software ERP y CRM para pequeñas y medianas empresas o, asociaciones o autónomos
Summary(fr): Logiciel ERP & CRM de gestion de PME/PMI, autoentrepreneurs ou associations
Summary(it): Programmo gestionale per piccole imprese, fondazioni e liberi professionisti

License: GPLv2+
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
An easy to use CRM & ERP open source/free software for small  
and medium companies, foundations or freelances. It includes different 
features for Enterprise Resource Planning (ERP) and Customer Relationship 
Management (CRM) but also for different other activities.
Dolibarr was designed to provide only features you need and be easy to 
use.

%description -l es
Un software ERP y CRM para pequeñas y medianas empresas, asociaciones
o autónomos. Incluye diferentes funcionalidades para la Planificación 
de Recursos Empresariales (ERP) y Gestión de la Relación con los
Clientes (CRM) así como para para otras diferentes actividades. 
Dolibarr ha sido diseñado para suministrarle solamente las funcionalidades
que necesita y haciendo hincapié en su facilidad de uso.
    
%description -l fr
Logiciel ERP & CRM de gestion de PME/PMI, autoentrepreneurs, 
artisans ou associations. Il permet de gérer vos clients, prospect, 
fournisseurs, devis, factures, comptes bancaires, agenda, campagne emailings
et bien d'autres choses dans une interface pensée pour la simplicité.

%description -l it
Un programmo gestionale per piccole e medie
imprese, fondazioni e liberi professionisti. Include varie funzionalità per
Enterprise Resource Planning e gestione dei clienti (CRM), ma anche ulteriori
attività. Progettato per poter fornire solo ciò di cui hai bisogno 
ed essere facile da usare.
Programmo web, progettato per poter fornire solo ciò di 
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
%{__rm} -rf $RPM_BUILD_ROOT

%{__mkdir} -p $RPM_BUILD_ROOT%{_sysconfdir}/dolibarr
%{__install} -m 644 etc/dolibarr/apache.conf $RPM_BUILD_ROOT%{_sysconfdir}/dolibarr/apache.conf
%{__install} -m 644 etc/dolibarr/file_contexts.dolibarr $RPM_BUILD_ROOT%{_sysconfdir}/dolibarr/file_contexts.dolibarr

# %{_datadir} = /usr/share
%{__mkdir} -p $RPM_BUILD_ROOT%{_datadir}/pixmaps
%{__install} -m 644 var/www/dolibarr/doc/images/dolibarr_48x48.png $RPM_BUILD_ROOT%{_datadir}/pixmaps/dolibarr.png
%{__mkdir} -p $RPM_BUILD_ROOT%{_datadir}/applications
%{__install} -m 644 var/www/dolibarr/build/rpm/dolibarr.desktop    $RPM_BUILD_ROOT%{_datadir}/applications/dolibarr.desktop

%{__mkdir} -p $RPM_BUILD_ROOT/var/www/dolibarr/build
%{__mkdir} -p $RPM_BUILD_ROOT/var/www/dolibarr/doc
%{__mkdir} -p $RPM_BUILD_ROOT/var/www/dolibarr/htdocs
%{__mkdir} -p $RPM_BUILD_ROOT/var/www/dolibarr/scripts
%{__cp} -pr var/www/dolibarr/build   $RPM_BUILD_ROOT/var/www/dolibarr
%{__cp} -pr var/www/dolibarr/doc     $RPM_BUILD_ROOT/var/www/dolibarr
%{__cp} -pr var/www/dolibarr/htdocs  $RPM_BUILD_ROOT/var/www/dolibarr
%{__cp} -pr var/www/dolibarr/scripts $RPM_BUILD_ROOT/var/www/dolibarr
%{__install} -m 644 var/www/dolibarr/COPYRIGHT  $RPM_BUILD_ROOT/var/www/dolibarr/doc/COPYRIGHT


#---- clean
%clean
%{__rm} -rf $RPM_BUILD_ROOT


#---- files
%files

%defattr(-,root,root)
%doc /var/www/dolibarr/doc/*
%dir /var/www/dolibarr/build
%dir /var/www/dolibarr/htdocs
%dir /var/www/dolibarr/scripts
%_datadir/pixmaps/dolibarr.png
%_datadir/applications/%{name}.desktop
/var/www/dolibarr/build/*
/var/www/dolibarr/htdocs/*
/var/www/dolibarr/scripts/*

%defattr(0664, -, -, 0755)
%config(noreplace) %{_sysconfdir}/dolibarr/apache.conf
%config(noreplace) %{_sysconfdir}/dolibarr/file_contexts.dolibarr


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
export sefileorig="%{_sysconfdir}/dolibarr/file_contexts.dolibarr"
export seconfig="%{_sysconfdir}/selinux/targeted/contexts/files/file_contexts.dolibarr"
#export config="/usr/share/dolibarr/htdocs/conf/conf.php"
export config="%{_sysconfdir}/dolibarr/conf.php"
export lockfile="/usr/share/dolibarr/install.lock"


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
%{__mkdir} -p $docdir
%{__mkdir} -p %{_sysconfdir}/dolibarr

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
    %{__cat} $installfileorig | sed -e 's/__SUPERUSERLOGIN__/root/g' | sed -e 's/__SUPERUSERPASSWORD__//g' > $installconfig
else
    %{__cat} $installfileorig | sed -e 's/__SUPERUSERLOGIN__/'$superuserlogin'/g' | sed -e 's/__SUPERUSERPASSWORD__/'$superuserpassword'/g' > $installconfig
fi
%{__chown} -R root:$apachegroup $installconfig
%{__chmod} -R 660 $installconfig

# Create an empty conf.php with permission to web server
if [ ! -f $config ]
then 
	echo Create empty file $config		
	touch $config
	chown -R root:$apachegroup $config
	chmod -R 660 $config
fi

# Create config file for apache $apacheconfig
#if [ ! -f $apacheconfig ]; then
#  	 echo Create dolibarr web server config file $apacheconfig
#     cp $apachefileorig $apacheconfig
#     chmod a-x $apacheconfig
#     chmod go-w $apacheconfig
#fi

# Create config file for se $seconfig
if [ "x$os" = "xfedora-redhat" -a -s /sbin/restorecon -a ! -f $seconfig ]; then
  	 echo Add SE Linux permission from file $sefileorig
#     cp $sefileorig $seconfig
     restorecon -R -v /etc/dolibarr
     restorecon -R -v /var/www/dolibarr
     restorecon -R -v /usr/share/dolibarr
fi

# Create a config link dolibarr.conf
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

# Restart web server
echo Restart web server
if [ -f %{_sysconfdir}/init.d/httpd ]; then
    %{_sysconfdir}/init.d/httpd restart
fi
if [ -f %{_sysconfdir}/init.d/apache2 ]; then
    %{_sysconfdir}/init.d/apache2 restart
fi

# Restart mysql
echo Restart mysql
if [ -f /etc/init.d/mysqld ]; then
	/etc/init.d/mysqld restart
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
#export config="/usr/share/dolibarr/htdocs/conf/conf.php"
export config="%{_sysconfdir}/dolibarr/conf.php"
export lockfile="$targetdir/install.lock"


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
    %{__rm} -f $apachelink
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
echo Removed remaining $config
%{__rm} -f $config
echo Removed remaining $installconfig
%{__rm} -f $installconfig
echo Removed remaining $lockfile
%{__rm} -f $lockfile
echo Removed remaining dir $targetdir/doc
rmdir $targetdir/doc >/dev/null 2>&1


%changelog
* Wed Jul 31 2011 Laurent Destailleur 3.1.0-0.2.beta1
- Initial version (#723326)
