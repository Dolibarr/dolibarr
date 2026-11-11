#!/bin/bash
#----------------------------------------------------------------------------
# \file         dev/build/patch/buildpatch.sh
# \brief        Create patch files
# \author       (c)2009-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
#----------------------------------------------------------------------------
# This script can be used to build a patch after a developer has made
# changes on files in its Dolibarr tree.
# The output patch file can then be submitted on Dolibarr dev mailing-list,
# with explanation on its goal, for inclusion in main branch.
#----------------------------------------------------------------------------

# shellcheck disable=2086,2291

echo ----- Building patch file mypatch.patch -----
if [ -z "$1" ] || [ -z "$2" ];
then
	echo Usage:   buildpatch.sh  original_dir_path    modified_dir_path
	echo Example: buildpatch.sh  /mydirA/dolibarrold  /mydirB/dolibarrnew
else
	echo Build patch between \"$1\" and \"$2\"
	diff -BNaur --exclude=CVS --exclude="*.patch" --exclude=".#*" --exclude="*~" --exclude="*.rej" --exclude="*.orig" --exclude="*.bak" --exclude=conf.php --exclude=documents  $1  $2  > mypatch.patch
fi
