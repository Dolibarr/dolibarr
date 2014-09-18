class apache::mod::rewrite {
  include ::apache::params
  ::apache::mod { 'rewrite': }
}
