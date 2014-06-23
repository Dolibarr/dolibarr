require 'spec_helper_acceptance'

describe 'postgresql::server::db', :unless => UNSUPPORTED_PLATFORMS.include?(fact('osfamily')) do
  after :all do
    # Cleanup after tests have ran
    apply_manifest("class { 'postgresql::server': ensure => absent }", :catch_failures => true)
  end

  it 'should idempotently create a db that we can connect to' do
    begin
      pp = <<-EOS.unindent
        $db = 'postgresql_test_db'
        class { 'postgresql::server': }

        postgresql::server::db { $db:
          user     => $db,
          password => postgresql_password($db, $db),
        }
      EOS

      apply_manifest(pp, :catch_failures => true)
      apply_manifest(pp, :catch_changes => true)

      psql('--command="select datname from pg_database" postgresql_test_db') do |r|
        expect(r.stdout).to match(/postgresql_test_db/)
        expect(r.stderr).to eq('')
      end
    ensure
      psql('--command="drop database postgresql_test_db" postgres')
    end
  end

  it 'should take a locale parameter' do
    pending('no support for locale parameter with centos 5', :if => (fact('osfamily') == 'RedHat' and fact('lsbmajdistrelease') == '5'))
    begin
      pp = <<-EOS.unindent
        class { 'postgresql::server': }
        postgresql::server::db { 'test1':
          user     => 'test1',
          password => postgresql_password('test1', 'test1'),
          encoding => 'UTF8',
          locale   => 'en_NG.UTF-8',
        }
      EOS

      apply_manifest(pp, :catch_failures => true)
      apply_manifest(pp, :catch_changes => true)

      psql('-c "show lc_ctype" test1') do |r|
        expect(r.stdout).to match(/en_NG/)
      end

      psql('-c "show lc_collate" test1') do |r|
        expect(r.stdout).to match(/en_NG/)
      end
    ensure
      psql('--command="drop database test1" postgres')
    end
  end

  it 'should take an istemplate parameter' do
    begin
      pp = <<-EOS.unindent
        $db = 'template2'
        class { 'postgresql::server': }

        postgresql::server::db { $db:
          user       => $db,
          password   => postgresql_password($db, $db),
          istemplate => true,
        }
      EOS

      apply_manifest(pp, :catch_failures => true)
      apply_manifest(pp, :catch_changes => true)

      psql('--command="select datname from pg_database" template2') do |r|
        expect(r.stdout).to match(/template2/)
        expect(r.stderr).to eq('')
      end
    ensure
      psql('--command="drop database template2" postgres', 'postgres', [1,2]) do |r|
        expect(r.stdout).to eq('')
        expect(r.stderr).to match(/cannot drop a template database/)
      end
    end
  end

  it 'should update istemplate parameter' do
    begin
      pp = <<-EOS.unindent
        $db = 'template2'
        class { 'postgresql::server': }

        postgresql::server::db { $db:
          user       => $db,
          password   => postgresql_password($db, $db),
          istemplate => false,
        }
      EOS

      apply_manifest(pp, :catch_failures => true)
      apply_manifest(pp, :catch_changes => true)

      psql('--command="select datname from pg_database" template2') do |r|
        expect(r.stdout).to match(/template2/)
        expect(r.stderr).to eq('')
      end
    ensure
      psql('--command="drop database template2" postgres')
    end
  end

  it 'should take a template parameter' do
    begin
      pp = <<-EOS.unindent
        $db = 'postgresql_test_db'
        class { 'postgresql::server': }

        postgresql::server::db { $db:
          user       => $db,
          template   => 'template1',
          password   => postgresql_password($db, $db),
        }
      EOS

      apply_manifest(pp, :catch_failures => true)
      apply_manifest(pp, :catch_changes => true)

      psql('--command="select datname from pg_database" postgresql_test_db') do |r|
        expect(r.stdout).to match(/postgresql_test_db/)
        expect(r.stderr).to eq('')
      end
    ensure
      psql('--command="drop database postgresql_test_db" postgres')
    end
  end
end
