require "#{File.join(File.dirname(__FILE__),'..','spec_helper.rb')}"

describe 'bool2ensure' do

  describe 'Test true2present' do
    it { should run.with_params(true).and_return('present') }
    it { should run.with_params('true').and_return('present') }
    it { should run.with_params('yes').and_return('present') }
    it { should run.with_params('y').and_return('present') }
  end

  describe 'Test false2absent' do
    it { should run.with_params(false).and_return('absent') }
    it { should run.with_params('false').and_return('absent') }
    it { should run.with_params('no').and_return('absent') }
    it { should run.with_params('n').and_return('absent') }
  end


end
