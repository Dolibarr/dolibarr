require 'spec_helper_acceptance'

describe 'mysql class', :unless => UNSUPPORTED_PLATFORMS.include?(fact('operatingsystem')) do
  case fact('osfamily')
  when 'RedHat'
    package_name     = 'mysql-server'
    service_name     = 'mysqld'
    service_provider = 'undef'
    mycnf            = '/etc/my.cnf'
  when 'Suse'
    case fact('operatingsystem')
    when 'OpenSuSE'
      package_name     = 'mysql-community-server'
      service_name     = 'mysql'
      service_provider = 'undef'
      mycnf            = '/etc/my.cnf'
    when 'SLES'
      package_name     = 'mysql'
      service_name     = 'mysql'
      service_provider = 'undef'
      mycnf            = '/etc/my.cnf'
    end
  when 'Debian'
    package_name     = 'mysql-server'
    service_name     = 'mysql'
    service_provider = 'undef'
    mycnf            = '/etc/mysql/my.cnf'
  when 'Ubuntu'
    package_name     = 'mysql-server'
    service_name     = 'mysql'
    service_provider = 'upstart'
    mycnf            = '/etc/mysql/my.cnf'
  end

  describe 'running puppet code' do
    # Using puppet_apply as a helper
    it 'should work with no errors' do
      pp = <<-EOS
        class { 'mysql::server': }
      EOS

      # Run it twice and test for idempotency
      apply_manifest(pp, :catch_failures => true)
      expect(apply_manifest(pp, :catch_failures => true).exit_code).to be_zero
    end

    describe package(package_name) do
      it { should be_installed }
    end

    describe service(service_name) do
      it { should be_running }
      it { should be_enabled }
    end
  end

  describe 'mycnf' do
    it 'should contain sensible values' do
      pp = <<-EOS
        class { 'mysql::server': }
      EOS
      apply_manifest(pp, :catch_failures => true)
    end

    describe file(mycnf) do
      it { should contain 'key_buffer_size = 16M' }
      it { should contain 'max_binlog_size = 100M' }
      it { should contain 'query_cache_size = 16M' }
    end
  end

  describe 'my.cnf changes' do
    it 'sets values' do
      pp = <<-EOS
        class { 'mysql::server':
          override_options => { 'mysqld' => 
            { 'key_buffer'       => '32M',
              'max_binlog_size'  => '200M',
              'query_cache_size' => '32M',
            }
          }  
        }
      EOS
      apply_manifest(pp, :catch_failures => true)
    end

    describe file(mycnf) do
      it { should contain 'key_buffer = 32M' }
      it { should contain 'max_binlog_size = 200M' }
      it { should contain 'query_cache_size = 32M' }
    end
  end

  describe 'my.cnf should contain multiple instances of the same option' do
    it 'sets multiple values' do
      pp = <<-EOS
        class { 'mysql::server':
          override_options => { 'mysqld' => 
            { 'replicate-do-db' => ['base1', 'base2', 'base3'], }
          }  
        }
      EOS
      apply_manifest(pp, :catch_failures => true)
    end

    describe file(mycnf) do
      it { should contain 'replicate-do-db = base1' }
      it { should contain 'replicate-do-db = base2' }
      it { should contain 'replicate-do-db = base3' }
    end
  end

  describe 'package_ensure => absent' do
    it 'uninstalls mysql' do
      pp = <<-EOS
        class { 'mysql::server':
          service_enabled => false,
          package_ensure  => absent,
          package_name    => #{package_name}
        }
      EOS
      apply_manifest(pp, :catch_failures => true)
    end

    describe package(package_name) do
      it { should_not be_installed }
    end
  end

  describe 'package_ensure => present' do
    it 'installs mysql' do
      pp = <<-EOS
        class { 'mysql::server':
          package_ensure => present,
          package_name   => #{package_name}
        }
      EOS
      apply_manifest(pp, :catch_failures => true)
    end

    describe package(package_name) do
      it { should be_installed }
    end
  end

  describe 'purge_conf_dir' do

    it 'purges the conf dir' do
      pp = <<-EOS
        class { 'mysql::server':
          purge_conf_dir => true
        }
      EOS
      shell('touch /etc/mysql/conf.d/test.conf')
      apply_manifest(pp, :catch_failures => true)
    end

    describe file('/etc/mysql/conf.d/test.conf') do
      it { should_not be_file }
    end
  end

  describe 'restart' do
    it 'restart => true' do
      pp = <<-EOS
        class { 'mysql::server':
          restart          => true,
          override_options => { 'mysqldump' => { 'default-character-set' => 'UTF-8' } }
        }
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.exit_code).to eq(2)
        expect(r.stdout).to match(/Scheduling refresh/)
      end
    end
    it 'restart => false' do
      pp = <<-EOS
        class { 'mysql::server':
          restart          => false,
          override_options => { 'mysqldump' => { 'default-character-set' => 'UTF-16' } }
        }
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.exit_code).to eq(2)
        expect(r.stdout).to_not match(/Scheduling refresh/)
      end
    end
  end

  describe 'root_group' do
    it 'creates a group' do
      pp = "group { 'test': ensure => present }"
      apply_manifest(pp, :catch_failures => true)
    end

    it 'sets the group of files' do
      pp = <<-EOS
        class { 'mysql::server':
          root_group => 'test',
          config_file => '/tmp/mysql_group_test',
        }
      EOS
      apply_manifest(pp, :catch_failures => true)
    end

    describe file('/tmp/mysql_group_test') do
      it { should be_grouped_into 'test' }
    end
  end

  describe 'service parameters' do
    it 'calls all parameters' do
      pp = <<-EOS
      class { 'mysql::server':
        service_enabled  => true,
        service_manage   => true,
        service_name     => #{service_name},
        service_provider => #{service_provider}
      }
      EOS
      apply_manifest(pp, :catch_failures => true)
    end
  end

  describe 'users, grants, and databases' do
    it 'are created' do
      pp = <<-EOS
      class { 'mysql::server':
        users => {
          'zerg1@localhost' => {
            ensure                   => 'present',
            max_connections_per_hour => '0',
            max_queries_per_hour     => '0',
            max_updates_per_hour     => '0',
            max_user_connections     => '0',
            password_hash            => '*F3A2A51A9B0F2BE2468926B4132313728C250DBF',
          }
        },
        grants => {
          'zerg1@localhost/zergdb.*' => {
            ensure     => 'present',
            options    => ['GRANT'],
            privileges => ['SELECT', 'INSERT', 'UPDATE', 'DELETE'],
            table      => 'zergdb.*',
            user       => 'zerg1@localhost',
          }
        },
        databases => {
          'zergdb' => {
            ensure  => 'present',
            charset => 'utf8',
          }
        },
      }
    EOS
      apply_manifest(pp, :catch_failures => true)
    end

    it 'has a user' do
      shell("mysql -NBe \"select '1' from mysql.user where CONCAT(user, '@', host) = 'zerg1@localhost'\"") do |r|
        expect(r.stdout).to match(/^1$/)
        expect(r.stderr).to be_empty
      end
    end
    it 'has a grant' do
      shell("mysql -NBe \"SHOW GRANTS FOR zerg1@localhost\"") do |r|
        expect(r.stdout).to match(/GRANT SELECT, INSERT, UPDATE, DELETE ON `zergdb`.* TO 'zerg1'@'localhost' WITH GRANT OPTION/)
        expect(r.stderr).to be_empty
      end
    end
    it 'has a database' do
      shell("mysql -NBe \"SHOW DATABASES LIKE 'zergdb'\"") do |r|
        expect(r.stdout).to match(/zergdb/)
        expect(r.stderr).to be_empty
      end
    end
  end

end
