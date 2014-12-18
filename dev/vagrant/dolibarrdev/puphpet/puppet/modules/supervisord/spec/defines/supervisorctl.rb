require 'spec_helper'

describe 'supervisord::supervisorctl', :type => :define do
  let(:title) {'command_foo'}
  let(:default_params) {{ 
    :command => 'command',
    :process => 'foo'
  }}
  let(:params) { default_params }
  let(:facts) {{ :concat_basedir => '/var/lib/puppet/concat' }}

  it { should contain_supervisord__supervisorctl('command_foo') }
end
