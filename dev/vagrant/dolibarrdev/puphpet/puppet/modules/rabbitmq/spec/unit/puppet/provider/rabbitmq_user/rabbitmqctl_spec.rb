require 'puppet'
require 'mocha'
RSpec.configure do |config|
  config.mock_with :mocha
end
provider_class = Puppet::Type.type(:rabbitmq_user).provider(:rabbitmqctl)
describe provider_class do
  before :each do
    @resource = Puppet::Type::Rabbitmq_user.new(
      {:name => 'foo', :password => 'bar'}
    )
    @provider = provider_class.new(@resource)
  end
  it 'should match user names' do
    @provider.expects(:rabbitmqctl).with('list_users').returns <<-EOT
Listing users ...
foo
...done.
EOT
    @provider.exists?.should == 'foo'
  end
  it 'should match user names with 2.4.1 syntax' do
    @provider.expects(:rabbitmqctl).with('list_users').returns <<-EOT
Listing users ...
foo bar
...done.
EOT
    @provider.exists?.should == 'foo bar'
  end
  it 'should not match if no users on system' do
    @provider.expects(:rabbitmqctl).with('list_users').returns <<-EOT
Listing users ...
...done.
EOT
    @provider.exists?.should be_nil
  end
  it 'should not match if no matching users on system' do
    @provider.expects(:rabbitmqctl).with('list_users').returns <<-EOT
Listing users ...
fooey
...done.
EOT
    @provider.exists?.should be_nil
  end
  it 'should match user names from list' do
    @provider.expects(:rabbitmqctl).with('list_users').returns <<-EOT
Listing users ...
one
two three
foo
bar
...done.
EOT
    @provider.exists?.should == 'foo'
  end
  it 'should create user and set password' do
    @resource[:password] = 'bar'
    @provider.expects(:rabbitmqctl).with('add_user', 'foo', 'bar')
    @provider.create
  end
  it 'should create user, set password and set to admin' do
    @resource[:password] = 'bar'
    @resource[:admin] = 'true'
    @provider.expects(:rabbitmqctl).with('add_user', 'foo', 'bar')
    @provider.expects(:rabbitmqctl).with('list_users').returns <<-EOT
Listing users ...
foo   []
icinga  [monitoring]
kitchen []
kitchen2        [abc, def, ghi]
...done.
EOT
    @provider.expects(:rabbitmqctl).with('set_user_tags', 'foo', ['administrator'])
    @provider.create
  end
  it 'should call rabbitmqctl to delete' do
    @provider.expects(:rabbitmqctl).with('delete_user', 'foo')
    @provider.destroy
  end
  it 'should be able to retrieve admin value' do
    @provider.expects(:rabbitmqctl).with('list_users').returns <<-EOT
Listing users ...
foo [administrator]
...done.
EOT
    @provider.admin.should == :true
    @provider.expects(:rabbitmqctl).with('list_users').returns <<-EOT
Listing users ...
one [administrator]
foo []
...done.
EOT
    @provider.admin.should == :false
  end
  it 'should fail if admin value is invalid' do
    @provider.expects(:rabbitmqctl).with('list_users').returns <<-EOT
Listing users ...
foo fail
...done.
EOT
    expect { @provider.admin }.to raise_error(Puppet::Error, /Could not match line/)
  end
  it 'should be able to set admin value' do
    @provider.expects(:rabbitmqctl).with('list_users').returns <<-EOT
Listing users ...
foo   []
icinga  [monitoring]
kitchen []
kitchen2        [abc, def, ghi]
...done.
EOT
    @provider.expects(:rabbitmqctl).with('set_user_tags', 'foo', ['administrator'])
    @provider.admin=:true
  end
  it 'should not interfere with existing tags on the user when setting admin value' do
    @provider.expects(:rabbitmqctl).with('list_users').returns <<-EOT
Listing users ...
foo   [bar, baz]
icinga  [monitoring]
kitchen []
kitchen2        [abc, def, ghi]
...done.
EOT
    @provider.expects(:rabbitmqctl).with('set_user_tags', 'foo', ['bar','baz', 'administrator'].sort) 
    @provider.admin=:true
  end
  it 'should be able to unset admin value' do
    @provider.expects(:rabbitmqctl).with('list_users').returns <<-EOT
Listing users ...
foo     [administrator]
guest   [administrator]
icinga  []
...done.
EOT
    @provider.expects(:rabbitmqctl).with('set_user_tags', 'foo', [])
    @provider.admin=:false
  end
  it 'should not interfere with existing tags on the user when unsetting admin value' do
    @provider.expects(:rabbitmqctl).with('list_users').returns <<-EOT
Listing users ...
foo   [administrator, bar, baz]
icinga  [monitoring]
kitchen []
kitchen2        [abc, def, ghi]
...done.
EOT
    @provider.expects(:rabbitmqctl).with('set_user_tags', 'foo', ['bar','baz'].sort) 
    @provider.admin=:false
  end

  it 'should clear all tags on existing user' do
    @provider.expects(:rabbitmqctl).with('list_users').returns <<-EOT
Listing users ...
one [administrator]
foo [tag1,tag2]
icinga  [monitoring]
kitchen []
kitchen2        [abc, def, ghi]
...done.
EOT
    @provider.expects(:rabbitmqctl).with('set_user_tags', 'foo', [])
    @provider.tags=[]
  end

  it 'should set multiple tags' do
    @provider.expects(:rabbitmqctl).with('list_users').returns <<-EOT
Listing users ...
one [administrator]
foo []
icinga  [monitoring]
kitchen []
kitchen2        [abc, def, ghi]
...done.
EOT
    @provider.expects(:rabbitmqctl).with('set_user_tags', 'foo', ['tag1','tag2'])
    @provider.tags=['tag1','tag2']
  end

  it 'should clear tags while keep admin tag' do
    @resource[:admin]  = true
    @provider.expects(:rabbitmqctl).with('list_users').returns <<-EOT
Listing users ...
one [administrator]
foo [administrator, tag1, tag2]
icinga  [monitoring]
kitchen []
kitchen2        [abc, def, ghi]
...done.
EOT
    @provider.expects(:rabbitmqctl).with('set_user_tags', 'foo', ["administrator"])
    @provider.tags=[]
  end

  it 'should change tags while keep admin tag' do
    @resource[:admin]  = true
    @provider.expects(:rabbitmqctl).with('list_users').returns <<-EOT
Listing users ...
one [administrator]
foo [administrator, tag1, tag2]
icinga  [monitoring]
kitchen []
kitchen2        [abc, def, ghi]
...done.
EOT
    @provider.expects(:rabbitmqctl).with('set_user_tags', 'foo', ["administrator","tag1","tag3","tag7"])
    @provider.tags=['tag1','tag7','tag3']
  end

  it 'should create user with tags and without admin' do
    @resource[:tags] = [ "tag1", "tag2" ]
    @provider.expects(:rabbitmqctl).with('add_user', 'foo', 'bar')
    @provider.expects(:rabbitmqctl).with('set_user_tags', 'foo', ["tag1","tag2"])
    @provider.expects(:rabbitmqctl).with('list_users').returns <<-EOT
Listing users ...
foo []
...done.
EOT
    @provider.create
  end

  it 'should create user with tags and with admin' do
    @resource[:tags] = [ "tag1", "tag2" ]
    @resource[:admin]  = true
    @provider.expects(:rabbitmqctl).with('add_user', 'foo', 'bar')
    @provider.expects(:rabbitmqctl).with('list_users').twice.returns <<-EOT
Listing users ...
foo []
...done.
EOT
    @provider.expects(:rabbitmqctl).with('set_user_tags', 'foo', ["administrator"])
    @provider.expects(:rabbitmqctl).with('set_user_tags', 'foo', ["administrator","tag1","tag2"])
    @provider.create
  end
  

end
