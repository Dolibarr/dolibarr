#!/bin/sh
# run with
# debian/get-orig-source.sh [x.y.z]

tmpdir=$(mktemp -d)
echo "tmpdir = $tmpdir"

if [ -n "$1" ]; then
    uscan_opts="--download-version=$1"
fi
uscan --noconf --force-download --no-symlink --verbose --destdir=$tmpdir $uscan_opts

cd $tmpdir

tgzfile=$(echo *.tar.gz)
version=$(echo "$tgzfile" | perl -pi -e 's/^dolibarr_//; s/\.zip$//; s/_/./g; s/\+nmu1//; s/$/+dfsg/;')

# Extract the zip file
tar -xvf $tgzfile
srcdir=$(find . -maxdepth 1 -mindepth 1 -type d | sed -e 's/\.\///')

if [ ! -d "$srcdir" ]; then
    echo "ERROR: Failed to identify the extracted directory in $tmpdir (got $srcdir)" >&2
    rm -rf $tmpdir
    exit 1
fi

# Repack as tar.xz
tar Jcf dolibarr_${version}.orig.tar.xz $srcdir

cd - >/dev/null

if [ -e ../dolibarr_${version}.orig.tar.xz ]; then
    echo "Not overwriting ../dolibarr_${version}.orig.tar.xz";
else
    echo "Created ../dolibarr_${version}.orig.tar.xz"
    mv $tmpdir/dolibarr_${version}.orig.tar.xz ../
fi

#rm -rf $tmpdir
