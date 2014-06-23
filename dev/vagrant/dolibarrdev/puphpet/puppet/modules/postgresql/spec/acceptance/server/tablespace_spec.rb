require 'spec_helper_acceptance'

describe 'postgresql::server::tablespace:', :unless => UNSUPPORTED_PLATFORMS.include?(fact('osfamily')) do
  after :all do
    # Cleanup after tests have ran
    apply_manifest("class { 'postgresql::server': ensure => absent }", :catch_failures => true)
  end

  it 'should idempotently create tablespaces and databases that are using them' do
    pp = <<-EOS.unindent
      class { 'postgresql::server': }

      file { '/tmp/postgres/pg_tablespaces':
        ensure => 'directory',
        owner  => 'postgres',
        group  => 'postgres',
        mode   => '0700',
      }

      postgresql::server::tablespace { 'tablespace1':
        location => '/tmp/postgres/pg_tablespaces/space1',
      }
      postgresql::server::database { 'tablespacedb1':
        encoding   => 'utf8',
        tablespace => 'tablespace1',
      }
      postgresql::server::db { 'tablespacedb2':
        user       => 'dbuser2',
        password   => postgresql_password('dbuser2', 'dbuser2'),
        tablespace => 'tablespace1',
      }

      postgresql::server::role { 'spcuser':
        password_hash => postgresql_password('spcuser', 'spcuser'),
      }
      postgresql::server::tablespace { 'tablespace2':
        location => '/tmp/postgres/pg_tablespaces/space2',
        owner    => 'spcuser',
      }
      postgresql::server::database { 'tablespacedb3':
        encoding   => 'utf8',
        tablespace => 'tablespace2',
      }
    EOS

    shell('mkdir -p /tmp/postgres')
    # Apply appropriate selinux labels
    if fact('osfamily') == 'RedHat'
      if shell('getenforce').stdout =~ /Enforcing/
        shell('chcon -Rv --type=postgresql_db_t /tmp/postgres')
      end
    end
    apply_manifest(pp, :catch_failures => true)
    apply_manifest(pp, :catch_changes => true)

    # Check that databases use correct tablespaces
    psql('--command="select ts.spcname from pg_database db, pg_tablespace ts where db.dattablespace = ts.oid and db.datname = \'"\'tablespacedb1\'"\'"') do |r|
      expect(r.stdout).to match(/tablespace1/)
      expect(r.stderr).to eq('')
    end

    psql('--command="select ts.spcname from pg_database db, pg_tablespace ts where db.dattablespace = ts.oid and db.datname = \'"\'tablespacedb3\'"\'"') do |r|
      expect(r.stdout).to match(/tablespace2/)
      expect(r.stderr).to eq('')
    end
  end
end
