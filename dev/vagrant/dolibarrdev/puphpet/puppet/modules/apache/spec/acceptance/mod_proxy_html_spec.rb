require 'spec_helper_acceptance'

describe 'apache::mod::proxy_html class', :unless => UNSUPPORTED_PLATFORMS.include?(fact('osfamily')) do
  case fact('osfamily')
  when 'Debian'
    service_name = 'apache2'
  when 'RedHat'
    service_name = 'httpd'
  when 'FreeBSD'
    service_name = 'apache22'
  end

  context "default proxy_html config" do
    if fact('osfamily') == 'RedHat' and fact('operatingsystemmajrelease') =~ /(5|6)/
      it 'adds epel' do
        pp = "class { 'epel': }"
        apply_manifest(pp, :catch_failures => true)
      end
    end

    it 'succeeds in puppeting proxy_html' do
      pp= <<-EOS
        class { 'apache': }
        class { 'apache::mod::proxy': }
        class { 'apache::mod::proxy_http': }
        # mod_proxy_html doesn't exist in RHEL5
        if $::osfamily == 'RedHat' and $::operatingsystemmajrelease != '5' {
          class { 'apache::mod::proxy_html': }
        }
      EOS
      apply_manifest(pp, :catch_failures => true)
    end

    describe service(service_name) do
      it { should be_enabled }
      it { should be_running }
    end
  end
end
