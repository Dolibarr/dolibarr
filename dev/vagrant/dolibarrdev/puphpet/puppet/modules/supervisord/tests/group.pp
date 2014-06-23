supervisord::group { 'mygroup':
  priority => 100,
  program  => ['program1', 'program2', 'program3']
}