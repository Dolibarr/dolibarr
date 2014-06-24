require 'spec_helper_acceptance'

describe 'mysql::server::backup class', :unless => UNSUPPORTED_PLATFORMS.include?(fact('operatingsystem')) do
  context 'should work with no errors' do
    it 'when configuring mysql backups' do
      pp = <<-EOS
        class { 'mysql::server': root_password => 'password' }
        mysql::db { 'backup1':
          user     => 'backup',
          password => 'secret',
        }

        class { 'mysql::server::backup':
          backupuser     => 'myuser',
          backuppassword => 'mypassword',
          backupdir      => '/tmp/backups',
          backupcompress => true,
          postscript     => [
            'rm -rf /var/tmp/mysqlbackups',
            'rm -f /var/tmp/mysqlbackups.done',
            'cp -r /tmp/backups /var/tmp/mysqlbackups',
            'touch /var/tmp/mysqlbackups.done',
          ],
        }
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stderr).to eq("")
      end
      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stderr).to eq("")
      end
    end
  end

  describe 'mysqlbackup.sh' do
    it 'should run mysqlbackup.sh with no errors' do
      shell("/usr/local/sbin/mysqlbackup.sh") do |r|
        expect(r.stderr).to eq("")
      end
    end

    it 'should dump all databases to single file' do
      shell('ls -l /tmp/backups/mysql_backup_*-*.sql.bz2 | wc -l') do |r|
        expect(r.stdout).to match(/1/)
        expect(r.exit_code).to be_zero
      end
    end

    context 'should create one file per database per run' do
      it 'executes mysqlbackup.sh a second time' do
        shell('sleep 1')
        shell('/usr/local/sbin/mysqlbackup.sh')
      end

      it 'creates at least one backup tarball' do
        shell('ls -l /tmp/backups/mysql_backup_*-*.sql.bz2 | wc -l') do |r|
          expect(r.stdout).to match(/2/)
          expect(r.exit_code).to be_zero
        end
      end
    end
  end
end
