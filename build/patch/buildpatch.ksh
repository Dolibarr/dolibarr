/bin/ksh
#
# This script can be used to build a patch after a developer has made
# changes on files in its Dolibarr tree.
# The output patch file can then be submited on Dolibarr dev mailing-list,
# with explanation on its goal, for inclusion in main branch.
#

export oldir=original_dir
export newdir=modified_dir

echo Building patch file mypatch.patch
diff -Naur --exclude=CVS --exclude="*.patch" --exclude=".#*" --exclude="*~" --exclude="*.rej" --exclude="*.orig" --exclude="*.bak" --exclude=conf.php --exclude=documents  $olddir  $newdir  > mypatch.patch

