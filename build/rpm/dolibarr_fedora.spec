#---------------------------------------------------------
# Spec file to build a rpm file
#
# This is an example to build a rpm file. You can use this 
# file to build a package for your own distributions and 
# edit it if you need to match your rules.
# --------------------------------------------------------

Name: dolibarr
Version: __VERSION__
Release: __RELEASE__%{?dist}
Summary: ERP and CRM software for small and medium companies or foundations 
Summary(es): Software ERP y CRM para pequeñas y medianas empresas, asociaciones o autónomos
Summary(fr): Logiciel ERP & CRM de gestion de PME/PMI, auto-entrepreneurs ou associations
Summary(it): Programmo gestionale per piccole imprese, fondazioni e liberi professionisti

License: GPLv2+
#Packager: Laurent Destailleur (Eldy) <eldy@users.sourceforge.net>
Vendor: Dolibarr dev team

URL: http://www.dolibarr.org
Source0: http://www.dolibarr.org/files/lastbuild/package_rpm_redhat-fedora/%{name}-%{version}.tgz
Patch0: %{name}-forrpm.patch
BuildArch: noarch
BuildRoot: %{_tmppath}/%{name}-%{version}-build

Group: Applications/Productivity
Requires: httpd, php >= 5.3.0, php-cli, php-gd, php-ldap, php-imap, php-mysql, php-adodb, php-nusoap, dejavu-sans-fonts
Requires: mysql-server, mysql
#BuildRequires: desktop-file-utils

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

%{__mkdir} -p $RPM_BUILD_ROOT%{_sysconfdir}/%{name}
%{__install} -m 644 build/rpm/conf.php $RPM_BUILD_ROOT%{_sysconfdir}/%{name}/conf.php
%{__install} -m 644 build/rpm/httpd-dolibarr.conf $RPM_BUILD_ROOT%{_sysconfdir}/%{name}/apache.conf
%{__install} -m 644 build/rpm/file_contexts.dolibarr $RPM_BUILD_ROOT%{_sysconfdir}/%{name}/file_contexts.dolibarr
%{__install} -m 644 build/rpm/install.forced.php.fedora $RPM_BUILD_ROOT%{_sysconfdir}/%{name}/install.forced.php

%{__mkdir} -p $RPM_BUILD_ROOT%{_datadir}/pixmaps
%{__install} -m 644 doc/images/dolibarr_48x48.png $RPM_BUILD_ROOT%{_datadir}/pixmaps/%{name}.png
%{__mkdir} -p $RPM_BUILD_ROOT%{_datadir}/applications
#desktop-file-install --delete-original --dir=$RPM_BUILD_ROOT%{_datadir}/applications build/rpm/%{name}.desktop
%{__install} -m 644 build/rpm/dolibarr.desktop $RPM_BUILD_ROOT%{_datadir}/applications/%{name}.desktop

%{__mkdir} -p $RPM_BUILD_ROOT%{_datadir}/%{name}/build/rpm
%{__mkdir} -p $RPM_BUILD_ROOT%{_datadir}/%{name}/build/tgz
%{__mkdir} -p $RPM_BUILD_ROOT%{_datadir}/%{name}/htdocs
%{__mkdir} -p $RPM_BUILD_ROOT%{_datadir}/%{name}/scripts
%{__cp} -pr build/rpm/*     $RPM_BUILD_ROOT%{_datadir}/%{name}/build/rpm
%{__cp} -pr build/tgz/*     $RPM_BUILD_ROOT%{_datadir}/%{name}/build/tgz
%{__cp} -pr htdocs  $RPM_BUILD_ROOT%{_datadir}/%{name}
%{__cp} -pr scripts $RPM_BUILD_ROOT%{_datadir}/%{name}
%{__rm} -rf $RPM_BUILD_ROOT%{_datadir}/%{name}/htdocs/includes/adodbtime  
%{__rm} -rf $RPM_BUILD_ROOT%{_datadir}/%{name}/htdocs/includes/nusoap

# Lang
for i in $RPM_BUILD_ROOT%{_datadir}/%{name}/htdocs/langs/*_*
do
  lang=$(basename $i)
  lang1=`expr substr $lang 1 2`; 
  lang2=`expr substr $lang 4 2 | tr "[:upper:]" "[:lower:]"`; 
  if [ "$lang1" = "$lang2" ] ; then
	echo "%lang(${lang1}) %{_datadir}/%{name}/htdocs/langs/${lang}/*.lang"
  else
	echo "%lang(${lang}) %{_datadir}/%{name}/htdocs/langs/${lang}/*.lang"
  fi
done >%{name}.lang


#---- clean
%clean
%{__rm} -rf $RPM_BUILD_ROOT



#---- files
%files -f %{name}.lang

%defattr(0755, root, root, 0755)
%dir %_datadir/dolibarr/scripts
%_datadir/dolibarr/scripts/*

%defattr(-, root, root, 0755)
%doc COPYING ChangeLog doc/index.html htdocs/langs/HOWTO-Translation.txt

%_datadir/pixmaps/dolibarr.png
%_datadir/applications/dolibarr.desktop

%dir %_datadir/dolibarr/build/rpm
%_datadir/dolibarr/build/rpm/*

%dir %_datadir/dolibarr/build/tgz
%_datadir/dolibarr/build/tgz/*

%dir %_datadir/dolibarr/htdocs
%_datadir/dolibarr/htdocs/accountancy
%_datadir/dolibarr/htdocs/adherents
%_datadir/dolibarr/htdocs/admin
%_datadir/dolibarr/htdocs/asterisk
%_datadir/dolibarr/htdocs/bookmarks
%_datadir/dolibarr/htdocs/boutique
%_datadir/dolibarr/htdocs/cashdesk
%_datadir/dolibarr/htdocs/categories
%_datadir/dolibarr/htdocs/comm
%_datadir/dolibarr/htdocs/commande
%_datadir/dolibarr/htdocs/compta
%_datadir/dolibarr/htdocs/conf
%_datadir/dolibarr/htdocs/contact
%_datadir/dolibarr/htdocs/contrat
%_datadir/dolibarr/htdocs/core
%_datadir/dolibarr/htdocs/cron
%_datadir/dolibarr/htdocs/ecm
%_datadir/dolibarr/htdocs/expedition
%_datadir/dolibarr/htdocs/exports
%_datadir/dolibarr/htdocs/externalsite
%_datadir/dolibarr/htdocs/fichinter
%_datadir/dolibarr/htdocs/fourn
%_datadir/dolibarr/htdocs/ftp
%_datadir/dolibarr/htdocs/imports
%_datadir/dolibarr/htdocs/includes
%_datadir/dolibarr/htdocs/install
%_datadir/dolibarr/htdocs/langs/HOWTO-Translation.txt
%_datadir/dolibarr/htdocs/livraison
%_datadir/dolibarr/htdocs/paybox
%_datadir/dolibarr/htdocs/paypal
%_datadir/dolibarr/htdocs/product
%_datadir/dolibarr/htdocs/projet
%_datadir/dolibarr/htdocs/public
%_datadir/dolibarr/htdocs/societe
%_datadir/dolibarr/htdocs/support
%_datadir/dolibarr/htdocs/theme
%_datadir/dolibarr/htdocs/user
%_datadir/dolibarr/htdocs/webservices
%_datadir/dolibarr/htdocs/*.ico
%_datadir/dolibarr/htdocs/*.patch
%_datadir/dolibarr/htdocs/*.php
%_datadir/dolibarr/htdocs/*.txt

%defattr(0664, -, -)
%config(noreplace) %{_sysconfdir}/dolibarr/conf.php
%config(noreplace) %{_sysconfdir}/dolibarr/apache.conf
%config(noreplace) %{_sysconfdir}/dolibarr/install.forced.php
%config(noreplace) %{_sysconfdir}/dolibarr/file_contexts.dolibarr



#---- post (after unzip during install)
%post

# Define vars
export docdir="/var/lib/dolibarr/documents"
export apachelink="%{_sysconfdir}/httpd/conf.d/dolibarr.conf"
export apacheuser='apache';
export apachegroup='apache';

# Remove dolibarr install/upgrade lock file if it exists
%{__rm} -f $docdir/install.lock

# Create empty directory for uploaded files and generated documents 
echo Create document directory $docdir
%{__mkdir} -p $docdir

# Set correct owner on config files
%{__chown} -R root:$apachegroup /etc/dolibarr/*

# Create config for se $seconfig
echo Add SE Linux permissions for dolibarr
# semanage add records into /etc/selinux/targeted/contexts/files/file_contexts.local
semanage fcontext -a -t httpd_sys_script_rw_t "/etc/dolibarr(/.*?)"
semanage fcontext -a -t httpd_sys_script_rw_t "/var/lib/dolibarr(/.*?)"
restorecon -R -v /etc/dolibarr
restorecon -R -v /var/lib/dolibarr

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

# Define vars
export apachelink="%{_sysconfdir}/httpd/conf.d/dolibarr.conf"

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
* Wed Jul 31 2011 Laurent Destailleur 3.2.0-0.1.a
- Initial version (#723326)
