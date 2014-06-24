require 'spec_helper_acceptance'

describe 'mysql_user', :unless => UNSUPPORTED_PLATFORMS.include?(fact('operatingsystem')) do
  describe 'setup' do
    it 'should work with no errors' do
      pp = <<-EOS
        class { 'mysql::server': }
      EOS

      apply_manifest(pp, :catch_failures => true)
    end
  end

  describe 'adding user' do
    it 'should work without errors' do
      pp = <<-EOS
        mysql_user { 'ashp@localhost':
          password_hash => '6f8c114b58f2ce9e',
        }
      EOS

      apply_manifest(pp, :catch_failures => true)
    end

    it 'should find the user' do
      shell("mysql -NBe \"select '1' from mysql.user where CONCAT(user, '@', host) = 'ashp@localhost'\"") do |r|
        expect(r.stdout).to match(/^1$/)
        expect(r.stderr).to be_empty
      end
    end
  end
end
