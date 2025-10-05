#!/bin/bash

echo "First we run tests that do not require authentication"
LEVEL00=$(find api/ gui/ public/ -type f -iname '00*.hurl')
hurl --test ${LEVEL00}

# Now we get ready to run tests that do require authentication
if [[ -z ${APIHEADER+x} ]]; then
	echo "APIHEADER bash variable is unset, no API tests that require authentication"
else
	echo "Now we are ready to run API tests that do require authentication"
	find api/ -type f -iname '10*.hurl' -not -iname '00*.hurl' -exec hurl --header "${APIHEADER}" --test "{}" \;
fi

#if [[ -z ${GUIHEADER+x} ]]; then
#	echo "GUIHEADER bash variable is unset, no GUI tests that require authentication"
#else
#	echo "Now we are ready to run GUI tests that do require authentication"
#	find gui/ -type f -iname '10*.hurl' -not -iname '00*.hurl' -exec hurl --header "${GUIHEADER}" --test "{}" \;
#fi
