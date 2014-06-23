require 'spec_helper_acceptance'

case fact('osfamily')
when 'FreeBSD'
  line = '0.freebsd.pool.ntp.org iburst maxpoll 9'
when 'Debian'
  line = '0.debian.pool.ntp.org iburst'
when 'RedHat'
  line = '0.centos.pool.ntp.org'
when 'SuSE'
  line = '0.opensuse.pool.ntp.org'
when 'Gentoo'
  line = '0.gentoo.pool.ntp.org'
when 'Linux'
  case fact('operatingsystem')
  when 'ArchLinux'
    line = '0.pool.ntp.org'
  when 'Gentoo'
    line = '0.gentoo.pool.ntp.org'
  end
when 'AIX'
  line = '0.debian.pool.ntp.org iburst'
end

describe 'ntp::config class', :unless => UNSUPPORTED_PLATFORMS.include?(fact('osfamily')) do
  it 'sets up ntp.conf' do
    apply_manifest(%{
      class { 'ntp': }
    }, :catch_failures => true)
  end

  describe file('/etc/ntp.conf') do
    it { should be_file }
    it { should contain line }
  end
end
