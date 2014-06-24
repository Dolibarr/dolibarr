require File.join(File.dirname(__FILE__), '..', 'vcsrepo')

Puppet::Type.type(:vcsrepo).provide(:dummy, :parent => Puppet::Provider::Vcsrepo) do
  desc "Dummy default provider"

  defaultfor :vcsrepo => :dummy

  def working_copy_exists?
    providers = @resource.class.providers.map{|x| x.to_s}.sort.reject{|x| x == "dummy"}.join(", ") rescue "none"
    raise("vcsrepo resource must have a provider, available: #{providers}")
  end
end
