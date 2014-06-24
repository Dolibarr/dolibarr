require 'facter'
Facter.add("puppi_projects") do
  confine :kernel => [ 'Linux' , 'SunOS' , 'FreeBSD' , 'Darwin' ]
  setcode do
    Facter::Util::Resolution.exec('ls `grep projectsdir  /etc/puppi/puppi.conf | sed \'s/projectsdir="\([^"]*\)"/\1/\'` | tr \'\n\' \',\' | sed \'s/,$//\'') if File.exists?("/etc/puppi/puppi.conf")
  end
end
