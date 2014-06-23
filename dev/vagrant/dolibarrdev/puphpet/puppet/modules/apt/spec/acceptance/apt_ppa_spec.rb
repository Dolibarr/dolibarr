require 'spec_helper_acceptance'

if fact('operatingsystem') == 'Ubuntu'
  describe 'apt::ppa', :unless => UNSUPPORTED_PLATFORMS.include?(fact('osfamily')) do

    context 'reset' do
      it 'removes ppa' do
        shell('rm /etc/apt/sources.list.d/canonical-kernel-team-ppa-*', :acceptable_exit_codes => [0,1,2])
        shell('rm /etc/apt/sources.list.d/raravena80-collectd5-*', :acceptable_exit_codes => [0,1,2])
      end
    end

    context 'adding a ppa that doesnt exist' do
      it 'should work with no errors' do
        pp = <<-EOS
        include '::apt'
        apt::ppa { 'ppa:canonical-kernel-team/ppa': }
        EOS

        apply_manifest(pp, :catch_failures => true)
      end

      describe 'contains the source file' do
        it 'contains a kernel ppa source' do
          shell('ls /etc/apt/sources.list.d/canonical-kernel-team-ppa-*', :acceptable_exit_codes => [0])
        end
      end
    end

    context 'reading a removed ppa.' do
      it 'setup' do
        # This leaves a blank file
        shell('echo > /etc/apt/sources.list.d/raravena80-collectd5-$(lsb_release -c -s).list')
      end

      it 'should read it successfully' do
        pp = <<-EOS
        include '::apt'
        apt::ppa { 'ppa:raravena80/collectd5': }
        EOS

        apply_manifest(pp, :catch_failures => true)
      end
    end

    context 'reset' do
      it 'removes added ppas' do
        shell('rm /etc/apt/sources.list.d/canonical-kernel-team-ppa-*')
        shell('rm /etc/apt/sources.list.d/raravena80-collectd5-*')
      end
    end

    context 'release' do
      context 'precise' do
        it 'works without failure' do
          pp = <<-EOS
          include '::apt'
          apt::ppa { 'ppa:canonical-kernel-team/ppa':
            release => precise,
          }
          EOS

          shell('rm -rf /etc/apt/sources.list.d/canonical-kernel-team-ppa*', :acceptable_exit_codes => [0,1,2])
          apply_manifest(pp, :catch_failures => true)
        end

        describe file('/etc/apt/sources.list.d/canonical-kernel-team-ppa-precise.list') do
          it { should be_file }
        end
      end
    end

    context 'options' do
      context '-y', :unless => default[:platform].match(/10\.04/) do
        it 'works without failure' do
          pp = <<-EOS
          include '::apt'
          apt::ppa { 'ppa:canonical-kernel-team/ppa':
            release => precise,
            options => '-y',
          }
          EOS

          shell('rm -rf /etc/apt/sources.list.d/canonical-kernel-team-ppa*', :acceptable_exit_codes => [0,1,2])
          apply_manifest(pp, :catch_failures => true)
        end

        describe file('/etc/apt/sources.list.d/canonical-kernel-team-ppa-precise.list') do
          it { should be_file }
        end
      end
    end

    context 'reset' do
      it { shell('rm -rf /etc/apt/sources.list.d/canonical-kernel-team-ppa*', :acceptable_exit_codes => [0,1,2]) }
    end
  end
end
