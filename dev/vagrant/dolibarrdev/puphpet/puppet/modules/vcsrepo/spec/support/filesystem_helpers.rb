module FilesystemHelpers

  def expects_chdir(path = resource.value(:path))
    Dir.expects(:chdir).with(path).at_least_once.yields
  end

  def expects_mkdir(path = resource.value(:path))
    Dir.expects(:mkdir).with(path).at_least_once
  end

  def expects_rm_rf(path = resource.value(:path))
    FileUtils.expects(:rm_rf).with(path)
  end

  def expects_directory?(returns = true, path = resource.value(:path))
    File.expects(:directory?).with(path).returns(returns)
  end
end
