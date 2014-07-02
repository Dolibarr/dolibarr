#!/bin/bash
# yum.sh - Made for Puppi

# Sources common header for Puppi scripts
. $(dirname $0)/header || exit 10

# Show help
showhelp () {
    echo "This script performs the yum operations required by puppi::project::yum"
    echo "It integrates and uses variables provided by other core Puppi scripts"
    echo "It has the following options:"
    echo "-a <action> (Optional) What action to perform. Available options: deploy (default), rollback, remove"
    echo "-n <rpm_name> (Required) Name of the package to handle"
    echo "-v <rpm_version> (Optional) The version of the rpm to manage. Default: latest"
    echo "-r <install_root> (Optional) The Instll root path. Default: /"
    echo 
    echo "Examples:"
    echo "yum.sh -a deploy -n ${rpm} -r ${install_root} -v ${rpm_version}"
}

rpm_version="latest"
install_root="/"

# Check Arguments
while [ $# -gt 0 ]; do
  case "$1" in
    -a)
      case $2 in
          rollback)
          action="rollback"
          ;;
          remove)
          action="remove"
          ;;
          *)
          action="install"
          ;;
      esac 
      shift 2 ;;
    -n)
      rpm_name=$2
      shift 2 ;;
    -v)
      rpm_version=$2
      shift 2 ;;
    -r)
      install_root=$2
      shift 2 ;;
    *)
      showhelp
      exit ;;
  esac
done


do_install () {
    if [ x$rpm_version == "xlatest" ] ; then
        full_rpm_name=$rpm_name
    else
        full_rpm_name=$rpm_name-$rpm_version
    fi

    # Archives version of the rpm to update
    oldversion=$(rpm -q $rpm_name --qf  "%{VERSION}-%{RELEASE}\n")
    if [ "$?" = "0" ]; then
        mkdir -p $archivedir/$project/$oldversion
        if [ $archivedir/$project/latest ] ; then
            rm -f $archivedir/$project/latest
        fi
        ln -sf $archivedir/$project/$oldversion $archivedir/$project/latest
    fi

    if [ x$install_root != "x/" ] ; then
        yum install -y -q --installroot=$install_root $full_rpm_name
    else
        yum install -y -q $full_rpm_name
    fi
}

do_rollback () {
    yum downgrade -y -q $rpm_name-$rollbackversion
}

do_remove () {
    yum remove -y -q $rpm_name
}

# Action!
case "$action" in
    install) do_install ;;
    rollback) do_rollback ;;
    remove) do_remove ;;
esac
