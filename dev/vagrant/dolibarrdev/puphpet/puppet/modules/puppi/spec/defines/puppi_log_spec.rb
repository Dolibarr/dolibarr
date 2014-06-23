require "#{File.join(File.dirname(__FILE__),'..','spec_helper.rb')}"

describe 'puppi::log' do

  let(:title) { 'mylog' }
  let(:node) { 'rspec.example42.com' }
  let(:params) {
    { 'log'         =>  '/var/log/mylog.log',
      'description' =>  'My Log',
    }
  }

  describe 'Test puppi log file creation' do
    it 'should create a puppi::log file' do
      should contain_file('/etc/puppi/logs/mylog').with_ensure('present')
    end
    it 'should populate correctly the puppi::log step file' do
      should contain_file('/etc/puppi/logs/mylog').with_content(/mylog.log/)
    end
  end

end
