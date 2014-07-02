require 'spec_helper'
describe 'mysql::server::monitor' do
  let :facts do
    { :osfamily => 'Debian', :root_home => '/root' }
  end
  let :pre_condition do
    "include 'mysql::server'"
  end

  let :default_params do
    {
      :mysql_monitor_username   => 'monitoruser',
      :mysql_monitor_password   => 'monitorpass',
      :mysql_monitor_hostname   => 'monitorhost',
    }
  end

  let :params do
    default_params
  end

  it { should contain_mysql_user('monitoruser@monitorhost')}

  it { should contain_mysql_grant('monitoruser@monitorhost/*.*').with(
    :ensure     => 'present',
    :user       => 'monitoruser@monitorhost',
    :table      => '*.*',
    :privileges => ["PROCESS", "SUPER"],
    :require    => 'Mysql_user[monitoruser@monitorhost]'
  )}
end
