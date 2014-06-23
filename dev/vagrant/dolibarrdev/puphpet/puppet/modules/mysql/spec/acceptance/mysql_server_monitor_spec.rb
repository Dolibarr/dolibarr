require 'spec_helper_acceptance'

describe 'mysql::server::monitor class', :unless => UNSUPPORTED_PLATFORMS.include?(fact('operatingsystem')) do
  it 'should work with no errors' do
    pp = <<-EOS
      class { 'mysql::server': root_password => 'password' }

      class { 'mysql::server::monitor':
        mysql_monitor_username => 'monitoruser',
        mysql_monitor_password => 'monitorpass',
        mysql_monitor_hostname => 'localhost',
      }
    EOS

    apply_manifest(pp, :catch_failures => true)
    expect(apply_manifest(pp, :catch_failures => true).exit_code).to be_zero
  end

  it 'should run mysqladmin ping with no errors' do
    expect(shell("mysqladmin -u monitoruser -pmonitorpass -h localhost ping").stdout).to match(/mysqld is alive/)
  end
end
