require "#{File.join(File.dirname(__FILE__),'..','spec_helper.rb')}"

describe 'puppi::info' do

  let(:title) { 'puppi::info' }
  let(:node) { 'rspec.example42.com' }
  let(:params) {
    { 'name'         =>  'sample',
      'description'  =>  'Sample Info',
      'templatefile' =>  'puppi/info.erb',
      'run'          =>  'myownscript',
    }
  }

  describe 'Test puppi info step file creation' do
    it 'should create a puppi::info step file' do
      should contain_file('/etc/puppi/info/sample').with_ensure('present')
    end
    it 'should populate correctly the puppi::info step file' do
      should contain_file('/etc/puppi/info/sample').with_content(/myownscript/)
    end
  end

end
