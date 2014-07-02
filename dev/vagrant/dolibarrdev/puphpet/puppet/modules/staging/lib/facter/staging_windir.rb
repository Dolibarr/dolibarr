Facter.add(:staging_windir) do
  confine :osfamily => :windows
  setcode do
    program_data = `echo %SYSTEMDRIVE%\\ProgramData`.chomp
    if File.directory? program_data
      "#{program_data}\\staging"
    else
      "C:\\staging"
    end
  end
end
