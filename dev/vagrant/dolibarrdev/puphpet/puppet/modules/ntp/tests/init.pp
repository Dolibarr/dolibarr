node default {

  notify { 'enduser-before': }
  notify { 'enduser-after': }

  class { 'ntp':
    require => Notify['enduser-before'],
    before  => Notify['enduser-after'],
  }

}
