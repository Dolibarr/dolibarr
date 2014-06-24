#
class ntp::config inherits ntp {

  if $keys_enable {
    # Workaround for the lack of dirname() in stdlib 3.2.
    $directory = inline_template('<%= File.dirname(keys_file) %>')
    file { $directory:
      ensure  => directory,
      owner   => 0,
      group   => 0,
      mode    => '0755',
      recurse => true,
    }
  }

  file { $config:
    ensure  => file,
    owner   => 0,
    group   => 0,
    mode    => '0644',
    content => template($config_template),
  }

}
