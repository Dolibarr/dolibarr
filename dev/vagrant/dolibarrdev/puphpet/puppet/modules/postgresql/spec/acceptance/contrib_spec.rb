require 'spec_helper_acceptance'

describe 'postgresql::server::contrib:', :unless => UNSUPPORTED_PLATFORMS.include?(fact('osfamily')) do
  after :all do
    # Cleanup after tests have ran, remove both contrib and server as contrib
    # pulls in the server based packages.
    pp = <<-EOS.unindent
      class { 'postgresql::server':
        ensure => absent,
      }
      class { 'postgresql::server::contrib':
        package_ensure => purged,
      }
    EOS

    apply_manifest(pp, :catch_failures => true)
  end

  it 'test loading class with no parameters' do
    pp = <<-EOS.unindent
      class { 'postgresql::server': }
      class { 'postgresql::server::contrib': }
    EOS

    apply_manifest(pp, :catch_failures => true)
    apply_manifest(pp, :catch_changes => true)
  end
end
