class apache::mod::php (
  $package_name   = undef,
  $package_ensure = 'present',
  $path           = undef,
  $extensions     = ['.php'],
  $content        = undef,
  $template       = 'apache/mod/php5.conf.erb',
  $source         = undef,
) {
  if ! defined(Class['apache::mod::prefork']) {
    fail('apache::mod::php requires apache::mod::prefork; please enable mpm_module => \'prefork\' on Class[\'apache\']')
  }
  validate_array($extensions)

  if $source and ($content or $template != 'apache/mod/php5.conf.erb') {
    warning('source and content or template parameters are provided. source parameter will be used')
  } elsif $content and $template != 'apache/mod/php5.conf.erb' {
    warning('content and template parameters are provided. content parameter will be used')
  }

  $manage_content = $source ? {
    undef   => $content ? {
      undef   => template($template),
      default => $content,
    },
    default => undef,
  }

  ::apache::mod { 'php5':
    package        => $package_name,
    package_ensure => $package_ensure,
    path           => $path,
  }

  include ::apache::mod::mime
  include ::apache::mod::dir
  Class['::apache::mod::mime'] -> Class['::apache::mod::dir'] -> Class['::apache::mod::php']

  # Template uses $extensions
  file { 'php5.conf':
    ensure  => file,
    path    => "${::apache::mod_dir}/php5.conf",
    owner   => 'root',
    group   => 'root',
    mode    => '0644',
    content => $manage_content,
    source  => $source,
    require => [
      Class['::apache::mod::prefork'],
      Exec["mkdir ${::apache::mod_dir}"],
    ],
    before  => File[$::apache::mod_dir],
    notify  => Service['httpd'],
  }
}
