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

if [[ -z ${DOLIUSERNAME+x} ]]; then
	read -rp "Your Dolibarr Username: " DOLIUSERNAME
fi
if [[ -z ${DOLIPASSWORD+x} ]]; then
	read -rsp "Your Dolibarr Password: " DOLIPASSWORD
	echo ""
fi

if [[ -z ${COOKIEJAR+x} ]]; then
	COOKIEJAR=/tmp/cookie.jar
fi

hurl --variable "hostnport=${hostnport}" --variable "username=${DOLIUSERNAME}" --secret "password=${DOLIPASSWORD}" --test gui/save_login_cookie.hurl --cookie-jar "${COOKIEJAR}"
