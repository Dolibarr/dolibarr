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

describe "ntp class:", :unless => UNSUPPORTED_PLATFORMS.include?(fact('osfamily')) do
  it 'applies successfully' do
    pp = "class { 'ntp': }"

    apply_manifest(pp, :catch_failures => true) do |r|
      expect(r.stderr).to eq("")
    end
  end

  describe 'autoconfig' do
    it 'raises a deprecation warning' do
      pp = "class { 'ntp': autoupdate => true }"

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/autoupdate parameter has been deprecated and replaced with package_ensure/)
      end
    end
  end

  describe 'config' do
    it 'sets the ntp.conf location' do
      pp = "class { 'ntp': config => '/etc/antp.conf' }"
      apply_manifest(pp, :catch_failures => true)
    end

    describe file('/etc/antp.conf') do
      it { should be_file }
    end
  end

  describe 'config_template' do
    it 'sets up template' do
      modulepath = default['distmoduledir']
      shell("mkdir -p #{modulepath}/test/templates")
      shell("echo 'testcontent' >> #{modulepath}/test/templates/ntp.conf")
    end

    it 'sets the ntp.conf location' do
      pp = "class { 'ntp': config_template => 'test/ntp.conf' }"
      apply_manifest(pp, :catch_failures => true)
    end

    describe file('/etc/ntp.conf') do
      it { should be_file }
      it { should contain 'testcontent' }
    end
  end

  describe 'driftfile' do
    it 'sets the driftfile location' do
      pp = "class { 'ntp': driftfile => '/tmp/driftfile' }"
      apply_manifest(pp, :catch_failures => true)
    end

    describe file('/etc/ntp.conf') do
      it { should be_file }
      it { should contain 'driftfile /tmp/driftfile' }
    end
  end

  describe 'keys' do
    it 'enables the key parameters' do
      pp = <<-EOS
      class { 'ntp':
        keys_enable     => true,
        keys_file       => '/etc/ntp/keys',
        keys_controlkey => '/etc/ntp/controlkey',
        keys_requestkey => '1',
        keys_trusted    => [ '1', '2' ],
      }
      EOS
      # Rely on a shell command instead of a file{} here to avoid loops
      # within puppet when it tries to manage /etc/ntp/keys before /etc/ntp.
      shell("mkdir -p /etc/ntp && echo '1 M AAAABBBB' >> /etc/ntp/keys")
      apply_manifest(pp, :catch_failures => true)
    end

    describe file('/etc/ntp.conf') do
      it { should be_file }
      it { should contain 'keys /etc/ntp/keys' }
      it { should contain 'controlkey /etc/ntp/controlkey' }
      it { should contain 'requestkey 1' }
      it { should contain 'trustedkey 1 2' }
    end
  end

  describe 'package' do
    it 'installs the right package' do
      pp = <<-EOS
      class { 'ntp':
        package_ensure => present,
        package_name   => ['#{packagename}'],
      }
      EOS
      apply_manifest(pp, :catch_failures => true)
    end

    describe package(packagename) do
      it { should be_installed }
    end
  end

  describe 'panic => false' do
    it 'enables the tinker panic setting' do
      pp = <<-EOS
      class { 'ntp':
        panic => false,
      }
      EOS
      apply_manifest(pp, :catch_failures => true)
    end

    describe file('/etc/ntp.conf') do
      it { should contain 'tinker panic' }
    end
  end

  describe 'panic => true' do
    it 'disables the tinker panic setting' do
      pp = <<-EOS
      class { 'ntp':
        panic => true,
      }
      EOS
      apply_manifest(pp, :catch_failures => true)
    end

    describe file('/etc/ntp.conf') do
      it { should_not contain 'tinker panic 0' }
    end
  end

  describe 'udlc' do
    it 'adds a udlc' do
      pp = "class { 'ntp': udlc => true }"
      apply_manifest(pp, :catch_failures => true)
    end

    describe file('/etc/ntp.conf') do
      it { should be_file }
      it { should contain '127.127.1.0' }
    end
  end

end
