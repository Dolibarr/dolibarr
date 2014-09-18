require 'spec_helper'
describe 'mysql::server' do
  let(:facts) {{:osfamily => 'RedHat', :root_home => '/root'}}

  context 'with defaults' do
    it { should contain_class('mysql::server::install') }
    it { should contain_class('mysql::server::config') }
    it { should contain_class('mysql::server::service') }
    it { should contain_class('mysql::server::root_password') }
    it { should contain_class('mysql::server::providers') }
  end

  # make sure that overriding the mysqld settings keeps the defaults for everything else
  context 'with overrides' do
    let(:params) {{ :override_options => { 'mysqld' => { 'socket' => '/var/lib/mysql/mysql.sock' } } }}
    it do
      should contain_file('/etc/my.cnf').with({
        :mode => '0644',
      }).with_content(/basedir/)
    end
  end

  describe 'with multiple instance of an option' do
    let(:params) {{ :override_options => { 'mysqld' => { 'replicate-do-db' => ['base1', 'base2', 'base3'], } }}}
    it do
      should contain_file('/etc/my.cnf').with_content(
        /^replicate-do-db = base1$/
      ).with_content(
        /^replicate-do-db = base2$/
      ).with_content(
        /^replicate-do-db = base3$/
      )
    end
  end

  describe 'an option set to true' do
    let(:params) {
      { :override_options => { 'mysqld' => { 'ssl' => true } }}
    }
    it do
      should contain_file('/etc/my.cnf').with_content(/^\s*ssl\s*(?:$|= true)/m)
    end
  end

  describe 'an option set to false' do
    let(:params) {
      { :override_options => { 'mysqld' => { 'ssl' => false } }}
    }
    it do
      should contain_file('/etc/my.cnf').with_content(/^\s*ssl = false/m)
    end
  end

  context 'with remove_default_accounts set' do
    let (:params) {{ :remove_default_accounts => true }}
    it { should contain_class('mysql::server::account_security') }
  end

  context 'mysql::server::install' do
    let(:params) {{ :package_ensure => 'present', :name => 'mysql-server' }}
    it do
      should contain_package('mysql-server').with({
      :ensure => :present,
      :name   => 'mysql-server',
    })
    end
  end

  context 'mysql::server::config' do
    it do
      should contain_file('/etc/mysql').with({
        :ensure => :directory,
        :mode   => '0755',
      })
    end

    it do
      should contain_file('/etc/mysql/conf.d').with({
        :ensure => :directory,
        :mode   => '0755',
      })
    end

    it do
      should contain_file('/etc/my.cnf').with({
        :mode => '0644',
      })
    end
  end

  context 'mysql::server::service' do
    context 'with defaults' do
      it { should contain_service('mysqld') }
    end

    context 'service_enabled set to false' do
      let(:params) {{ :service_enabled => false }}

      it do
        should contain_service('mysqld').with({
          :ensure => :stopped
        })
      end
    end
  end

  context 'mysql::server::root_password' do
    describe 'when defaults' do
      it { should_not contain_mysql_user('root@localhost') }
      it { should_not contain_file('/root/.my.cnf') }
    end
    describe 'when set' do
      let(:params) {{:root_password => 'SET' }}
      it { should contain_mysql_user('root@localhost') }
      it { should contain_file('/root/.my.cnf') }
    end

  end

  context 'mysql::server::providers' do
    describe 'with users' do
      let(:params) {{:users => {
        'foo@localhost' => {
          'max_connections_per_hour' => '1',
          'max_queries_per_hour'     => '2',
          'max_updates_per_hour'     => '3',
          'max_user_connections'     => '4',
          'password_hash'            => '*F3A2A51A9B0F2BE2468926B4132313728C250DBF'
        },
        'foo2@localhost' => {}
      }}}
      it { should contain_mysql_user('foo@localhost').with(
        :max_connections_per_hour => '1',
        :max_queries_per_hour     => '2',
        :max_updates_per_hour     => '3',
        :max_user_connections     => '4',
        :password_hash            => '*F3A2A51A9B0F2BE2468926B4132313728C250DBF'
      )}
      it { should contain_mysql_user('foo2@localhost').with(
        :max_connections_per_hour => nil,
        :max_queries_per_hour     => nil,
        :max_updates_per_hour     => nil,
        :max_user_connections     => nil,
        :password_hash            => ''
      )}
    end

    describe 'with grants' do
      let(:params) {{:grants => {
        'foo@localhost/somedb.*' => {
          'user'       => 'foo@localhost',
          'table'      => 'somedb.*',
          'privileges' => ["SELECT", "UPDATE"],
          'options'    => ["GRANT"],
        },
        'foo2@localhost/*.*' => {
          'user'       => 'foo2@localhost',
          'table'      => '*.*',
          'privileges' => ["SELECT"],
        },
      }}}
      it { should contain_mysql_grant('foo@localhost/somedb.*').with(
        :user       => 'foo@localhost',
        :table      => 'somedb.*',
        :privileges => ["SELECT", "UPDATE"],
        :options    => ["GRANT"]
      )}
      it { should contain_mysql_grant('foo2@localhost/*.*').with(
        :user       => 'foo2@localhost',
        :table      => '*.*',
        :privileges => ["SELECT"],
        :options    => nil
      )}
    end

    describe 'with databases' do
      let(:params) {{:databases => {
        'somedb' => {
          'charset' => 'latin1',
          'collate' => 'latin1',
        },
        'somedb2' => {}
      }}}
      it { should contain_mysql_database('somedb').with(
        :charset => 'latin1',
        :collate => 'latin1'
      )}
      it { should contain_mysql_database('somedb2')}
    end

  end

end
