#/bin/ksh
#----------------------------------------------------------------------------
# \file         build/patch/buildpatch.ksh
# \brief        Create patch files
# \version      $Revision$
# \author       (c)2009 Laurent Destailleur  <eldy@users.sourceforge.net>
#----------------------------------------------------------------------------
# This script can be used to build a patch after a developer has made
# changes on files in its Dolibarr tree.
# The output patch file can then be submited on Dolibarr dev mailing-list,
# with explanation on its goal, for inclusion in main branch.
#----------------------------------------------------------------------------

# Put here full path of original and new dolibarr directories
# Example: olddir=/mydirA1/mydirA2/dolibarrold
# Example: newdir=/mydirB1/mydirB2/dolibarr
export olddir=original_dir
export newdir=modified_dir

echo ----- Building patch file mypatch.patch -----
echo Build patch between \"$olddir\" and \"$newdir\" 
diff -BNaur --exclude=CVS --exclude="*.patch" --exclude=".#*" --exclude="*~" --exclude="*.rej" --exclude="*.orig" --exclude="*.bak" --exclude=conf.php --exclude=documents  $olddir  $newdir  > mypatch.patch

