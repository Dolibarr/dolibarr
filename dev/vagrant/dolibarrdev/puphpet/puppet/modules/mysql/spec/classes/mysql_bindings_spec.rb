require 'spec_helper'

describe 'mysql::bindings' do
  let(:params) {{
    'java_enable'   => true,
    'perl_enable'   => true,
    'php_enable'    => true,
    'python_enable' => true,
    'ruby_enable'   => true,
  }}

  shared_examples 'bindings' do |osfamily, operatingsystem, java_name, perl_name, php_name, python_name, ruby_name|
    let :facts do
      { :osfamily => osfamily, :operatingsystem => operatingsystem, :root_home => '/root'}
    end
    it { should contain_package('mysql-connector-java').with(
      :name   => java_name,
      :ensure => 'present'
    )}
    it { should contain_package('perl_mysql').with(
      :name     => perl_name,
      :ensure   => 'present'
    )}
    it { should contain_package('python-mysqldb').with(
      :name   => python_name,
      :ensure => 'present'
    )}
    it { should contain_package('ruby_mysql').with(
      :name     => ruby_name,
      :ensure   => 'present'
    )}
  end

  context 'Debian' do
    it_behaves_like 'bindings', 'Debian', 'Debian', 'libmysql-java', 'libdbd-mysql-perl', 'php5-mysql', 'python-mysqldb', 'libmysql-ruby'
    it_behaves_like 'bindings', 'Debian', 'Ubuntu', 'libmysql-java', 'libdbd-mysql-perl', 'php5-mysql', 'python-mysqldb', 'libmysql-ruby'
  end

  context 'freebsd' do
    it_behaves_like 'bindings', 'FreeBSD', 'FreeBSD', 'databases/mysql-connector-java', 'p5-DBD-mysql', 'databases/php5-mysql', 'databases/py-MySQLdb', 'databases/ruby-mysql'
  end

  context 'redhat' do
    it_behaves_like 'bindings', 'RedHat', 'RedHat', 'mysql-connector-java', 'perl-DBD-MySQL', 'php-mysql', 'MySQL-python', 'ruby-mysql'
    it_behaves_like 'bindings', 'RedHat', 'OpenSuSE', 'mysql-connector-java', 'perl-DBD-MySQL', 'php-mysql', 'MySQL-python', 'ruby-mysql'
  end

  describe 'on any other os' do
    let :facts do
      {:osfamily => 'foo', :root_home => '/root'}
    end

    it 'should fail' do
      expect { subject }.to raise_error(/Unsupported osfamily: foo/)
    end
  end

end
