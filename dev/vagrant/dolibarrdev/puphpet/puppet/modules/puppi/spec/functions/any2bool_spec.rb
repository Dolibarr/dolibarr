require "#{File.join(File.dirname(__FILE__),'..','spec_helper.rb')}"

describe 'any2bool' do

  describe 'Test Any2True' do
    it { should run.with_params(true).and_return(true) }
    it { should run.with_params('true').and_return(true) }
    it { should run.with_params('yes').and_return(true) }
    it { should run.with_params('y').and_return(true) }
  end

  describe 'Test Any2false' do
    it { should run.with_params(false).and_return(false) }
    it { should run.with_params('false').and_return(false) }
    it { should run.with_params('no').and_return(false) }
    it { should run.with_params('n').and_return(false) }
  end


end
