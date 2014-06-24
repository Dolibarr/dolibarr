# == Define: elasticsearch::template
#
#  This define allows you to insert, update or delete templates that are used within Elasticsearch for the indexes
#
# === Parameters
#
# [*file*]
#   File path of the template ( json file )
#   Value type is string
#   Default value: undef
#   This variable is optional
#
# [*replace*]
#   Set to 'true' if you intend to replace the existing template
#   Value type is boolean
#   Default value: false
#   This variable is optional
#
# [*delete*]
#   Set to 'true' if you intend to delete the existing template
#   Value type is boolean
#   Default value: false
#   This variable is optional
#
# [*host*]
#   Host name or IP address of the ES instance to connect to
#   Value type is string
#   Default value: localhost
#   This variable is optional
#
# [*port*]
#   Port number of the ES instance to connect to
#   Value type is number
#   Default value: 9200
#   This variable is optional
#
# === Authors
#
# * Richard Pijnenburg <mailto:richard@ispavailability.com>
#
define elasticsearch::template(
  $ensure  = 'present',
  $file    = undef,
  $host    = 'localhost',
  $port    = 9200
) {

  require elasticsearch

  # ensure
  if ! ($ensure in [ 'present', 'absent' ]) {
    fail("\"${ensure}\" is not a valid ensure parameter value")
  }

  if ! is_integer($port) {
    fail("\"${port}\" is not an integer")
  }

  Exec {
    path      => [ '/bin', '/usr/bin', '/usr/local/bin' ],
    cwd       => '/',
    tries     => 3,
    try_sleep => 10
  }

  # Build up the url
  $es_url = "http://${host}:${port}/_template/${name}"

  # Can't do a replace and delete at the same time

  if ($ensure == 'present') {

    # Fail when no file is supplied
    if $file == undef {
      fail('The variable "file" cannot be empty when inserting or updating a template')

    } else { # we are good to go. notify to insert in case we deleted
      $insert_notify = Exec[ "insert_template_${name}" ]
    }

  } else {

    $insert_notify = undef

  }

  # Delete the existing template
  # First check if it exists of course
  exec { "delete_template_${name}":
    command     => "curl -s -XDELETE ${es_url}",
    onlyif      => "test $(curl -s '${es_url}?pretty=true' | wc -l) -gt 1",
    notify      => $insert_notify,
    refreshonly => true
  }

  if ($ensure == 'present') {

    # place the template file
    file { "${elasticsearch::confdir}/templates_import/elasticsearch-template-${name}.json":
      ensure  => 'present',
      source  => $file,
      notify  => Exec[ "delete_template_${name}" ],
      require => Exec[ 'mkdir_templates' ],
    }

    exec { "insert_template_${name}":
      command     => "curl -s -XPUT ${es_url} -d @${elasticsearch::confdir}/templates_import/elasticsearch-template-${name}.json",
      unless      => "test $(curl -s '${es_url}?pretty=true' | wc -l) -gt 1",
      refreshonly => true
    }

  }

}
