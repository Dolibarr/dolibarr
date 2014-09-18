require 'puppet/provider/a2mod'

Puppet::Type.type(:a2mod).provide(:a2mod, :parent => Puppet::Provider::A2mod) do
    desc "Manage Apache 2 modules on Debian and Ubuntu"

    optional_commands :encmd => "a2enmod"
    optional_commands :discmd => "a2dismod"
    commands :apache2ctl => "apache2ctl"

    confine :osfamily => :debian
    defaultfor :operatingsystem => [:debian, :ubuntu]

    def self.instances
      modules = apache2ctl("-M").lines.collect { |line|
        m = line.match(/(\w+)_module \(shared\)$/)
        m[1] if m
      }.compact

      modules.map do |mod|
        new(
          :name     => mod,
          :ensure   => :present,
          :provider => :a2mod
        )
      end
    end

    def create
        encmd resource[:name]
    end

    def destroy
        discmd resource[:name]
    end
end
