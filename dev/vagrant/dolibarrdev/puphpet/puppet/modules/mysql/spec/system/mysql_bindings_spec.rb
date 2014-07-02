require 'spec_helper_system'

describe 'mysql::bindings class' do
  let(:os) {
    node.facts['osfamily']
  }

  case node.facts['osfamily']
  when 'RedHat'
    java_package   = 'mysql-connector-java'
    perl_package   = 'perl-DBD-MySQL'
    python_package = 'MySQL-python'
    ruby_package   = 'ruby-mysql'
  when 'Suse'
    java_package   = 'mysql-connector-java'
    perl_package   = 'perl-DBD-MySQL'
    python_package = 'python-mysql'
    case node.facts['operatingsystem']
    when /OpenSuSE/
      ruby_package = 'rubygem-mysql'
    when /(SLES|SLED)/
      ruby_package = 'ruby-mysql'
    end
  when 'Debian'
    java_package = 'libmysql-java'
    perl_package   = 'libdbd-mysql-perl'
    python_package = 'python-mysqldb'
    ruby_package   = 'libmysql-ruby'
  when 'FreeBSD'
    java_package = 'databases/mysql-connector-java'
    perl_package   = 'p5-DBD-mysql'
    python_package = 'databases/py-MySQLdb'
    ruby_package   = 'ruby-mysql'
  else
    case node.facts['operatingsystem']
    when 'Amazon'
      java_package = 'mysql-connector-java'
    perl_package   = 'perl-DBD-MySQL'
    python_package = 'MySQL-python'
    ruby_package   = 'ruby-mysql'
    end
  end

  describe 'running puppet code' do
    # Using puppet_apply as a helper
    it 'should work with no errors' do
      pp = <<-EOS
        class { 'mysql::bindings': }
      EOS

      # Run it twice and test for idempotency
      puppet_apply(pp) do |r|
        r.exit_code.should_not == 1
        r.refresh
        r.exit_code.should be_zero
      end
    end
  end

  describe 'enabling bindings' do
    it 'should work with no errors' do
      puppet_apply(%{
      class { 'mysql::bindings':
        java_enable   => true,
        perl_enable   => true,
        python_enable => true,
        ruby_enable   => true,
      }
      })
    end

    describe package(java_package) do
      it { should be_installed }
    end

    describe package(perl_package) do
      it { should be_installed }
    end

    describe package(python_package) do
      it { should be_installed }
    end

    describe package(ruby_package) do
      it { should be_installed }
    end

  end

end
