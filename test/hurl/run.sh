#!/bin/bash

echo "First we run tests that do not require authentication"
find api/ gui/ public/ -type f -iname '00*.hurl' -exec hurl --test "{}" \;

# Now we get ready to run tests that do require authentication
if [[ -f .settings/default.properties ]]; then
	source .settings/default.properties
else
	echo "Warning, there are no .settings/default.properties to source, so tests which require an authentication header will fail!"
fi
if [[ -z ${APIHEADER+x} ]]; then
	echo "APIHEADER bash variable is unset even after 'source .settings/default.properties'"
	exit 127
fi

echo "Now we are ready to run API tests that do require authentication"
find api/ -type f -iname '10*.hurl' -not -iname '00*.hurl' -exec hurl --header "${APIHEADER}" --test "{}" \;


# echo "Now we are ready to run GUI tests that do require authentication"
# find gui/ -type f -iname '10*.hurl' -not -iname '00*.hurl' -exec hurl --header "${GUIHEADER}" --test "{}" \;
