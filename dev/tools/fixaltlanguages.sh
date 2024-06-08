#!/bin/bash
# Recursively deduplicate file lines on a per file basis
# Useful to deduplicate language files
#
# Needs awk 4.0 for the inplace fixing command
#
# Copyright (C) 2016		Raphaël Doursenaud					<rdoursenaud@gpcsolutions.fr>
# Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>

# Syntax
if  [ "$1" != "list" ] && [ "$1" != "fix" ]
then
	echo "Scan alternate language files and remove entries found in parent file"
	echo "Usage: fixaltlanguages.sh (list|fix) (all|file.lang) [ll_CC]"
	exit 1
fi
if [ "$2" = "" ]
then
	echo "Scan alternate language files and remove entries found in parent file"
	echo "Usage: fixaltlanguages.sh (list|fix) (all|file.lang) [ll_CC]"
	exit 1
fi

ACTION=$1
LANGFILE=$2
LANG=$3
exit_code=0


if [ -r "$LANGFILE" ] ; then
	if [ "$LANG" = "" ] ; then
		LANG=$(basename "$(dirname "$LANGFILE")")
	fi
	LANGFILE=$(basename "$LANGFILE")
fi

# To detect
if [ "$ACTION" = "list" ]
then
	echo Feature not available
	exit_code=1
fi

echo "$ACTION $LANGFILE $LANG"

# To fix
if [ "$ACTION" = "fix" ]
then
	for dir in htdocs/langs/"$LANG"*/
	do
		dirshort=$(basename "$dir")

		# echo $dirshort

		aa="$(echo "$dirshort" | cut -d_ -f1)"
		bb="$(echo "$dirshort" | cut -d_ -f2)"
		export aa ; export bb
		aaupper=$(echo "$dirshort" | awk -F_ '{ print toupper($1) }')
		if [ "$aaupper" = "EN" ]
		then
			aaupper="US"
		fi
		if [ "$aaupper" = "EL" ]
		then
			aaupper="GR"
		fi
		if [ "${bb}" = "EG" ]
		then
			aaupper="SA"
		fi
		if [ "${bb}" = "IQ" ]
		then
			aaupper="SA"
		fi

		bblower=$(echo "$dirshort" | awk -F_ '{ print tolower($2) }')

		echo "***** Process language '${aa}_${bb}'"
		if [ "$aa" != "$bblower" ] && [ "$dirshort" != "en_US" ]
		then
			reflang="htdocs/langs/${aa}_$aaupper"
			echo "$reflang '${aa}_${bb}' != '${aa}_$aaupper'"

			# If $reflang is a main language to use to sanitize the alternative file
			if [ -d "$reflang" ]
			then
				if [ "${aa}_${bb}" != "${aa}_$aaupper" ]
				then
					echo "***** Search original in $reflang"
					echo "$dirshort is an alternative language of $reflang"
					echo "./dev/translation/strip_language_file.php '${aa}_$aaupper' '${aa}_${bb}' '$LANGFILE'"
					RESULT=$(./dev/translation/strip_language_file.php "${aa}_${aaupper}" "${aa}_${bb}" "$LANGFILE")
					changed=0
					for fic in htdocs/langs/"${aa}_${bb}"/*.delta ; do
						# No delta file found ('*' surely still present)
						if [ ! -r "$fic" ] ; then break ; fi
						f=${fic//\.delta/}
						if diff -q "$f" "$f.delta" >/dev/null ; then
							rm "$f.delta"
						else
							mv "$f.delta" "$f" ; changed=1 ; exit_code=1
						fi
					done
					[ "$changed" != "0" ] && echo "$RESULT"
					for fic in htdocs/langs/"${aa}_${bb}"/*.lang ;
					do f=$(wc -l < "$fic");
						#echo $f lines into file $fic;
						if [ "$f" = 1 ]
						then
							exit_code=1
							echo "Only one line remaining in file '$fic', we delete it";
							rm "$fic"
						fi;
					done
				fi
			fi
		fi
	done;
fi

exit $exit_code
