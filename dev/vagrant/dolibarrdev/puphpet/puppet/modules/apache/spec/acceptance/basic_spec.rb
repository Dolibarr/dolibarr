require 'spec_helper_acceptance'

describe 'disable selinux:', :unless => UNSUPPORTED_PLATFORMS.include?(fact('osfamily')) do
  it "because otherwise apache won't work" do
    apply_manifest(%{
      exec { "setenforce 0":
        path   => "/bin:/sbin:/usr/bin:/usr/sbin",
        onlyif => "which setenforce && getenforce | grep Enforcing",
      }
    }, :catch_failures => true)
  end
end
