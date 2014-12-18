require 'spec_helper'

shared_context :epel_testing_source do
  it do
    should contain_yumrepo('epel-testing-source').with({
      'failovermethod' => 'priority',
      'proxy'          => 'absent',
      'enabled'        => '0',
      'gpgcheck'       => '1',
    })
  end
end

shared_context :epel_testing_source_6 do
  include_context :epel_testing_source

  it do
    should contain_yumrepo('epel-testing-source').with({
      'baseurl'        => "http://download.fedoraproject.org/pub/epel/testing/6/SRPMS",
      'gpgkey'         => "file:///etc/pki/rpm-gpg/RPM-GPG-KEY-EPEL-6",
      'descr'          => "Extra Packages for Enterprise Linux 6 - Testing - $basearch - Source",
    })
  end
end

shared_context :epel_testing_source_5 do
  include_context :epel_testing_source

  it do
    should contain_yumrepo('epel-testing-source').with({
      'baseurl'        => "http://download.fedoraproject.org/pub/epel/testing/5/SRPMS",
      'gpgkey'         => "file:///etc/pki/rpm-gpg/RPM-GPG-KEY-EPEL-5",
      'descr'          => "Extra Packages for Enterprise Linux 5 - Testing - $basearch - Source",
    })
  end
end
