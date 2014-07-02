require 'spec_helper_acceptance'

describe 'apache::mod::deflate class' do
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

  context "default deflate config" do
    it 'succeeds in puppeting deflate' do
      pp= <<-EOS
        class { 'apache': }
        include apache::mod::deflate
      EOS
      apply_manifest(pp, :catch_failures => true)
    end

    describe service(service_name) do
      it { should be_enabled }
      it { should be_running }
    end

    describe file("#{mod_dir}/deflate.conf") do
      it { should contain "AddOutputFilterByType DEFLATE text/html text/plain text/xml" }
      it { should contain "AddOutputFilterByType DEFLATE text/css" }
      it { should contain "AddOutputFilterByType DEFLATE application/x-javascript application/javascript application/ecmascript" }
      it { should contain "AddOutputFilterByType DEFLATE application/rss+xml" }
      it { should contain "DeflateFilterNote Input instream" }
      it { should contain "DeflateFilterNote Output outstream" }
      it { should contain "DeflateFilterNote Ratio ratio" }
    end
  end
end
