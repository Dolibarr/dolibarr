require "#{File.join(File.dirname(__FILE__),'..','spec_helper.rb')}"

describe 'php::pear::module' do

  let(:title) { 'php::pear::module' }
  let(:node) { 'rspec.example42.com' }
  let(:facts) { { 'operatingsystem' => 'Ubuntu' } }

  describe 'Test standard installation' do
    let(:params) { { 'name' =>  'Crypt-CHAP', } }
    it 'should install pear module with default OS package' do
      should contain_package('pear-Crypt-CHAP').with_name('php-Crypt-CHAP')
    end
    it 'should notify the default service' do
      should contain_package('pear-Crypt-CHAP').with_notify('Service[apache2]')
    end
  end

  describe 'Test custom params' do
    let(:params) { { 'name' =>  'Crypt-CHAP', 'module_prefix' => 'my-' , 'service_autorestart' => false } }
    it 'should create a package with custom prefix' do
      should contain_package('pear-Crypt-CHAP').with(
        'ensure' => 'present',
        'name'   => 'my-Crypt-CHAP'
      )
      should contain_package('pear-Crypt-CHAP').without('notify')
    end
  end

  describe 'Test uninstallation' do
    let(:params) { { 'name' =>  'Crypt-CHAP', 'ensure' => 'absent' } }
    it 'should remove the package' do
      should contain_package('pear-Crypt-CHAP').with_ensure('absent')
    end
  end

  describe 'Test installation via exec' do
    let(:params) { { 'name' =>  'Crypt-CHAP', 'use_package' => 'false' } }
    it 'should install pear module with exec commands' do
      should contain_exec('pear-Crypt-CHAP').with(
        'command' => 'pear -d preferred_state=stable install  pear.php.net/Crypt-CHAP',
        'unless'  => 'pear info pear.php.net/Crypt-CHAP'
      )
    end
  end


end

