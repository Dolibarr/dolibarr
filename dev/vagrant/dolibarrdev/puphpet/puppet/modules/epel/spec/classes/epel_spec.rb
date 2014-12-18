require 'spec_helper'
require 'classes/shared_gpgkey'
require 'classes/shared_base'
require 'classes/shared_source'
require 'classes/shared_debuginfo'
require 'classes/shared_testing'
require 'classes/shared_testing_source'
require 'classes/shared_testing_debuginfo'

describe 'epel' do
  it { should create_class('epel') }
  it { should contain_class('epel::params') }

  context "operatingsystem => #{default_facts[:operatingsystem]}" do
    context 'os_maj_version => 6' do
      include_context :base_6
      include_context :gpgkey_6
      include_context :epel_source_6
      include_context :epel_debuginfo_6
      include_context :epel_testing_6
      include_context :epel_testing_source_6
      include_context :epel_testing_debuginfo_6

      let :facts do
        default_facts.merge({
          :operatingsystemrelease => '6.4',
          :os_maj_version         => '6',
        })
      end

      context 'epel_baseurl => http://example.com/epel/6/x86_64' do
        let(:params) {{ :epel_baseurl => "http://example.com/epel/6/x86_64" }}
        it { should contain_yumrepo('epel').with('baseurl'  => 'http://example.com/epel/6/x86_64') }
      end
      
      context 'epel_mirrorlist => absent' do
        let(:params) {{ :epel_mirrorlist => 'absent' }}
        it { should contain_yumrepo('epel').with('mirrorlist'  => 'absent') }
      end
    end

    context 'os_maj_version => 5' do
      include_context :base_5
      include_context :gpgkey_5
      include_context :epel_source_5
      include_context :epel_debuginfo_5
      include_context :epel_testing_5
      include_context :epel_testing_source_5
      include_context :epel_testing_debuginfo_5

      let :facts do
        default_facts.merge({
          :operatingsystemrelease => '5.9',
          :os_maj_version         => '5',
        })
      end
    end
  end

  context 'operatingsystem => Amazon' do    
    let :facts do
      default_facts.merge({
        :operatingsystem  => 'Amazon',
      })
    end

    it { should_not contain_yumrepo('epel-testing') }
    it { should_not contain_yumrepo('epel-testing-debuginfo') }
    it { should_not contain_yumrepo('epel-testing-source') }
    it { should_not contain_yumrepo('epel-debuginfo') }
    it { should_not contain_yumrepo('epel-source') }

    it do
      should contain_yumrepo('epel').with({
        'enabled'   => '1',
        'gpgcheck'  => '1',
      })
    end
  end
end