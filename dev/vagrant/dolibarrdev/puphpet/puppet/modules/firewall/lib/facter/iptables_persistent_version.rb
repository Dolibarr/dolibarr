Facter.add(:iptables_persistent_version) do
  confine :operatingsystem => %w{Debian Ubuntu}
  setcode do
    # Throw away STDERR because dpkg >= 1.16.7 will make some noise if the
    # package isn't currently installed.
    cmd = "dpkg-query -Wf '${Version}' iptables-persistent 2>/dev/null"
    version = Facter::Util::Resolution.exec(cmd)

    if version.nil? or !version.match(/\d+\.\d+/)
      nil
    else
      version
    end
  end
end
