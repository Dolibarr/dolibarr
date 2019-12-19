FROM mariadb:latest
# Enable comented out UTF8 charset/collation options
RUN sed '/utf8/ s/^#//' /etc/mysql/mariadb.cnf >/tmp/t && mv /tmp/t /etc/mysql/mariadb.cnf
