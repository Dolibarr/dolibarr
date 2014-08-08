require 'spec_helper'

describe 'postgresql::server::pg_hba_rule', :type => :define do
  let :facts do
    {
      :osfamily => 'Debian',
      :operatingsystem => 'Debian',
      :operatingsystemrelease => '6.0',
      :concat_basedir => tmpfilename('pg_hba'),
    }
  end
  let :title do
    'test'
  end
  let :target do
    tmpfilename('pg_hba_rule')
  end

  context 'test template 1' do
    let :params do
      {
        :type => 'host',
        :database => 'all',
        :user => 'all',
        :address => '1.1.1.1/24',
        :auth_method => 'md5',
        :target => target,
      }
    end
    it do
      content = param('concat::fragment', 'pg_hba_rule_test', 'content')
      content.should =~ /host\s+all\s+all\s+1\.1\.1\.1\/24\s+md5/
    end
  end

  context 'test template 2' do
    let :params do
      {
        :type => 'local',
        :database => 'all',
        :user => 'all',
        :auth_method => 'ident',
        :target => target,
      }
    end
    it do
      content = param('concat::fragment', 'pg_hba_rule_test', 'content')
      content.should =~ /local\s+all\s+all\s+ident/
    end
  end

  context 'test template 3' do
    let :params do
      {
        :type => 'host',
        :database => 'all',
        :user => 'all',
        :address => '0.0.0.0/0',
        :auth_method => 'ldap',
        :auth_option => 'foo=bar',
        :target => target,
      }
    end
    it do
      content = param('concat::fragment', 'pg_hba_rule_test', 'content')
      content.should =~ /host\s+all\s+all\s+0\.0\.0\.0\/0\s+ldap\s+foo=bar/
    end
  end

  context 'validation' do
    context 'validate type test 1' do
      let :params do
        {
          :type => 'invalid',
          :database => 'all',
          :user => 'all',
          :address => '0.0.0.0/0',
          :auth_method => 'ldap',
          :target => target,
        }
      end
      it 'should fail parsing when type is not valid' do
        expect {subject}.to raise_error(Puppet::Error,
          /The type you specified \[invalid\] must be one of/)
      end
    end

    context 'validate auth_method' do
      let :params do
        {
          :type => 'local',
          :database => 'all',
          :user => 'all',
          :address => '0.0.0.0/0',
          :auth_method => 'invalid',
          :target => target,
        }
      end

      it 'should fail parsing when auth_method is not valid' do
        expect {subject}.to raise_error(Puppet::Error,
          /The auth_method you specified \[invalid\] must be one of/)
      end
    end

    context 'validate unsupported auth_method' do
      let :pre_condition do
        <<-EOS
          class { 'postgresql::globals':
            version => '9.0',
          }
          class { 'postgresql::server': }
        EOS
      end

      let :params do
        {
          :type => 'local',
          :database => 'all',
          :user => 'all',
          :address => '0.0.0.0/0',
          :auth_method => 'peer',
          :target => target,
        }
      end

      it 'should fail parsing when auth_method is not valid' do
        expect {subject}.to raise_error(Puppet::Error,
          /The auth_method you specified \[peer\] must be one of: trust, reject, md5, sha1, password, gss, sspi, krb5, ident, ldap, radius, cert, pam/)
      end
    end

    context 'validate supported auth_method' do
      let :pre_condition do
        <<-EOS
          class { 'postgresql::globals':
            version => '9.2',
          }
          class { 'postgresql::server': }
        EOS
      end

      let :params do
        {
          :type => 'local',
          :database => 'all',
          :user => 'all',
          :address => '0.0.0.0/0',
          :auth_method => 'peer',
          :target => target,
        }
      end

      it do
        content = param('concat::fragment', 'pg_hba_rule_test', 'content')
        content.should =~ /local\s+all\s+all\s+0\.0\.0\.0\/0\s+peer/
      end
    end

  end
end
