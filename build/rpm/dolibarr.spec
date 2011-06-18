%define name dolibarr
%define version	__VERSION__
# For Mandrake
#%define release 1mdk
# For all other distrib
%define release __RELEASE__
%define filenametgz __FILENAMETGZ__


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

#Requires=perl
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
%setup -q


#---- build
%build
# Nothing to build


#---- install
%install
rm -rf $RPM_BUILD_ROOT

mkdir -p $RPM_BUILD_ROOT/usr/local/dolibarr/doc
mkdir -p $RPM_BUILD_ROOT/usr/local/dolibarr/htdocs
mkdir -p $RPM_BUILD_ROOT/usr/local/dolibarr/scripts
#mkdir -p $RPM_BUILD_ROOT/%{_sysconfdir}/dolibarr
#mkdir -p $RPM_BUILD_ROOT/%{_sysconfdir}/cron.daily

install -m 444 README  $RPM_BUILD_ROOT/usr/local/dolibarr/README
install -m 444 COPYRIGHT  $RPM_BUILD_ROOT/usr/local/dolibarr/COPYRIGHT
cp -pr doc $RPM_BUILD_ROOT/usr/local/dolibarr
cp -pr htdocs $RPM_BUILD_ROOT/usr/local/dolibarr
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
%dir /usr/local/dolibarr/scripts
/usr/local/dolibarr/htdocs/*
/usr/local/dolibarr/scripts/*
/usr/local/dolibarr/README
/usr/local/dolibarr/COPYRIGHT

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
echo ----- Dolibarr %version - Dolibarr dev team -----
echo Dolibarr files have been installed in /usr/local/dolibarr
echo


%changelog

