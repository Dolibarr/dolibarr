require 'spec_helper_acceptance'

case fact('osfamily')
when 'RedHat', 'FreeBSD', 'Linux', 'Gentoo'
  servicename = 'ntpd'
when 'AIX'
  servicename = 'xntpd'
else
  servicename = 'ntp'
end

describe 'ntp::service class', :unless => UNSUPPORTED_PLATFORMS.include?(fact('osfamily')) do
  describe 'basic test' do
    it 'sets up the service' do
      apply_manifest(%{
        class { 'ntp': }
      }, :catch_failures => true)
    end

    describe service(servicename) do
      it { should be_enabled }
      it { should be_running }
    end
  end

  describe 'service parameters' do
    it 'starts the service' do
      pp = <<-EOS
      class { 'ntp':
        service_enable => true,
        service_ensure => running,
        service_manage => true,
        service_name   => '#{servicename}'
      }
      EOS
      apply_manifest(pp, :catch_failures => true)
    end

    describe service(servicename) do
      it { should be_running }
      it { should be_enabled }
    end
  end

  describe 'service is unmanaged' do
    it 'shouldnt stop the service' do
      pp = <<-EOS
      class { 'ntp':
        service_enable => false,
        service_ensure => stopped,
        service_manage => false,
        service_name   => '#{servicename}'
      }
      EOS
      apply_manifest(pp, :catch_failures => true)
    end

    describe service(servicename) do
      it { should be_running }
      it { should be_enabled }
    end
  end
end
