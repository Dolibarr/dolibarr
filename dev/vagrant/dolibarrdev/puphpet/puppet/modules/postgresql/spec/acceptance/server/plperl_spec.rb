require 'spec_helper_acceptance'

describe 'server plperl:', :unless => UNSUPPORTED_PLATFORMS.include?(fact('osfamily')) do
  after :all do
    # Cleanup after tests have ran
    pp = <<-EOS.unindent
      class { 'postgresql::server': ensure => absent }
      class { 'postgresql::server::plperl': package_ensure => purged }
    EOS

    apply_manifest(pp, :catch_failures => true)
  end

  it 'test loading class with no parameters' do
    pending('no support for plperl with default version on centos 5',
      :if => (fact('osfamily') == 'RedHat' and fact('lsbmajdistrelease') == '5'))
    pp = <<-EOS.unindent
      class { 'postgresql::server': }
      class { 'postgresql::server::plperl': }
    EOS

    apply_manifest(pp, :catch_failures => true)
    apply_manifest(pp, :catch_changes => true)
  end
end
