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

URL: http://%{name}.com
Source: http://dl.sf.net/dolibarr/%{name}-%{version}.tgz
BuildArch: noarch
BuildArchitectures: noarch
BuildRoot: /tmp/%{name}-buildroot
#Icon: dolibarr_logo1.gif

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

install -m 444 README  $RPM_BUILD_ROOT/usr/local/dolibarr/README
install -m 444 COPYRIGHT  $RPM_BUILD_ROOT/usr/local/dolibarr/COPYRIGHT
cp -pr doc $RPM_BUILD_ROOT/usr/local/dolibarr
cp -pr htdocs $RPM_BUILD_ROOT/usr/local/dolibarr
cp -pr misc $RPM_BUILD_ROOT/usr/local/dolibarr
cp -pr mysql $RPM_BUILD_ROOT/usr/local/dolibarr
cp -pr pgsql $RPM_BUILD_ROOT/usr/local/dolibarr
cp -pr scripts $RPM_BUILD_ROOT/usr/local/dolibarr


#---- clean
%clean
rm -rf $RPM_BUILD_ROOT


#---- files
%files
%defattr(-,root,root)
%doc README
%doc COPYRIGHT
%doc /usr/local/dolibarr/doc/*
#%config /usr/local/dolibarr/htdocs/conf/conf.php
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

