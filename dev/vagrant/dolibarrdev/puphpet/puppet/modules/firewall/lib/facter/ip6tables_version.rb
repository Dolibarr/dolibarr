Facter.add(:ip6tables_version) do
  confine :kernel => :linux
  setcode do
    version = Facter::Util::Resolution.exec('ip6tables --version')
    if version
      version.match(/\d+\.\d+\.\d+/).to_s
    else
      nil
    end
  end
end
