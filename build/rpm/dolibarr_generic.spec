#---------------------------------------------------------
# Spec file to build a rpm file
#
# This is an example to build a rpm file. You can use this 
# file to build a package for your own distributions and 
# edit it if you need to match your rules.
# --------------------------------------------------------

Name: dolibarr
Version: __VERSION__
Release: __RELEASE__
Summary: ERP and CRM software for small and medium companies or foundations 
Summary(es): Software ERP y CRM para pequeñas y medianas empresas, asociaciones o autónomos
Summary(fr): Logiciel ERP & CRM de gestion de PME/PMI, autoentrepreneurs ou associations
Summary(it): Programmo gestionale per piccole imprese, fondazioni e liberi professionisti

License: GPLv2+
#Packager: Laurent Destailleur (Eldy) <eldy@users.sourceforge.net>
Vendor: Dolibarr dev team

URL: http://www.dolibarr.org
Source0: http://www.dolibarr.org/files/lastbuild/package_rpm_generic/%{name}-%{version}.tgz
Patch0: %{name}-forrpm.patch
BuildArch: noarch
BuildRoot: %{_tmppath}/%{name}-%{version}-build

Group: Applications/Productivity
Requires: mysql-server mysql httpd php php-cli php-gd php-ldap php-imap 
Requires: php-mysql >= 4.1.0 

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
%patch0 -p0 -b .patch


#---- build
%build
# Nothing to build



#---- install
%install
%{__rm} -rf $RPM_BUILD_ROOT

%{__mkdir} -p $RPM_BUILD_ROOT%{_sysconfdir}/dolibarr
%{__install} -m 644 build/rpm/conf.php $RPM_BUILD_ROOT%{_sysconfdir}/dolibarr/conf.php
%{__install} -m 644 build/rpm/httpd-dolibarr.conf $RPM_BUILD_ROOT%{_sysconfdir}/dolibarr/apache.conf
%{__install} -m 644 build/rpm/file_contexts.dolibarr $RPM_BUILD_ROOT%{_sysconfdir}/dolibarr/file_contexts.dolibarr
%{__install} -m 644 build/rpm/install.forced.php.generic $RPM_BUILD_ROOT%{_sysconfdir}/dolibarr/install.forced.php

%{__mkdir} -p $RPM_BUILD_ROOT%{_datadir}/pixmaps
%{__install} -m 644 doc/images/dolibarr_48x48.png $RPM_BUILD_ROOT%{_datadir}/pixmaps/dolibarr.png
%{__mkdir} -p $RPM_BUILD_ROOT%{_datadir}/applications
#desktop-file-install --delete-original --dir=$RPM_BUILD_ROOT%{_datadir}/applications build/rpm/dolibarr.desktop
%{__install} -m 644 build/rpm/dolibarr.desktop $RPM_BUILD_ROOT%{_datadir}/applications/dolibarr.desktop

%{__mkdir} -p $RPM_BUILD_ROOT/usr/share/dolibarr/build/rpm
%{__mkdir} -p $RPM_BUILD_ROOT/usr/share/dolibarr/build/tgz
%{__mkdir} -p $RPM_BUILD_ROOT/usr/share/dolibarr/htdocs
%{__mkdir} -p $RPM_BUILD_ROOT/usr/share/dolibarr/scripts
%{__cp} -pr build/rpm/*     $RPM_BUILD_ROOT/usr/share/dolibarr/build/rpm
%{__cp} -pr build/tgz/*     $RPM_BUILD_ROOT/usr/share/dolibarr/build/tgz
%{__cp} -pr htdocs  $RPM_BUILD_ROOT/usr/share/dolibarr
%{__cp} -pr scripts $RPM_BUILD_ROOT/usr/share/dolibarr


#---- clean
%clean
%{__rm} -rf $RPM_BUILD_ROOT



#---- files
%files

%defattr(0755, root, root, 0755)
%dir %_datadir/dolibarr/scripts
%_datadir/dolibarr/scripts/*

%defattr(-, root, root, 0755)
%doc COPYING ChangeLog doc/index.html
%dir %_datadir/dolibarr/build/rpm
%dir %_datadir/dolibarr/build/tgz
%dir %_datadir/dolibarr/htdocs
%_datadir/pixmaps/dolibarr.png
%_datadir/applications/dolibarr.desktop
%_datadir/dolibarr/build/rpm/*
%_datadir/dolibarr/build/tgz/*
%_datadir/dolibarr/htdocs/*

%defattr(0664, -, -)
%config(noreplace) %{_sysconfdir}/dolibarr/conf.php
%config(noreplace) %{_sysconfdir}/dolibarr/apache.conf
%config(noreplace) %{_sysconfdir}/dolibarr/install.forced.php
%config(noreplace) %{_sysconfdir}/dolibarr/file_contexts.dolibarr

#lang(ar_SA) %_datadir/dolibarr/htdocs/langs/ar_SA
#lang(ca_ES) %_datadir/dolibarr/htdocs/langs/ca_ES
#lang(da_DK) %_datadir/dolibarr/htdocs/langs/da_DK
#lang(de_AT) %_datadir/dolibarr/htdocs/langs/de_AT
#lang(de_DE) %_datadir/dolibarr/htdocs/langs/de_DE
#lang(el_GR) %_datadir/dolibarr/htdocs/langs/el_GR
#lang(en_AU) %_datadir/dolibarr/htdocs/langs/el_AU
#lang(en_GB) %_datadir/dolibarr/htdocs/langs/el_GB
#lang(en_IN) %_datadir/dolibarr/htdocs/langs/el_IN
#lang(en_NZ) %_datadir/dolibarr/htdocs/langs/el_NZ
#lang(en)    %_datadir/dolibarr/htdocs/langs/en_US
#lang(es_AR) %_datadir/dolibarr/htdocs/langs/es_AR
#lang(es)    %_datadir/dolibarr/htdocs/langs/es_ES
#lang(es_HN) %_datadir/dolibarr/htdocs/langs/es_HN
#lang(es_MX) %_datadir/dolibarr/htdocs/langs/en_MX
#lang(es_PR) %_datadir/dolibarr/htdocs/langs/en_PR
#lang(fa_IR) %_datadir/dolibarr/htdocs/langs/fa_IR
#lang(fi)    %_datadir/dolibarr/htdocs/langs/fi_FI
#lang(fr_BE) %_datadir/dolibarr/htdocs/langs/fr_BE
#lang(fr_CA) %_datadir/dolibarr/htdocs/langs/fr_CA
#lang(fr_CH) %_datadir/dolibarr/htdocs/langs/fr_CH
#lang(fr)    %_datadir/dolibarr/htdocs/langs/fr_FR



#---- post (after unzip during install)
%post

# Define vars
export docdir="/var/lib/dolibarr/documents"
export installconfig="%{_sysconfdir}/dolibarr/install.forced.php"

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

# Remove dolibarr install/upgrade lock file if it exists
%{__rm} -f $docdir/install.lock

# Create empty directory for uploaded files and generated documents 
echo Create document directory $docdir
%{__mkdir} -p $docdir

# Create install.forced.php into Dolibarr install directory
if [ "x$os" = "xubuntu-debian" ]
then
	superuserlogin=''
	superuserpassword=''
	if [ -f %{_sysconfdir}/mysql/debian.cnf ] ; then
	    # Load superuser login and pass
	    superuserlogin=$(/bin/grep --max-count=1 "user" %{_sysconfdir}/mysql/debian.cnf | /bin/sed -e 's/^user[ =]*//g')
	    superuserpassword=$(/bin/grep --max-count=1 "password" %{_sysconfdir}/mysql/debian.cnf | /bin/sed -e 's/^password[ =]*//g')
	fi
	echo Mysql superuser found to use is $superuserlogin
	%{__cat} /usr/share/dolibarr/build/rpm/install.forced.php.generic | sed -e 's/__SUPERUSERLOGIN__/'$superuserlogin'/g' | sed -e 's/__SUPERUSERPASSWORD__/'$superuserpassword'/g' > $installconfig
	%{__chmod} -R 660 $installconfig
fi

# Set correct owner on config files
%{__chown} -R root:$apachegroup /etc/dolibarr/*

# Create config for se $seconfig
if [ "x$os" = "xfedora-redhat" -a -s /sbin/restorecon ]; then
    echo Add SE Linux permissions for dolibarr
    # semanage add records into /etc/selinux/targeted/contexts/files/file_contexts.local
    semanage fcontext -a -t httpd_sys_script_rw_t "/etc/dolibarr(/.*?)"
    semanage fcontext -a -t httpd_sys_script_rw_t "/var/lib/dolibarr(/.*?)"
    restorecon -R -v /etc/dolibarr
    restorecon -R -v /var/lib/dolibarr
fi

# Create a config link dolibarr.conf
if [ ! -L $apachelink ]; then
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
echo "----- Dolibarr %version-%release - (c) Dolibarr dev team -----"
echo "Dolibarr files are now installed (into /usr/share/dolibarr)."
echo "To finish installation and use Dolibarr, click on the menu" 
echo "entry Dolibarr ERP-CRM or call the following page from your"
echo "web browser:"  
echo "http://localhost/dolibarr/"
echo "-------------------------------------------------------"
echo



#---- postun (after uninstall)
%postun

# Detect OS
os='unknown';
if [ -d %{_sysconfdir}/httpd/conf.d ]; then
    export os='fedora-redhat';
    export apachelink="%{_sysconfdir}/httpd/conf.d/dolibarr.conf"
fi
if [ -d %{_sysconfdir}/apache2/conf.d -a `grep ^wwwrun /etc/passwd | wc -l` -ge 1 ]; then
    export os='opensuse';
    export apachelink="%{_sysconfdir}/apache2/conf.d/dolibarr.conf"
fi
if [ -d %{_sysconfdir}/httpd/conf.d -a `grep -i "^mageia\|mandriva" /etc/issue | wc -l` -ge 1 ]; then
    export os='mageia-mandriva';
    export apachelink="%{_sysconfdir}/httpd/conf.d/dolibarr.conf"
fi
if [ -d %{_sysconfdir}/apache2/conf.d -a `grep ^www-data /etc/passwd | wc -l` -ge 1 ]; then
    export os='ubuntu-debian';
    export apachelink="%{_sysconfdir}/apache2/conf.d/dolibarr.conf"
fi

# Remove apache link
if [ -L $apachelink ] ;
then
    echo "Delete apache config link for Dolibarr ($apachelink)"
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



%changelog
* Wed Jan 04 2012 Laurent Destailleur 3.1.1-0.0
- Initial version (#723326)
