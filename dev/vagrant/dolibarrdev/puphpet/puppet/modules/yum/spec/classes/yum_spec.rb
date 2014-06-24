require "#{File.join(File.dirname(__FILE__),'..','spec_helper.rb')}"

describe 'yum' do

  let(:title) { 'yum' }
  let(:node) { 'rspec.example42.com' }
  let(:facts) { { :ipaddress => '10.42.42.42' } }

  describe 'Test minimal installation' do
    it { should contain_file('yum.conf').with_ensure('present') }
  end

  describe 'Test decommissioning - absent' do
    let(:params) { {:absent => true } }
    it 'should remove yum configuration file' do should contain_file('yum.conf').with_ensure('absent') end
  end

  describe 'Test customizations - source' do
    let(:params) { {:source => "puppet:///modules/yum/spec"} }
    it { should contain_file('yum.conf').with_source('puppet:///modules/yum/spec') }
  end

end
