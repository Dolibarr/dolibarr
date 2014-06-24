require 'spec_helper'

describe 'apache::params', :type => :class do
  context "On a Debian OS" do
    let :facts do
      {
        :osfamily               => 'Debian',
        :operatingsystemrelease => '6',
        :concat_basedir         => '/dne',
        :lsbdistcodename        => 'squeeze',
        :operatingsystem        => 'Debian',
        :id                     => 'root',
        :kernel                 => 'Linux',
        :path                   => '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin',
      }
    end
    it { should contain_apache__params }

    # There are 4 resources in this class currently
    # there should not be any more resources because it is a params class
    # The resources are class[apache::version], class[apache::params], class[main], class[settings], stage[main]
    it "Should not contain any resources" do
      subject.resources.size.should == 5
    end
  end
end
