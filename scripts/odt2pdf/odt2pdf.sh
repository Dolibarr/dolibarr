#!/bin/bash
# @copyright  GPL License 2010 -  Vikas Mahajan - http://vikasmahajan.wordpress.com
# @copyright  GPL License 2013 -  Florian HEnry - florian.henry@open-concept.pro
#
# Convert an ODT into a PDF using "jodconverter" tool.
# Dolibarr variable MAIN_ODT_AS_PDF must be defined to have this script called after ODT generation.
# Dolibarr variable MAIN_DOL_SCRIPTS_ROOT must be defined to path of script directories (otherwise dolibarr will try to guess).
 

#if [ -f "$1.odt" ]
# then
#    soffice --invisible --convert-to pdf:writer_pdf_Export --outdir $2 "$1.odt"
#    retcode=$?
#    if [ $retcode -ne 0 ]
#     then
#      echo "Error while converting odt to pdf: $retcode";
#      exit 1
#    fi
# else
#  echo "Error: Odt file does not exist"
#  exit 1
#fi

if [ -f "$1.odt" ]
 then
  nbprocess=$(pgrep -c soffice)
  if [ $nbprocess -ne 1 ]
   then
    soffice --invisible --accept="socket,host=127.0.0.1,port=8100;urp;" --nofirststartwizard --headless &
    retcode=$?
    if [ $retcode -ne 0 ]
     then
      echo "Error running soffice: $retcode"
      exit 1
    fi
    sleep 2
  fi
  jodconverter "$1.odt" "$1.pdf"
  retcode=$?
  if [ $retcode -ne 0 ]
   then
    echo "Error while converting odt to pdf: $retcode"
    exit 1
  fi
  sleep 1
 else
  echo "Error: Odt file does not exist"
  exit 1
fi
