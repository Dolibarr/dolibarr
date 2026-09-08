#!/bin/bash
# Copyright (C) 2025		Jon Bendtsen	<jon.bendtsen.github@jonb.dk>
# Copyright (C) 2025		MDW		<mdeweerd@users.noreply.github.com>

if [[ -z "${DOLIHOST+x}" ]]; then
	DOLIHOST="localhost"
fi
hostnport="${DOLIHOST}"
if [[ -z "${DOLIPORT+x}" ]]; then
	hostnport="${hostnport}:8080"
else
	hostnport="${hostnport}:${DOLIPORT}"
fi
if [[ -z "${DOLISUBURL+x}" ]]; then
	DOLISUBURL=""
fi
if [[ "" != "${DOLISUBURL}" ]]; then
	hostnport="${hostnport}/${DOLISUBURL}"
fi

if [[ -z "${DOLIUSERNAME+x}" ]]; then
	echo "To do GUI tests we need \$DOLIUSERNAME or:"
	read -rp "  Your Dolibarr Username: " DOLIUSERNAME
fi
if [[ -z "${DOLIPASSWORD+x}" ]]; then
	echo "To do GUI tests we need \$DOLIPASSWORD or:"
	read -rsp "  Your Dolibarr Password: " DOLIPASSWORD
	echo ""
fi

if [[ -z "${COOKIEJAR+x}" ]]; then
	COOKIEJAR=/tmp/cookie.jar
fi

hurl --variable "hostnport=${hostnport}" --variable "username=${DOLIUSERNAME}" --secret "password=${DOLIPASSWORD}" --test gui/save_login_cookie.hurl --cookie-jar "${COOKIEJAR}"
