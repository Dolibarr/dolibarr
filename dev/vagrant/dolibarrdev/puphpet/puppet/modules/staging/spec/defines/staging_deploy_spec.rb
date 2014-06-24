require 'spec_helper'
describe 'staging::deploy', :type => :define do

  # forcing a more sane caller_module_name to match real usage.
  let(:facts) { { :caller_module_name => 'spec',
                  :osfamily           => 'RedHat',
                  :path               => '/usr/local/bin:/usr/bin:/bin', } }

  describe 'when deploying tar.gz' do
    let(:title) { 'sample.tar.gz' }
    let(:params) { { :source => 'puppet:///modules/staging/sample.tar.gz',
      :target => '/usr/local' } }

    it {
      should contain_file('/opt/staging')
      should contain_file('/opt/staging/spec/sample.tar.gz')
      should contain_exec('extract sample.tar.gz').with({
        :command => 'tar xzf /opt/staging/spec/sample.tar.gz',
        :path    => '/usr/local/bin:/usr/bin:/bin',
        :cwd     => '/usr/local',
        :creates => '/usr/local/sample'
      })
    }
  end

end
