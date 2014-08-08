require 'spec_helper'

describe 'the mysql_password function' do
  before :all do
    Puppet::Parser::Functions.autoloader.loadall
  end

  let(:scope) { PuppetlabsSpec::PuppetInternals.scope }

  it 'should exist' do
    Puppet::Parser::Functions.function('mysql_password').should == 'function_mysql_password'
  end

  it 'should raise a ParseError if there is less than 1 arguments' do
    lambda { scope.function_mysql_password([]) }.should( raise_error(Puppet::ParseError))
  end

  it 'should raise a ParseError if there is more than 1 arguments' do
    lambda { scope.function_mysql_password(%w(foo bar)) }.should( raise_error(Puppet::ParseError))
  end

  it 'should convert password into a hash' do
    result = scope.function_mysql_password(%w(password))
    result.should(eq('*2470C0C06DEE42FD1618BB99005ADCA2EC9D1E19'))
  end

end
