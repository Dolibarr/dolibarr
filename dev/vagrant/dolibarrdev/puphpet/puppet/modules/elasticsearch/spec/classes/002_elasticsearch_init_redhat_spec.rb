require 'spec_helper'

describe 'elasticsearch', :type => 'class' do

  [ 'RedHat', 'CentOS', 'Fedora', 'Scientific', 'Amazon', 'OracleLinux' ].each do |distro|

    context "on #{distro} OS" do

      let :facts do {
        :operatingsystem => distro
      } end

      context 'Main class' do

        # init.pp
        it { should contain_class('elasticsearch::package') }
        it { should contain_class('elasticsearch::config') }
        it { should contain_class('elasticsearch::service') }

      end

      context 'package installation' do
        
        context 'via repository' do

          context 'with default settings' do
            
           it { should contain_package('elasticsearch').with(:ensure => 'present') }

          end

          context 'with specified version' do

            let :params do {
              :version => '1.0'
            } end

            it { should contain_package('elasticsearch').with(:ensure => '1.0') }
          end

          context 'with auto upgrade enabled' do

            let :params do {
              :autoupgrade => true
            } end

            it { should contain_package('elasticsearch').with(:ensure => 'latest') }
          end

        end

        context 'when setting package version and package_url' do

          let :params do {
            :version     => '0.90.10',
            :package_url => 'puppet:///path/to/some/elasticsearch-0.90.10.rpm'
          } end

          it { expect { should raise_error(Puppet::Error) } }

        end

        context 'via package_url setting' do

          context 'using puppet:/// schema' do

            let :params do {
              :package_url => 'puppet:///path/to/package.rpm'
            } end

            it { should contain_file('/var/lib/elasticsearch/package.rpm').with(:source => 'puppet:///path/to/package.rpm', :backup => false) }
            it { should contain_package('elasticsearch').with(:ensure => 'present', :source => '/var/lib/elasticsearch/package.rpm', :provider => 'rpm') }
          end

          context 'using http:// schema' do

            let :params do {
              :package_url => 'http://www.domain.com/path/to/package.rpm'
            } end

            it { should contain_exec('create_package_dir_elasticsearch').with(:command => 'mkdir -p /var/lib/elasticsearch') }
            it { should contain_file('/var/lib/elasticsearch').with(:purge => false, :force => false, :require => "Exec[create_package_dir_elasticsearch]") }
            it { should contain_exec('download_package_elasticsearch').with(:command => 'wget -O /var/lib/elasticsearch/package.rpm http://www.domain.com/path/to/package.rpm 2> /dev/null', :require => 'File[/var/lib/elasticsearch]') }
            it { should contain_package('elasticsearch').with(:ensure => 'present', :source => '/var/lib/elasticsearch/package.rpm', :provider => 'rpm') }
          end

          context 'using https:// schema' do

            let :params do {
              :package_url => 'https://www.domain.com/path/to/package.rpm'
            } end

            it { should contain_exec('create_package_dir_elasticsearch').with(:command => 'mkdir -p /var/lib/elasticsearch') }
            it { should contain_file('/var/lib/elasticsearch').with(:purge => false, :force => false, :require => 'Exec[create_package_dir_elasticsearch]') }
            it { should contain_exec('download_package_elasticsearch').with(:command => 'wget -O /var/lib/elasticsearch/package.rpm https://www.domain.com/path/to/package.rpm 2> /dev/null', :require => 'File[/var/lib/elasticsearch]') }
            it { should contain_package('elasticsearch').with(:ensure => 'present', :source => '/var/lib/elasticsearch/package.rpm', :provider => 'rpm') }
          end

          context 'using ftp:// schema' do

            let :params do {
              :package_url => 'ftp://www.domain.com/path/to/package.rpm'
            } end

            it { should contain_exec('create_package_dir_elasticsearch').with(:command => 'mkdir -p /var/lib/elasticsearch') }
            it { should contain_file('/var/lib/elasticsearch').with(:purge => false, :force => false, :require => 'Exec[create_package_dir_elasticsearch]') }
            it { should contain_exec('download_package_elasticsearch').with(:command => 'wget -O /var/lib/elasticsearch/package.rpm ftp://www.domain.com/path/to/package.rpm 2> /dev/null', :require => 'File[/var/lib/elasticsearch]') }
            it { should contain_package('elasticsearch').with(:ensure => 'present', :source => '/var/lib/elasticsearch/package.rpm', :provider => 'rpm') }
          end

          context 'using file:// schema' do

            let :params do {
              :package_url => 'file:/path/to/package.rpm'
            } end

            it { should contain_exec('create_package_dir_elasticsearch').with(:command => 'mkdir -p /var/lib/elasticsearch') }
            it { should contain_file('/var/lib/elasticsearch').with(:purge => false, :force => false, :require => 'Exec[create_package_dir_elasticsearch]') }
            it { should contain_file('/var/lib/elasticsearch/package.rpm').with(:source => '/path/to/package.rpm', :backup => false) }
            it { should contain_package('elasticsearch').with(:ensure => 'present', :source => '/var/lib/elasticsearch/package.rpm', :provider => 'rpm') }
          end

        end

      end # package

      context 'service setup' do

        context 'with provider \'init\'' do

          context 'and default settings' do

            it { should contain_service('elasticsearch').with(:ensure => 'running') }

          end

          context 'and set defaults via hash param' do

            let :params do {
              :init_defaults => { 'SERVICE_USER' => 'root', 'SERVICE_GROUP' => 'root' }
            } end

            it { should contain_file('/etc/sysconfig/elasticsearch').with(:content => "### MANAGED BY PUPPET ###\n\nSERVICE_GROUP=root\nSERVICE_USER=root\n", :notify => 'Service[elasticsearch]') }

          end

          context 'and set defaults via file param' do

            let :params do {
              :init_defaults_file => 'puppet:///path/to/elasticsearch.defaults'
            } end

            it { should contain_file('/etc/sysconfig/elasticsearch').with(:source => 'puppet:///path/to/elasticsearch.defaults', :notify => 'Service[elasticsearch]') }

          end

          context 'no service restart when defaults change' do

            let :params do {
              :init_defaults     => { 'SERVICE_USER' => 'root', 'SERVICE_GROUP' => 'root' },
              :restart_on_change => false
            } end

            it { should contain_file('/etc/sysconfig/elasticsearch').with(:content => "### MANAGED BY PUPPET ###\n\nSERVICE_GROUP=root\nSERVICE_USER=root\n").without_notify }

          end

          context 'and set init file via template' do

            let :params do {
              :init_template => "elasticsearch/etc/init.d/elasticsearch.RedHat.erb"
            } end

            it { should contain_file('/etc/init.d/elasticsearch').with(:notify => 'Service[elasticsearch]') }

          end

          context 'No service restart when restart_on_change is false' do

            let :params do {
              :init_template     => "elasticsearch/etc/init.d/elasticsearch.RedHat.erb",
              :restart_on_change => false
            } end

            it { should contain_file('/etc/init.d/elasticsearch').without_notify }

          end

          context 'when its unmanaged do nothing with it' do

            let :params do {
              :status => 'unmanaged'
            } end

            it { should contain_service('elasticsearch').with(:ensure => nil, :enable => false) }

          end

        end

      end # Services

      context 'when setting the module to absent' do

         let :params do {
           :ensure => 'absent'
         } end

         it { should contain_file('/etc/elasticsearch').with(:ensure => 'absent', :force => true, :recurse => true) }
         it { should contain_package('elasticsearch').with(:ensure => 'purged') }
         it { should contain_service('elasticsearch').with(:ensure => 'stopped', :enable => false) }

      end

    end

  end

end
