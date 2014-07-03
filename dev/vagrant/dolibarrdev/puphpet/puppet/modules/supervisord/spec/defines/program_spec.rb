require 'spec_helper'

describe 'supervisord::program', :type => :define do
  let(:title) {'foo'}
  let(:default_params) {{ :command => 'bar',
                  :stdout_logfile  => '/var/log/supervisor/program_foo.log',
                  :stderr_logfile  => '/var/log/supervisor/program_foo.error',
                  :user            => 'baz'
  }}
  let(:params) { default_params }
  let(:facts) {{ :concat_basedir => '/var/lib/puppet/concat' }}

  it { should contain_supervisord__program('foo') }
  it { should contain_file('/etc/supervisor.d/program_foo.conf').with_content(/\[program:foo\]/) }
  it { should contain_file('/etc/supervisor.d/program_foo.conf').with_content(/command=bar/) }
  it { should contain_file('/etc/supervisor.d/program_foo.conf').with_content(/user=baz/) }
  it { should contain_file('/etc/supervisor.d/program_foo.conf').with_content(/stdout_logfile=\/var\/log\/supervisor\/program_foo.log/) }
  it { should contain_file('/etc/supervisor.d/program_foo.conf').with_content(/stderr_logfile=\/var\/log\/supervisor\/program_foo.error/) }

end
