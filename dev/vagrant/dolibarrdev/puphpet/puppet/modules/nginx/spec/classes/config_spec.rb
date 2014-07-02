require 'spec_helper'
describe 'nginx::config' do

  describe 'with defaults' do
    [
      { :osfamily => 'debian', :operatingsystem => 'debian', },
      { :osfamily => 'debian', :operatingsystem => 'ubuntu', },
      { :osfamily => 'redhat', :operatingsystem => 'fedora', },
      { :osfamily => 'redhat', :operatingsystem => 'rhel', },
      { :osfamily => 'redhat', :operatingsystem => 'redhat', },
      { :osfamily => 'redhat', :operatingsystem => 'centos', },
      { :osfamily => 'redhat', :operatingsystem => 'scientific', },
      { :osfamily => 'redhat', :operatingsystem => 'amazon', },
      { :osfamily => 'suse',   :operatingsystem => 'suse', },
      { :osfamily => 'suse',   :operatingsystem => 'opensuse', },
      { :osfamily => 'gentoo', :operatingsystem => 'gentoo', },
      { :osfamily => 'linux',  :operatingsystem => 'gentoo', },
    ].each do |facts|

      context "when osfamily/operatingsystem is #{facts[:osfamily]}/#{facts[:operatingsystem]}" do

        let :facts do
          {
            :osfamily        => facts[:osfamily],
            :operatingsystem => facts[:operatingsystem],
          }
        end

        it { should contain_class("nginx::params") }

        it { should contain_file("/etc/nginx").only_with(
          :path   => "/etc/nginx",
          :ensure => 'directory',
          :owner => 'root',
          :group => 'root',
          :mode => '0644'
        )}
        it { should contain_file("/etc/nginx/conf.d").only_with(
          :path   => '/etc/nginx/conf.d',
          :ensure => 'directory',
          :owner => 'root',
          :group => 'root',
          :mode => '0644'
        )}
        it { should contain_file("/etc/nginx/conf.mail.d").only_with(
          :path   => '/etc/nginx/conf.mail.d',
          :ensure => 'directory',
          :owner => 'root',
          :group => 'root',
          :mode => '0644'
        )}
        it { should contain_file("/etc/nginx/conf.d/vhost_autogen.conf").with_ensure('absent') }
        it { should contain_file("/etc/nginx/conf.mail.d/vhost_autogen.conf").with_ensure('absent') }
        it { should contain_file("/var/nginx").with(
          :ensure => 'directory',
          :owner => 'root',
          :group => 'root',
          :mode => '0644'
        )}
        it { should contain_file("/var/nginx/client_body_temp").with(
          :ensure => 'directory',
          :group => 'root',
          :mode => '0644'
        )}
        it { should contain_file("/var/nginx/proxy_temp").with(
          :ensure => 'directory',
          :group => 'root',
          :mode => '0644'
        )}
        it { should contain_file('/etc/nginx/sites-enabled/default').with_ensure('absent') }
        it { should contain_file("/etc/nginx/nginx.conf").with(
          :ensure => 'file',
          :owner => 'root',
          :group => 'root',
          :mode => '0644'
        )}
        it { should contain_file("/etc/nginx/conf.d/proxy.conf").with(
          :ensure => 'file',
          :owner => 'root',
          :group => 'root',
          :mode => '0644'
        )}
        it { should contain_file("/tmp/nginx.d").with(
          :ensure => 'absent',
          :purge => true,
          :recurse => true
        )}
        it { should contain_file("/tmp/nginx.mail.d").with(
          :ensure => 'absent',
          :purge => true,
          :recurse => true
        )}
      end
    end
  end

  describe 'with defaults' do
    [
      { :osfamily => 'debian', :operatingsystem => 'debian', },
      { :osfamily => 'debian', :operatingsystem => 'ubuntu', },
    ].each do |facts|

      context "when osfamily/operatingsystem is #{facts[:osfamily]}/#{facts[:operatingsystem]}" do

        let :facts do
          {
            :osfamily        => facts[:osfamily],
            :operatingsystem => facts[:operatingsystem],
          }
        end
        it { should contain_file("/var/nginx/client_body_temp").with(:owner => 'www-data')}
        it { should contain_file("/var/nginx/proxy_temp").with(:owner => 'www-data')}
        it { should contain_file("/etc/nginx/nginx.conf").with_content %r{^user www-data;}}
      end
    end
  end

  describe 'with defaults' do
    [
      { :osfamily => 'redhat', :operatingsystem => 'fedora', },
      { :osfamily => 'redhat', :operatingsystem => 'rhel', },
      { :osfamily => 'redhat', :operatingsystem => 'redhat', },
      { :osfamily => 'redhat', :operatingsystem => 'centos', },
      { :osfamily => 'redhat', :operatingsystem => 'scientific', },
      { :osfamily => 'redhat', :operatingsystem => 'amazon', },
      { :osfamily => 'suse',   :operatingsystem => 'suse', },
      { :osfamily => 'suse',   :operatingsystem => 'opensuse', },
      { :osfamily => 'gentoo', :operatingsystem => 'gentoo', },
      { :osfamily => 'linux',  :operatingsystem => 'gentoo', },
    ].each do |facts|

      context "when osfamily/operatingsystem is #{facts[:osfamily]}/#{facts[:operatingsystem]}" do

        let :facts do
          {
            :osfamily        => facts[:osfamily],
            :operatingsystem => facts[:operatingsystem],
          }
        end
        it { should contain_file("/var/nginx/client_body_temp").with(:owner => 'nginx')}
        it { should contain_file("/var/nginx/proxy_temp").with(:owner => 'nginx')}
        it { should contain_file("/etc/nginx/nginx.conf").with_content %r{^user nginx;}}
      end
    end
  end

  describe 'os-independent items' do

    let :facts do
      {
        :osfamily        => 'debian',
        :operatingsystem => 'debian',
      }
    end

    describe "nginx.conf template content" do
      [
        {
          :title => 'should set worker_processes',
          :attr  => 'worker_processes',
          :value => '4',
          :match => 'worker_processes 4;',
        },
        {
          :title => 'should set worker_rlimit_nofile',
          :attr  => 'worker_rlimit_nofile',
          :value => '10000',
          :match => 'worker_rlimit_nofile 10000;',
        },
        {
          :title => 'should set error_log',
          :attr  => 'nginx_error_log',
          :value => '/path/to/error.log',
          :match => 'error_log  /path/to/error.log;',
        },
        {
          :title => 'should set worker_connections',
          :attr  => 'worker_connections',
          :value => '100',
          :match => '  worker_connections 100;',
        },
        {
          :title => 'should set access_log',
          :attr  => 'http_access_log',
          :value => '/path/to/access.log',
          :match => '  access_log  /path/to/access.log;',
        },
        {
          :title => 'should set server_tokens',
          :attr  => 'server_tokens',
          :value => 'on',
          :match => '  server_tokens on;',
        },
        {
          :title => 'should set proxy_cache_path',
          :attr  => 'proxy_cache_path',
          :value => '/path/to/proxy.cache',
          :match => '  proxy_cache_path    /path/to/proxy.cache levels=1 keys_zone=d2:100m max_size=500m inactive=20m;',
        },
        {
          :title    => 'should not set proxy_cache_path',
          :attr     => 'proxy_cache_path',
          :value    => false,
          :notmatch => /  proxy_cache_path    \/path\/to\/proxy\.cache levels=1 keys_zone=d2:100m max_size=500m inactive=20m;/,
        },
        {
          :title => 'should contain ordered appended directives',
          :attr  => 'http_cfg_append',
          :value => { 'test1' => 'test value 1', 'test2' => 'test value 2', 'allow' => 'test value 3' },
          :match => [
            '  allow test value 3;',
            '  test1 test value 1;',
            '  test2 test value 2;',
          ],
        },
      ].each do |param|
        context "when #{param[:attr]} is #{param[:value]}" do
          let :params do { param[:attr].to_sym => param[:value] } end

          it { should contain_file("/etc/nginx/nginx.conf").with_mode('0644') }
          it param[:title] do
            verify_contents(subject, "/etc/nginx/nginx.conf", Array(param[:match]))
            Array(param[:notmatch]).each do |item|
              should contain_file("/etc/nginx/nginx.conf").without_content(item)
            end
          end
        end
      end
    end

    describe "proxy.conf template content" do
      [
        {
          :title => 'should set client_max_body_size',
          :attr  => 'client_max_body_size',
          :value => '5m',
          :match => 'client_max_body_size      5m;',
        },
        {
          :title => 'should set proxy_buffers',
          :attr  => 'proxy_buffers',
          :value => '50 5k',
          :match => 'proxy_buffers           50 5k;',
        },
        {
          :title => 'should set proxy_buffer_size',
          :attr  => 'proxy_buffer_size',
          :value => '2k',
          :match => 'proxy_buffer_size       2k;',
        },
        {
          :title => 'should set proxy_http_version',
          :attr  => 'proxy_http_version',
          :value => '1.1',
          :match => 'proxy_http_version      1.1;',
        },
        {
          :title => 'should contain ordered appended directives',
          :attr  => 'proxy_set_header',
          :value => ['header1','header2'],
          :match => [
            'proxy_set_header        header1;',
            'proxy_set_header        header2;',
          ],
        },
      ].each do |param|
        context "when #{param[:attr]} is #{param[:value]}" do
          let :params do { param[:attr].to_sym => param[:value] } end

          it { should contain_file("/etc/nginx/conf.d/proxy.conf").with_mode('0644') }
          it param[:title] do
            verify_contents(subject, "/etc/nginx/conf.d/proxy.conf", Array(param[:match]))
            Array(param[:notmatch]).each do |item|
              should contain_file("/etc/nginx/conf.d/proxy.conf").without_content(item)
            end
          end
        end
      end
    end

    context "when confd_purge true" do
      let(:params) {{:confd_purge => true}}
      it { should contain_file('/etc/nginx/conf.d').with(
        :purge => true,
        :recurse => true
      )}
    end

    context "when confd_purge false" do
      let(:params) {{:confd_purge => false}}
      it { should contain_file('/etc/nginx/conf.d').without([
        'ignore',
        'purge',
        'recurse'
      ])}
    end

    context "when vhost_purge true" do
      let(:params) {{:vhost_purge => true}}
      it { should contain_file('/etc/nginx/sites-available').with(
        :purge => true,
        :recurse => true
      )}
      it { should contain_file('/etc/nginx/sites-enabled').with(
        :purge => true,
        :recurse => true
      )}
    end

    context "when vhost_purge false" do
      let(:params) {{:vhost_purge => false}}
      it { should contain_file('/etc/nginx/sites-available').without([
        'ignore',
        'purge',
        'recurse'
      ])}
      it { should contain_file('/etc/nginx/sites-enabled').without([
        'ignore',
        'purge',
        'recurse'
      ])}
    end
  end
end
