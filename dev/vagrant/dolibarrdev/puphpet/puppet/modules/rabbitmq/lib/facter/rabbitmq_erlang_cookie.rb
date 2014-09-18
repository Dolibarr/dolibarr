# Fact: rabbitmq_erlang_cookie
#
# Purpose: To determine the current erlang cookie value.
#
# Resolution: Returns the cookie.
Facter.add(:rabbitmq_erlang_cookie) do
  confine :osfamily => %w[Debian RedHat Suse]

  setcode do
    if File.exists?('/var/lib/rabbitmq/.erlang.cookie')
      File.read('/var/lib/rabbitmq/.erlang.cookie')
    else
      nil
    end
  end
end
