require "#{File.join(File.dirname(__FILE__),'..','spec_helper.rb')}"

describe 'puppi::initialize' do

  let(:title) { 'puppi::initialize' }
  let(:node) { 'rspec.example42.com' }
  let(:params) {
    { 'enable'   =>  'true',
      'name'     =>  'get',
      'command'  =>  'echo',
      'priority' =>  '50',
      'project'  =>  'myapp',
    }
  }

  describe 'Test puppi initialize step file creation' do
    it 'should create a puppi::initialize step file' do
      should contain_file('/etc/puppi/projects/myapp/initialize/50-get').with_ensure('present')
    end
    it 'should populate correctly the puppi::initialize step file' do
      should contain_file('/etc/puppi/projects/myapp/initialize/50-get').with_content("su - root -c \"export project=myapp && /etc/puppi/scripts/echo \"\n")
    end
  end

end
