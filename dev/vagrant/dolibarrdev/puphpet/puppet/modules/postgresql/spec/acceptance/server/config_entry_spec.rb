require 'spec_helper_acceptance'

describe 'postgresql::server::config_entry:', :unless => UNSUPPORTED_PLATFORMS.include?(fact('osfamily')) do
  after :all do
    # Cleanup after tests have ran
    apply_manifest("class { 'postgresql::server': ensure => absent }", :catch_failures => true)
  end

  it 'should change setting and reflect it in show all' do
    pp = <<-EOS.unindent
      class { 'postgresql::server': }

      postgresql::server::config_entry { 'check_function_bodies':
        value => 'off',
      }
    EOS

    apply_manifest(pp, :catch_failures => true)
    apply_manifest(pp, :catch_changes => true)

    psql('--command="show all" postgres') do |r|
      expect(r.stdout).to match(/check_function_bodies.+off/)
      expect(r.stderr).to eq('')
    end
  end
end
