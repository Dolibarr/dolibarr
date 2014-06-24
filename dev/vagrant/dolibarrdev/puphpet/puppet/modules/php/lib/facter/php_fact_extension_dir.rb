Facter.add("php_fact_extension_dir") do
  setcode do
    Facter::Util::Resolution.exec('php-config --extension-dir')    || nil
  end
end
