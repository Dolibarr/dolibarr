require 'spec_helper'

describe 'array2csv' do
  it { should run.with_params(['value1']).and_return('value1') }
  it { should run.with_params(['value1', 'value2', 'value3']).and_return('value1,value2,value3') }
  it { should run.with_params('foo').and_raise_error(Puppet::ParseError) }
  it { should run.with_params().and_raise_error(Puppet::ParseError) }
end