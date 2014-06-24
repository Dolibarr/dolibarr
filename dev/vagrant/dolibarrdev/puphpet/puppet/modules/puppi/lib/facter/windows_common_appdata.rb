require 'facter'
Facter.add(:windows_common_appdata) do
  confine :operatingsystem => :windows
  if Facter.value(:osfamily) == "windows"
    require 'win32/dir'
  end
  setcode do
    Dir::COMMON_APPDATA
  end
end
