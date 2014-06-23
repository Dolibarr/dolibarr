require 'spec_helper_acceptance'

describe 'apache::mod::dav_svn class' do
  case fact('osfamily')
  when 'Debian'
    mod_dir      = '/etc/apache2/mods-available'
    service_name = 'apache2'
  when 'RedHat'
    mod_dir      = '/etc/httpd/conf.d'
    service_name = 'httpd'
  when 'FreeBSD'
    mod_dir      = '/usr/local/etc/apache22/Modules'
    service_name = 'apache22'
  end

  context "default dav_svn config" do
    it 'succeeds in puppeting dav_svn' do
      pp= <<-EOS
        class { 'apache': }
        include apache::mod::dav_svn
      EOS
      apply_manifest(pp, :catch_failures => true)
    end

    describe service(service_name) do
      it { should be_enabled }
      it { should be_running }
    end

    describe file("#{mod_dir}/dav_svn.load") do
      it { should contain "LoadModule dav_svn_module" }
    end
  end

  context "dav_svn with enabled authz_svn config" do
    it 'succeeds in puppeting dav_svn' do
      pp= <<-EOS
        class { 'apache': }
        class { 'apache::mod::dav_svn':
            authz_svn_enabled => true,
        }
      EOS
      apply_manifest(pp, :catch_failures => true)
    end

    describe service(service_name) do
      it { should be_enabled }
      it { should be_running }
    end

    describe file("#{mod_dir}/dav_svn_authz_svn.load") do
      it { should contain "LoadModule authz_svn_module" }
    end
  end
end
