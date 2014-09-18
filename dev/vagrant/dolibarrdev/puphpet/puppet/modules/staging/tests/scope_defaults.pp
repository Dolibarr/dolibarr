Exec {
  path => '/bin',
}

if scope_defaults('Exec', 'path') {
  notice('good')
}
