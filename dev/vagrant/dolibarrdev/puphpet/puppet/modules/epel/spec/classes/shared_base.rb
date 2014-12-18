require 'spec_helper'

shared_context :base do
  it do
    should contain_yumrepo('epel').with({
      'failovermethod' => 'priority',
      'proxy'          => 'absent',
      'enabled'        => '1',
      'gpgcheck'       => '1',
    })
  end
end

shared_context :base_6 do
  include_context :base

  it do
    should contain_yumrepo('epel').with({
      'mirrorlist'     => "http://mirrors.fedoraproject.org/mirrorlist?repo=epel-6&arch=$basearch",
      'gpgkey'         => "file:///etc/pki/rpm-gpg/RPM-GPG-KEY-EPEL-6",
      'descr'          => "Extra Packages for Enterprise Linux 6 - $basearch",
    })
  end
end

shared_context :base_5 do
  include_context :base

  it do
    should contain_yumrepo('epel').with({
      'mirrorlist'     => "http://mirrors.fedoraproject.org/mirrorlist?repo=epel-5&arch=$basearch",
      'gpgkey'         => "file:///etc/pki/rpm-gpg/RPM-GPG-KEY-EPEL-5",
      'descr'          => "Extra Packages for Enterprise Linux 5 - $basearch",
    })
  end
end
