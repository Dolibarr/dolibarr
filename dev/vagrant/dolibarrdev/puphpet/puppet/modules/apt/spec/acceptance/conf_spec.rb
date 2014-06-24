require 'spec_helper_acceptance'

describe 'apt::conf define', :unless => UNSUPPORTED_PLATFORMS.include?(fact('osfamily')) do
  context 'defaults' do
    it 'should work with no errors' do
      pp = <<-EOS
      apt::conf { 'test':
        content => 'test',
      }
      EOS

      apply_manifest(pp, :catch_failures => true)
    end

    describe file('/etc/apt/apt.conf.d/50test') do
      it { should be_file }
      it { should contain 'test' }
    end
  end

  context 'ensure' do
    context 'absent' do
      it 'should work with no errors' do
        pp = <<-EOS
        apt::conf { 'test':
          ensure  => absent,
          content => 'test',
        }
        EOS

        apply_manifest(pp, :catch_failures => true)
      end

      describe file('/etc/apt/apt.conf.d/50test') do
        it { should_not be_file }
      end
    end
  end

  context 'priority' do
    context '99' do
      it 'should work with no errors' do
        pp = <<-EOS
        apt::conf { 'test':
          ensure   => present,
          content  => 'test',
          priority => '99',
        }
        EOS

        apply_manifest(pp, :catch_failures => true)
      end

      describe file('/etc/apt/apt.conf.d/99test') do
        it { should be_file }
        it { should contain 'test' }
      end
    end
  end

  context 'cleanup' do
    it 'deletes 99test' do
      shell ('rm -rf /etc/apt/apt.conf.d/99test')
    end
  end
end
