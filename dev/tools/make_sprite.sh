#!/bin/bash

# Based of: https://gist.github.com/jaymzcd/342399 and https://github.com/buren/flag-sprite-maker

# uses imagemagick to stich together all images in a folder and
# then writes a css file with the correct offsets along with a
# test html page for verification that its all good

# Usage:
# $ ./make_sprite.sh path class_name image_extension

set -euo pipefail
IFS=$'\n\t'

name='output'; # output will be placed in a folder named this
path="${1:-}"  # Path to flag images
classname=${2:-flag}"-sprite"
ext="."${3:-png}; # the extension to iterate over for input files

css="$name/$classname.css";
html="$name/test.html";

rm -fr $name;
mkdir $name;
touch $css $html;

echo "Generating sprite file...";
convert $path*$ext -append $name/$classname$ext;
echo "Sprite complete! - Creating css & test output...";

echo -e "<html>\n<head>\n\t<link rel=\"stylesheet\" href=\"`basename $css`\" />\n</head>\n<body>\n\t<h1>Sprite test page</h1>\n" >> $html

echo -e ".$classname {\n\tbackground:url('$classname$ext') no-repeat top left; display:inline-block;\n}" >> $css;
counter=0;
offset=0;
for file in $path*$ext
do
    width=`identify -format "%[fx:w]" "$file"`;
    height=`identify -format "%[fx:h]" "$file"`;
    idname=`basename "$file" $ext`;
    clean=${idname// /-}
    echo -e ".$classname.$clean {" >> $css;
    echo -e "\tbackground-position:0 -${offset}px;" >> $css;
    echo -e "\twidth: ${width}px;" >> $css;
    echo -e "\theight: ${height}px;\n}" >> $css;

    echo -e "<div style=\"display:inline-block;width:100px;\"><div style=\"overflow-x:hidden;text-overflow:ellipsis;white-space:nowrap;\">$clean</div> <a href=\"#\" class=\"$classname $clean\"></a></div>\n" >> $html;

    let offset+=$height;
    let counter+=1;
    echo -e "\t#$counter done";
done

echo -e "<h2>Full sprite:</h2>\n<img src=\"$classname$ext\" />" >> $html;
echo -e "</body>\n</html>" >> $html;

echo -e "\nComplete! - $counter sprites created, css written & test page output.";
