# Note: this sets the global DirectoryIndex directive, it may be necessary to consider being able to modify the apache::vhost to declare DirectoryIndex statements in a vhost configuration
# Parameters:
# - $indexes provides a string for the DirectoryIndex directive http://httpd.apache.org/docs/current/mod/mod_dir.html#directoryindex
class apache::mod::dir (
  $dir     = 'public_html',
  $indexes = ['index.html','index.html.var','index.cgi','index.pl','index.php','index.xhtml'],
) {
  validate_array($indexes)
  ::apache::mod { 'dir': }

  # Template uses
  # - $indexes
  file { 'dir.conf':
    ensure  => file,
    path    => "${::apache::mod_dir}/dir.conf",
    content => template('apache/mod/dir.conf.erb'),
    require => Exec["mkdir ${::apache::mod_dir}"],
    before  => File[$::apache::mod_dir],
    notify  => Service['httpd'],
  }
}
