# This function is based on Ken Barber's get_scope_args
# It has been slightly changed and renamed to avoid naming clash

module Puppet::Parser::Functions
  newfunction(:get_class_args, :type => :rvalue, :doc => <<-EOS
This function will return all arguments passed to the current scope. This could
be a class or defined resource.
    EOS
  ) do |arguments|
 
    if (arguments.size != 0) then
      raise(Puppet::ParseError, "validate_resource(): Wrong number of arguments "+
        "given #{arguments.size} for 0")
    end

    # Grab the current scope, turn it to a hash but do not be recursive 
    # about it.
    classhash = to_hash(recursive=false)

    # Strip bits that do not matter for validation
#    classhash.delete("name")
#    classhash.delete("title")
#    classhash.delete("caller_module_name")
#    classhash.delete("module_name")

    # Return munged classhash
    classhash
  end
end

# vim: set ts=2 sw=2 et :

