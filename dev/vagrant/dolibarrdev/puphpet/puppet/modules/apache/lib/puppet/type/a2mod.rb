Puppet::Type.newtype(:a2mod) do
    @doc = "Manage Apache 2 modules"

    ensurable

    newparam(:name) do
       Puppet.warning "The a2mod provider is deprecated, please use apache::mod instead"
       desc "The name of the module to be managed"

       isnamevar

    end

    newparam(:lib) do
      desc "The name of the .so library to be loaded"

      defaultto { "mod_#{@resource[:name]}.so" }
    end
 
    newparam(:identifier) do
      desc "Module identifier string used by LoadModule. Default: module-name_module"

      # http://httpd.apache.org/docs/2.2/mod/module-dict.html#ModuleIdentifier

      defaultto { "#{resource[:name]}_module" }
    end

    autorequire(:package) { catalog.resource(:package, 'httpd')}

end
