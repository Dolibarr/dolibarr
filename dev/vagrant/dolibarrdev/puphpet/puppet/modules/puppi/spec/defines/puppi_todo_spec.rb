require "#{File.join(File.dirname(__FILE__),'..','spec_helper.rb')}"

describe 'puppi::todo' do

  let(:title) { 'mytodo' }
  let(:node) { 'rspec.example42.com' }
  let(:params) {
    { 'notes'         =>  'Test Notes',
      'description'   =>  'Test Description',
      'check_command' =>  'check_test',
      'run'           =>  'test',
    }
  }

  describe 'Test puppi todo file creation' do
    it 'should create a puppi::todo file' do
      should contain_file('/etc/puppi/todo/mytodo').with_ensure('present')
    end
    it 'should populate correctly the puppi::todo step file' do
      should contain_file('/etc/puppi/todo/mytodo').with_content(/check_test/)
    end
  end

end
