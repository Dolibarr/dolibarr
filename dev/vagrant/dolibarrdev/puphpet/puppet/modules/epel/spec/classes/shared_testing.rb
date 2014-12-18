require 'spec_helper'

shared_context :epel_testing do
  it do
    should contain_yumrepo('epel-testing').with({
      'failovermethod' => 'priority',
      'proxy'          => 'absent',
      'enabled'        => '0',
      'gpgcheck'       => '1',
    })
  end
end

shared_context :epel_testing_6 do
  include_context :epel_testing

  it do
    should contain_yumrepo('epel-testing').with({
      'baseurl'        => "http://download.fedoraproject.org/pub/epel/testing/6/$basearch",
      'gpgkey'         => "file:///etc/pki/rpm-gpg/RPM-GPG-KEY-EPEL-6",
      'descr'          => "Extra Packages for Enterprise Linux 6 - Testing - $basearch ",
    })
  end
end

shared_context :epel_testing_5 do
  include_context :epel_testing

  it do
    should contain_yumrepo('epel-testing').with({
      'baseurl'        => "http://download.fedoraproject.org/pub/epel/testing/5/$basearch",
      'gpgkey'         => "file:///etc/pki/rpm-gpg/RPM-GPG-KEY-EPEL-5",
      'descr'          => "Extra Packages for Enterprise Linux 5 - Testing - $basearch ",
    })
  end
end
