require 'spec_helper_acceptance'

describe 'mysql::server::root_password class', :unless => UNSUPPORTED_PLATFORMS.include?(fact('operatingsystem')) do

  describe 'reset' do
    it 'shuts down mysql' do
      pp = <<-EOS
      class { 'mysql::server': service_enabled => false }
      EOS

      apply_manifest(pp, :catch_failures => true)
    end

    it 'deletes the /root/.my.cnf password' do
      shell('rm -rf /root/.my.cnf')
    end

    it 'deletes all databases' do
      case fact('osfamily')
      when 'RedHat', 'Suse'
        shell('rm -rf `grep datadir /etc/my.cnf | cut -d" " -f 3`/*')
      when 'Debian'
        shell('rm -rf `grep datadir /etc/mysql/my.cnf | cut -d" " -f 3`/*')
        shell('mysql_install_db')
      end
    end

    it 'starts up mysql' do
      pp = <<-EOS
      class { 'mysql::server': service_enabled => true }
      EOS

      puppet_apply(pp, :catch_failures => true)
    end
  end

  describe 'when unset' do
    it 'should work' do
      pp = <<-EOS
        class { 'mysql::server': root_password => 'test' }
      EOS

      # Run it twice and test for idempotency
      apply_manifest(pp, :catch_failures => true)
      expect(apply_manifest(pp, :catch_failures => true).exit_code).to be_zero
    end
  end

  describe 'when set' do
    it 'should work' do
      pp = <<-EOS
        class { 'mysql::server': root_password => 'new', old_root_password => 'test' }
      EOS

      # Run it twice and test for idempotency
      apply_manifest(pp, :catch_failures => true)
      expect(apply_manifest(pp, :catch_failures => true).exit_code).to be_zero
    end
  end
end

# Debian relies on a debian-sys-maint@ account to do almost everything.
# Without recreating this account we can't even stop the service in future
# tests.
if fact('osfamily') == 'Debian'
  describe 'readd debian user' do
    it 'readds the user' do
      shell("MYSQL_PASSWORD=`head -5 /etc/mysql/debian.cnf | grep password | cut -d' ' -f 3`; mysql -NBe \"GRANT ALL PRIVILEGES ON *.* to 'debian-sys-maint'@'localhost' IDENTIFIED BY '${MYSQL_PASSWORD}' WITH GRANT OPTION;\"")
    end
  end
end
