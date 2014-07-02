require 'spec_helper_acceptance'

describe 'apt::source', :unless => UNSUPPORTED_PLATFORMS.include?(fact('osfamily')) do

  context 'apt::source' do
    context 'ensure => present' do
      it 'should work with no errors' do
        pp = <<-EOS
        include '::apt'
        apt::source { 'puppetlabs':
          ensure     => present,
          location   => 'http://apt.puppetlabs.com',
          repos      => 'main',
          key        => '4BD6EC30',
          key_server => 'pgp.mit.edu',
        }
        EOS

        shell('apt-key del 4BD6EC30', :acceptable_exit_codes => [0,1,2])
        shell('rm /etc/apt/sources.list.d/puppetlabs.list', :acceptable_exit_codes => [0,1,2])
        apply_manifest(pp, :catch_failures => true)
      end

      describe 'key should exist' do
        it 'finds puppetlabs key' do
          shell('apt-key list | grep 4BD6EC30', :acceptable_exit_codes => [0])
        end
      end

      describe file('/etc/apt/sources.list.d/puppetlabs.list') do
        it { should be_file }
      end
    end

    context 'ensure => absent' do
      it 'should work with no errors' do
        pp = <<-EOS
        include '::apt'
        apt::source { 'puppetlabs':
          ensure     => absent,
          location   => 'http://apt.puppetlabs.com',
          repos      => 'main',
          key        => '4BD6EC30',
          key_server => 'pgp.mit.edu',
        }
        EOS

        apply_manifest(pp, :catch_failures => true)
      end

      # The key should remain -we don't delete those when deleting a source.
      describe 'key should exist' do
        it 'finds puppetlabs key' do
          shell('apt-key list | grep 4BD6EC30', :acceptable_exit_codes => [0])
        end
      end
      describe file('/etc/apt/sources.list.d/puppetlabs.list') do
        it { should_not be_file }
      end
    end

  end

  context 'release' do
    context 'test' do
      it 'should work with no errors' do
        pp = <<-EOS
        include '::apt'
        apt::source { 'puppetlabs':
          ensure     => present,
          location   => 'http://apt.puppetlabs.com',
          repos      => 'main',
          key        => '4BD6EC30',
          key_server => 'pgp.mit.edu',
          release    => 'precise',
        }
        EOS

        apply_manifest(pp, :catch_failures => true)
      end

      describe file('/etc/apt/sources.list.d/puppetlabs.list') do
        it { should be_file }
        it { should contain 'deb http://apt.puppetlabs.com precise main' }
      end
    end
  end

  context 'include_src' do
    context 'true' do
      it 'should work with no errors' do
        pp = <<-EOS
        include '::apt'
        apt::source { 'puppetlabs':
          ensure      => present,
          location    => 'http://apt.puppetlabs.com',
          repos       => 'main',
          key         => '4BD6EC30',
          key_server  => 'pgp.mit.edu',
          include_src => true,
        }
        EOS

        apply_manifest(pp, :catch_failures => true)
      end

      describe file('/etc/apt/sources.list.d/puppetlabs.list') do
        it { should be_file }
        it { should contain 'deb-src http://apt.puppetlabs.com' }
      end
    end

    context 'false' do
      it 'should work with no errors' do
        pp = <<-EOS
        include '::apt'
        apt::source { 'puppetlabs':
          ensure      => present,
          location    => 'http://apt.puppetlabs.com',
          repos       => 'main',
          key         => '4BD6EC30',
          key_server  => 'pgp.mit.edu',
          include_src => false,
        }
        EOS

        apply_manifest(pp, :catch_failures => true)
      end

      describe file('/etc/apt/sources.list.d/puppetlabs.list') do
        it { should be_file }
        it { should_not contain 'deb-src http://apt.puppetlabs.com' }
      end
    end
  end

  context 'required_packages' do
    context 'vim' do
      it 'should work with no errors' do
        pp = <<-EOS
        include '::apt'
        apt::source { 'puppetlabs':
          ensure             => present,
          location           => 'http://apt.puppetlabs.com',
          repos              => 'main',
          key                => '4BD6EC30',
          key_server         => 'pgp.mit.edu',
          required_packages  => 'vim',
        }
        EOS

        shell('apt-get -y remove vim')
        apply_manifest(pp, :catch_failures => true)
      end

      describe package('vim') do
        it { should be_installed }
      end
    end
  end

  context 'key content' do
    context 'giant key' do
      it 'should work with no errors' do
        pp = <<-EOS
        include '::apt'
        apt::source { 'puppetlabs':
          ensure      => present,
          location    => 'http://apt.puppetlabs.com',
          repos       => 'main',
          key         => '4BD6EC30',
          key_content => '-----BEGIN PGP PUBLIC KEY BLOCK-----
          Version: GnuPG v1.4.12 (GNU/Linux)
          Comment: GPGTools - http://gpgtools.org

          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
          =a34C
          -----END PGP PUBLIC KEY BLOCK-----',
        }
        EOS

        shell('apt-key del 4BD6EC30', :acceptable_exit_codes => [0,1,2])
        apply_manifest(pp, :catch_failures => true)
      end

      describe file('/etc/apt/sources.list.d/puppetlabs.list') do
        it { should be_file }
      end
      describe 'keys should exist' do
        it 'finds puppetlabs key' do
          shell('apt-key list | grep 4BD6EC30')
        end
      end
    end
  end

  context 'key_source' do
    context 'http://apt.puppetlabs.com/pubkey.gpg' do
      it 'should work with no errors' do
        pp = <<-EOS
        include '::apt'
        apt::source { 'puppetlabs':
          ensure     => present,
          location   => 'http://apt.puppetlabs.com',
          release    => 'precise',
          repos      => 'main',
          key        => '4BD6EC30',
          key_source  => 'http://apt.puppetlabs.com/pubkey.gpg',
        }
        EOS

        shell('apt-key del 4BD6EC30', :acceptable_exit_codes => [0,1,2])
        apply_manifest(pp, :catch_failures => true)
      end

      describe file('/etc/apt/sources.list.d/puppetlabs.list') do
        it { should be_file }
        it { should contain 'deb http://apt.puppetlabs.com precise main' }
      end
      describe 'keys should exist' do
        it 'finds puppetlabs key' do
          shell('apt-key list | grep 4BD6EC30')
        end
      end
    end
  end

  context 'pin' do
    context 'false' do
      it 'should work with no errors' do
        pp = <<-EOS
        include '::apt'
        apt::source { 'puppetlabs':
          ensure     => present,
          location   => 'http://apt.puppetlabs.com',
          repos      => 'main',
          key        => '4BD6EC30',
          key_server => 'pgp.mit.edu',
          pin        => false,
        }
        EOS

        apply_manifest(pp, :catch_failures => true)
      end

      describe file('/etc/apt/preferences.d/puppetlabs.pref') do
        it { should_not be_file }
      end
    end
    context 'true' do
      it 'should work with no errors' do
        pp = <<-EOS
        include '::apt'
        apt::source { 'puppetlabs':
          ensure     => present,
          location   => 'http://apt.puppetlabs.com',
          repos      => 'main',
          key        => '4BD6EC30',
          key_server => 'pgp.mit.edu',
          pin        => true,
        }
        EOS

        apply_manifest(pp, :catch_failures => true)
      end

      describe file('/etc/apt/preferences.d/puppetlabs.pref') do
        it { should be_file }
      end
    end
  end

end
