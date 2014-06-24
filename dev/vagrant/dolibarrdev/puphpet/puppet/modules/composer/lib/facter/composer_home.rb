Facter.add(:composer_home) do
  setcode do
    ENV['HOME']
  end
end
