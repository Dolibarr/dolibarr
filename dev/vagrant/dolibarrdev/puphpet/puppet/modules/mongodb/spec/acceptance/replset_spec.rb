require 'spec_helper_acceptance'

if hosts.length > 1
  describe 'mongodb_replset resource' do
    after :all do
      # Have to drop the DB to disable replsets for further testing
      on hosts, %{mongo local --verbose --eval 'db.dropDatabase()'}

      pp = <<-EOS
        class { 'mongodb::globals': }
        -> class { 'mongodb::server':
          ensure => purged,
        }
        if $::osfamily == 'RedHat' {
          class { 'mongodb::client': }
        }
      EOS

      apply_manifest_on(hosts.reverse, pp, :catch_failures => true)
    end

    it 'configures mongo on both nodes' do
      pp = <<-EOS
        class { 'mongodb::globals': }
        -> class { 'mongodb::server':
          bind_ip => '0.0.0.0',
          replset => 'test',
        }
        if $::osfamily == 'RedHat' {
          class { 'mongodb::client': }
        }
      EOS

      apply_manifest_on(hosts.reverse, pp, :catch_failures => true)
      apply_manifest_on(hosts.reverse, pp, :catch_changes  => true)
    end

    it 'sets up the replset with puppet' do
      pp = <<-EOS
        mongodb_replset { 'test':
          members => [#{hosts.collect{|x|"'#{x}:27017'"}.join(',')}],
        }
      EOS
      apply_manifest_on(hosts_as('master'), pp, :catch_failures => true)
      on(hosts_as('master'), 'mongo --quiet --eval "printjson(rs.conf())"') do |r|
        expect(r.stdout).to match /#{hosts[0]}:27017/
        expect(r.stdout).to match /#{hosts[1]}:27017/
      end
    end

    it 'inserts data on the master' do
      sleep(30)
      on hosts_as('master'), %{mongo --verbose --eval 'db.test.save({name:"test1",value:"some value"})'}
    end

    it 'checks the data on the master' do
      on hosts_as('master'), %{mongo --verbose --eval 'printjson(db.test.findOne({name:"test1"}))'} do |r|
        expect(r.stdout).to match /some value/
      end
    end

    it 'checks the data on the slave' do
      sleep(10)
      on hosts_as('slave'), %{mongo --verbose --eval 'rs.slaveOk(); printjson(db.test.findOne({name:"test1"}))'} do |r|
        expect(r.stdout).to match /some value/
      end
    end
  end
end
