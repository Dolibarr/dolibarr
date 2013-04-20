#!/bin/bash
# @copyright  GPL License 2010 -  Vikas Mahajan - http://vikasmahajan.wordpress.com

if [ -f "$1.odt" ]
then
pgrep -U `id -u` soffice
if [ $? -ne 0 ]
then
soffice -headless -accept="socket,host=127.0.0.1,port=8100;urp;" -nofirststartwizard
sleep 2
fi
jodconverter "$1.odt" "$1.pdf"
if [ $? -ne 0 ]
then
echo "Error while converting odt to pdf"
exit 1
fi
sleep 1
else
echo "Error: Odt file does not exist"
exit 1
fi
