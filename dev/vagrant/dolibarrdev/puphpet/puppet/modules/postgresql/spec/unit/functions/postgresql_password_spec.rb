require 'spec_helper'

describe 'postgresql_password', :type => :puppet_function do
  it { should run.with_params('foo', 'bar').
    and_return('md596948aad3fcae80c08a35c9b5958cd89') }
end
