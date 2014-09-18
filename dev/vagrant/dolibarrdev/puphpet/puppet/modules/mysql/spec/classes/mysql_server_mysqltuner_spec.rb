describe 'mysql::server::mysqltuner' do

  it { should contain_file('/usr/local/bin/mysqltuner') }

end
