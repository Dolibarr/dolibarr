class apache::mod::xsendfile {
  include ::apache::params
  ::apache::mod { 'xsendfile': }
}
