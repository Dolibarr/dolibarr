require 'spec_helper_acceptance'

describe 'mysql_database', :unless => UNSUPPORTED_PLATFORMS.include?(fact('operatingsystem')) do
  describe 'setup' do
    it 'should work with no errors' do
      pp = <<-EOS
        class { 'mysql::server': }
      EOS

      apply_manifest(pp, :catch_failures => true)
    end
  end

  describe 'creating database' do
    it 'should work without errors' do
      pp = <<-EOS
        mysql_database { 'spec_db':
          ensure => present,
        }
      EOS

      apply_manifest(pp, :catch_failures => true)
    end

    it 'should find the database' do
      shell("mysql -NBe \"SHOW DATABASES LIKE 'spec_db'\"") do |r|
        expect(r.stdout).to match(/^spec_db$/)
        expect(r.stderr).to be_empty
      end
    end
  end

  describe 'charset and collate' do
    it 'should create two db of different types idempotently' do
      pp = <<-EOS
        mysql_database { 'spec_latin1':
          charset => 'latin1',
          collate => 'latin1_swedish_ci',
        }
        mysql_database { 'spec_utf8':
          charset => 'utf8',
          collate => 'utf8_general_ci',
        }
      EOS

      apply_manifest(pp, :catch_failures => true)
      apply_manifest(pp, :catch_changes => true)
    end

    it 'should find latin1 db' do
      shell("mysql -NBe \"SHOW VARIABLES LIKE '%_database'\" spec_latin1") do |r|
        expect(r.stdout).to match(/^character_set_database\tlatin1\ncollation_database\tlatin1_swedish_ci$/)
        expect(r.stderr).to be_empty
      end
    end

    it 'should find utf8 db' do
      shell("mysql -NBe \"SHOW VARIABLES LIKE '%_database'\" spec_utf8") do |r|
        expect(r.stdout).to match(/^character_set_database\tutf8\ncollation_database\tutf8_general_ci$/)
        expect(r.stderr).to be_empty
      end
    end
  end
end
