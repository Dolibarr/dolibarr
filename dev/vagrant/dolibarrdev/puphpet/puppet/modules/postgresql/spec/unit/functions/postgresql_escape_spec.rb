require 'spec_helper'

describe 'postgresql_escape', :type => :puppet_function do
  it { should run.with_params('foo').
    and_return('$$foo$$') }
end
describe 'postgresql_escape', :type => :puppet_function do
  it { should run.with_params('fo$$o').
    and_return('$ed$fo$$o$ed$') }
end
