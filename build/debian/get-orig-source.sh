#!/bin/sh

tmpdir=$(mktemp -d)


# Download source file
if [ -n "$1" ]; then
    uscan_opts="--download-version=$1"
fi
#uscan --noconf --force-download --no-symlink --destdir=$tmpdir $uscan_opts

cd $tmpdir

# Other method to download (comment uscan if you use this)
wget http://sourceforge.net/projects/tcpdf/files/tcpdf_6_0_093.zip

# Rename file to add +dfsg
zipfile=$(echo *.zip)
version=$(echo "$zipfile" | perl -pi -e 's/^tcpdf_//; s/\.zip$//; s/_/./g; s/$/+dfsg/;')

# Extract the zip file
unzip -q $zipfile
srcdir=$(find . -maxdepth 1 -mindepth 1 -type d | sed -e 's/\.\///')

if [ ! -d "$srcdir" ]; then
    echo "ERROR: Failed to identify the extracted directory in $tmpdir (got $srcdir)" >&2
    rm -rf $tmpdir
    exit 1
fi

# Cleanup unwanted files
rm -rf $srcdir/fonts/free*

# Repack as tar.xz
tar Jcf tcpdf_${version}.orig.tar.xz $srcdir

cd - >/dev/null

if [ -e ../tcpdf_${version}.orig.tar.xz ]; then
    echo "Not overwriting ../tcpdf_${version}.orig.tar.xz";
else
    echo "Created ../tcpdf_${version}.orig.tar.xz"
    mv $tmpdir/tcpdf_${version}.orig.tar.xz ../
fi

rm -rf $tmpdir
