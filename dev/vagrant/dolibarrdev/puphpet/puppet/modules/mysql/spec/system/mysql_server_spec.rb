require 'spec_helper_system'

describe 'mysql class' do
  case node.facts['osfamily']
  when 'RedHat'
    package_name = 'mysql-server'
    service_name = 'mysqld'
    mycnf        = '/etc/my.cnf'
  when 'Suse'
    package_name = 'mysql-community-server'
    service_name = 'mysql'
    mycnf        = '/etc/my.cnf'
  when 'Debian'
    package_name = 'mysql-server'
    service_name = 'mysql'
    mycnf        = '/etc/mysql/my.cnf'
  end

  describe 'running puppet code' do
    # Using puppet_apply as a helper
    it 'should work with no errors' do
      pp = <<-EOS
        class { 'mysql::server': }
      EOS

      # Run it twice and test for idempotency
      puppet_apply(pp) do |r|
        r.exit_code.should_not == 1
        r.refresh
        r.exit_code.should be_zero
      end
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
      puppet_apply(pp) do |r|
        r.exit_code.should_not == 1
      end
    end

    describe file(mycnf) do
      it { should contain 'key_buffer = 16M' }
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
      puppet_apply(pp) do |r|
        r.exit_code.should_not == 1
      end
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
      puppet_apply(pp) do |r|
        r.exit_code.should_not == 1
      end
    end

    describe file(mycnf) do
      it { should contain 'replicate-do-db = base1' }
      it { should contain 'replicate-do-db = base2' }
      it { should contain 'replicate-do-db = base3' }
    end
  end

end
