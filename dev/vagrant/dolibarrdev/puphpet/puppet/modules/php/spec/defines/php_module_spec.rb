require "#{File.join(File.dirname(__FILE__),'..','spec_helper.rb')}"

describe 'php::module' do

  let(:title) { 'php::module' }
  let(:node) { 'rspec.example42.com' }
  let(:facts) { { 'operatingsystem' => 'Ubuntu' } }

  describe 'Test standard installation' do
    let(:params) { { 'name' =>  'ps', } }
    it 'should create a package with the default OS prefix' do
      should contain_package('PhpModule_ps').with_name('php5-ps')
    end
    it 'should notify the default service' do
      should contain_package('PhpModule_ps').with_notify('Service[apache2]')
    end
  end

  describe 'Test custom params' do
    let(:params) { { 'name' =>  'ps', 'module_prefix' => 'my-' , 'service_autorestart' => false } }
    it 'should create a package with custom prefix' do
      should contain_package('PhpModule_ps').with(
        'ensure' => 'present',
        'name'   => 'my-ps'
      )
      should contain_package('PhpModule_ps').without('notify')
    end
  end

  describe 'Test uninstallation' do
    let(:params) { { 'name' =>  'ps', 'absent' => 'true' } }
    it 'should remove the package' do
      should contain_package('PhpModule_ps').with_ensure('absent')
    end
  end

end

