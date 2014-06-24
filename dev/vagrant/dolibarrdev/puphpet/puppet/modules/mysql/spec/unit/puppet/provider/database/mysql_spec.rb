require 'spec_helper'

provider_class = Puppet::Type.type(:database).provider(:mysql)

describe provider_class do
  subject { provider_class }

  let(:root_home) { '/root' }
  let(:defaults_file) { '--defaults-extra-file=/root/.my.cnf' }

  let(:raw_databases) do
    <<-SQL_OUTPUT
information_schema
mydb
mysql
performance_schema
test
    SQL_OUTPUT
  end

  let(:parsed_databases) { %w(information_schema mydb mysql performance_schema test) }

  before :each do
    @resource = Puppet::Type::Database.new(
      { :charset => 'utf8', :name => 'new_database' }
    )
    @provider = provider_class.new(@resource)
    Facter.stubs(:value).with(:root_home).returns(root_home)
    Puppet::Util.stubs(:which).with('mysql').returns('/usr/bin/mysql')
    subject.stubs(:which).with('mysql').returns('/usr/bin/mysql')
    subject.stubs(:defaults_file).returns('--defaults-extra-file=/root/.my.cnf')
  end

  describe 'self.instances' do
    it 'returns an array of databases' do
      subject.stubs(:mysql).with([defaults_file, '-NBe', 'show databases']).returns(raw_databases)

      databases = subject.instances.collect {|x| x.name }
      parsed_databases.should match_array(databases)
    end
  end

  describe 'create' do
    it 'makes a user' do
      subject.expects(:mysql).with([defaults_file, '-NBe', "create database `#{@resource[:name]}` character set #{@resource[:charset]}"])
      @provider.create
    end
  end

  describe 'destroy' do
    it 'removes a user if present' do
      subject.expects(:mysqladmin).with([defaults_file, '-f', 'drop', "#{@resource[:name]}"])
      @provider.destroy
    end
  end

  describe 'charset' do
    it 'returns a charset' do
      subject.expects(:mysql).with([defaults_file, '-NBe', "show create database `#{@resource[:name]}`"]).returns('mydbCREATE DATABASE `mydb` /*!40100 DEFAULT CHARACTER SET utf8 */')
      @provider.charset.should == 'utf8'
    end
  end

  describe 'charset=' do
    it 'changes the charset' do
      subject.expects(:mysql).with([defaults_file, '-NBe', "alter database `#{@resource[:name]}` CHARACTER SET blah"]).returns('0')

      @provider.charset=('blah')
    end
  end

  describe 'exists?' do
    it 'checks if user exists' do
      subject.expects(:mysql).with([defaults_file, '-NBe', 'show databases']).returns('information_schema\nmydb\nmysql\nperformance_schema\ntest')
      @provider.exists?
    end
  end

  describe 'self.defaults_file' do
    it 'sets --defaults-extra-file' do
      File.stubs(:file?).with('#{root_home}/.my.cnf').returns(true)
      @provider.defaults_file.should == '--defaults-extra-file=/root/.my.cnf'
    end
  end

end
