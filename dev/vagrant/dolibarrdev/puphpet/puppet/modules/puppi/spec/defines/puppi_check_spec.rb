require "#{File.join(File.dirname(__FILE__),'..','spec_helper.rb')}"

describe 'puppi::check' do

  let(:title) { 'puppi::check' }
  let(:node) { 'rspec.example42.com' }
  let(:facts) { { :arch => 'i386' } }
  let(:params) {
    { 'enable'   =>  'true',
      'name'     =>  'get',
      'command'  =>  'echo',
      'priority' =>  '50',
      'project'  =>  'myapp',
    }
  }

  describe 'Test puppi check step file creation' do
    it 'should create a puppi::check step file' do
      should contain_file('Puppi_check_myapp_50_get').with_ensure('present')
    end
    it 'should populate correctly the puppi::check step file' do
      should contain_file('Puppi_check_myapp_50_get').with_content(/\/usr\/lib\/nagios\/plugins\/echo/)
    end
  end

end
