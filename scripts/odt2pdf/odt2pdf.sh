#!/bin/bash
# @copyright  GPL License 2010 - Vikas Mahajan - http://vikasmahajan.wordpress.com
# @copyright  GPL License 2013 - Florian HEnry - florian.henry@open-concept.pro
# @copyright  GPL License 2017 - Laurent Destailleur - eldy@users.sourceforge.net
# @copyright  GPL License 2019 - Camille Lafitte - cam.lafit@azerttyu.net
# Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
#
# Convert an ODT into a PDF using "native" or "jodconverter" or "pyodconverter" or "unoconv" tool.
# Dolibarr variable MAIN_ODT_AS_PDF must be defined ...
#  to value "libreoffice" to call soffice native exporter feature (in such a case, this script is useless)
#  or value "unoconv" to call unoconv CLI tool after ODT generation.
#  or value "pyodconverter" to call DocumentConverter.py after ODT generation.
#  or value "jodconverter" to call jodconverter wrapper after ODT generation
#  or value "/pathto/jodconverter-cli-file.jar" to call jodconverter java tool without wrapper after ODT generation.
# Dolibarr variable MAIN_DOL_SCRIPTS_ROOT must be defined to path of script directories (otherwise dolibarr will try to guess).
#
# NOTE: Using this script is deprecated, you can now convert generated ODT to PDF on the fly by setting the value MAIN_ODT_AS_PDF
# to 'libreoffice'. It requires only soffice (OpenOffice or LibreOffice) installed on server (use apt install soffice libreoffice-common libreoffice-writer).
# If you got this error: javaldx failed! Warning: failed to read path from javaldx with no return to prompt when running soffice --headless -env:UserInstallation=file:"/tmp" --convert-to pdf --outdir xxx ./yyy.odt,
# check that directory defined into env:UserInstallation parameters exists and is writeable.

if [ "$1" = "" ] || [ "$2" = "" ]
then
	echo "Usage:   odt2pdf.sh fullfilename [native|unoconv|jodconverter|pyodconverter|pathtojodconverterjar|pandoc]"
	echo "Example: odt2pdf.sh myfile unoconv"
	echo "Example: odt2pdf.sh myfile ~/jodconverter/jodconverter-cli-2.2.2.jar"
	exit
fi

FILENAME=$1
METHOD=$2


# Full patch where soffice is installed
soffice=${SOFFICE:=/usr/bin/soffice}
if [ ! -x "$soffice" ] ; then
	soffice=$(which soffice 2>/dev/null)
fi

if [ ! -x "$soffice" ] ; then
	echo "Need path to soffice - install 'soffice' or set SOFFICE to path"
	exit 1
fi

soffice_basename=$(basename "$soffice")

# Temporary directory (web user must have permission to read/write). You can set here path to your DOL_DATA_ROOT/admin/temp directory for example.
home_java=${HOME_JAVA:=/tmp}


# Main program
if [ -f "${FILENAME}.odt" ]
then

	if [ "${METHOD}" == "native" ]
	then
		"$soffice" --headless "-env:UserInstallation=file:///$home_java/" --convert-to  pdf:writer_pdf_Export --outdir "$(dirname "${FILENAME}")" "${FILENAME}.odt"
		exit 0
	fi

	if [ "${METHOD}" == "unoconv" ]
	then
		# See issue https://github.com/dagwieers/unoconv/issues/87
		/usr/bin/unoconv -vvv "${FILENAME}.odt"
		retcode=$?
		if [ $retcode -ne 0 ]
		then
			echo "Error while converting odt to pdf: $retcode"
			exit 1
		fi
		exit 0
	fi

	if [ "${METHOD}" == "pandoc" ]
	then
		pandoc "${FILENAME}.odt" -t pdf -o "${FILENAME}.pdf"
		retcode=$?
		if [ $retcode -ne 0 ]
		then
			echo "Error while converting odt to pdf: $retcode"
			exit 1
		fi
		exit 0
	fi

	nbprocess=$(pgrep -c "$soffice_basename")
	if [ "$nbprocess" -ne 1 ]	# If there is some soffice process running
	then
		# shellcheck disable=2089
		cmd="\"$soffice\" --invisible --accept=socket,host=127.0.0.1,port=8100;urp; --nofirststartwizard --headless \"-env:UserInstallation=file:///$home_java/\""
		# shellcheck disable=2090
		export HOME="$home_java" && cd "$home_java" && eval "$cmd" &
		retcode=$?
		if [ $retcode -ne 0 ]
		then
			echo "Error running soffice: $retcode"
			exit 1
		fi
		sleep 2
	fi

	if [ "${METHOD}" == "jodconverter" ]
	then
		jodconverter "${FILENAME}.odt" "${FILENAME}.pdf"
	else
		if [ "${METHOD}" == "pyodconverter" ]
		then
			python DocumentConverter.py "${FILENAME}.odt" "${FILENAME}.pdf"
		else
			java -jar "${METHOD}" "${FILENAME}.odt" "${FILENAME}.pdf"
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
	echo "Error: Odt file ${FILENAME}.odt does not exist"
	exit 1
fi
