#!/bin/sh
# Scan for new official sources and download file
# run with debian/get-orig-source.sh [x.y.z]

tmpdir=$(mktemp -d)
echo "tmpdir = $tmpdir"

if [ -n "$1" ]; then
    uscan_opts="--download-version=$1"
fi
uscan --noconf --force-download --no-symlink --verbose --destdir=$tmpdir $uscan_opts

cd $tmpdir

tgzfile=$(echo *.tgz)
version=$(echo "$tgzfile" | perl -pi -e 's/^dolibarr-//; s/\.tgz$//; s/_/./g; s/\+nmu1//; ')

cd - >/dev/null

mv $tmpdir/dolibarr-${version}.tgz ../
echo "File ../dolibarr-${version}.tgz is ready for git-import"

rm -rf $tmpdir
