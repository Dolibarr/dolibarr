require 'spec_helper_acceptance'

describe 'postgresql::lib::python:', :unless => UNSUPPORTED_PLATFORMS.include?(fact('osfamily')) do
  after :all do
    # Cleanup after tests have ran
    apply_manifest("class { 'postgresql::lib::python': package_ensure => purged }", :catch_failures => true)
  end

  it 'test loading class with no parameters' do
    pending('psycopg2 not available natively for centos 5', :if => (fact('osfamily') == 'RedHat' and fact('lsbmajdistrelease') == '5'))

    pp = <<-EOS.unindent
      class { 'postgresql::lib::python': }
    EOS

    apply_manifest(pp, :catch_failures => true)
    apply_manifest(pp, :catch_changes => true)
  end
end
