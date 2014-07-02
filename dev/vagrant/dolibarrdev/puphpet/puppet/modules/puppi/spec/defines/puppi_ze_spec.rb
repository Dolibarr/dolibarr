require "#{File.join(File.dirname(__FILE__),'..','spec_helper.rb')}"

describe 'puppi::ze' do

  let(:title) { 'sample' }
  let(:node) { 'rspec.example42.com' }
  let(:params) {
    { 'helper'     => 'mytest',
      'variables'  => { 'var1' => 'get', 'var2' => 'got' },
      'name'       => 'sample',
    }
  }

  describe 'Test puppi ze data file creation' do
    it 'should create a puppi::ze step file' do
      should contain_file('puppize_sample').with_ensure('present')
    end
  end

end
