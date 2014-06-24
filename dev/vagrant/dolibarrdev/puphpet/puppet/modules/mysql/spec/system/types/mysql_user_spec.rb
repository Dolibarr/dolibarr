require 'spec_helper_system'

describe 'mysql_user' do

  describe 'setup' do
    it 'should work with no errors' do
      pp = <<-EOS
        class { 'mysql::server': }
      EOS

      puppet_apply(pp)
    end
  end

  describe 'adding user' do
    it 'should work without errors' do
      pp = <<-EOS
        mysql_user { 'ashp@localhost':
          password_hash => '6f8c114b58f2ce9e',
        }
      EOS

      puppet_apply(pp)
    end

    it 'should find the user' do
      shell("mysql -NBe \"select '1' from mysql.user where CONCAT(user, '@', host) = 'ashp@localhost'\"") do |r|
        r.stdout.should =~ /^1$/
        r.stderr.should be_empty
        r.exit_code.should be_zero
      end
    end
  end

end
