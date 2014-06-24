require "#{File.join(File.dirname(__FILE__),'..','spec_helper.rb')}"

describe 'puppi' do

  let(:node) { 'rspec.example42.com' }
  let(:node) { 'rspec.example42.com' }
  let(:facts) { { :ipaddress => '10.42.42.42' } }

  describe 'Test standard installation' do
    it { should contain_file('puppi').with_ensure('present') }
    it { should contain_file('puppi.conf').with_ensure('present') }
    it { should contain_file('puppi.scripts').with_ensure('present') }
    it { should contain_file('puppi_basedir').with_ensure('directory') }
    it { should contain_file('puppi_datadir').with_ensure('directory') }
  end

end
