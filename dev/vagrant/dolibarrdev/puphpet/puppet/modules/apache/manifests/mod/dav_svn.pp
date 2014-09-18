class apache::mod::dav_svn (
  $authz_svn_enabled = false,
) {
    Class['::apache::mod::dav'] -> Class['::apache::mod::dav_svn']
    include ::apache::mod::dav
    ::apache::mod { 'dav_svn': } 
    
    if $authz_svn_enabled {
      ::apache::mod { 'authz_svn':
        loadfile_name => 'dav_svn_authz_svn.load',
        require       => Apache::Mod['dav_svn'],
      }
    }
}
