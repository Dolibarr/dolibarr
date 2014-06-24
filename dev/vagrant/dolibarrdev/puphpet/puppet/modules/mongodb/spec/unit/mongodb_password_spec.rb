require 'spec_helper'

describe 'the mongodb_password function' do
  before :all do
    Puppet::Parser::Functions.autoloader.loadall
  end

  let(:scope) { PuppetlabsSpec::PuppetInternals.scope }

  it 'should exist' do
    Puppet::Parser::Functions.function('mongodb_password').should == 'function_mongodb_password'
  end

  it 'should raise a ParseError if there no arguments' do
    lambda { scope.function_mongodb_password([]) }.should( raise_error(Puppet::ParseError))
  end

  it 'should raise a ParseError if there is more than 2 arguments' do
    lambda { scope.function_mongodb_password(%w(foo bar baz)) }.should( raise_error(Puppet::ParseError))
  end

  it 'should convert password into a hash' do
    result = scope.function_mongodb_password(%w(user pass))
    result.should(eq('e0c4a7b97d4db31f5014e9694e567d6b'))
  end

end
