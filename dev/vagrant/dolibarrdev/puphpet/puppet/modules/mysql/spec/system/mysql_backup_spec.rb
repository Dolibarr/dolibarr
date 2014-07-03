require 'spec_helper_system'

describe 'mysql::server::backup class' do
  context 'should work with no errors' do
    pp = <<-EOS
      class { 'mysql::server': override_options => { 'root_password' => 'password' } }
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

    context puppet_apply(pp) do
      its(:stderr) { should be_empty }
      its(:exit_code) { should_not == 1 }
      its(:refresh) { should be_nil }
      its(:stderr) { should be_empty }
      its(:exit_code) { should be_zero }
    end
  end

  describe 'mysqlbackup.sh' do
    context 'should run mysqlbackup.sh with no errors' do
      context shell("/usr/local/sbin/mysqlbackup.sh") do
        its(:exit_code) { should be_zero }
        its(:stderr) { should be_empty }
      end
    end

    context 'should dump all databases to single file' do
      describe command('ls /tmp/backups/ | grep -c "mysql_backup_[0-9][0-9]*-[0-9][0-9]*.sql.bz2"') do
        it { should return_stdout /1/ }
        it { should return_exit_status 0 }
      end
    end

    context 'should create one file per database per run' do
      context shell("/usr/local/sbin/mysqlbackup.sh") do
        its(:exit_code) { should be_zero }
        its(:stderr) { should be_empty }
      end

      describe command('ls /tmp/backups/ | grep -c "mysql_backup_backup1_[0-9][0-9]*-[0-9][0-9]*.sql.bz2"') do
        it { should return_stdout /2/ }
        it { should return_exit_status 0 }
      end
    end
  end
end
