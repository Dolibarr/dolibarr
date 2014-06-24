require 'spec_helper_acceptance'

describe 'mongodb::server class' do

  shared_examples 'normal tests' do |tengen|
    if tengen
      case fact('osfamily')
      when 'RedHat'
        package_name = 'mongodb-org-server'
        service_name = 'mongod'
        config_file  = '/etc/mongod.conf'
      when 'Debian'
        package_name = 'mongodbdb-org-10gen'
        service_name = 'mongodb'
        config_file  = '/etc/mongodb.conf'
      end
    else
      case fact('osfamily')
      when 'RedHat'
        package_name = 'mongodb-server'
        service_name = 'mongod'
        config_file  = '/etc/mongodb.conf'
      when 'Debian'
        package_name = 'mongodb-server'
        service_name = 'mongodb'
        config_file  = '/etc/mongodb.conf'
      end
    end

    client_name  = 'mongo --version'

    context "default parameters with 10gen => #{tengen}" do
      after :all do
        if tengen
          puts "XXX uninstalls mongodb because changing the port with tengen doesn't work because they have a crappy init script"
          pp = <<-EOS
            class {'mongodb::globals': manage_package_repo => #{tengen}, }
            -> class { 'mongodb::server': ensure => absent, }
            -> class { 'mongodb::client': ensure => absent, }
          EOS
          apply_manifest(pp, :catch_failures => true)
        end
      end

      it 'should work with no errors' do
        case fact('osfamily')
        when 'RedHat'
          pp = <<-EOS
            class { 'mongodb::globals': manage_package_repo => #{tengen}, }
            -> class { 'mongodb::server': }
            -> class { 'mongodb::client': }
          EOS
        when 'Debian'
          pp = <<-EOS
            class { 'mongodb::globals': manage_package_repo => #{tengen}, }
            -> class { 'mongodb::server': }
          EOS
        end

        apply_manifest(pp, :catch_failures => true)
        apply_manifest(pp, :catch_changes  => true)
      end

      describe package(package_name) do
        it { should be_installed }
      end

      describe file(config_file) do
        it { should be_file }
      end

      describe service(service_name) do
         it { should be_enabled }
         it { should be_running }
      end

      describe port(27017) do
        it do
          sleep(20)
          should be_listening
        end
      end

      describe command(client_name) do
        it do
          should return_exit_status 0
        end
      end
    end

    context "test using custom port and 10gen => #{tengen}" do
      it 'change port to 27018' do
        pp = <<-EOS
          class { 'mongodb::globals': manage_package_repo => #{tengen}, }
          -> class { 'mongodb::server': port => 27018, }
        EOS

        apply_manifest(pp, :catch_failures => true)
        apply_manifest(pp, :catch_changes  => true)
      end

      describe port(27018) do
        sleep(20)
        it { sleep 5 ; should be_listening }
      end
    end

    describe "uninstalling with 10gen => #{tengen}" do
      it 'uninstalls mongodb' do
        pp = <<-EOS
          class {'mongodb::globals': manage_package_repo => #{tengen}, }
          -> class { 'mongodb::server': ensure => absent, }
          -> class { 'mongodb::client': ensure => absent, }
        EOS
        apply_manifest(pp, :catch_failures => true)
      end
    end
  end

  it_behaves_like 'normal tests', false
  it_behaves_like 'normal tests', true
end
