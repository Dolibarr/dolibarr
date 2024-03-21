#!/bin/bash

# shellcheck disable=2002,2004,2028,2034,2053,2068,2086,2116,2143,2207

## Need "rpl" package
RPL_INSTALLED=$(dpkg -s rpl)
if [[ -z ${RPL_INSTALLED} ]]; then
	echo "This bash need rpl command, you can install it with: sudo apt install rpl"
fi

DIR_HTDOCS=$( cd "$( dirname "${BASH_SOURCE[0]}" )/../../htdocs" >/dev/null && pwd )

PATTERN=""
if [[ -f $1 ]]; then
	TFile=("$1")        # specific file
elif [[ -n $1 ]]; then
	PATTERN=$1  # name of a particular file or pattern (ex: societe.class.php)
else
	PATTERN="*.class.php" # *.lib.php
fi

if [[ -n ${PATTERN} ]]; then
	TCLASSFILE=$(find "${DIR_HTDOCS}" -name "${PATTERN}" | grep -v "/custom/" | grep -v "/includes/" | grep -v -F -f "${DIR_HTDOCS}/../.gitignore")
	TFile=()
	I=0
	for f in ${TCLASSFILE}; do
		TFile[${I}]="${f}"
		((I++))
	done
fi


REGEX_FNC_W='^([[:blank:]]*)(public|private|protected)?[ \t]*(static)?[ \t]*[^\$\(]function[ \t]*([a-zA-Z0-9\-\_]*)[\(](.*)[\)][ \t]*([\{]?)$'
INDENT="    "

for f in ${TFile[@]}; do
	#    echo ${f}

	IFS=$'\n'
	TLine=($(cat "${f}" | grep -E "${REGEX_FNC_W}"))

	for LINE in ${TLine[@]}; do

		if [[ ${LINE} =~ ^${REGEX_FNC_W}$ ]]; then
			FIRST_INDENT=${BASH_REMATCH[1]}       # seem not work
			FNC_TYPE=${BASH_REMATCH[2]}
			STATIC=${BASH_REMATCH[3]}
			FNC_NAME=${BASH_REMATCH[4]}
			PARAMETERS_ORIGIN=${BASH_REMATCH[5]}
			BRACKET_END=${BASH_REMATCH[6]}

			if [[ ${LINE} =~ ^([[:blank:]]*) ]]; then # but this seems work to get indentation
				FIRST_INDENT=${BASH_REMATCH[1]}
			fi

			[[ ${FNC_NAME} =~ ^__ ]] && continue    # skip magic function

			CAMEL_CASE=$(echo "${FNC_NAME}" | sed -r 's/(_)([a-zA-Z0-9])/\U\2/g')
			[[ ${CAMEL_CASE} = ${FNC_NAME} ]] && continue       # skip if no difference

			#echo A: ${#FIRST_INDENT}
			#printf "${FIRST_INDENT}TEST INDENT\n"
			#echo B: ${FNC_TYPE}
			#echo C: ${STATIC}
			#echo D: ${FNC_NAME}
			#echo D: ${CAMEL_CASE}
			#echo E: ${PARAMETERS_ORIGIN}
			#echo F: ${BRACKET_END}
			#exit

			[[ -n $(cat "${f}" | grep -i "function[[:blank:]]*${CAMEL_CASE}") ]] && continue   # skip if already exists

			TCommentLine=()
			J=1
			while :; do
				COMMENT=$(cat ${f} | grep -B ${J} ${LINE/\$/\\$} | head -n1 | grep -P '^[\t\ ]*(/\*\*|\*[^/]?|\*/)')
				if [[ -n ${COMMENT}  ]]; then
					TCommentLine[${J}]="${COMMENT}"
					((J++))
				else
					break
				fi
			done

			COMMENT_ORIGIN=""
			COMMENT_ORIGIN_WITH_DEPRECATED=""
			COMMENT_DUPLICATE=""
			if [[ ${#TCommentLine[@]} -gt 0 ]]; then
				for (( idx=${#TCommentLine[@]} ; idx>0 ; idx-- )) ; do
					COMMENT_ORIGIN="${COMMENT_ORIGIN}\n${TCommentLine[idx]}"
				done

				COMMENT_DUPLICATE=${COMMENT_ORIGIN}

				COMMENT_ORIGIN_WITH_DEPRECATED=$(echo "${COMMENT_ORIGIN%?} @deprecated\n${FIRST_INDENT} * @see ${CAMEL_CASE}\n${FIRST_INDENT} */")
			fi

			PARAMETERS=${PARAMETERS_ORIGIN}
			TParam=()
			I=0
			while [[ ${PARAMETERS} =~ (\$[a-zA-Z0-9\_\-]+) ]]; do
				TParam[${I}]=${BASH_REMATCH[1]}
				PARAMETERS=${PARAMETERS#*"${BASH_REMATCH[1]}"}
				((I++))
			done

			PARAMS_STR=$(printf ", %s" "${TParam[@]}")
			PARAMS_STR=${PARAMS_STR:2}

			REPLACE=${LINE}
			[[ -z ${BRACKET_END} ]] && REPLACE="${LINE}\n${FIRST_INDENT}{\n${FIRST_INDENT}${INDENT}" || REPLACE="${LINE}\n${FIRST_INDENT}${INDENT}"
			[[ -n ${STATIC} ]] && REPLACE="${REPLACE}return self::" || REPLACE="${REPLACE}return \$this->"
			REPLACE="${REPLACE}${CAMEL_CASE}(${PARAMS_STR});\n${FIRST_INDENT}}\n\n"
			REPLACE="${REPLACE}${FIRST_INDENT}${COMMENT_ORIGIN}\n${FIRST_INDENT}"
			[[ -n ${STATIC} ]] && REPLACE="${REPLACE}${STATIC} "
			[[ -n ${FNC_TYPE} ]] && REPLACE="${REPLACE}${FNC_TYPE} "
			REPLACE="${REPLACE}function ${CAMEL_CASE}(${PARAMETERS_ORIGIN})"
			[[ -n ${BRACKET_END} ]] && REPLACE="${REPLACE}\n${FIRST_INDENT}{"

			echo " ${FNC_NAME} -> ${CAMEL_CASE}"

			if [[ -n ${COMMENT_ORIGIN_WITH_DEPRECATED} ]]; then
				rpl -e --quiet "${COMMENT_ORIGIN}" ${COMMENT_ORIGIN_WITH_DEPRECATED} "${f}"
			fi
			rpl -e --quiet "${LINE}" ${REPLACE} "${f}"

		fi

	done
done


