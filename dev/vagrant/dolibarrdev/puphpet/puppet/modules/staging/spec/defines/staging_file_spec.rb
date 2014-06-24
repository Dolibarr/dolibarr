require 'spec_helper'
describe 'staging::file', :type => :define do

  # forcing a more sane caller_module_name to match real usage.
  let(:facts) { { :caller_module_name=> 'spec' } }

  describe 'when deploying via puppet' do
    let(:title) { 'sample.tar.gz' }
    let(:params) { { :source => 'puppet:///modules/staging/sample.tar.gz' } }

    it {
      should contain_file('/opt/staging')
      should contain_file('/opt/staging/spec/sample.tar.gz')
      should_not contain_exec('/opt/staging/spec/sample.tar.gz')
    }
  end

  describe 'when deploying via local' do
    let(:title) { 'sample.tar.gz' }
    let(:params) { { :source => '/nfs/sample.tar.gz',
      :target => '/usr/local/sample.tar.gz',
    } }

    it {
      should contain_file('/opt/staging')
      should contain_file('/usr/local/sample.tar.gz')
      should_not contain_exec('/opt/staging/spec/sample.tar.gz')
    }
  end

  describe 'when deploying via http' do
    let(:title) { 'sample.tar.gz' }
    let(:params) { { :source => 'http://webserver/sample.tar.gz' } }

    it {
      should contain_file('/opt/staging')
      should contain_exec('/opt/staging/spec/sample.tar.gz').with( {
        :command => 'curl  -f -L -o sample.tar.gz http://webserver/sample.tar.gz',
        :path        => '/usr/local/bin:/usr/bin:/bin',
        :environment => nil,
        :cwd         => '/opt/staging/spec',
        :creates     => '/opt/staging/spec/sample.tar.gz',
        :logoutput   => 'on_failure',
      })
    }
  end

  describe 'when deploying via http with custom curl options' do
    let(:title) { 'sample.tar.gz' }
    let(:params) { {
      :source => 'http://webserver/sample.tar.gz',
      :curl_option => '-b',
    } }

    it {
      should contain_file('/opt/staging')
      should contain_exec('/opt/staging/spec/sample.tar.gz').with( {
        :command => 'curl -b -f -L -o sample.tar.gz http://webserver/sample.tar.gz',
        :path        => '/usr/local/bin:/usr/bin:/bin',
        :environment => nil,
        :cwd         => '/opt/staging/spec',
        :creates     => '/opt/staging/spec/sample.tar.gz',
        :logoutput   => 'on_failure',
      })
    }
  end

  describe 'when deploying via http with parameters' do
    let(:title) { 'sample.tar.gz' }
    let(:params) { { :source => 'http://webserver/sample.tar.gz',
      :target => '/usr/local/sample.tar.gz',
    } }

    it { should contain_file('/opt/staging')
      should contain_exec('/usr/local/sample.tar.gz').with( {
        :command => 'curl  -f -L -o sample.tar.gz http://webserver/sample.tar.gz',
        :path        => '/usr/local/bin:/usr/bin:/bin',
        :environment => nil,
        :cwd         => '/usr/local',
        :creates     => '/usr/local/sample.tar.gz',
      })
    }
  end

  describe 'when deploying via https' do
     let(:title) { 'sample.tar.gz' }
     let(:params) { { :source => 'https://webserver/sample.tar.gz' } }

     it { should contain_file('/opt/staging') }
     it { should contain_exec('/opt/staging/spec/sample.tar.gz').with( {
       :command => 'curl  -f -L -o sample.tar.gz https://webserver/sample.tar.gz',
       :path        => '/usr/local/bin:/usr/bin:/bin',
       :environment => nil,
       :cwd         => '/opt/staging/spec',
       :creates     => '/opt/staging/spec/sample.tar.gz',
       :logoutput   => 'on_failure',
     }) }
  end

  describe 'when deploying via https with parameters' do
    let(:title) { 'sample.tar.gz' }
    let(:params) { { :source => 'https://webserver/sample.tar.gz',
      :username => 'puppet',
      :password => 'puppet',
    } }

    it {
      should contain_file('/opt/staging')
      should contain_exec('/opt/staging/spec/sample.tar.gz').with( {
        :command => 'curl  -f -L -o sample.tar.gz -u puppet:puppet https://webserver/sample.tar.gz',
        :path        => '/usr/local/bin:/usr/bin:/bin',
        :environment => nil,
        :cwd         => '/opt/staging/spec',
        :creates     => '/opt/staging/spec/sample.tar.gz',
        :logoutput   => 'on_failure',
      })
    }
  end

  describe 'when deploying via ftp' do
    let(:title) { 'sample.tar.gz' }
    let(:params) { { :source => 'ftp://webserver/sample.tar.gz' } }

    it {
      should contain_file('/opt/staging')
      should contain_exec('/opt/staging/spec/sample.tar.gz').with( {
        :command => 'curl  -o sample.tar.gz ftp://webserver/sample.tar.gz',
        :path        => '/usr/local/bin:/usr/bin:/bin',
        :environment => nil,
        :cwd         => '/opt/staging/spec',
        :creates     => '/opt/staging/spec/sample.tar.gz',
        :logoutput   => 'on_failure',
      })
    }
  end

  describe 'when deploying via ftp with parameters' do
    let(:title) { 'sample.tar.gz' }
    let(:params) { { :source => 'ftp://webserver/sample.tar.gz',
      :username => 'puppet',
      :password => 'puppet',
    } }

    it {
      should contain_file('/opt/staging')
      should contain_exec('/opt/staging/spec/sample.tar.gz').with( {
        :command => 'curl  -o sample.tar.gz -u puppet:puppet ftp://webserver/sample.tar.gz',
        :path        => '/usr/local/bin:/usr/bin:/bin',
        :environment => nil,
        :cwd         => '/opt/staging/spec',
        :creates     => '/opt/staging/spec/sample.tar.gz',
        :logoutput   => 'on_failure',
      })
    }
  end

end
