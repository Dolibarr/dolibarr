require 'spec_helper_acceptance'

describe 'apache::mod::mime class' do
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

  context "default mime config" do
    it 'succeeds in puppeting mime' do
      pp= <<-EOS
        class { 'apache': }
        include apache::mod::mime
      EOS
      apply_manifest(pp, :catch_failures => true)
    end

    describe service(service_name) do
      it { should be_enabled }
      it { should be_running }
    end

    describe file("#{mod_dir}/mime.conf") do
      it { should contain "AddType application/x-compress .Z" }
    end
  end
end
