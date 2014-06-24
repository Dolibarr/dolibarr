require 'spec_helper'

describe 'supervisord' do

  concatdir = '/var/lib/puppet/concat'
  configfile = '/etc/supervisord.conf'
  let(:facts) {{ :concat_basedir => concatdir }}

  it { should contain_class('supervisord') }
  it { should contain_class('supervisord::install') }
  it { should contain_class('supervisord::config') }
  it { should contain_class('supervisord::service') }
  it { should contain_concat__fragment('supervisord_main').with_content(/logfile/) }

  describe '#install_pip' do
    context 'default' do
      it { should_not contain_class('supervisord::pip') }
    end

    context 'true' do
      let (:params) {{ :install_pip => true }}
      it { should contain_class('supervisord::pip') }
    end
  end

  describe '#env_var' do
    context 'default' do
      it { should contain_class('supervisord').without_env_hash }
      it { should contain_class('supervisord').without_env_string }
    end
    #context 'is specified' do
    #  let(:params) {{ :env_var => 'foovars' }}
    #  let(:hiera_data) {{ :foovars => { 'key1' => 'value1', 'key2' => 'value2' } }}
    #  it { should contain_concat__fragment('supervisord_main').with_content(/environment=key1='value1',key2='value2'/) }
    #end
  end

  describe '#environment' do
    context 'default' do
      it { should contain_class('supervisord').without_env_string }
    end
    context 'is specified' do
      let(:params) {{ :environment => { 'key1' => 'value1', 'key2' => 'value2' } }}
      it { should contain_concat__fragment('supervisord_main').with_content(/environment=key1='value1',key2='value2'/) }
    end
  end

  describe '#install_init' do
    context 'default' do
      it { should_not contain_file('/etc/init.d/supervisord') }
    end

    context 'false' do 
      it { should_not contain_file('/etc/init.d/supervisord') }
    end

    describe 'on supported OS'
      context 'with Debian' do
        let(:facts) {{ :osfamily => 'Debian', :concat_basedir => concatdir }}
        it { should contain_file('/etc/init.d/supervisord') }
      end

      context 'with RedHat' do
        let(:facts) {{ :osfamily => 'RedHat', :concat_basedir => concatdir }}
        it { should contain_file('/etc/init.d/supervisord') }
      end
    end
      
  describe '#unix_socket' do
    context 'default' do
      it { should contain_concat__fragment('supervisord_unix')}
    end
    context 'false' do
      let(:params) {{ :unix_socket => false }}
      it { should_not contain_concat__fragment('supervisord_unix')}
    end
  end

  describe '#inet_server' do
    context 'default' do
      it { should_not contain_concat__fragment('supervisord_inet')}
    end
    context 'true' do
      let(:params) {{ :inet_server => true }}
      it { should contain_concat__fragment('supervisord_inet')}
    end
  end

  describe '#run_path' do
    context 'default' do
      it { should_not contain_file('/var/run') }
    end
    context 'custom setting' do
      let(:params) {{ :run_path => '/var/run/supervisord'}}
      it { should contain_file('/var/run/supervisord') }
    end
  end
end