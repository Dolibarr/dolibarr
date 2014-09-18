require 'spec_helper_acceptance'

case fact('osfamily')
when 'FreeBSD'
  packagename = 'net/ntp'
when 'Gentoo'
  packagename = 'net-misc/ntp'
when 'Linux'
  case fact('operatingsystem')
  when 'ArchLinux'
    packagename = 'ntp'
  when 'Gentoo'
    packagename = 'net-misc/ntp'
  end
when 'AIX'
  packagename = 'bos.net.tcp.client'
else
  packagename = 'ntp'
end

describe 'ntp::install class', :unless => UNSUPPORTED_PLATFORMS.include?(fact('osfamily')) do
  it 'installs the package' do
    apply_manifest(%{
      class { 'ntp': }
    }, :catch_failures => true)
  end

  describe package(packagename) do
    it { should be_installed }
  end
end
