class apache::mod::dev {
  # Development packages are not apache modules
  warning('apache::mod::dev is deprecated; please use apache::dev')
  include ::apache::dev
}
