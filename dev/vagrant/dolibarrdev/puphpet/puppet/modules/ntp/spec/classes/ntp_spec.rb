require 'spec_helper'

describe 'ntp' do

  ['Debian', 'RedHat','SuSE', 'FreeBSD', 'Archlinux', 'Gentoo', 'Gentoo (Facter < 1.7)'].each do |system|
    if system == 'Gentoo (Facter < 1.7)'
      let(:facts) {{ :osfamily => 'Linux', :operatingsystem => 'Gentoo' }}
    else
      let(:facts) {{ :osfamily => system }}
    end

    it { should include_class('ntp::install') }
    it { should include_class('ntp::config') }
    it { should include_class('ntp::service') }

    describe "ntp::config on #{system}" do
      it { should contain_file('/etc/ntp.conf').with_owner('0') }
      it { should contain_file('/etc/ntp.conf').with_group('0') }
      it { should contain_file('/etc/ntp.conf').with_mode('0644') }

      describe 'allows template to be overridden' do
        let(:params) {{ :config_template => 'my_ntp/ntp.conf.erb' }}
        it { should contain_file('/etc/ntp.conf').with({
          'content' => /server foobar/})
        }
      end

      describe "keys for osfamily #{system}" do
        context "when enabled" do
          let(:params) {{
            :keys_enable     => true,
            :keys_file       => '/etc/ntp/ntp.keys',
            :keys_trusted    => ['1', '2', '3'],
            :keys_controlkey => '2',
            :keys_requestkey => '3',
          }}

          it { should contain_file('/etc/ntp').with({
            'ensure'  => 'directory'})
          }
          it { should contain_file('/etc/ntp.conf').with({
            'content' => /trustedkey 1 2 3/})
          }
          it { should contain_file('/etc/ntp.conf').with({
            'content' => /controlkey 2/})
          }
          it { should contain_file('/etc/ntp.conf').with({
            'content' => /requestkey 3/})
          }
        end
      end

      context "when disabled" do
        let(:params) {{
          :keys_enable     => false,
          :keys_file       => '/etc/ntp/ntp.keys',
          :keys_trusted    => ['1', '2', '3'],
          :keys_controlkey => '2',
          :keys_requestkey => '3',
        }}

        it { should_not contain_file('/etc/ntp').with({
          'ensure'  => 'directory'})
        }
        it { should_not contain_file('/etc/ntp.conf').with({
          'content' => /trustedkey 1 2 3/})
        }
        it { should_not contain_file('/etc/ntp.conf').with({
          'content' => /controlkey 2/})
        }
        it { should_not contain_file('/etc/ntp.conf').with({
          'content' => /requestkey 3/})
        }
      end

      describe 'preferred servers' do
        context "when set" do
          let(:params) {{
            :servers           => ['a', 'b', 'c', 'd'],
            :preferred_servers => ['a', 'b']
          }}

          it { should contain_file('/etc/ntp.conf').with({
            'content' => /server a prefer\nserver b prefer\nserver c\nserver d/})
          }
        end
        context "when not set" do
          let(:params) {{
            :servers           => ['a', 'b', 'c', 'd'],
            :preferred_servers => []
          }}

          it { should_not contain_file('/etc/ntp.conf').with({
            'content' => /server a prefer/})
          }
        end
      end

      describe "ntp::install on #{system}" do
        let(:params) {{ :package_ensure => 'present', :package_name => ['ntp'], }}

        it { should contain_package('ntp').with(
          :ensure => 'present',
          :name   => 'ntp'
        )}

        describe 'should allow package ensure to be overridden' do
          let(:params) {{ :package_ensure => 'latest', :package_name => ['ntp'] }}
          it { should contain_package('ntp').with_ensure('latest') }
        end

        describe 'should allow the package name to be overridden' do
          let(:params) {{ :package_ensure => 'present', :package_name => ['hambaby'] }}
          it { should contain_package('ntp').with_name('hambaby') }
        end
      end

      describe 'ntp::service' do
        let(:params) {{
          :service_manage => true,
          :service_enable => true,
          :service_ensure => 'running',
          :service_name   => 'ntp'
        }}

        describe 'with defaults' do
          it { should contain_service('ntp').with(
            :enable => true,
            :ensure => 'running',
            :name   => 'ntp'
          )}
        end

        describe 'service_ensure' do
          describe 'when overridden' do
            let(:params) {{ :service_name => 'ntp', :service_ensure => 'stopped' }}
            it { should contain_service('ntp').with_ensure('stopped') }
          end
        end

        describe 'service_manage' do
          let(:params) {{
            :service_manage => false,
            :service_enable => true,
            :service_ensure => 'running',
            :service_name   => 'ntpd',
          }}

          it 'when set to false' do
            should_not contain_service('ntp').with({
              'enable' => true,
              'ensure' => 'running',
              'name'   => 'ntpd'
            })
          end
        end
      end
    end

    context 'ntp::config' do
      describe "for operating system Gentoo (Facter < 1.7)" do
        let(:facts) {{ :operatingsystem => 'Gentoo',
                       :osfamily        => 'Linux' }}

        it 'uses the NTP pool servers by default' do
          should contain_file('/etc/ntp.conf').with({
            'content' => /server \d.gentoo.pool.ntp.org/,
          })
        end
      end

      describe "on osfamily Gentoo" do
        let(:facts) {{ :osfamily => 'Gentoo' }}

        it 'uses the NTP pool servers by default' do
          should contain_file('/etc/ntp.conf').with({
            'content' => /server \d.gentoo.pool.ntp.org/,
          })
        end
      end

      describe "on osfamily Debian" do
        let(:facts) {{ :osfamily => 'debian' }}

        it 'uses the debian ntp servers by default' do
          should contain_file('/etc/ntp.conf').with({
            'content' => /server \d.debian.pool.ntp.org iburst/,
          })
        end
      end

      describe "on osfamily RedHat" do
        let(:facts) {{ :osfamily => 'RedHat' }}

        it 'uses the redhat ntp servers by default' do
          should contain_file('/etc/ntp.conf').with({
            'content' => /server \d.centos.pool.ntp.org/,
          })
        end
      end

      describe "on osfamily SuSE" do
        let(:facts) {{ :osfamily => 'SuSE' }}

        it 'uses the opensuse ntp servers by default' do
          should contain_file('/etc/ntp.conf').with({
            'content' => /server \d.opensuse.pool.ntp.org/,
          })
        end
      end

      describe "on osfamily FreeBSD" do
        let(:facts) {{ :osfamily => 'FreeBSD' }}

        it 'uses the freebsd ntp servers by default' do
          should contain_file('/etc/ntp.conf').with({
            'content' => /server \d.freebsd.pool.ntp.org iburst maxpoll 9/,
          })
        end
      end

      describe "on osfamily ArchLinux" do
        let(:facts) {{ :osfamily => 'ArchLinux' }}

        it 'uses the NTP pool servers by default' do
          should contain_file('/etc/ntp.conf').with({
            'content' => /server \d.pool.ntp.org/,
          })
        end
      end

      describe "for operating system family unsupported" do
        let(:facts) {{
          :osfamily  => 'unsupported',
        }}

        it { expect{ subject }.to raise_error(
          /^The ntp module is not supported on an unsupported based system./
        )}
      end
    end

    describe 'for virtual machines' do
      let(:facts) {{ :osfamily        => 'Archlinux',
                     :is_virtual      => 'true' }}

      it 'should not use local clock as a time source' do
        should_not contain_file('/etc/ntp.conf').with({
          'content' => /server.*127.127.1.0.*fudge.*127.127.1.0 stratum 10/,
        })
      end

      it 'allows large clock skews' do
        should contain_file('/etc/ntp.conf').with({
          'content' => /tinker panic 0/,
        })
      end
    end

    describe 'for physical machines' do
      let(:facts) {{ :osfamily        => 'Archlinux',
                     :is_virtual      => 'false' }}

      it 'disallows large clock skews' do
        should_not contain_file('/etc/ntp.conf').with({
          'content' => /tinker panic 0/,
        })
      end
    end
  end

end
