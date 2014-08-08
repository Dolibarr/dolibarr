require 'spec_helper_system'

describe 'mysql::server::monitor class' do
  context 'should work with no errors' do
    pp = <<-EOS
      class { 'mysql::server': root_password => 'password' } 
      
      class { 'mysql::server::monitor':
        mysql_monitor_username => 'monitoruser',
        mysql_monitor_password => 'monitorpass',
        mysql_monitor_hostname => 'localhost',
      }
    EOS

    context puppet_apply(pp) do
      its(:stderr) { should be_empty }
      its(:exit_code) { should_not == 1 }
      its(:refresh) { should be_nil }
      its(:stderr) { should be_empty }
      its(:exit_code) { should be_zero }
    end

    context 'should run mysqladmin ping with no errors' do
      describe command("mysqladmin -u monitoruser -pmonitorpass -h localhost ping") do
        it { should return_stdout /mysqld is alive/ }
        it { should return_exit_status 0 }
      end
    end
  end
end
