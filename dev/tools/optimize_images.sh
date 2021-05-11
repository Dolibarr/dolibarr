#!/bin/bash
# Borrowed from https://gist.github.com/lgiraudel/6065155
# Inplace mode added by Raphaël Doursenaud <rdoursenaud@gpcsolutions.fr>

PROGNAME=${0##*/}
INPUT=''
QUIET='0'
NOSTATS='0'
INPLACE='0'				 # (1=Images are replaced, 0=New images are stored into $OUTPUT)
max_input_size=0
max_output_size=0


usage()
{
  cat <<EO
Usage: $PROGNAME [options]

Script to optimize JPG and PNG images in a directory.

Options:
EO
cat <<EO | column -s\& -t
	-h, --help  	   & shows this help
	-q, --quiet 	   & disables output
	-i, --input [dir]  & specify input directory (current directory by default)
	-o, --output [dir] & specify output directory ("output" by default)
	-ns, --no-stats	& no stats at the end
	-p, --inplace	  & optimizes files inplace
EO
}

# $1: input image
# $2: output image
optimize_image()
{
	input_file_size=$(stat -c%s "$1")
	max_input_size=$(expr $max_input_size + $input_file_size)

	if [ "${1##*.}" = "png" ]; then
		#optipng -o1 -clobber -quiet $1 -out $2.firstpass
		optipng -o1 -quiet $1 -out $2.firstpass
		pngcrush -q -rem alla -reduce $2.firstpass $2 >/dev/null
		rm -fr $2.firstpass
	fi
	if [ "${1##*.}" = "jpg" -o "${1##*.}" = "jpeg" ]; then
		jpegtran -copy none -progressive $1 > $2
	fi

	output_file_size=$(stat -c%s "$2")
	max_output_size=$(expr $max_output_size + $output_file_size)
}

get_max_file_length()
{
	local maxlength=0

	IMAGES=$(find $INPUT -regextype posix-extended -regex '.*\.(jpg|jpeg|png)' | grep -v $OUTPUT)

	for CURRENT_IMAGE in $IMAGES; do
		filename=$(basename "$CURRENT_IMAGE")
		if [[ ${#filename} -gt $maxlength ]]; then
			maxlength=${#filename}
		fi
	done

	echo "$maxlength"
}

main()
{
	test=`type pngcrush >/dev/null 2>&1`
	result=$?
	if [ "x$result" == "x1" ]; then
		echo "Tool pngcrush not found" && exit 
	fi
	
	test=`type optipng >/dev/null 2>&1`
	result=$?
	if [ "x$result" == "x1" ]; then
		echo "Tool optipng not found" && exit 
	fi

	test=`type jpegtran >/dev/null 2>&1`
	result=$?
	if [ "x$result" == "x1" ]; then
		echo "Tool jpegtran not found" && exit 
	fi


	# If $INPUT is empty, then we use current directory
	if [[ "$INPUT" == "" ]]; then
		INPUT=$(pwd)
	fi

	# If $OUTPUT is empty, then we use the directory "output" in the current directory
	if [[ "$OUTPUT" == "" ]]; then
		OUTPUT=$(pwd)/output
	fi
	# If inplace, we use /tmp for output
	if [[ "$INPLACE" == "1" ]]; then
		OUTPUT='/tmp/optimize'
	fi

	echo "Mode is $INPLACE (1=Images are replaced, 0=New images are stored into $OUTPUT)"
	
	# We create the output directory
	mkdir -p $OUTPUT

	# To avoid some troubles with filename with spaces, we store the current IFS (Internal File Separator)...
	SAVEIFS=$IFS
	# ...and we set a new one
	IFS=$(echo -en "\n\b")

	max_filelength=`get_max_file_length`
	pad=$(printf '%0.1s' "."{1..600})
	sDone=' [ DONE ]'
	linelength=$(expr $max_filelength + ${#sDone} + 5)

	# Search of all jpg/jpeg/png in $INPUT
	# We remove images from $OUTPUT if $OUTPUT is a subdirectory of $INPUT
	echo "Scan $INPUT to find images"
	IMAGES=$(find $INPUT -regextype posix-extended -regex '.*\.(jpg|jpeg|png)' | grep -v $OUTPUT)

	if [ "$QUIET" == "0" ]; then
		echo --- Optimizing $INPUT ---
		echo
	fi
	for CURRENT_IMAGE in $IMAGES; do
		echo "Process $CURRENT_IMAGE"
		filename=$(basename $CURRENT_IMAGE)
		if [ "$QUIET" == "0" ]; then
			printf '%s ' "$filename"
			printf '%*.*s' 0 $((linelength - ${#filename} - ${#sDone} )) "$pad"
		fi

		optimize_image $CURRENT_IMAGE $OUTPUT/$filename

		# Replace file
		if [[ "$INPLACE" == "1" ]]; then
			mv $OUTPUT/$filename $CURRENT_IMAGE
		fi

		if [ "$QUIET" == "0" ]; then
			printf '%s\n' "$sDone"
		fi
	done

	# Cleanup
	if [[ "$INPLACE" == "1" ]]; then
		rm -rf $OUTPUT
	fi

	# we restore the saved IFS
	IFS=$SAVEIFS

	if [ "$NOSTATS" == "0" -a "$QUIET" == "0" ]; then
		echo
		echo "Input: " $(human_readable_filesize $max_input_size)
		echo "Output: " $(human_readable_filesize $max_output_size)
		space_saved=$(expr $max_input_size - $max_output_size)
		echo "Space save: " $(human_readable_filesize $space_saved)
	fi
}

human_readable_filesize()
{
echo -n $1 | awk 'function human(x) {
	 s=" b  Kb Mb Gb Tb"
	 while (x>=1024 && length(s)>1)
		   {x/=1024; s=substr(s,4)}
	 s=substr(s,1,4)
	 xf=(s==" b ")?"%5d   ":"%.2f"
	 return sprintf( xf"%s", x, s)
  }
  {gsub(/^[0-9]+/, human($1)); print}'
}

SHORTOPTS="h,i:,o:,q,s,p"
LONGOPTS="help,input:,output:,quiet,no-stats,inplace"
ARGS=$(getopt -s bash --options $SHORTOPTS --longoptions $LONGOPTS --name $PROGNAME -- "$@")

# Syntax
if [ "x$1" != "xlist" -a "x$1" != "xfix" ]
then
	echo "Usage: optimize_images.sh (list|fix) -i dirtoscan"
	exit
fi

eval set -- "$ARGS"
while true; do
	case $1 in
		-h|--help)
			usage
			exit 0
			;;
		-i|--input)
			shift
			INPUT=$1
			;;
		-o|--output)
			shift
			OUTPUT=$1
			;;
		-q|--quiet)
			QUIET='1'
			;;
		-s|--no-stats)
			NOSTATS='1'
			;;
		-p|--inplace)
			INPLACE='1'
			;;
		--)
			shift
			break
			;;
		*)
			shift
			break
			;;
	esac
	shift
done

# To convert
if [ "x$1" = "xlist" ]
then
	INPLACE=0
fi

main

