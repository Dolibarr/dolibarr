require 'spec_helper'
describe 'apt::force', :type => :define do
  let(:facts) { { :lsbdistid => 'Debian' } }
  let :pre_condition do
    'include apt::params'
  end

  let :title do
    'my_package'
  end

  let :default_params do
    {
      :release => 'testing',
      :version => false
    }
  end

  describe "when using default parameters" do
    let :params do
      default_params
    end
    it { should contain_exec("/usr/bin/apt-get -y -t #{params[:release]} install #{title}").with(
      :unless => "/usr/bin/test \$(/usr/bin/apt-cache policy -t #{params[:release]} #{title} | /bin/grep -E 'Installed|Candidate' | /usr/bin/uniq -s 14 | /usr/bin/wc -l) -eq 1",
      :timeout => '300'
    ) }
  end

  describe "when specifying false release parameter" do
    let :params do
      default_params.merge(:release => false)
    end
    it { should contain_exec("/usr/bin/apt-get -y  install #{title}").with(
      :unless  => "/usr/bin/dpkg -s #{title} | grep -q 'Status: install'"
    ) }
  end

  describe "when specifying version parameter" do
    let :params do
      default_params.merge(:version => '1')
    end
    it { should contain_exec("/usr/bin/apt-get -y -t #{params[:release]} install #{title}=#{params[:version]}").with(
      :unless => "/usr/bin/apt-cache policy -t #{params[:release]} #{title} | /bin/grep -q 'Installed: #{params[:version]}'"
    ) }
  end

  describe "when specifying false release and version parameters" do
    let :params do
      default_params.merge(
        :release => false,
        :version => '1'
      )
    end
    it { should contain_exec("/usr/bin/apt-get -y  install #{title}=1").with(
      :unless => "/usr/bin/dpkg -s #{title} | grep -q 'Version: #{params[:version]}'"
    ) }
  end
end
