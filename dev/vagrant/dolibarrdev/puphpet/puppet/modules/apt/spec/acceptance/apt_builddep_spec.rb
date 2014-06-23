require 'spec_helper_acceptance'

describe 'apt::builddep', :unless => UNSUPPORTED_PLATFORMS.include?(fact('osfamily')) do

  context 'reset' do
    it 'removes packages' do
      shell('apt-get -y remove znc')
      shell('apt-get -y remove g++')
    end
  end

  context 'apt::builddep' do
    it 'should work with no errors' do
      pp = <<-EOS
      include '::apt'
      apt::builddep { 'znc': }
      EOS

      apply_manifest(pp, :catch_failures => true)
    end

    describe 'should install g++ as a dependency' do
      describe package('g++') do
        it { should be_installed }
      end
    end
  end

  context 'reset' do
    it 'removes packages' do
      shell('apt-get -y remove znc')
      shell('apt-get -y remove g++')
    end
  end

end
