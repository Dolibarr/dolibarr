require 'spec_helper'

shared_context :epel_testing_debuginfo do
  it do
    should contain_yumrepo('epel-testing-debuginfo').with({
      'failovermethod' => 'priority',
      'proxy'          => 'absent',
      'enabled'        => '0',
      'gpgcheck'       => '1',
    })
  end
end

shared_context :epel_testing_debuginfo_6 do
  include_context :epel_testing_debuginfo

  it do
    should contain_yumrepo('epel-testing-debuginfo').with({
      'baseurl'        => "http://download.fedoraproject.org/pub/epel/testing/6/$basearch/debug",
      'gpgkey'         => "file:///etc/pki/rpm-gpg/RPM-GPG-KEY-EPEL-6",
      'descr'          => "Extra Packages for Enterprise Linux 6 - Testing - $basearch - Debug",
    })
  end
end

shared_context :epel_testing_debuginfo_5 do
  include_context :epel_testing_debuginfo

  it do
    should contain_yumrepo('epel-testing-debuginfo').with({
      'baseurl'        => "http://download.fedoraproject.org/pub/epel/testing/5/$basearch/debug",
      'gpgkey'         => "file:///etc/pki/rpm-gpg/RPM-GPG-KEY-EPEL-5",
      'descr'          => "Extra Packages for Enterprise Linux 5 - Testing - $basearch - Debug",
    })
  end
end
