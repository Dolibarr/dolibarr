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
DB_PASSROOT=${DB_PASSROOT:=}
DB_USER=${DB_USER:=travis}
DB_PASS=${DB_PASS:=password}
DB_CACHE_FILE="${TRAVIS_BUILD_DIR}/db_init.sql"
DB_PREFIX="llx21_"

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
	rm "${DB_CACHE_FILE}".md5 2>/dev/null
	echo "Saving DB to cache file '${DB_CACHE_FILE}'"
	eval ${SUDO} "${MYSQLDUMP}" ${USERPASS_OPT} -h 127.0.0.1 travis \
		--hex-blob --lock-tables=false --skip-add-locks \
		| sed -e 's/DEFINER=[^ ]* / /' > ${DB_CACHE_FILE}
	echo "${sum}" > "${DB_CACHE_FILE}".md5
)


if [ "${DB_USER}" = travis ] && [ -r "${CONF_FILE}" ] ; then
	# Cleanup configuration file in ci
	mv "${CONF_FILE}" "${CONF_FILE}.$(date +"%Y%m%d%H%M%S").bak"
fi

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
			echo '$'"dolibarr_main_db_user='${DB_USER}'"';'
			echo '$'"dolibarr_main_db_pass='${DB_PASS}'"';'
		fi
		if [ "$DB" = 'postgresql' ]; then
			echo '$'dolibarr_main_db_type=\'pgsql\'';'
			echo '$'dolibarr_main_db_port=5432';'
			echo '$'dolibarr_main_db_user=\'postgres\'';'
			echo '$'dolibarr_main_db_pass=\'postgres\'';'
		fi
		if [ "${DB_PREFIX}" != '' ]; then
			echo '$'"dolibarr_main_db_prefix='${DB_PREFIX}'"';'
		fi
		echo '$'dolibarr_main_authentication=\'dolibarr\'';'
		echo '$'force_install_createuser=true';'
		echo '$'"dolibarr_main_db_collation='utf8_unicode_ci'"';'
	} > "$CONF_FILE"
	cat $CONF_FILE
	echo
fi

# From here on, the DB_PREFIX can not be empty, set default value if empty
DB_PREFIX="${DB_PREFIX:=llx_}"

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
	if [ "${DB_PASSROOT}" = "" ] ; then
		ROOTPASS_OPT="-u ${DB_ROOT}"
	else
		ROOTPASS_OPT="-u ${DB_ROOT} --password='${DB_PASSROOT}'"
	fi
	USERPASS_OPT="-u ${DB_USER} --password=\"${DB_PASS}\""

	echo "MySQL set root password"

	if [ 1 = 1 ]  ; then
		CMDS=( \
				""
			"FLUSH PRIVILEGES; DROP DATABASE travis; CREATE DATABASE IF NOT EXISTS travis CHARACTER SET = 'utf8';"
			"CREATE USER '${DB_ROOT}'@'localhost' IDENTIFIED BY '${DB_PASSROOT}';"
			"CREATE USER '${DB_ROOT}'@'127.0.0.1' IDENTIFIED BY '${DB_PASSROOT}';"
			"CREATE USER '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';"
			"CREATE USER '${DB_USER}'@'127.0.0.1' IDENTIFIED BY '${DB_PASS}';"
			"GRANT ALL PRIVILEGES ON travis.* TO ${DB_ROOT}@localhost;"
			"GRANT ALL PRIVILEGES ON travis.* TO ${DB_ROOT}@127.0.0.1;"
			"GRANT ALL PRIVILEGES ON travis.* TO ${DB_USER}@127.0.0.1;"
			"GRANT ALL PRIVILEGES ON travis.* TO ${DB_USER}@localhost;"
			"FLUSH PRIVILEGES;"
		)
		# Local, not changing root
		for CMD in "${CMDS[@]}" ; do
			${SUDO} "${MYSQL}" ${ROOTPASS_OPT} -h 127.0.0.1 -e "$CMD"
		done
	else
		DB_ROOT='root'
		DB_PASSROOT='password'
		ROOTPASS_OPT="-u ${DB_ROOT} '--password=${DB_PASSROOT}'"
		${SUDO} "${MYSQL}" -u "${ROOTPASS_OPT}" -h 127.0.0.1 -e "FLUSH PRIVILEGES; CREATE DATABASE IF NOT EXISTS travis CHARACTER SET = 'utf8'; ALTER USER '${DB_ROOT}'@'localhost' IDENTIFIED BY '${DB_PASSROOT}'; CREATE USER '${DB_ROOT}'@'127.0.0.1' IDENTIFIED BY '${DB_PASSROOT}'; CREATE USER '${DB_USER}'@'127.0.0.1' IDENTIFIED BY '${DB_PASS}'; GRANT ALL PRIVILEGES ON travis.* TO '${DB_ROOT}@127.0.0.1; GRANT ALL PRIVILEGES ON travis.* TO '${DB_USER}'@127.0.0.1; FLUSH PRIVILEGES;"
	fi
	echo "MySQL grant"
	${SUDO} "${MYSQL}" ${ROOTPASS_OPT} -h 127.0.0.1 -e "FLUSH PRIVILEGES; GRANT ALL PRIVILEGES ON travis.* TO ${DB_USER}@127.0.0.1; FLUSH PRIVILEGES;"
	${SUDO} "${MYSQL}" ${ROOTPASS_OPT} -h 127.0.0.1 -e "FLUSH PRIVILEGES; GRANT ALL PRIVILEGES ON travis.* TO ${DB_USER}@localhost; FLUSH PRIVILEGES;"
	echo "MySQL list current users"
	${SUDO} "${MYSQL}" ${ROOTPASS_OPT} -h 127.0.0.1 -e 'use mysql; select * from user;'
	echo "List pid file"
	${SUDO} "${MYSQL}" ${ROOTPASS_OPT} -h 127.0.0.1 -e "show variables like '%pid%';"

	#sudo kill `cat /var/lib/mysqld/mysqld.pid`
	#sudo systemctl start mariadb

	echo "MySQL grant"
	${SUDO} "${MYSQL}" ${ROOTPASS_OPT} -h 127.0.0.1 -e "GRANT ALL PRIVILEGES ON travis.* TO ${DB_USER}@127.0.0.1;"
	echo "MySQL flush"
	${SUDO} "${MYSQL}" ${ROOTPASS_OPT} -h 127.0.0.1 -e 'FLUSH PRIVILEGES;'


	# Compute md5 based on install file contents, and on db prefix
	# shellcheck disable=2046
	sum=$(md5sum $(find "${TRAVIS_BUILD_DIR}/htdocs/install" -type f) | { LC_ALL=C sort ; echo "$DB_PREFIX" ;} | md5sum)
	# shellcheck disable=2046
	cnt=$(md5sum $(find "${TRAVIS_BUILD_DIR}/htdocs/install" -type f) | wc)
	echo "MD5SUM $sum COUNT:$cnt"
	load_cache=0
	if [ -r "$DB_CACHE_FILE".md5 ] && [ -r "$DB_CACHE_FILE" ] && [ -x "$(which "${MYSQLDUMP}")" ] ; then
		cache_sum="$(<"$DB_CACHE_FILE".md5)"
		[ "$sum" = "$cache_sum" ] && load_cache=1
	fi

	if [ "$load_cache" = "1" ] ; then
		echo "MySQL load cached sql"
		eval ${SUDO} "${MYSQL}" --force ${USERPASS_OPT} -h 127.0.0.1 -D travis < ${DB_CACHE_FILE} | tee $TRAVIS_BUILD_DIR/db_from_cacheinit.log
	else
		echo "MySQL load initial sql"
		sed 's/\([ `]\)llx_/\1'"${DB_PREFIX}/g" < "${TRAVIS_BUILD_DIR}/dev/initdemo/mysqldump_dolibarr_3.5.0.sql" | eval ${SUDO} "${MYSQL}" --force ${USERPASS_OPT} -h 127.0.0.1 -D travis | tee $TRAVIS_BUILD_DIR/initial_350.log
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
	echo '$'"force_install_databaselogin='${DB_USER}'"';'
	echo '$'"force_install_databasepass='${DB_PASS}'"';'
	if [ "${DB_PREFIX}" != '' ] ; then
		echo '$'"force_install_prefix='${DB_PREFIX}'"';'
	fi
	#echo '$'"force_install_dolibarrlogin='admin'"';'
	#echo '$'force_install_createuser=true';'
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

		sed "s/ llx_/ ${DB_PREFIX}/g" <"${TRAVIS_BUILD_DIR}/htdocs/install/mysql/migration/repair.sql" |  eval ${SUDO} "${MYSQL}" --force ${USERPASS_OPT} -h 127.0.0.1 -D travis

		# Apply repair options:
		# Excluded options: force_utf8_on_tables force_utf8mb4_on_tables rebuild_sequences ; do
		PHP_REPAIR_OPT=""
		for opt in force_disable_of_modules_not_found restore_thirdparties_logos restore_user_pictures rebuild_product_thumbs clean_linked_elements clean_menus clean_orphelin_dir clean_product_stock_batch clean_perm_table repair_link_d set_empty_time_spent_amount force_collation_from_conf_on_tables ; do
			PHP_REPAIR_OPT="$PHP_REPAIR_OPT\$_POST['$opt'] = '1';"
		done
		LOGNAME="${TRAVIS_BUILD_DIR}/repair${pVer//./}${v//./}"
		"${PHP}" $PHP_OPT -r "$PHP_REPAIR_OPT; include 'repair.php';" > ${LOGNAME}.log

		{
			"${PHP}" $PHP_OPT upgrade2.php 0.0.0 0.0.0 MAIN_MODULE_API,MAIN_MODULE_ProductBatch,MAIN_MODULE_SupplierProposal,MAIN_MODULE_STRIPE,MAIN_MODULE_ExpenseReport
			"${PHP}" $PHP_OPT upgrade2.php 0.0.0 0.0.0 MAIN_MODULE_WEBSITE,MAIN_MODULE_TICKET,MAIN_MODULE_ACCOUNTING,MAIN_MODULE_MRP
			"${PHP}" $PHP_OPT upgrade2.php 0.0.0 0.0.0 MAIN_MODULE_RECEPTION,MAIN_MODULE_RECRUITMENT
			"${PHP}" $PHP_OPT upgrade2.php 0.0.0 0.0.0 MAIN_MODULE_KnowledgeManagement,MAIN_MODULE_EventOrganization,MAIN_MODULE_PARTNERSHIP
			"${PHP}" $PHP_OPT upgrade2.php 0.0.0 0.0.0 MAIN_MODULE_EmailCollector
		} > $TRAVIS_BUILD_DIR/enablemodule.log
	) && save_db_cache
fi
