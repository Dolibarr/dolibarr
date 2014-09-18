require "#{File.join(File.dirname(__FILE__),'..','spec_helper.rb')}"

describe 'puppi::run' do

  let(:title) { 'myapp' }
  let(:node) { 'rspec.example42.com' }
  let(:params) {
    { 
      'project'  =>  'myapp',
    }
  }

  describe 'Test puppi run exe creation' do
    it { should contain_exec('Run_Puppi_myapp').with_command(/puppi deploy myapp/) }
  end

end
