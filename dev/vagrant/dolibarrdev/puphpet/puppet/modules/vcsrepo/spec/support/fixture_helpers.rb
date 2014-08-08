module FixtureHelpers

  def fixture(name, ext = '.txt')
    File.read(File.join(File.dirname(__FILE__), '..', 'fixtures', name.to_s + ext))
  end

end
