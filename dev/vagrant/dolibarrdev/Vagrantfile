require 'yaml'

dir = File.dirname(File.expand_path(__FILE__))

configValues = YAML.load_file("#{dir}/puphpet/config.yaml")
data = configValues['vagrantfile-local']

Vagrant.configure("2") do |config|
  config.vm.box = "#{data['vm']['box']}"
  config.vm.box_url = "#{data['vm']['box_url']}"

  if data['vm']['hostname'].to_s.strip.length != 0
    config.vm.hostname = "#{data['vm']['hostname']}"
  end

  if data['vm']['network']['private_network'].to_s != ''
    config.vm.network "private_network", ip: "#{data['vm']['network']['private_network']}"
  end

  data['vm']['network']['forwarded_port'].each do |i, port|
    if port['guest'] != '' && port['host'] != ''
      config.vm.network :forwarded_port, guest: port['guest'].to_i, host: port['host'].to_i
    end
  end

  data['vm']['synced_folder'].each do |i, folder|
    if folder['source'] != '' && folder['target'] != ''
      nfs = (folder['nfs'] == "true") ? "nfs" : nil
      if nfs == "nfs"
        config.vm.synced_folder "#{folder['source']}", "#{folder['target']}", id: "#{i}", type: nfs
      else
        config.vm.synced_folder "#{folder['source']}", "#{folder['target']}", id: "#{i}", type: nfs,
          group: 'www-data', owner: 'www-data', mount_options: ["dmode=775", "fmode=764"]
      end
    end
  end

  config.vm.usable_port_range = (10200..10500)

  if data['vm']['chosen_provider'].empty? || data['vm']['chosen_provider'] == "virtualbox"
    ENV['VAGRANT_DEFAULT_PROVIDER'] = 'virtualbox'

    config.vm.provider :virtualbox do |virtualbox|
      data['vm']['provider']['virtualbox']['modifyvm'].each do |key, value|
        if key == "memory"
          next
        end

        if key == "natdnshostresolver1"
          value = value ? "on" : "off"
        end

        virtualbox.customize ["modifyvm", :id, "--#{key}", "#{value}"]
      end

      virtualbox.customize ["modifyvm", :id, "--memory", "#{data['vm']['memory']}"]

      if data['vm']['hostname'].to_s.strip.length != 0
        virtualbox.customize ["modifyvm", :id, "--name", config.vm.hostname]
      end
    end
  end

  if data['vm']['chosen_provider'] == "vmware_fusion" || data['vm']['chosen_provider'] == "vmware_workstation"
    ENV['VAGRANT_DEFAULT_PROVIDER'] = (data['vm']['chosen_provider'] == "vmware_fusion") ? "vmware_fusion" : "vmware_workstation"

    config.vm.provider "vmware_fusion" do |v|
      data['vm']['provider']['vmware'].each do |key, value|
        if key == "memsize"
          next
        end

        v.vmx["#{key}"] = "#{value}"
      end

      v.vmx["memsize"] = "#{data['vm']['memory']}"

      if data['vm']['hostname'].to_s.strip.length != 0
        v.vmx["displayName"] = config.vm.hostname
      end
    end
  end

  if data['vm']['chosen_provider'] == "parallels"
    ENV['VAGRANT_DEFAULT_PROVIDER'] = "parallels"

    config.vm.provider "parallels" do |v|
      data['vm']['provider']['parallels'].each do |key, value|
        if key == "memsize"
          next
        end

        v.customize ["set", :id, "--#{key}", "#{value}"]
      end

      v.memory = "#{data['vm']['memory']}"

      if data['vm']['hostname'].to_s.strip.length != 0
        v.name = config.vm.hostname
      end
    end
  end

  ssh_username = !data['ssh']['username'].nil? ? data['ssh']['username'] : "vagrant"

  config.vm.provision "shell" do |s|
    s.path = "puphpet/shell/initial-setup.sh"
    s.args = "/vagrant/puphpet"
  end
  config.vm.provision "shell" do |kg|
    kg.path = "puphpet/shell/ssh-keygen.sh"
    kg.args = "#{ssh_username}"
  end
  config.vm.provision :shell, :path => "puphpet/shell/update-puppet.sh"

  config.vm.provision :puppet do |puppet|
    puppet.facter = {
      "ssh_username"     => "#{ssh_username}",
      "provisioner_type" => ENV['VAGRANT_DEFAULT_PROVIDER'],
      "vm_target_key"    => 'vagrantfile-local',
    }
    puppet.manifests_path = "#{data['vm']['provision']['puppet']['manifests_path']}"
    puppet.manifest_file = "#{data['vm']['provision']['puppet']['manifest_file']}"
    puppet.module_path = "#{data['vm']['provision']['puppet']['module_path']}"

    if !data['vm']['provision']['puppet']['options'].empty?
      puppet.options = data['vm']['provision']['puppet']['options']
    end
  end

  config.vm.provision :shell, :path => "puphpet/shell/execute-files.sh"
  config.vm.provision :shell, :path => "puphpet/shell/important-notices.sh"

  if File.file?("#{dir}/puphpet/files/dot/ssh/id_rsa")
    config.ssh.private_key_path = [
      "#{dir}/puphpet/files/dot/ssh/id_rsa",
      "#{dir}/puphpet/files/dot/ssh/insecure_private_key"
    ]
  end

  if !data['ssh']['host'].nil?
    config.ssh.host = "#{data['ssh']['host']}"
  end
  if !data['ssh']['port'].nil?
    config.ssh.port = "#{data['ssh']['port']}"
  end
  if !data['ssh']['username'].nil?
    config.ssh.username = "#{data['ssh']['username']}"
  end
  if !data['ssh']['guest_port'].nil?
    config.ssh.guest_port = data['ssh']['guest_port']
  end
  if !data['ssh']['shell'].nil?
    config.ssh.shell = "#{data['ssh']['shell']}"
  end
  if !data['ssh']['keep_alive'].nil?
    config.ssh.keep_alive = data['ssh']['keep_alive']
  end
  if !data['ssh']['forward_agent'].nil?
    config.ssh.forward_agent = data['ssh']['forward_agent']
  end
  if !data['ssh']['forward_x11'].nil?
    config.ssh.forward_x11 = data['ssh']['forward_x11']
  end
  if !data['vagrant']['host'].nil?
    config.vagrant.host = data['vagrant']['host'].gsub(":", "").intern
  end

end

