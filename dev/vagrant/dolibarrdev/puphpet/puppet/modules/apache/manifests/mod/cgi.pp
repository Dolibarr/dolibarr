class apache::mod::cgi {
  Class['::apache::mod::prefork'] -> Class['::apache::mod::cgi']
  ::apache::mod { 'cgi': }
}
