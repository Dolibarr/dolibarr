supervisord::program { 'myprogram':
  command     => 'command --args',
  priority    => '100',
  environment => {
    'HOME'   => '/home/myuser',
    'PATH'   => '/bin:/sbin:/usr/bin:/usr/sbin',
    'SECRET' => 'mysecret'
  }
}