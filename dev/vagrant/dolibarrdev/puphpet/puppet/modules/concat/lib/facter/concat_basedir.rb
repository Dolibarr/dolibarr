# == Fact: concat_basedir
#
# A custom fact that sets the default location for fragments
#
# "${::vardir}/concat/"
#
Facter.add("concat_basedir") do
  setcode do
    File.join(Puppet[:vardir],"concat")
  end
end
