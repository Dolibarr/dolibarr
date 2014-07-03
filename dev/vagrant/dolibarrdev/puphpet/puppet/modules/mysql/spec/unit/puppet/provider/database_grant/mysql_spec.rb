require 'puppet'
require 'mocha/api'
require 'spec_helper'
RSpec.configure do |config|
  config.mock_with :mocha
end
provider_class = Puppet::Type.type(:database_grant).provider(:mysql)
describe provider_class do
  let(:root_home) { '/root' }

  before :each do
    @resource = Puppet::Type::Database_grant.new(
      { :privileges => 'all', :provider => 'mysql', :name => 'user@host'}
    )
    @provider = provider_class.new(@resource)
    Facter.stubs(:value).with(:root_home).returns(root_home)
    File.stubs(:file?).with("#{root_home}/.my.cnf").returns(true)
  end

  it 'should query privileges from the database' do
    provider_class.expects(:mysql) .with(["--defaults-extra-file=#{root_home}/.my.cnf", 'mysql', '-Be', 'describe user']).returns <<-EOT
Field	Type	Null	Key	Default	Extra
Host	char(60)	NO	PRI		
User	char(16)	NO	PRI		
Password	char(41)	NO			
Select_priv	enum('N','Y')	NO		N	
Insert_priv	enum('N','Y')	NO		N	
Update_priv	enum('N','Y')	NO		N
EOT
    provider_class.expects(:mysql).with(["--defaults-extra-file=#{root_home}/.my.cnf", 'mysql', '-Be', 'describe db']).returns <<-EOT
Field	Type	Null	Key	Default	Extra
Host	char(60)	NO	PRI		
Db	char(64)	NO	PRI		
User	char(16)	NO	PRI		
Select_priv	enum('N','Y')	NO		N	
Insert_priv	enum('N','Y')	NO		N	
Update_priv	enum('N','Y')	NO		N
EOT
    provider_class.user_privs.should == %w(Select_priv Insert_priv Update_priv)
    provider_class.db_privs.should == %w(Select_priv Insert_priv Update_priv)
  end

  it 'should query set privileges' do
    provider_class.expects(:mysql).with(["--defaults-extra-file=#{root_home}/.my.cnf", 'mysql', '-Be', "select * from mysql.user where user='user' and host='host'"]).returns <<-EOT
Host	User	Password	Select_priv	Insert_priv	Update_priv
host	user		Y	N	Y
EOT
    @provider.privileges.should == %w(Select_priv Update_priv)
  end

  it 'should recognize when all privileges are set' do
    provider_class.expects(:mysql).with(["--defaults-extra-file=#{root_home}/.my.cnf", 'mysql', '-Be', "select * from mysql.user where user='user' and host='host'"]).returns <<-EOT
Host	User	Password	Select_priv	Insert_priv	Update_priv
host	user		Y	Y	Y
EOT
    @provider.all_privs_set?.should == true
  end

  it 'should recognize when all privileges are not set' do
    provider_class.expects(:mysql).with(["--defaults-extra-file=#{root_home}/.my.cnf", 'mysql', '-Be', "select * from mysql.user where user='user' and host='host'"]).returns <<-EOT
Host	User	Password	Select_priv	Insert_priv	Update_priv
host	user		Y	N	Y
EOT
    @provider.all_privs_set?.should == false
  end

  it 'should be able to set all privileges' do
    provider_class.expects(:mysql).with(["--defaults-extra-file=#{root_home}/.my.cnf", 'mysql', '-NBe', "SELECT '1' FROM user WHERE user='user' AND host='host'"]).returns "1\n"
    provider_class.expects(:mysql).with(["--defaults-extra-file=#{root_home}/.my.cnf", 'mysql', '-Be', "update user set Select_priv = 'Y', Insert_priv = 'Y', Update_priv = 'Y' where user='user' and host='host'"])
    provider_class.expects(:mysqladmin).with(%W(--defaults-extra-file=#{root_home}/.my.cnf flush-privileges))
    @provider.privileges=(%w(all))
  end

  it 'should be able to set partial privileges' do
    provider_class.expects(:mysql).with(["--defaults-extra-file=#{root_home}/.my.cnf", 'mysql', '-NBe', "SELECT '1' FROM user WHERE user='user' AND host='host'"]).returns "1\n"
    provider_class.expects(:mysql).with(["--defaults-extra-file=#{root_home}/.my.cnf", 'mysql', '-Be', "update user set Select_priv = 'Y', Insert_priv = 'N', Update_priv = 'Y' where user='user' and host='host'"])
    provider_class.expects(:mysqladmin).with(%W(--defaults-extra-file=#{root_home}/.my.cnf flush-privileges))
    @provider.privileges=(%w(Select_priv Update_priv))
  end

  it 'should be case insensitive' do
    provider_class.expects(:mysql).with(["--defaults-extra-file=#{root_home}/.my.cnf", 'mysql', '-NBe', "SELECT '1' FROM user WHERE user='user' AND host='host'"]).returns "1\n"
    provider_class.expects(:mysql).with(["--defaults-extra-file=#{root_home}/.my.cnf", 'mysql', '-Be', "update user set Select_priv = 'Y', Insert_priv = 'Y', Update_priv = 'Y' where user='user' and host='host'"])
    provider_class.expects(:mysqladmin).with(["--defaults-extra-file=#{root_home}/.my.cnf", 'flush-privileges'])
    @provider.privileges=(%w(SELECT_PRIV insert_priv UpDaTe_pRiV))
  end

  it 'should not pass --defaults-extra-file if $root_home/.my.cnf is absent' do
    File.stubs(:file?).with("#{root_home}/.my.cnf").returns(false)
    provider_class.expects(:mysql).with(['mysql', '-NBe', "SELECT '1' FROM user WHERE user='user' AND host='host'"]).returns "1\n"
    provider_class.expects(:mysql).with(['mysql', '-Be', "update user set Select_priv = 'Y', Insert_priv = 'N', Update_priv = 'Y' where user='user' and host='host'"])
    provider_class.expects(:mysqladmin).with(%w(flush-privileges))
    @provider.privileges=(%w(Select_priv Update_priv))
  end
end
