class apache::mod::pagespeed (
  $inherit_vhost_config          = 'on',
  $filter_xhtml                  = false,
  $cache_path                    = '/var/cache/mod_pagespeed/',
  $log_dir                       = '/var/log/pagespeed',
  $memache_servers               = [],
  $rewrite_level                 = 'CoreFilters',
  $disable_filters               = [],
  $enable_filters                = [],
  $forbid_filters                = [],
  $rewrite_deadline_per_flush_ms = 10,
  $additional_domains            = undef,
  $file_cache_size_kb            = 102400,
  $file_cache_clean_interval_ms  = 3600000,
  $lru_cache_per_process         = 1024,
  $lru_cache_byte_limit          = 16384,
  $css_flatten_max_bytes         = 2048,
  $css_inline_max_bytes          = 2048,
  $css_image_inline_max_bytes    = 2048,
  $image_inline_max_bytes        = 2048,
  $js_inline_max_bytes           = 2048,
  $css_outline_min_bytes         = 3000,
  $js_outline_min_bytes          = 3000,
  $inode_limit                   = 500000,
  $image_max_rewrites_at_once    = 8,
  $num_rewrite_threads           = 4,
  $num_expensive_rewrite_threads = 4,
  $collect_statistics            = 'on',
  $statistics_logging            = 'on',
  $allow_view_stats              = [],
  $allow_pagespeed_console       = [],
  $allow_pagespeed_message       = [],
  $message_buffer_size           = 100000,
  $additional_configuration      = {},
  $apache_version                = $::apache::apache_version,
){

  $_lib = $::apache::apache_version ? {
    '2.4'   => 'mod_pagespeed_ap24.so',
    default => undef
  }

  apache::mod { 'pagespeed':
    lib => $_lib,
  }

  file { 'pagespeed.conf':
    ensure  => file,
    path    => "${::apache::mod_dir}/pagespeed.conf",
    content => template('apache/mod/pagespeed.conf.erb'),
    require => Exec["mkdir ${::apache::mod_dir}"],
    before  => File[$::apache::mod_dir],
    notify  => Service['httpd'],
  }
}
