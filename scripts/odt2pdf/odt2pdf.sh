#!/bin/bash
# @copyright  GPL License 2010 - Vikas Mahajan - http://vikasmahajan.wordpress.com
# @copyright  GPL License 2013 - Florian HEnry - florian.henry@open-concept.pro
# @copyright  GPL License 2017 - Laurent Destailleur - eldy@users.sourceforge.net
#
# Convert an ODT into a PDF using "jodconverter" or "pyodconverter" or "unoconv" tool.
# Dolibarr variable MAIN_ODT_AS_PDF must be defined 
#  to value "unoconv" to call unoconv CLI tool after ODT generation.
#  or value "pyodconverter" to call DocumentConverter.py after ODT generation.
#  or value "jodconverter" to call jodconverter wrapper after ODT generation
#  or value "/pathto/jodconverter-cli-file.jar" to call jodconverter java tool without wrapper after ODT generation.
# Dolibarr variable MAIN_DOL_SCRIPTS_ROOT must be defined to path of script directories (otherwise dolibarr will try to guess).


if [ "x$1" == "x" ] 
then
	echo "Usage:   odt2pdf.sh fullfilename [unoconv|jodconverter|pyodconverter|pathtojodconverterjar]"
	echo "Example: odt2pdf.sh myfile unoconv"
	echo "Example: odt2pdf.sh myfile ~/jodconverter/jodconverter-cli-2.2.2.jar"
	exit
fi




# Full patch where soffice is installed 
soffice="/usr/bin/soffice"

# Temporary directory (web user must have permission to read/write). You can set here path to your DOL_DATA_ROOT/admin/temp directory for example. 
home_java="/tmp"


# Main program
if [ -f "$1.odt" ]
then

  if [ "x$2" == "xunoconv" ]
  then
      # See issue https://github.com/dagwieers/unoconv/issues/87
      /usr/bin/unoconv -vvv "$1.odt"
      retcode=$?
	  if [ $retcode -ne 0 ]
	   then
	    echo "Error while converting odt to pdf: $retcode"
	    exit 1
	  fi
	  exit 0
  fi

  nbprocess=$(pgrep -c soffice)
  if [ $nbprocess -ne 1 ]	# If there is some soffice process running
   then
    cmd="$soffice --invisible --accept=socket,host=127.0.0.1,port=8100;urp; --nofirststartwizard --headless -env:UserInstallation=file:///$home_java/"
    export HOME=$home_java && cd $home_java && $cmd&
    retcode=$?
    if [ $retcode -ne 0 ]
     then
      echo "Error running soffice: $retcode"
      exit 1
    fi
    sleep 2
  fi
  
  if [ "x$2" == "xjodconverter" ]
  then
      jodconverter "$1.odt" "$1.pdf"
  else
      if [ "x$2" == "xpyodconverter" ]
      then
         python DocumentConverter.py "$1.odt" "$1.pdf"
      else
         java -jar $2 "$1.odt" "$1.pdf" 
      fi
  fi
  
  retcode=$?
  if [ $retcode -ne 0 ]
   then
    echo "Error while converting odt to pdf: $retcode"
    exit 1
  fi
  
  sleep 1
else
  echo "Error: Odt file $1.odt does not exist"
  exit 1
fi
