require 'spec_helper_acceptance'

describe 'postgresql::lib::java:', :unless => UNSUPPORTED_PLATFORMS.include?(fact('osfamily')) do
  after :all do
    # Cleanup after tests have ran
    apply_manifest("class { 'postgresql::lib::java': package_ensure => purged }", :catch_failures => true)
  end

  it 'test loading class with no parameters' do
    pending('libpostgresql-java-jdbc not available natively for Ubuntu 10.04 and Debian 6',
      :if => (fact('osfamily') == 'Debian' and ['6', '10'].include?(fact('lsbmajdistrelease'))))

    pp = <<-EOS.unindent
      class { 'postgresql::lib::java': }
    EOS

    apply_manifest(pp, :catch_failures => true)
    apply_manifest(pp, :catch_changes => true)
  end
end
