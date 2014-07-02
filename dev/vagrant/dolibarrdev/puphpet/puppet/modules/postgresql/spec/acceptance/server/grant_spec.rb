require 'spec_helper_acceptance'

describe 'postgresql::server::grant:', :unless => UNSUPPORTED_PLATFORMS.include?(fact('osfamily')) do
  after :all do
    # Cleanup after tests have ran
    apply_manifest("class { 'postgresql::server': ensure => absent }", :catch_failures => true)
  end

  it 'should grant access so a user can create in a database' do
    begin
      pp = <<-EOS.unindent
        $db = 'postgres'
        $user = 'psql_grant_tester'
        $password = 'psql_grant_pw'

        class { 'postgresql::server': }

        # Since we are not testing pg_hba or any of that, make a local user for ident auth
        user { $user:
          ensure => present,
        }

        postgresql::server::role { $user:
          password_hash => postgresql_password($user, $password),
        }

        postgresql::server::database { $db: }

        postgresql::server::grant { 'grant create test':
          object_type => 'database',
          privilege   => 'CREATE',
          db          => $db,
          role        => $user,
        }
      EOS

      apply_manifest(pp, :catch_failures => true)
      apply_manifest(pp, :catch_changes => true)

      # Check that the user can create a table in the database
      psql('--command="create table foo (foo int)" postgres', 'psql_grant_tester') do |r|
        expect(r.stdout).to match(/CREATE TABLE/)
        expect(r.stderr).to eq('')
      end
    ensure
      psql('--command="drop table foo" postgres', 'psql_grant_tester')
    end
  end
end
