#
# ensure_packages.rb
#

module Puppet::Parser::Functions
  newfunction(:ensure_packages, :type => :statement, :doc => <<-EOS
Takes a list of packages and only installs them if they don't already exist.
It optionally takes a hash as a second parameter that will be passed as the
third argument to the ensure_resource() function.
    EOS
  ) do |arguments|

    if arguments.size > 2 or arguments.size == 0
      raise(Puppet::ParseError, "ensure_packages(): Wrong number of arguments " +
        "given (#{arguments.size} for 1 or 2)")
    elsif arguments.size == 2 and !arguments[1].is_a?(Hash) 
      raise(Puppet::ParseError, 'ensure_packages(): Requires second argument to be a Hash')
    end

    packages = Array(arguments[0])

    if arguments[1]
      defaults = { 'ensure' => 'present' }.merge(arguments[1])
    else
      defaults = { 'ensure' => 'present' }
    end

    Puppet::Parser::Functions.function(:ensure_resource)
    packages.each { |package_name|
      function_ensure_resource(['package', package_name, defaults ])
    }
  end
end

# vim: set ts=2 sw=2 et :
