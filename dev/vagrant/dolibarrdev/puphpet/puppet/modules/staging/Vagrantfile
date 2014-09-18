# -*- mode: ruby -*-
# vi: set ft=ruby :

Vagrant.configure("2") do |config|
  # All Vagrant configuration is done here. The most common configuration
  # options are documented and commented below. For a complete reference,
  # please see the online documentation at vagrantup.com.

  #config.vm.synced_folder "manifests", "/tmp/manifests", "tests"
  config.vm.synced_folder "./", "/etc/puppet/modules/staging"

  config.vm.define :staging do |m|
    m.vm.box = "centos63"
    m.vm.box_url = "https://dl.dropbox.com/s/eqdrqnla4na8qax/centos63.box"

    m.vm.hostname = 'staging'
    m.vm.provider :vmware_fusion do |v|
      v.vmx["displayName"] = "staging"
      v.vmx["memsize"] = 512
      v.vmx["numvcpus"] = 4
    end

    m.vm.provision :puppet do |puppet|
      puppet.manifests_path = "tests"
      puppet.module_path    = "spec/fixtures/modules/"
      puppet.manifest_file  = "init.pp"
    end
  end
end
