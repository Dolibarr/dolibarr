#!/usr/bin/bash -xv
# Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
# shellcheck disable=2050,2089,2090,2086

TRAVIS_BUILD_DIR=${TRAVIS_BUILD_DIR:=$(realpath "$(dirname "$0")/../../..")}
MYSQL=${MYSQL:=mysql}
MYSQLDUMP=${MYSQLDUMP:="${MYSQL}dump"}
PHP=${PHP:=php}
PHP_OPT="-d error_reporting=32767"

DB=${DB:=mariadb}
DB_ROOT=${DB_ROOT:=root}
DB_PASS=${DB_PASS:=}
DB_CACHE_FILE="${TRAVIS_BUILD_DIR}/db_init.sql"

TRAVIS_DOC_ROOT_PHP="${TRAVIS_DOC_ROOT_PHP:=$TRAVIS_BUILD_DIR/htdocs}"
TRAVIS_DATA_ROOT_PHP="${TRAVIS_DATA_ROOT_PHP:=$TRAVIS_BUILD_DIR/documents}"

if [[ "$(uname -a)" =~ "MINGW"* ]] || [[ "$(uname -a)" =~ "CYGWIN"* ]] ; then
	TRAVIS_BUILD_DIR=$(cygpath -w "${TRAVIS_BUILD_DIR}")
	TRAVIS_BUILD_DIR=$(echo "$TRAVIS_BUILD_DIR" | sed 's/\\/\//g')
	TRAVIS_DOC_ROOT_PHP=$(cygpath -w "${TRAVIS_DOC_ROOT_PHP}")
	TRAVIS_DATA_ROOT_PHP=$(cygpath -w "${TRAVIS_DATA_ROOT_PHP}")
	SUDO=""
else
	SUDO="sudo"
fi
CONF_FILE=${CONF_FILE:=${TRAVIS_BUILD_DIR}/htdocs/conf/conf.php}

function save_db_cache() (
	set -x
	rm "${DB_CACHE_FILE}".md5 2>/dev/null
	echo "Saving DB to cache file '${DB_CACHE_FILE}'"
	${SUDO} "${MYSQLDUMP}" -u "$DB_ROOT" -h 127.0.0.1 $PASS_OPT travis \
		--hex-blob --lock-tables=false --skip-add-locks \
		| sed -e 's/DEFINER=[^ ]* / /' > ${DB_CACHE_FILE}
	echo "${sum}" > "${DB_CACHE_FILE}".md5
	set +x
)


if [ -r "${CONF_FILE}" ] ; then
	echo "'${CONF_FILE} exists, not overwriting!"

else
	echo "Setting up Dolibarr '$CONF_FILE'"
	{
		echo '<?php'
		echo 'error_reporting(E_ALL);'
		echo '$'dolibarr_main_url_root=\'http://127.0.0.1\'';'
		echo '$'dolibarr_main_document_root=\'${TRAVIS_DOC_ROOT_PHP}\'';'
		echo '$'dolibarr_main_data_root=\'${TRAVIS_DATA_ROOT_PHP}\'';'
		echo '$'dolibarr_main_db_host=\'127.0.0.1\'';'
		echo '$'dolibarr_main_db_name=\'travis\'';'
		echo '$'dolibarr_main_instance_unique_id=\'travis1234567890\'';'
		if [ "$DB" = 'mysql' ] || [ "$DB" = 'mariadb' ]; then
			echo '$'dolibarr_main_db_type=\'mysqli\'';'
			echo '$'dolibarr_main_db_port=3306';'
			echo '$'dolibarr_main_db_user=\'travis\'';'
			echo '$'dolibarr_main_db_pass=\'password\'';'
		fi
		if [ "$DB" = 'postgresql' ]; then
			echo '$'dolibarr_main_db_type=\'pgsql\'';'
			echo '$'dolibarr_main_db_port=5432';'
			echo '$'dolibarr_main_db_user=\'postgres\'';'
			echo '$'dolibarr_main_db_pass=\'postgres\'';'
		fi
		echo '$'dolibarr_main_authentication=\'dolibarr\'';'
	} > "$CONF_FILE"
	cat $CONF_FILE
	echo
fi

load_cache=0
echo "Setting up database '$DB'"
if [ "$DB" = 'mysql' ] || [ "$DB" = 'mariadb' ] || [ "$DB" = 'postgresql' ]; then
	echo "MySQL stop"
	${SUDO} systemctl stop mariadb.service
	echo "MySQL restart without pass"
	#sudo mysqld_safe --skip-grant-tables --socket=/tmp/aaa
	${SUDO} mysqld_safe --skip-grant-tables --socket=/tmp/aaa &
	sleep 3
	${SUDO} ps fauxww
	if [ "${DB_PASS}" = "" ] ; then
		PASS_OPT="-password="
		PASS_OPT=""
	else
		PASS_OPT="'-password=${DB_PASS}'"
	fi
	echo "MySQL set root password"

	if [ 1 = 1 ]  ; then
		CMDS=( \
				""
			"FLUSH PRIVILEGES; DROP DATABASE travis; CREATE DATABASE IF NOT EXISTS travis CHARACTER SET = 'utf8';"
			"CREATE USER 'root'@'localhost' IDENTIFIED BY '$DB_PASS';"
			"CREATE USER 'root'@'127.0.0.1' IDENTIFIED BY '$DB_PASS';"
			"CREATE USER 'travis'@'localhost' IDENTIFIED BY 'password';"
			"CREATE USER 'travis'@'127.0.0.1' IDENTIFIED BY 'password';"
			"GRANT ALL PRIVILEGES ON travis.* TO root@localhost;"
			"GRANT ALL PRIVILEGES ON travis.* TO root@127.0.0.1;"
			"GRANT ALL PRIVILEGES ON travis.* TO travis@127.0.0.1;"
			"GRANT ALL PRIVILEGES ON travis.* TO travis@localhost;"
			"FLUSH PRIVILEGES;"
		)
		# Local, not changing root
		for CMD in "${CMDS[@]}" ; do
			${SUDO} "${MYSQL}" -u "$DB_ROOT" ${PASS_OPT} -h 127.0.0.1 -e "$CMD"
		done
	else
		DB_ROOT='root'
		DB_PASS='password'
		${SUDO} "${MYSQL}" -u "$DB_ROOT" -h 127.0.0.1 -e "FLUSH PRIVILEGES; CREATE DATABASE IF NOT EXISTS travis CHARACTER SET = 'utf8'; ALTER USER 'root'@'localhost' IDENTIFIED BY '$DB_PASS'; CREATE USER 'root'@'127.0.0.1' IDENTIFIED BY '$DB_PASS'; CREATE USER 'travis'@'127.0.0.1' IDENTIFIED BY 'password'; GRANT ALL PRIVILEGES ON travis.* TO root@127.0.0.1; GRANT ALL PRIVILEGES ON travis.* TO travis@127.0.0.1; FLUSH PRIVILEGES;"
	fi
	echo "MySQL grant"
	${SUDO} "${MYSQL}" -u "$DB_ROOT" -h 127.0.0.1 $PASS_OPT -e 'FLUSH PRIVILEGES; GRANT ALL PRIVILEGES ON travis.* TO travis@127.0.0.1; FLUSH PRIVILEGES;'
	${SUDO} "${MYSQL}" -u "$DB_ROOT" -h 127.0.0.1 $PASS_OPT -e 'FLUSH PRIVILEGES; GRANT ALL PRIVILEGES ON travis.* TO travis@localhost; FLUSH PRIVILEGES;'
	echo "MySQL list current users"
	${SUDO} "${MYSQL}" -u "$DB_ROOT" -h 127.0.0.1 $PASS_OPT -e 'use mysql; select * from user;'
	echo "List pid file"
	${SUDO} "${MYSQL}" -u "$DB_ROOT" -h 127.0.0.1 $PASS_OPT -e "show variables like '%pid%';"

	#sudo kill `cat /var/lib/mysqld/mysqld.pid`
	#sudo systemctl start mariadb

	echo "MySQL grant"
	${SUDO} "${MYSQL}" -u "$DB_ROOT" -h 127.0.0.1 $PASS_OPT -e 'GRANT ALL PRIVILEGES ON travis.* TO travis@127.0.0.1;'
	echo "MySQL flush"
	${SUDO} "${MYSQL}" -u "$DB_ROOT" -h 127.0.0.1 $PASS_OPT -e 'FLUSH PRIVILEGES;'

	sum=$(find "${TRAVIS_BUILD_DIR}/htdocs/install" -type f -exec md5sum {} + | LC_ALL=C sort | md5sum)
	cnt=$(find "${TRAVIS_BUILD_DIR}/htdocs/install" -type f -exec md5sum {} + | wc)
	echo "OLDSUM $sum COUNT:$cnt"

	# shellcheck disable=2046
	sum=$(md5sum $(find "${TRAVIS_BUILD_DIR}/htdocs/install" -type f) | LC_ALL=C sort | md5sum)
	# shellcheck disable=2046
	cnt=$(md5sum $(find "${TRAVIS_BUILD_DIR}/htdocs/install" -type f) | wc)
	echo "NEWSUM $sum COUNT:$cnt"
	load_cache=0
	if [ -r "$DB_CACHE_FILE".md5 ] && [ -r "$DB_CACHE_FILE" ] && [ -x "$(which "${MYSQLDUMP}")" ] ; then
		cache_sum="$(<"$DB_CACHE_FILE".md5)"
		[ "$sum" = "$cache_sum" ] && load_cache=1
	fi

	if [ "$load_cache" = "1" ] ; then
		echo "MySQL load cached sql"
		${SUDO} "${MYSQL}" --force -u "$DB_ROOT" -h 127.0.0.1 $PASS_OPT -D travis < ${DB_CACHE_FILE} | tee $TRAVIS_BUILD_DIR/db_from_cacheinit.log
	else
		echo "MySQL load initial sql"
		${SUDO} "${MYSQL}" --force -u "$DB_ROOT" -h 127.0.0.1 $PASS_OPT -D travis < ${TRAVIS_BUILD_DIR}/dev/initdemo/mysqldump_dolibarr_3.5.0.sql | tee $TRAVIS_BUILD_DIR/initial_350.log
	fi
elif [ "$DB" = 'postgresql' ]; then
	echo Install pgsql if run is for pgsql

	echo "Check pgloader version"
	pgloader --version
	#ps fauxww | grep postgres
	ls /etc/postgresql/13/main/

	${SUDO} sed -i -e '/local.*peer/s/postgres/all/' -e 's/peer\|md5/trust/g' /etc/postgresql/13/main/pg_hba.conf
	${SUDO} cat /etc/postgresql/13/main/pg_hba.conf

	${SUDO} service postgresql restart

	psql postgresql://postgres:postgres@127.0.0.1:5432 -l -A

	psql postgresql://postgres:postgres@127.0.0.1:5432 -c 'create database travis;'
	psql postgresql://postgres:postgres@127.0.0.1:5432 -c "CREATE USER travis WITH ENCRYPTED PASSWORD 'travis';"
	psql postgresql://postgres:postgres@127.0.0.1:5432 -c 'GRANT ALL PRIVILEGES ON DATABASE travis TO travis;'

	psql postgresql://postgres:postgres@127.0.0.1:5432 -l -A
fi


export INSTALL_FORCED_FILE="${TRAVIS_BUILD_DIR}/htdocs/install/install.forced.php"
echo "Setting up Dolibarr '$INSTALL_FORCED_FILE' to test installation"
# Ensure we catch errors
set +e
{
	echo '<?php'
	echo 'error_reporting(E_ALL);'
	echo '$'force_install_noedit=2';'
	if [ "$DB" = 'mysql' ] || [ "$DB" = 'mariadb' ]; then
		echo '$'force_install_type=\'mysqli\'';'
		echo '$'force_install_port=3306';'
	fi
	if [ "$DB" = 'postgresql' ]; then
		echo '$'force_install_type=\'pgsql\'';'
		echo '$'force_install_port=5432';'
	fi
	echo '$'force_install_dbserver=\'127.0.0.1\'';'
	echo '$'force_install_database=\'travis\'';'
	echo '$'force_install_databaselogin=\'travis\'';'
	echo '$'force_install_databasepass=\'\'';'
	echo '$'force_install_prefix=\'llx_\'';'
	echo '$'force_install_createuser=false';'
} > "$INSTALL_FORCED_FILE"

if [ "$load_cache" != "1" ] ; then
	(
		cd "${TRAVIS_BUILD_DIR}/htdocs/install" || exit 1

		VERSIONS=("3.5.0" "3.6.0" "3.7.0" "3.8.0" "3.9.0")
		VERSIONS+=("4.0.0")
		VERSIONS+=("5.0.0" "6.0.0" "7.0.0" "8.0.0" "9.0.0")
		VERSIONS+=("10.0.0" "11.0.0" "12.0.0" "13.0.0" "14.0.0")
		VERSIONS+=("15.0.0" "16.0.0" "18.0.0" "19.0.0" "20.0.0")
		VERSIONS+=("21.0.0")
		pVer=${VERSIONS[0]}
		for v in "${VERSIONS[@]:1}" ; do
			LOGNAME="${TRAVIS_BUILD_DIR}/upgrade${pVer//./}${v//./}"
			"${PHP}" $PHP_OPT upgrade.php "$pVer" "$v" ignoredbversion > "${LOGNAME}.log"
			"${PHP}" $PHP_OPT upgrade2.php "$pVer" "$v" ignoredbversion > "${LOGNAME}-2.log"
			"${PHP}" $PHP_OPT step5.php "$pVer" "$v" ignoredbversion > "${LOGNAME}-3.log"
			pVer="$v"
		done

		${SUDO} "${MYSQL}" --force -u "$DB_ROOT" -h 127.0.0.1 $PASS_OPT -D travis < "${TRAVIS_BUILD_DIR}/htdocs/install/mysql/migration/repair.sql"


		{
			"${PHP}" $PHP_OPT upgrade2.php 0.0.0 0.0.0 MAIN_MODULE_API,MAIN_MODULE_ProductBatch,MAIN_MODULE_SupplierProposal,MAIN_MODULE_STRIPE,MAIN_MODULE_ExpenseReport
			"${PHP}" $PHP_OPT upgrade2.php 0.0.0 0.0.0 MAIN_MODULE_WEBSITE,MAIN_MODULE_TICKET,MAIN_MODULE_ACCOUNTING,MAIN_MODULE_MRP
			"${PHP}" $PHP_OPT upgrade2.php 0.0.0 0.0.0 MAIN_MODULE_RECEPTION,MAIN_MODULE_RECRUITMENT
			"${PHP}" $PHP_OPT upgrade2.php 0.0.0 0.0.0 MAIN_MODULE_KnowledgeManagement,MAIN_MODULE_EventOrganization,MAIN_MODULE_PARTNERSHIP
			"${PHP}" $PHP_OPT upgrade2.php 0.0.0 0.0.0 MAIN_MODULE_EmailCollector
		} > $TRAVIS_BUILD_DIR/enablemodule.log
	) && save_db_cache
fi
