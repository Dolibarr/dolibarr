require 'spec_helper_acceptance'

describe 'apt class', :unless => UNSUPPORTED_PLATFORMS.include?(fact('osfamily')) do

  context 'reset' do
    it 'fixes the sources.list' do
      shell('cp /etc/apt/sources.list /tmp')
    end
  end

  context 'always_apt_update => true' do
    it 'should work with no errors' do
      pp = <<-EOS
      class { 'apt': always_apt_update => true }
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/apt_update/)
      end
    end
  end
  context 'always_apt_update => false' do
    it 'should work with no errors' do
      pp = <<-EOS
      class { 'apt': always_apt_update => false }
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to_not match(/apt_update/)
      end
    end
  end

  # disable_keys drops in a 99unauth file to ignore keys in
  # other files.
  context 'disable_keys => true' do
    it 'should work with no errors' do
      pp = <<-EOS
      class { 'apt': disable_keys => true }
      EOS

      apply_manifest(pp, :catch_failures => true)
    end

    describe file('/etc/apt/apt.conf.d/99unauth') do
      it { should be_file }
      it { should contain 'APT::Get::AllowUnauthenticated 1;' }
    end
  end
  context 'disable_keys => false' do
    it 'should work with no errors' do
      pp = <<-EOS
      class { 'apt': disable_keys => false }
      EOS

      apply_manifest(pp, :catch_failures => true)
    end

    describe file('/etc/apt/apt.conf.d/99unauth') do
      it { should_not be_file }
    end
  end

  # proxy_host sets the proxy to use for transfers.
  # proxy_port sets the proxy port to use.
  context 'proxy settings' do
    it 'should work with no errors' do
      pp = <<-EOS
      class { 'apt': 
        proxy_host => 'localhost',
        proxy_port => '7042',
      }
      EOS

      apply_manifest(pp, :catch_failures => true)
    end

    describe file('/etc/apt/apt.conf.d/proxy') do
      it { should be_file }
      it { should contain 'Acquire::http::Proxy "http://localhost:7042\";' }
    end
  end

  context 'purge_sources' do
    it 'creates a fake apt file' do
      shell('touch /etc/apt/sources.list.d/fake.list')
      shell('echo "deb fake" >> /etc/apt/sources.list')
    end
    it 'purge_sources_list and purge_sources_list_d => true' do
      pp = <<-EOS
      class { 'apt':
        purge_sources_list   => true,
        purge_sources_list_d => true,
      }
      EOS

      apply_manifest(pp, :catch_failures => true)
    end

    describe file('/etc/apt/sources.list') do
      it { should_not contain 'deb fake' }
    end

    describe file('/etc/apt/sources.list.d/fake.list') do
      it { should_not be_file }
    end
  end
  context 'proxy settings' do
    it 'should work with no errors' do
      pp = <<-EOS
      class { 'apt': 
        proxy_host => 'localhost',
        proxy_port => '7042',
      }
      EOS

      apply_manifest(pp, :catch_failures => true)
    end

    describe file('/etc/apt/apt.conf.d/proxy') do
      it { should be_file }
      it { should contain 'Acquire::http::Proxy "http://localhost:7042\";' }
    end
  end

  context 'purge_sources' do
    context 'false' do
      it 'creates a fake apt file' do
        shell('touch /etc/apt/sources.list.d/fake.list')
        shell('echo "deb fake" >> /etc/apt/sources.list')
      end
      it 'purge_sources_list and purge_sources_list_d => false' do
        pp = <<-EOS
        class { 'apt':
          purge_sources_list   => false,
          purge_sources_list_d => false,
        }
        EOS

        apply_manifest(pp, :catch_failures => false)
      end

      describe file('/etc/apt/sources.list') do
        it { should contain 'deb fake' }
      end

      describe file('/etc/apt/sources.list.d/fake.list') do
        it { should be_file }
      end
    end

    context 'true' do
      it 'creates a fake apt file' do
        shell('touch /etc/apt/sources.list.d/fake.list')
        shell('echo "deb fake" >> /etc/apt/sources.list')
      end
      it 'purge_sources_list and purge_sources_list_d => true' do
        pp = <<-EOS
        class { 'apt':
          purge_sources_list   => true,
          purge_sources_list_d => true,
        }
        EOS

        apply_manifest(pp, :catch_failures => true)
      end

      describe file('/etc/apt/sources.list') do
        it { should_not contain 'deb fake' }
      end

      describe file('/etc/apt/sources.list.d/fake.list') do
        it { should_not be_file }
      end
    end
  end

  context 'purge_preferences_d' do
    context 'false' do
      it 'creates a preferences file' do
        shell('touch /etc/apt/preferences.d/test')
      end

      it 'should work with no errors' do
        pp = <<-EOS
        class { 'apt': purge_preferences_d => false }
        EOS

        apply_manifest(pp, :catch_failures => true)
      end

      describe file('/etc/apt/preferences.d/test') do
        it { should be_file }
      end
    end
    context 'true' do
      it 'creates a preferences file' do
        shell('touch /etc/apt/preferences.d/test')
      end

      it 'should work with no errors' do
        pp = <<-EOS
        class { 'apt': purge_preferences_d => true }
        EOS

        apply_manifest(pp, :catch_failures => true)
      end

      describe file('/etc/apt/preferences.d/test') do
        it { should_not be_file }
      end
    end
  end

  context 'update_timeout' do
    context '5000' do
      it 'should work with no errors' do
        pp = <<-EOS
        class { 'apt': update_timeout => '5000' }
        EOS

        apply_manifest(pp, :catch_failures => true)
      end
    end
  end

  context 'reset' do
    it 'fixes the sources.list' do
      shell('cp /tmp/sources.list /etc/apt')
    end
  end

end
