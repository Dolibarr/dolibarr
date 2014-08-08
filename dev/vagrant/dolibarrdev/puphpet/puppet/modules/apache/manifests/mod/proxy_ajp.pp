class apache::mod::proxy_ajp {
  Class['::apache::mod::proxy'] -> Class['::apache::mod::proxy_ajp']
  ::apache::mod { 'proxy_ajp': }
}
