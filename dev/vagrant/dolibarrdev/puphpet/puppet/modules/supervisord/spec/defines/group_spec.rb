require 'spec_helper'

describe 'supervisord::group', :type => :define do
  let(:title) {'foo'}
  let(:params) {{ :programs => ['bar', 'baz'] }}
  let(:facts) {{ :concat_basedir => '/var/lib/puppet/concat' }}

  it { should contain_supervisord__group('foo').with_program }
  it { should contain_file('/etc/supervisor.d/group_foo.conf').with_content(/programs=bar,baz/) }

  describe '#priority' do
    it 'should default to undef' do
      should_not contain_file('/etc/supervisor.d/group_foo.conf').with_content(/priority/)
      should contain_file('/etc/supervisor.d/group_foo.conf').with_content(/programs=bar,baz/)
    end
    context '100' do
      let(:params) {{ :priority => '100', :programs => ['bar', 'baz'] }}
      it { should contain_file('/etc/supervisor.d/group_foo.conf').with_content(/priority=100/) }
      it { should contain_file('/etc/supervisor.d/group_foo.conf').with_content(/programs=bar,baz/) }
    end
  end
end