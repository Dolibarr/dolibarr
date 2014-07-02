require "#{File.join(File.dirname(__FILE__),'..','spec_helper.rb')}"

describe 'puppi::helper' do

  let(:title) { 'spec' }
  let(:node) { 'rspec.example42.com' }
  let(:params) {
    { 'template'   => 'puppi/helpers/standard.yml.erb'  }
  }

  describe 'Test puppi helper file creation' do
    it 'should create a puppi helper file' do
      should contain_file('puppi_helper_spec').with_ensure('present')
    end
    it 'should populate correctly the helper file' do
      should contain_file('puppi_helper_spec').with_content(/info/)
    end
  end

end
