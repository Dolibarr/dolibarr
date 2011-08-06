#---------------------------------------------------------
# Spec file to build a rpm file
#
# This is an example to build a rpm file. You can use this 
# file to build a package for your own distributions and 
# edit it if you need to match your rules.
# --------------------------------------------------------

%define version __VERSION__

Name: dolibarr
Version: %{version}
Release: __RELEASE__
Summary: ERP and CRM software for small and medium companies or foundations 
Summary(es): Software ERP y CRM para pequeñas y medianas empresas o, asociaciones o autónomos
Summary(fr): Logiciel ERP & CRM de gestion de PME/PMI, autoentrepreneurs ou associations
Summary(it): Programmo gestionale per piccole imprese, fondazioni e liberi professionisti

License: GPLv2+
#Packager: Laurent Destailleur (Eldy) <eldy@users.sourceforge.net>
Vendor: Dolibarr dev team

URL: http://www.dolibarr.org
Source0: http://www.dolibarr.org/files/fedora/dolibarr-%{version}.tgz
BuildArch: noarch
#BuildArchitectures: noarch
BuildRoot: %{_tmppath}/dolibarr-%{version}-build

# For Mandriva-Mageia
Group: Networking/WWW
# For all other distrib
Group: Applications/Internet

# Requires for Fedora-Redhat
Requires: mysql-server mysql httpd php php-cli php-gd php-ldap php-imap php-mysql 
# Requires for OpenSuse
#Requires: mysql-community-server mysql-community-server-client apache2 apache2-mod_php5 php5 php5-gd php5-ldap php5-imap php5-mysql php5-openssl 
# Requires for Mandriva-Mageia
#Requires: mysql mysql-client apache-base apache-mod_php php-cgi php-cli php-bz2 php-gd php-ldap php-imap php-mysqli php-openssl 

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
fournisseurs, devis, factures, comptes bancaires, agenda, campagnes mailings
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

%{__mkdir} -p $RPM_BUILD_ROOT%{_datadir}/pixmaps
%{__install} -m 644 usr/share/dolibarr/doc/images/dolibarr_48x48.png $RPM_BUILD_ROOT%{_datadir}/pixmaps/dolibarr.png
%{__mkdir} -p $RPM_BUILD_ROOT%{_datadir}/applications
%{__install} -m 644 usr/share/dolibarr/build/rpm/dolibarr.desktop $RPM_BUILD_ROOT%{_datadir}/applications/dolibarr.desktop

%{__mkdir} -p $RPM_BUILD_ROOT/usr/share/dolibarr/build
%{__mkdir} -p $RPM_BUILD_ROOT/usr/share/dolibarr/htdocs
%{__mkdir} -p $RPM_BUILD_ROOT/usr/share/dolibarr/scripts
%{__mkdir} -p $RPM_BUILD_ROOT%{_datadir}/doc/dolibarr
%{__cp} -pr usr/share/dolibarr/build   $RPM_BUILD_ROOT/usr/share/dolibarr
%{__cp} -pr usr/share/dolibarr/htdocs  $RPM_BUILD_ROOT/usr/share/dolibarr
%{__cp} -pr usr/share/dolibarr/scripts $RPM_BUILD_ROOT/usr/share/dolibarr
%{__cp} -pr usr/share/dolibarr/doc/*   $RPM_BUILD_ROOT%{_datadir}/doc/dolibarr
%{__install} -m 644 usr/share/dolibarr/COPYRIGHT $RPM_BUILD_ROOT%{_datadir}/doc/dolibarr/COPYRIGHT


#---- clean
%clean
%{__rm} -rf $RPM_BUILD_ROOT


#---- files
%files

%defattr(-, root, root, 0755)
%doc /usr/share/doc/dolibarr/*
%dir /usr/share/dolibarr/build
%dir /usr/share/dolibarr/htdocs
%dir /usr/share/dolibarr/scripts
%_datadir/pixmaps/dolibarr.png
%_datadir/applications/dolibarr.desktop
/usr/share/dolibarr/build/*
/usr/share/dolibarr/htdocs/*
/usr/share/dolibarr/scripts/*
#lang(ar_SA) /usr/share/dolibarr/htdocs/langs/ar_SA
#lang(ca_ES) /usr/share/dolibarr/htdocs/langs/ca_ES
#lang(da_DK) /usr/share/dolibarr/htdocs/langs/da_DK
#lang(de_AT) /usr/share/dolibarr/htdocs/langs/de_AT
#lang(de_DE) /usr/share/dolibarr/htdocs/langs/de_DE
#lang(el_GR) /usr/share/dolibarr/htdocs/langs/el_GR
#lang(en_AU) /usr/share/dolibarr/htdocs/langs/el_AU
#lang(en_GB) /usr/share/dolibarr/htdocs/langs/el_GB
#lang(en_IN) /usr/share/dolibarr/htdocs/langs/el_IN
#lang(en_NZ) /usr/share/dolibarr/htdocs/langs/el_NZ
#lang(en) /usr/share/dolibarr/htdocs/langs/en_US
#lang(es_AR) /usr/share/dolibarr/htdocs/langs/es_AR
#lang(es) /usr/share/dolibarr/htdocs/langs/es_ES
#lang(es_HN) /usr/share/dolibarr/htdocs/langs/es_HN
#lang(es_MX) /usr/share/dolibarr/htdocs/langs/en_MX
#lang(es_PR) /usr/share/dolibarr/htdocs/langs/en_PR
#lang(fa_IR) /usr/share/dolibarr/htdocs/langs/fa_IR
#lang(fi_FI) /usr/share/dolibarr/htdocs/langs/fi_FI
#lang(fr_BE) /usr/share/dolibarr/htdocs/langs/fr_BE
#lang(fr_CA) /usr/share/dolibarr/htdocs/langs/fr_CA
#lang(fr_CH) /usr/share/dolibarr/htdocs/langs/fr_CH
#lang(fr) /usr/share/dolibarr/htdocs/langs/fr_FR

%defattr(0664, -, -, 0755)
%config(noreplace) %{_sysconfdir}/dolibarr/apache.conf
%config(noreplace) %{_sysconfdir}/dolibarr/file_contexts.dolibarr


#---- post (after unzip during install)
%post

# Define vars
export docdir="/var/lib/dolibarr/documents"
export installfileorig="/usr/share/dolibarr/build/rpm/install.forced.php.install"
export installconfig="%{_sysconfdir}/dolibarr/install.forced.php"
export config="%{_sysconfdir}/dolibarr/conf.php"

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

# Remove lock file
%{__rm} -f $docdir/install.lock

# Create empty directory for uploaded files and generated documents 
echo Create document directory $docdir
%{__mkdir} -p $docdir

# Create install.forced.php into Dolibarr install directory
%{__cat} $installfileorig | sed -e 's/__SUPERUSERLOGIN__/root/g' | sed -e 's/__SUPERUSERPASSWORD__//g' > $installconfig
%{__chown} -R root:$apachegroup $installconfig
%{__chmod} -R 660 $installconfig

# Create an empty conf.php with permission to web server
if [ ! -f $config ]
then
    echo Create empty file $config
    touch $config
    %{__chown} -R root:$apachegroup $config
    %{__chmod} -R 660 $config
fi

# Create config for se $seconfig
if [ "x$os" = "xfedora-redhat" -a -s /sbin/restorecon ]; then
    echo Add SE Linux permissions for dolibarr
    # semanage add records into /etc/selinux/targeted/contexts/files/file_contexts.local
    semanage fcontext -a -t httpd_sys_script_rw_t "/etc/dolibarr(/.*?)"
    #semanage fcontext -a -t httpd_sys_script_rw_t "/usr/share/dolibarr(/.*?)"
    semanage fcontext -a -t httpd_sys_script_rw_t "/var/lib/dolibarr(/.*?)"
    restorecon -R -v /etc/dolibarr
    #restorecon -R -v /usr/share/dolibarr
    restorecon -R -v /var/lib/dolibarr
fi

# Create a config link dolibarr.conf
if [ ! -f $apachelink ]; then
    echo Create dolibarr web server config link $apachelink
    ln -fs %{_sysconfdir}/dolibarr/apache.conf $apachelink
fi

echo Set permission to $apacheuser:$apachegroup on /var/lib/dolibarr
%{__chown} -R $apacheuser:$apachegroup /var/lib/dolibarr
%{__chmod} -R o-w /var/lib/dolibarr

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
echo "Dolibarr files are now installed (into /usr/share/dolibarr)."
echo "To finish installation and use Dolibarr, click on ne menu" 
echo "entry Dolibarr ERP-CRM or call the following page from your"
echo "web browser:"  
echo "http://localhost/dolibarr/"
echo "--------------------------------------------------"
echo


#---- postun (after uninstall)
%postun

# Define vars
export docdir="/var/lib/dolibarr/documents"
export installfileorig="/usr/share/dolibarr/build/rpm/install.forced.php.install"
export installconfig="%{_sysconfdir}/dolibarr/install.forced.php"
export config="%{_sysconfdir}/dolibarr/conf.php"


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


%changelog
* Wed Jul 31 2011 Laurent Destailleur 3.1.0-0.2.beta1
- Initial version (#723326)
