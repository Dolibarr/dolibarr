require 'spec_helper_acceptance'

describe 'postgresql::validate_db_connection:', :unless => UNSUPPORTED_PLATFORMS.include?(fact('osfamily')) do
  before :all do
    # Setup postgresql server and a sample database for tests to use.
    pp = <<-EOS.unindent
      $db = 'foo'
      class { 'postgresql::server': }

      postgresql::server::db { $db:
        user     => $db,
        password => postgresql_password($db, $db),
      }
    EOS

    apply_manifest(pp, :catch_failures => true)
  end

  after :all do
    # Remove postgresql server after all tests have ran.
    apply_manifest("class { 'postgresql::server': ensure => absent }", :catch_failures => true)
  end

  it 'should run puppet with no changes declared if socket connectivity works' do
    pp = <<-EOS.unindent
      postgresql::validate_db_connection { 'foo':
        database_name => 'foo',
        run_as        => 'postgres',
      }
    EOS

    apply_manifest(pp, :catch_failures => true)
  end

  it 'should keep retrying if database is down' do
    # So first we shut the db down, then background a startup routine with a
    # sleep 10 in front of it. That way the tests should continue while
    # the pause and db startup happens in the background.
    shell("/etc/init.d/postgresql* stop")
    shell('nohup bash -c "sleep 10; /etc/init.d/postgresql* start" > /dev/null 2>&1 &')

    pp = <<-EOS.unindent
      postgresql::validate_db_connection { 'foo':
        database_name => 'foo',
        tries         => 30,
        sleep         => 1,
        run_as        => 'postgres',
      }
    EOS

    apply_manifest(pp, :catch_failures => true)
  end

  it 'should run puppet with no changes declared if db ip connectivity works' do
    pp = <<-EOS.unindent
      postgresql::validate_db_connection { 'foo':
        database_host     => 'localhost',
        database_name     => 'foo',
        database_username => 'foo',
        database_password => 'foo',
      }
    EOS

    apply_manifest(pp, :catch_failures => true)
  end

  it 'should fail catalogue if database connectivity fails' do
    pp = <<-EOS.unindent
      postgresql::validate_db_connection { 'foobarbaz':
        database_host     => 'localhost',
        database_name     => 'foobarbaz',
        database_username => 'foobarbaz',
        database_password => 'foobarbaz',
      }
    EOS

    apply_manifest(pp, :expect_failures => true)
  end
end
