require 'spec_helper_acceptance'

describe 'apt::pin define', :unless => UNSUPPORTED_PLATFORMS.include?(fact('osfamily')) do
  context 'defaults' do
    it 'should work with no errors' do
      pp = <<-EOS
      include apt
      apt::pin { 'vim-puppet': }
      EOS

      apply_manifest(pp, :catch_failures => true)
    end

    describe file('/etc/apt/preferences.d/vim-puppet.pref') do
      it { should be_file }
      it { should contain 'Pin: release a=vim-puppet' }
    end
  end

  context 'ensure' do
    context 'present' do
      it 'should work with no errors' do
        pp = <<-EOS
        include apt
        apt::pin { 'vim-puppet': ensure => present }
        EOS

        apply_manifest(pp, :catch_failures => true)
      end

      describe file('/etc/apt/preferences.d/vim-puppet.pref') do
        it { should be_file }
        it { should contain 'Pin: release a=vim-puppet' }
      end
    end

    context 'absent' do
      it 'should work with no errors' do
        pp = <<-EOS
        include apt
        apt::pin { 'vim-puppet': ensure => absent }
        EOS

        apply_manifest(pp, :catch_failures => true)
      end

      describe file('/etc/apt/preferences.d/vim-puppet.pref') do
        it { should_not be_file }
      end
    end
  end

  context 'order' do
    context '99' do
      it 'should work with no errors' do
        pp = <<-EOS
        include apt
        apt::pin { 'vim-puppet':
          ensure => present,
          order  => '99',
        }
        EOS

        apply_manifest(pp, :catch_failures => true)
      end

      describe file('/etc/apt/preferences.d/99-vim-puppet.pref') do
        it { should be_file }
        it { should contain 'Pin: release a=vim-puppet' }
      end
    end
  end

  context 'packages' do
    context 'test' do
      it 'should work with no errors' do
        pp = <<-EOS
        include apt
        apt::pin { 'vim-puppet':
          ensure   => present,
          packages => 'test',
        }
        EOS

        apply_manifest(pp, :catch_failures => true)
      end

      describe file('/etc/apt/preferences.d/vim-puppet.pref') do
        it { should be_file }
        it { should contain 'Package: test' }
        it { should contain 'Pin: release a=vim-puppet' }
      end
    end
  end

  context 'release' do
    context 'testrelease' do
      it 'should work with no errors' do
        pp = <<-EOS
        include apt
        apt::pin { 'vim-puppet':
          ensure  => present,
          release => 'testrelease',
        }
        EOS

        apply_manifest(pp, :catch_failures => true)
      end

      describe file('/etc/apt/preferences.d/vim-puppet.pref') do
        it { should be_file }
        it { should contain 'Pin: release a=testrelease' }
      end
    end
  end

  context 'origin' do
    context 'testrelease' do
      it 'should work with no errors' do
        pp = <<-EOS
        include apt
        apt::pin { 'vim-puppet':
          ensure  => present,
          origin  => 'testrelease',
        }
        EOS

        apply_manifest(pp, :catch_failures => true)
      end

      describe file('/etc/apt/preferences.d/vim-puppet.pref') do
        it { should be_file }
        it { should contain 'Pin: origin testrelease' }
      end
    end
  end

  context 'version' do
    context '1.0.0' do
      it 'should work with no errors' do
        pp = <<-EOS
        include apt
        apt::pin { 'vim-puppet':
          ensure   => present,
          packages => 'test',
          version  => '1.0.0',
        }
        EOS

        apply_manifest(pp, :catch_failures => true)
      end

      describe file('/etc/apt/preferences.d/vim-puppet.pref') do
        it { should be_file }
        it { should contain 'Package: test' }
        it { should contain 'Pin: version 1.0.0' }
      end
    end
  end

  context 'codename' do
    context 'testname' do
      it 'should work with no errors' do
        pp = <<-EOS
        include apt
        apt::pin { 'vim-puppet':
          ensure   => present,
          codename => 'testname',
        }
        EOS

        apply_manifest(pp, :catch_failures => true)
      end

      describe file('/etc/apt/preferences.d/vim-puppet.pref') do
        it { should be_file }
        it { should contain 'Pin: release n=testname' }
      end
    end
  end

  context 'release_version' do
    context '1.1.1' do
      it 'should work with no errors' do
        pp = <<-EOS
        include apt
        apt::pin { 'vim-puppet':
          ensure          => present,
          release_version => '1.1.1',
        }
        EOS

        apply_manifest(pp, :catch_failures => true)
      end

      describe file('/etc/apt/preferences.d/vim-puppet.pref') do
        it { should be_file }
        it { should contain 'Pin: release v=1.1.1' }
      end
    end
  end

  context 'component' do
    context 'testcomponent' do
      it 'should work with no errors' do
        pp = <<-EOS
        include apt
        apt::pin { 'vim-puppet':
          ensure    => present,
          component => 'testcomponent',
        }
        EOS

        apply_manifest(pp, :catch_failures => true)
      end

      describe file('/etc/apt/preferences.d/vim-puppet.pref') do
        it { should be_file }
        it { should contain 'Pin: release c=testcomponent' }
      end
    end
  end

  context 'originator' do
    context 'testorigin' do
      it 'should work with no errors' do
        pp = <<-EOS
        include apt
        apt::pin { 'vim-puppet':
          ensure     => present,
          originator => 'testorigin',
        }
        EOS

        apply_manifest(pp, :catch_failures => true)
      end

      describe file('/etc/apt/preferences.d/vim-puppet.pref') do
        it { should be_file }
        it { should contain 'Pin: release o=testorigin' }
      end
    end
  end

  context 'label' do
    context 'testlabel' do
      it 'should work with no errors' do
        pp = <<-EOS
        include apt
        apt::pin { 'vim-puppet':
          ensure => present,
          label  => 'testlabel',
        }
        EOS

        apply_manifest(pp, :catch_failures => true)
      end

      describe file('/etc/apt/preferences.d/vim-puppet.pref') do
        it { should be_file }
        it { should contain 'Pin: release l=testlabel' }
      end
    end
  end

end
