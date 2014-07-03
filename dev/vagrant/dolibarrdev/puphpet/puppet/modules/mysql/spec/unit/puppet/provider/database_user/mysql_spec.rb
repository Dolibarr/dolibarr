require 'spec_helper'

provider_class = Puppet::Type.type(:database_user).provider(:mysql)

describe provider_class do
  subject { provider_class }

  let(:root_home) { '/root' }
  let(:defaults_file) { '--defaults-extra-file=/root/.my.cnf' }
  let(:newhash) { '*6C8989366EAF75BB670AD8EA7A7FC1176A95CEF5' }

  let(:raw_users) do
    <<-SQL_OUTPUT
root@127.0.0.1
root@::1
@localhost
debian-sys-maint@localhost
root@localhost
usvn_user@localhost
@vagrant-ubuntu-raring-64
    SQL_OUTPUT
  end

  let(:parsed_users) { %w(root@127.0.0.1 root@::1 debian-sys-maint@localhost root@localhost usvn_user@localhost) }

  before :each do
    # password hash = mypass
    @resource = Puppet::Type::Database_user.new(
      { :password_hash => '*6C8989366EAF75BB670AD8EA7A7FC1176A95CEF4',
        :name => 'joe@localhost',
        :max_user_connections => '10'
      }
    )
    @provider = provider_class.new(@resource)
    Facter.stubs(:value).with(:root_home).returns(root_home)
    Puppet::Util.stubs(:which).with('mysql').returns('/usr/bin/mysql')
    subject.stubs(:which).with('mysql').returns('/usr/bin/mysql')
    subject.stubs(:defaults_file).returns('--defaults-extra-file=/root/.my.cnf')
  end

  describe 'self.instances' do
    it 'returns an array of users' do
      subject.stubs(:mysql).with([defaults_file, 'mysql', "-BNeselect concat(User, '@',Host) as User from mysql.user"]).returns(raw_users)

      usernames = subject.instances.collect {|x| x.name }
      parsed_users.should match_array(usernames)
    end
  end

  describe 'create' do
    it 'makes a user' do
      subject.expects(:mysql).with([defaults_file, 'mysql', '-e', "grant usage on *.* to 'joe'@'localhost' identified by PASSWORD
      '*6C8989366EAF75BB670AD8EA7A7FC1176A95CEF4' with max_user_connections 10"])
      @provider.expects(:exists?).returns(true)
      @provider.create.should be_true
    end
  end

  describe 'destroy' do
    it 'removes a user if present' do
      subject.expects(:mysql).with([defaults_file, 'mysql', '-e', "drop user 'joe'@'localhost'"])
      @provider.expects(:exists?).returns(false)
      @provider.destroy.should be_true
    end
  end

  describe 'password_hash' do
    it 'returns a hash' do
      subject.expects(:mysql).with([defaults_file, 'mysql', '-NBe', "select password from mysql.user where CONCAT(user, '@', host) = 'joe@localhost'"]).returns('*6C8989366EAF75BB670AD8EA7A7FC1176A95CEF4')
      @provider.password_hash.should == '*6C8989366EAF75BB670AD8EA7A7FC1176A95CEF4'
    end
  end

  describe 'password_hash=' do
    it 'changes the hash' do
      subject.expects(:mysql).with([defaults_file, 'mysql', '-e', "SET PASSWORD FOR 'joe'@'localhost' = '*6C8989366EAF75BB670AD8EA7A7FC1176A95CEF5'"]).returns('0')

      @provider.expects(:password_hash).returns('*6C8989366EAF75BB670AD8EA7A7FC1176A95CEF5')
      @provider.password_hash=('*6C8989366EAF75BB670AD8EA7A7FC1176A95CEF5')
    end
  end

  describe 'max_user_connections' do
    it 'returns max user connections' do
      subject.expects(:mysql).with([defaults_file, 'mysql', '-NBe', "select max_user_connections from mysql.user where CONCAT(user, '@', host) = 'joe@localhost'"]).returns('10')
      @provider.max_user_connections.should == '10'
    end
  end

  describe 'max_user_connections=' do
    it 'changes max user connections' do
      subject.expects(:mysql).with([defaults_file, 'mysql', '-e', "grant usage on *.* to 'joe'@'localhost' with max_user_connections 42"]).returns('0')
      @provider.expects(:max_user_connections).returns('42')
      @provider.max_user_connections=('42')
    end
  end

  describe 'exists?' do
    it 'checks if user exists' do
      subject.expects(:mysql).with([defaults_file, 'mysql', '-NBe', "select '1' from mysql.user where CONCAT(user, '@', host) = 'joe@localhost'"]).returns('1')
      @provider.exists?.should be_true
    end
  end

  describe 'flush' do
    it 'removes cached privileges' do
      subject.expects(:mysqladmin).with([defaults_file, 'flush-privileges'])
      @provider.flush
    end
  end

  describe 'self.defaults_file' do
    it 'sets --defaults-extra-file' do
      File.stubs(:file?).with('#{root_home}/.my.cnf').returns(true)
      @provider.defaults_file.should == '--defaults-extra-file=/root/.my.cnf'
    end
  end

end
