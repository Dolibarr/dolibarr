# Convenience class to call each of the three providers with the corresponding
# hashes provided in mysql::server.
# See README.md for details.
class mysql::server::providers {
  create_resources('mysql_user', $mysql::server::users)
  create_resources('mysql_grant', $mysql::server::grants)
  create_resources('mysql_database', $mysql::server::databases)
}
