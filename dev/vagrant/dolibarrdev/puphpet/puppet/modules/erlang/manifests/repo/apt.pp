# == Class: erlang::repo::apt
#
# Install an apt package repository containing Erlang.
# All parameters have sane default values in erlang::params.
#
# === Parameters
# [*key_signature*]
#   The signature for the key used to sign packages in the repository.
#
# [*package_name*]
#   Name of the Erlang package in the specified repository.
#
# [*remote_repo_key_location*]
#   URL of the public key for the remote repository.
#
# [*remote_repo_location*]
#   URL of the remote debian repository.
#
# [*repos*]
#   Which of the standard repositories to install from the
#   remote repo. For instance main, contrib, restricted.
#
class erlang::repo::apt(
  $key_signature            = $erlang::key_signature,
  $package_name             = $erlang::package_name,
  $remote_repo_key_location = $erlang::remote_repo_key_location,
  $remote_repo_location     = $erlang::remote_repo_location,
  $repos                    = $erlang::repos,
) {

  Class['erlang::repo::apt'] -> Package<| title == $package_name |>

  apt::source { 'erlang':
    include_src => false,
    key         => $key_signature,
    key_source  => $remote_repo_key_location,
    location    => $remote_repo_location,
    repos       => $repos,
  }

}
