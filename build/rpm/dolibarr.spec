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
AutoReqProv: yes


%description
Dolibarr ERP & CRM

%description -l fr
Dolibarr ERP & CRM est un logiciel de gestion de PME/PMI, autoentrepreneurs, 
artisans ou associations. Il permet de gérer vos clients, prospect, 
fournisseurs, devis, factures, comptes bancaires, agenda, campagne emailings
et bien d'autres choses dans une interface pensée pour la simplicité.


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
/var/www/dolibarr/build/*
/var/www/dolibarr/htdocs/*
/var/www/dolibarr/scripts/*
/var/www/dolibarr/README
/var/www/dolibarr/COPYRIGHT
#%config /var/www/dolibarr/htdocs/conf/conf.php

#---- post (after install)
%post

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

# Create a config link %{_sysconfdir}/httpd/conf.d/dolibarr.conf
if [ ! -f %{_sysconfdir}/httpd/conf.d/dolibarr.conf ]; then
     echo Create dolibarr web server config link %{_sysconfdir}/httpd/conf.d/dolibarr.conf
     ln -fs /etc/dolibarr/apache.conf %{_sysconfdir}/httpd/conf.d/dolibarr.conf
fi

# Set permissions
echo Set permission on $targetdir
chown -R apache.apache $targetdir
chmod -R a-w $targetdir

echo Set permission on $docdir
chown -R apache.apache $docdir
chmod -R o-w $docdir

if [ -s /usr/bin/chcon ]; then
    echo Set SELinux permissions 
    chcon -R -h -t httpd_sys_content_t $targetdir
    chcon -R -h -t httpd_sys_content_t $docdir
    chcon -R -h -t httpd_sys_script_rw_t $targetdir
    chcon -R -h -t httpd_sys_script_rw_t $docdir
    chcon -R -h -t httpd_sys_script_exec_t $targetdir
fi

# Create empty conf.php file for web installer
if [ ! -s $targetdir/htdocs/conf/conf.php ]; then
    echo Create empty Dolibarr conf.php file
    touch $targetdir/htdocs/conf/conf.php
    chown apache.apache $targetdir/htdocs/conf/conf.php
    chmod ug+rw $targetdir/htdocs/conf/conf.php
fi

# Restart web server
echo Restart web server
if [ -f %{_sysconfdir}/init.d/httpd ]; then
    %{_sysconfdir}/init.d/httpd restart
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

export conffile="%{_sysconfdir}/httpd/conf.d/dolibarr.conf"
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
fi

rm -rf /etc/dolibarr


%changelog
