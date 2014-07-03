class apache::mod::proxy_balancer {

  include ::apache::mod::proxy
  include ::apache::mod::proxy_http

  Class['::apache::mod::proxy'] -> Class['::apache::mod::proxy_balancer']
  Class['::apache::mod::proxy_http'] -> Class['::apache::mod::proxy_balancer']
  ::apache::mod { 'proxy_balancer': }

}
