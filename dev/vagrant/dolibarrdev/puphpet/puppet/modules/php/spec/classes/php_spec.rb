require "#{File.join(File.dirname(__FILE__),'..','spec_helper.rb')}"

describe 'php' do

  let(:title) { 'php' }
  let(:node) { 'rspec.example42.com' }
  let(:facts) { { :ipaddress => '10.42.42.42' } }

  describe 'Test standard installation' do
    it { should contain_package('php').with_ensure('present') }
    it { should contain_file('php.conf').with_ensure('present') }
  end

  describe 'Test installation of a specific version' do
    let(:params) { {:version => '1.0.42' } }
    it { should contain_package('php').with_ensure('1.0.42') }
  end

  describe 'Test decommissioning - absent' do
    let(:params) { {:absent => true, :monitor => true } }

    it 'should remove Package[php]' do should contain_package('php').with_ensure('absent') end 
    it 'should remove php configuration file' do should contain_file('php.conf').with_ensure('absent') end
  end

  describe 'Test customizations - template' do
    let(:params) { {:template => "php/spec.erb" , :options => { 'opt_a' => 'value_a' } } }

    it 'should generate a valid template' do
      content = catalogue.resource('file', 'php.conf').send(:parameters)[:content]
      content.should match "fqdn: rspec.example42.com"
    end
    it 'should generate a template that uses custom options' do
      content = catalogue.resource('file', 'php.conf').send(:parameters)[:content]
      content.should match "value_a"
    end

  end

  describe 'Test customizations - source' do
    let(:params) { {:source => "puppet://modules/php/spec" , :source_dir => "puppet://modules/php/dir/spec" , :source_dir_purge => true } }

    it 'should request a valid source ' do
      content = catalogue.resource('file', 'php.conf').send(:parameters)[:source]
      content.should == "puppet://modules/php/spec"
    end
    it 'should request a valid source dir' do
      content = catalogue.resource('file', 'php.dir').send(:parameters)[:source]
      content.should == "puppet://modules/php/dir/spec"
    end
    it 'should purge source dir if source_dir_purge is true' do
      content = catalogue.resource('file', 'php.dir').send(:parameters)[:purge]
      content.should == true
    end
  end

  describe 'Test customizations - custom class' do
    let(:params) { {:my_class => "php::spec" } }
    it 'should automatically include a custom class' do
      content = catalogue.resource('file', 'php.conf').send(:parameters)[:content]
      content.should match "fqdn: rspec.example42.com"
    end
  end

  describe 'Test Puppi Integration' do
    let(:params) { {:puppi => true, :puppi_helper => "myhelper"} }

    it 'should generate a puppi::ze define' do
      content = catalogue.resource('puppi::ze', 'php').send(:parameters)[:helper]
      content.should == "myhelper"
    end
  end


end

