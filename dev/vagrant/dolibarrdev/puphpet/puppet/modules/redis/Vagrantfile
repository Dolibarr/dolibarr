VAGRANTFILE_API_VERSION = "2"
Vagrant.configure(VAGRANTFILE_API_VERSION) do |config|
  config.vbguest.auto_update = false
  config.hostmanager.enabled = false

  config.vm.define 'redis' do |node|
      node.vm.box = "centos65"
      node.vm.hostname = "redis.local"
      node.vm.network :private_network, ip: "192.168.100.100"
      node.vm.provision :shell, :inline => "yum install -y git && gem install librarian-puppet --no-ri --no-rdoc"
      node.vm.provision :shell, :inline => "cd /vagrant ; librarian-puppet install --clean --path /etc/puppet/modules"
      node.vm.provision :puppet do |puppet|
        puppet.manifests_path = ["vm", "/etc/puppet/modules/redis/tests"]
        puppet.manifest_file  = "init.pp"
      end
  end

end
