require 'spec_helper'

describe 'epel::rpm_gpg_key' do
  context 'os_maj_version => 6' do
    let :facts do
      default_facts.merge({
        :operatingsystemrelease => '6.4',
        :os_maj_version         => '6',
      })
    end
    
    let :title do
      'EPEL-6'
    end
  
    let :params do
      { :path => "/etc/pki/rpm-gpg/RPM-GPG-KEY-EPEL-6" }
    end

    it do
      should contain_exec("import-#{title}").with({
        'path'      => '/bin:/usr/bin:/sbin:/usr/sbin',
        'command'   => "rpm --import #{params[:path]}",
        'unless'    => "rpm -q gpg-pubkey-$(echo $(gpg --throw-keyids < #{params[:path]}) | cut --characters=11-18 | tr '[A-Z]' '[a-z]')",
        'require'   => "File[#{params[:path]}]",
        'logoutput' => 'on_failure',
      })
    end
  end

  context 'os_maj_version => 5' do
    let :facts do
      default_facts.merge({
        :operatingsystemrelease => '5.9',
        :os_maj_version         => '5',
      })
    end
    
    let :title do
      'EPEL-5'
    end
  
    let :params do
      { :path => "/etc/pki/rpm-gpg/RPM-GPG-KEY-EPEL-5" }
    end

    it do
      should contain_exec("import-#{title}").with({
        'path'      => '/bin:/usr/bin:/sbin:/usr/sbin',
        'command'   => "rpm --import #{params[:path]}",
        'unless'    => "rpm -q gpg-pubkey-$(echo $(gpg --throw-keyids < #{params[:path]}) | cut --characters=11-18 | tr '[A-Z]' '[a-z]')",
        'require'   => "File[#{params[:path]}]",
        'logoutput' => 'on_failure',
      })
    end
  end
end
