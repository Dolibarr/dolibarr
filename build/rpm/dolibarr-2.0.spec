%define name dolibarr
%define version	2.0
# For Mandrake
#%define release 1mdk
# For all other distrib
%define release 1

Name: %{name}
Version: %{version}
Release: %{release}
Summary: Dolibarr

License: GPL
Packager: Laurent Destailleur (Eldy) <eldy@users.sourceforge.net>
Vendor: Dolibarr dev team

URL: http://%{name}.sourceforge.net
Source: http://dl.sf.net/dolibarr/%{name}-%{version}.tgz
BuildArch: noarch
BuildArchitectures: noarch
BuildRoot: /tmp/%{name}-buildroot
Icon: dolibarr_logo1.gif

# For Mandrake
Group: Networking/WWW
# For all other distrib
Group: Applications/Internet

#Requires=perl
AutoReqProv: yes


%description
Dolibarr

%description -l fr
Dolibarr est un logiciel de gestion de PME/PMI, artisans ou 
associations.


#---- prep
%prep
%setup -q


#---- build
%build
# Nothing to build


#---- install
%install
rm -rf $RPM_BUILD_ROOT
mkdir -p $RPM_BUILD_ROOT/usr/local/dolibarr/doc
mkdir -p $RPM_BUILD_ROOT/usr/local/dolibarr/htdocs
mkdir -p $RPM_BUILD_ROOT/usr/local/dolibarr/misc
mkdir -p $RPM_BUILD_ROOT/usr/local/dolibarr/mysql
mkdir -p $RPM_BUILD_ROOT/usr/local/dolibarr/pgsl
mkdir -p $RPM_BUILD_ROOT/usr/local/dolibarr/scripts
#mkdir -p $RPM_BUILD_ROOT/%{_sysconfdir}/dolibarr
#mkdir -p $RPM_BUILD_ROOT/%{_sysconfdir}/cron.daily

#install -m 444 httpd_conf $RPM_BUILD_ROOT/usr/local/dolibarr/doc/httpd_conf
install -m 444 doc/* $RPM_BUILD_ROOT/usr/local/dolibarr/doc
install -m 444 htdocs/* $RPM_BUILD_ROOT/usr/local/dolibarr/htdoc
install -m 444 misc/* $RPM_BUILD_ROOT/usr/local/dolibarr/misc
install -m 444 mysql/* $RPM_BUILD_ROOT/usr/local/dolibarr/mysql
install -m 444 pgsql/* $RPM_BUILD_ROOT/usr/local/dolibarr/pgsql
install -m 444 scripts/* $RPM_BUILD_ROOT/usr/local/dolibarr/scripts


#---- clean
%clean
rm -rf $RPM_BUILD_ROOT


#---- files
%files
%defattr(-,root,root)
%doc README.TXT
%doc /usr/local/dolibarr/doc/*
%config /%{_sysconfdir}/dolibarr/htdocs/conf/conf.php
%dir /usr/local/dolibarr/doc
%dir /usr/local/dolibarr/htdocs
%dir /usr/local/dolibarr/misc
%dir /usr/local/dolibarr/mysql
%dir /usr/local/dolibarr/pgsql
%dir /usr/local/dolibarr/scripts

/usr/local/dolibarr/doc/*
/usr/local/dolibarr/htdocs/*
/usr/local/dolibarr/misc/*
/usr/local/dolibarr/mysql/*
/usr/local/dolibarr/pgsql/*
/usr/local/dolibarr/scripts/*


#---- post
%post

# Create a config file
#if [ 1 -eq 1 ]; then
#  if [ ! -f /%{_sysconfdir}/dolibarr/dolibarr.`hostname`.conf ]; then
#    /bin/cat /%{_sysconfdir}/dolibarr/dolibarr.model.conf | \
#      /usr/bin/perl -p -e 's|^SiteDomain=.*$|SiteDomain="'`hostname`'"|;
#                       s|^HostAliases=.*$|HostAliases="REGEX[^.*'${HOSTNAME//./\\\\.}'\$]"|;
#                      ' > /%{_sysconfdir}/dolibarr/dolibarr.`hostname`.conf || :
#  fi
#fi

# Show result
echo
echo ----- dolibarr %version - Dolibarr dev team -----
echo dolibarr files have been installed in /usr/local/dolibarr
echo


%changelog

