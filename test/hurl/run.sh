#!/bin/bash

if [[ -z ${DOLIHOST+x} ]]; then
	DOLIHOST="localhost"
fi
hostnport="${DOLIHOST}"
if [[ -z ${DOLIPORT+x} ]]; then
	hostnport="${hostnport}:8080"
else
	hostnport="${hostnport}:${DOLIPORT}"
fi
if [[ -z ${DOLISUBURL+x} ]]; then
	DOLISUBURL=""
fi
if [[ "" != "${DOLISUBURL}" ]]; then
	hostnport="${hostnport}/${DOLISUBURL}"
fi

echo "First we run tests that do not require authentication"
find api/ gui/ public/ -type f -iname '00*.hurl' -exec hurl --variable "hostnport=${hostnport}" --test "{}" \;

# Now we get ready to run tests that do require authentication
if [[ -z ${DOLAPIKEY+x} ]]; then
	echo "DOLAPIKEY bash variable is unset, no API tests that require authentication"
else
	echo "Now we are ready to run API tests that do require authentication"
	find api/ -type f -iname '10*.hurl' -not -iname '00*.hurl' -exec hurl --header "${DOLAPIKEY}" --test "{}" \;
fi

if [[ -z ${GUIHEADER+x} ]]; then
	echo "GUIHEADER bash variable is unset, no GUI tests that require authentication"
else
	echo "Now we are ready to run GUI tests that do require authentication"
	find gui/ -type f -iname '10*.hurl' -not -iname '00*.hurl' -exec hurl --header "${GUIHEADER}" --test "{}" \;
fi
