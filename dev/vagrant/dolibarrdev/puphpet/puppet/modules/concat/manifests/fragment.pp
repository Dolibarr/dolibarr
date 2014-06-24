# == Define: concat::fragment
#
# Puts a file fragment into a directory previous setup using concat
#
# === Options:
#
# [*target*]
#   The file that these fragments belong to
# [*content*]
#   If present puts the content into the file
# [*source*]
#   If content was not specified, use the source
# [*order*]
#   By default all files gets a 10_ prefix in the directory you can set it to
#   anything else using this to influence the order of the content in the file
# [*ensure*]
#   Present/Absent or destination to a file to include another file
# [*mode*]
#   Deprecated
# [*owner*]
#   Deprecated
# [*group*]
#   Deprecated
# [*backup*]
#   Deprecated
#
define concat::fragment(
    $target,
    $content = undef,
    $source  = undef,
    $order   = 10,
    $ensure  = undef,
    $mode    = undef,
    $owner   = undef,
    $group   = undef,
    $backup  = undef
) {
  validate_string($target)
  validate_string($content)
  if !(is_string($source) or is_array($source)) {
    fail('$source is not a string or an Array.')
  }
  validate_string($order)
  if $mode {
    warning('The $mode parameter to concat::fragment is deprecated and has no effect')
  }
  if $owner {
    warning('The $owner parameter to concat::fragment is deprecated and has no effect')
  }
  if $group {
    warning('The $group parameter to concat::fragment is deprecated and has no effect')
  }
  if $backup {
    warning('The $backup parameter to concat::fragment is deprecated and has no effect')
  }
  if $ensure == undef {
    $_ensure = getparam(Concat[$target], 'ensure')
  } else {
    if ! ($ensure in [ 'present', 'absent' ]) {
      warning('Passing a value other than \'present\' or \'absent\' as the $ensure parameter to concat::fragment is deprecated.  If you want to use the content of a file as a fragment please use the $source parameter.')
    }
    $_ensure = $ensure
  }

  include concat::setup

  $safe_name        = regsubst($name, '[/:\n]', '_', 'GM')
  $safe_target_name = regsubst($target, '[/:\n]', '_', 'GM')
  $concatdir        = $concat::setup::concatdir
  $fragdir          = "${concatdir}/${safe_target_name}"
  $fragowner            = $concat::setup::fragment_owner
  $fragmode             = $concat::setup::fragment_mode

  # The file type's semantics are problematic in that ensure => present will
  # not over write a pre-existing symlink.  We are attempting to provide
  # backwards compatiblity with previous concat::fragment versions that
  # supported the file type's ensure => /target syntax

  # be paranoid and only allow the fragment's file resource's ensure param to
  # be file, absent, or a file target
  $safe_ensure = $_ensure ? {
    ''        => 'file',
    undef     => 'file',
    'file'    => 'file',
    'present' => 'file',
    'absent'  => 'absent',
    default   => $_ensure,
  }

  # if it looks line ensure => /target syntax was used, fish that out
  if ! ($_ensure in ['', 'present', 'absent', 'file' ]) {
    $ensure_target = $_ensure
  } else {
    $ensure_target = undef
  }

  # the file type's semantics only allows one of: ensure => /target, content,
  # or source
  if ($ensure_target and $source) or
    ($ensure_target and $content) or
    ($source and $content) {
    fail('You cannot specify more than one of $content, $source, $ensure => /target')
  }

  if ! ($content or $source or $ensure_target) {
    crit('No content, source or symlink specified')
  }

  # punt on group ownership until some point in the distant future when $::gid
  # can be relied on to be present
  file { "${fragdir}/fragments/${order}_${safe_name}":
    ensure  => $safe_ensure,
    owner   => $fragowner,
    mode    => $fragmode,
    source  => $source,
    content => $content,
    backup  => false,
    alias   => "concat_fragment_${name}",
    notify  => Exec["concat_${target}"]
  }
}
