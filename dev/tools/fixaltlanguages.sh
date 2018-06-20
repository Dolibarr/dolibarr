#!/bin/sh
# Recursively deduplicate file lines on a per file basis
# Useful to deduplicate language files
#
# Needs awk 4.0 for the inplace fixing command
#
# Raphaël Doursenaud - rdoursenaud@gpcsolutions.fr

# Syntax
if [ "x$1" != "xlist" -a "x$1" != "xfix" ]
then
	echo "Scan alternate language files and remove entries found into parent file"
	echo "Usage: fixaltlanguages.sh (list|fix) (all|file.lang) [xx_XX]"
	exit
fi
if [ "x$2" = "x" ]
then
	echo "Scan alternate language files and remove entries found into parent file"
	echo "Usage: fixaltlanguages.sh (list|fix) (all|file.lang) [xx_XX]"
	exit
fi

# To detect
if [ "x$1" = "xlist" ]
then
	echo Feature not available
fi

# To fix
if [ "x$1" = "xfix" ]
then
    for dir in `find htdocs/langs/$3* -type d`
    do
    	dirshort=`basename $dir`
    	
    	#echo $dirshort
    	
		export aa=`echo $dirshort | nawk -F"_" '{ print $1 }'`
        export bb=`echo $dirshort | nawk -F"_" '{ print $2 }'`
        aaupper=`echo $dirshort | nawk -F"_" '{ print toupper($1) }'`
        if [ $aaupper = "EN" ]
        then
        	aaupper="US"
        fi
        if [ $aaupper = "EL" ]
        then
        	aaupper="GR"
        fi        
        if [ $bb = "EG" ]
        then
        	aaupper="SA"
        fi        

    	bblower=`echo $dirshort | nawk -F"_" '{ print tolower($2) }'`

		echo "***** Process language "$aa"_"$bb
    	if [ "$aa" != "$bblower" -a "$dirshort" != "en_US" ]
    	then
    	    reflang="htdocs/langs/"$aa"_"$aaupper
    	    if [ -d $reflang -a $aa"_"$bb != $aa"_"$aaupper ]
    	    then
		    	echo "***** Search original into "$reflang
    			echo $dirshort is an alternative language of $reflang
    			echo ./dev/translation/strip_language_file.php $aa"_"$aaupper $aa"_"$bb $2
    			./dev/translation/strip_language_file.php $aa"_"$aaupper $aa"_"$bb $2
    			for fic in `ls htdocs/langs/${aa}_${bb}/*.delta`; do f=`echo $fic | sed -e 's/\.delta//'`; echo $f; mv $f.delta $f; done 
    			for fic in `ls htdocs/langs/${aa}_${bb}/*.lang`; 
    			do f=`cat $fic | wc -l`; 
    			    #echo $f lines into file $fic; 
    			    if [ $f = 1 ] 
    			    then 
    			        echo Only one line remainging into file $fic, we delete it;
    			        rm $fic 
    			    fi;
    			done
    		fi
    	fi
    done;
fi
