#
# params_lookup.rb
#
# This function lookups for a variable value in various locations
# following this order (first match is returned)
# - Hiera backend (if present) for modulename_varname
# - Hiera backend (if present) for varname (if second argument is 'global')
# - Top Scope Variable ::modulename_varname
# - Top Scope Variable ::varname (if second argument is 'global')
# - Module default: ::modulename::params::varname
#
# It's based on a suggestion of Dan Bode on how to better manage
# Example42 NextGen modules params lookups.
# Major help has been given by  Brice Figureau, Peter Meier
# and Ohad Levy during the Fosdem 2012 days (thanks guys)
#
# Tested and adapted to Puppet 2.6.x and later
#
# Alessandro Franceschi al@lab42.it
#
module Puppet::Parser::Functions
  newfunction(:params_lookup, :type => :rvalue, :doc => <<-EOS
This fuction looks for the given variable name in a set of different sources:
- Hiera, if available ('modulename_varname')
- Hiera, if available (if second argument is 'global')
- ::modulename_varname
- ::varname (if second argument is 'global')
- ::modulename::params::varname
If no value is found in the defined sources, it returns an empty string ('')
    EOS
  ) do |arguments|

    raise(Puppet::ParseError, "params_lookup(): Define at least the variable name " +
      "given (#{arguments.size} for 1)") if arguments.size < 1

    value = ''
    var_name = arguments[0]
    module_name = parent_module_name

    # Hiera Lookup
    if Puppet::Parser::Functions.function('hiera')
      value = function_hiera(["#{module_name}_#{var_name}", ''])
      return value if (not value.nil?) && (value != :undefined) && (value != '')

      value = function_hiera(["#{var_name}", '']) if arguments[1] == 'global'
      return value if (not value.nil?) && (value != :undefined) && (value != '')
    end

    # Top Scope Variable Lookup (::modulename_varname)
    value = lookupvar("::#{module_name}_#{var_name}")
    return value if (not value.nil?) && (value != :undefined) && (value != '')

    # Look up ::varname (only if second argument is 'global')
    if arguments[1] == 'global'
      value = lookupvar("::#{var_name}")
      return value if (not value.nil?) && (value != :undefined) && (value != '')
    end

    # needed for the next two lookups
    classname = self.resource.name.downcase
    loaded_classes = catalog.classes

    # self::params class lookup for default value
    if loaded_classes.include?("#{classname}::params")
      value = lookupvar("::#{classname}::params::#{var_name}")
      return value if (not value.nil?) && (value != :undefined) && (value != '')
    end

    # Params class lookup for default value
    if loaded_classes.include?("#{module_name}::params")
      value = lookupvar("::#{module_name}::params::#{var_name}")
      return value if (not value.nil?) && (value != :undefined) && (value != '')
    end

    return ''
  end
end
