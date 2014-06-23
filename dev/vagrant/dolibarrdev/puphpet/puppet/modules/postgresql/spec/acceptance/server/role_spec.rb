require 'spec_helper_acceptance'

describe 'postgresql::server::role:', :unless => UNSUPPORTED_PLATFORMS.include?(fact('osfamily')) do
  after :all do
    # Cleanup after tests have ran
    apply_manifest("class { 'postgresql::server': ensure => absent }", :catch_failures => true)
  end

  it 'should idempotently create a user who can log in' do
    pp = <<-EOS.unindent
      $user = "postgresql_test_user"
      $password = "postgresql_test_password"

      class { 'postgresql::server': }

      # Since we are not testing pg_hba or any of that, make a local user for ident auth
      user { $user:
        ensure => present,
      }

      postgresql::server::role { $user:
        password_hash => postgresql_password($user, $password),
      }
    EOS

    apply_manifest(pp, :catch_failures => true)

    # Check that the user can log in
    psql('--command="select datname from pg_database" postgres', 'postgresql_test_user') do |r|
      expect(r.stdout).to match(/template1/)
      expect(r.stderr).to eq('')
    end
  end

  it 'should idempotently alter a user who can log in' do
    pp = <<-EOS.unindent
      $user = "postgresql_test_user"
      $password = "postgresql_test_password2"

      class { 'postgresql::server': }

      # Since we are not testing pg_hba or any of that, make a local user for ident auth
      user { $user:
        ensure => present,
      }

      postgresql::server::role { $user:
        password_hash => postgresql_password($user, $password),
      }
    EOS

    apply_manifest(pp, :catch_failures => true)
    apply_manifest(pp, :catch_changes => true)

    # Check that the user can log in
    psql('--command="select datname from pg_database" postgres', 'postgresql_test_user') do |r|
      expect(r.stdout).to match(/template1/)
      expect(r.stderr).to eq('')
    end
  end

  it 'should idempotently create a user with a cleartext password' do
    pp = <<-EOS.unindent
      $user = "postgresql_test_user2"
      $password = "postgresql_test_password2"

      class { 'postgresql::server': }

      # Since we are not testing pg_hba or any of that, make a local user for ident auth
      user { $user:
        ensure => present,
      }

      postgresql::server::role { $user:
        password_hash => $password,
      }
    EOS

    apply_manifest(pp, :catch_failures => true)
    apply_manifest(pp, :catch_changes => true)

    # Check that the user can log in
    psql('--command="select datname from pg_database" postgres', 'postgresql_test_user2') do |r|
      expect(r.stdout).to match(/template1/)
      expect(r.stderr).to eq('')
    end
  end
end
