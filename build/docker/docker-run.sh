#!/bin/bash

function initDolibarr()
{
  local CURRENT_UID=$(id -u www-data)
  local CURRENT_GID=$(id -g www-data)
  usermod -u ${WWW_USER_ID} www-data
  groupmod -g ${WWW_GROUP_ID} www-data

  if [[ ! -d /var/www/documents ]]; then
    echo "[INIT] => create volume directory /var/www/documents ..."
    mkdir -p /var/www/documents
  fi

  echo "[INIT] => update PHP Config ..."
  cat > ${PHP_INI_DIR}/conf.d/dolibarr-php.ini << EOF
date.timezone = ${PHP_INI_DATE_TIMEZONE}
sendmail_path = /usr/sbin/sendmail -t -i
memory_limit = ${PHP_INI_MEMORY_LIMIT}
EOF

  if [[ ! -f /var/www/html/conf/conf.php ]]; then
    echo "[INIT] => update Dolibarr Config ..."
    cat > /var/www/html/conf/conf.php << EOF
<?php
\$dolibarr_main_url_root='${DOLI_URL_ROOT}';
\$dolibarr_main_document_root='/var/www/html';
\$dolibarr_main_url_root_alt='/custom';
\$dolibarr_main_document_root_alt='/var/www/html/custom';
\$dolibarr_main_data_root='/var/www/documents';
\$dolibarr_main_db_host='${DOLI_DB_HOST}';
\$dolibarr_main_db_port='3306';
\$dolibarr_main_db_name='${DOLI_DB_NAME}';
\$dolibarr_main_db_prefix='llx_';
\$dolibarr_main_db_user='${DOLI_DB_USER}';
\$dolibarr_main_db_pass='${DOLI_DB_PASSWORD}';
\$dolibarr_main_db_type='mysqli';
EOF
  fi

  echo "[INIT] => update ownership for file in Dolibarr Config ..."
  chown www-data:www-data /var/www/html/conf/conf.php
  chmod 400 /var/www/html/conf/conf.php

  if [[ ${CURRENT_UID} -ne ${WWW_USER_ID} || ${CURRENT_GID} -ne ${WWW_GROUP_ID} ]]; then
    # Refresh file ownership cause it has changed
    echo "[INIT] => As UID / GID have changed from default, update ownership for files in /var/ww ..."
    chown -R www-data:www-data /var/www
  else
    # Reducing load on init : change ownership only for volumes declared in docker
    echo "[INIT] => update ownership for files in /var/www/documents ..."
    chown -R www-data:www-data /var/www/documents
  fi
}

function waitForDataBase()
{
  r=1
  while [[ ${r} -ne 0 ]]; do
    mysql -u ${DOLI_DB_USER} --protocol tcp -p${DOLI_DB_PASSWORD} -h ${DOLI_DB_HOST} -e "status" > /dev/null 2>&1
    r=$?
    if [[ ${r} -ne 0 ]]; then
      echo "Waiting that SQL database is up ..."
      sleep 2
    fi
  done
}

function lockInstallation()
{
  touch /var/www/documents/install.lock
  chown www-data:www-data /var/www/documents/install.lock
  chmod 400 /var/www/documents/install.lock
}

function initializeDatabase()
{
  for fileSQL in /var/www/html/install/mysql/tables/*.sql; do
    if [[ ${fileSQL} != *.key.sql ]]; then
      echo "Importing table from `basename ${fileSQL}` ..."
      sed -i 's/--.*//g;' ${fileSQL} # remove all comment
      mysql -u ${DOLI_DB_USER} -p${DOLI_DB_PASSWORD} -h ${DOLI_DB_HOST} ${DOLI_DB_NAME} < ${fileSQL}
    fi
  done

  for fileSQL in /var/www/html/install/mysql/tables/*.key.sql; do
    echo "Importing table key from `basename ${fileSQL}` ..."
    sed -i 's/--.*//g;' ${fileSQL}
    mysql -u ${DOLI_DB_USER} -p${DOLI_DB_PASSWORD} -h ${DOLI_DB_HOST} ${DOLI_DB_NAME} < ${fileSQL} > /dev/null 2>&1
  done

  for fileSQL in /var/www/html/install/mysql/functions/*.sql; do
    echo "Importing `basename ${fileSQL}` ..."
    sed -i 's/--.*//g;' ${fileSQL}
    mysql -u ${DOLI_DB_USER} -p${DOLI_DB_PASSWORD} -h ${DOLI_DB_HOST} ${DOLI_DB_NAME} < ${fileSQL} > /dev/null 2>&1
  done

  for fileSQL in /var/www/html/install/mysql/data/*.sql; do
    echo "Importing data from `basename ${fileSQL}` ..."
    sed -i 's/--.*//g;' ${fileSQL}
    mysql -u ${DOLI_DB_USER} -p${DOLI_DB_PASSWORD} -h ${DOLI_DB_HOST} ${DOLI_DB_NAME} < ${fileSQL} > /dev/null 2>&1
  done

  echo "Create SuperAdmin account ..."
  pass_crypted=`echo -n ${DOLI_ADMIN_PASSWORD} | md5sum | awk '{print $1}'`
  mysql -u ${DOLI_DB_USER} -p${DOLI_DB_PASSWORD} -h ${DOLI_DB_HOST} ${DOLI_DB_NAME} -e "INSERT INTO llx_user (entity, login, pass_crypted, lastname, admin, statut) VALUES (0, '${DOLI_ADMIN_LOGIN}', '${pass_crypted}', 'SuperAdmin', 1, 1);" > /dev/null 2>&1

  echo "Set some default const ..."
  mysql -u ${DOLI_DB_USER} -p${DOLI_DB_PASSWORD} -h ${DOLI_DB_HOST} ${DOLI_DB_NAME} -e "DELETE FROM llx_const WHERE name='MAIN_VERSION_LAST_INSTALL';" > /dev/null 2>&1
  mysql -u ${DOLI_DB_USER} -p${DOLI_DB_PASSWORD} -h ${DOLI_DB_HOST} ${DOLI_DB_NAME} -e "DELETE FROM llx_const WHERE name='MAIN_NOT_INSTALLED';" > /dev/null 2>&1
  mysql -u ${DOLI_DB_USER} -p${DOLI_DB_PASSWORD} -h ${DOLI_DB_HOST} ${DOLI_DB_NAME} -e "DELETE FROM llx_const WHERE name='MAIN_LANG_DEFAULT';" > /dev/null 2>&1
  mysql -u ${DOLI_DB_USER} -p${DOLI_DB_PASSWORD} -h ${DOLI_DB_HOST} ${DOLI_DB_NAME} -e "INSERT INTO llx_const(name,value,type,visible,note,entity) values('MAIN_VERSION_LAST_INSTALL', '${DOLI_VERSION}', 'chaine', 0, 'Dolibarr version when install', 0);" > /dev/null 2>&1
  mysql -u ${DOLI_DB_USER} -p${DOLI_DB_PASSWORD} -h ${DOLI_DB_HOST} ${DOLI_DB_NAME} -e "INSERT INTO llx_const(name,value,type,visible,note,entity) VALUES ('MAIN_LANG_DEFAULT', 'auto', 'chaine', 0, 'Default language', 1);" > /dev/null 2>&1
}

function migrateDatabase()
{
  TARGET_VERSION="$(echo ${DOLI_VERSION} | cut -d. -f1).$(echo ${DOLI_VERSION} | cut -d. -f2).0"
  echo "Schema update is required ..."
  echo "Dumping Database into /var/www/documents/dump.sql ..."

  mysqldump -u ${DOLI_DB_USER} -p${DOLI_DB_PASSWORD} -h ${DOLI_DB_HOST} ${DOLI_DB_NAME} > /var/www/documents/dump.sql
  r=${?}
  if [[ ${r} -ne 0 ]]; then
    echo "Dump failed ... Aborting migration ..."
    return ${r}
  fi
  echo "Dump done ... Starting Migration ..."

  echo "" > /var/www/documents/migration_error.html
  pushd /var/www/htdocs/install > /dev/null
  php upgrade.php ${INSTALLED_VERSION} ${TARGET_VERSION} >> /var/www/documents/migration_error.html 2>&1 && \
  php upgrade2.php ${INSTALLED_VERSION} ${TARGET_VERSION} >> /var/www/documents/migration_error.html 2>&1 && \
  php step5.php ${INSTALLED_VERSION} ${TARGET_VERSION} >> /var/www/documents/migration_error.html 2>&1
  r=$?
  popd > /dev/null

  if [[ ${r} -ne 0 ]]; then
    echo "Migration failed ... Restoring DB ... check file /var/www/documents/migration_error.html for more info on error ..."
    mysql -u ${DOLI_DB_USER} -p${DOLI_DB_PASSWORD} -h ${DOLI_DB_HOST} ${DOLI_DB_NAME} < /var/www/documents/dump.sql
    echo "DB Restored ..."
    return ${r}
  else
    echo "Migration successful ... Enjoy !!"
  fi

  return 0
}

function run()
{
  initDolibarr
  waitForDataBase
  echo "Current Version is : ${DOLI_VERSION}"

  if [[ ${DOLI_INSTALL_AUTO} -eq 1 && ! -f /var/www/documents/install.lock ]]; then
    mysql -u ${DOLI_DB_USER} -p${DOLI_DB_PASSWORD} -h ${DOLI_DB_HOST} ${DOLI_DB_NAME} -e "SELECT Q.LAST_INSTALLED_VERSION FROM (SELECT INET_ATON(CONCAT(value, REPEAT('.0', 3 - CHAR_LENGTH(value) + CHAR_LENGTH(REPLACE(value, '.', ''))))) as VERSION_ATON, value as LAST_INSTALLED_VERSION FROM llx_const WHERE name IN ('MAIN_VERSION_LAST_INSTALL', 'MAIN_VERSION_LAST_UPGRADE') and entity=0) Q ORDER BY VERSION_ATON DESC LIMIT 1" > /tmp/lastinstall.result 2>&1
    r=$?
    if [[ ${r} -ne 0 ]]; then
      initializeDatabase
    else
      INSTALLED_VERSION=`grep -v LAST_INSTALLED_VERSION /tmp/lastinstall.result`
      echo "Last installed Version is : ${INSTALLED_VERSION}"
      if [[ "$(echo ${INSTALLED_VERSION} | cut -d. -f1)" -lt "$(echo ${DOLI_VERSION} | cut -d. -f1)" ]]; then
        migrateDatabase
      else
        echo "Schema update is not required ... Enjoy !!"
      fi
    fi
    lockInstallation
  fi
}

run

exec apache2-foreground
