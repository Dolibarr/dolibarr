describe 'mysql::client' do
  let(:facts) {{ :osfamily => 'RedHat' }}

  context 'with defaults' do
    it { should_not contain_class('mysql::bindings') }
    it { should contain_package('mysql_client') }
  end

  context 'with bindings enabled' do
    let(:params) {{ :bindings_enable => true }}

    it { should contain_class('mysql::bindings') }
    it { should contain_package('mysql_client') }
  end

end
