class apache::mod::proxy_http {
  Class['::apache::mod::proxy'] -> Class['::apache::mod::proxy_http']
  ::apache::mod { 'proxy_http': }
}
