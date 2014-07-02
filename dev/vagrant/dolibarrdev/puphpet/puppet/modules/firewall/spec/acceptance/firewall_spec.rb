require 'spec_helper_acceptance'

describe 'firewall type' do

  describe 'reset' do
    it 'deletes all rules' do
      shell('iptables --flush; iptables -t nat --flush; iptables -t mangle --flush')
    end
  end

  describe 'name' do
    context 'valid' do
      it 'applies cleanly' do
        pp = <<-EOS
          class { '::firewall': }
          firewall { '001 - test': ensure => present }
        EOS

        apply_manifest(pp, :catch_failures => true)
      end

    end

    context 'invalid' do
      it 'fails' do
        pp = <<-EOS
          class { '::firewall': }
          firewall { 'test': ensure => present }
        EOS

        apply_manifest(pp, :expect_failures => true) do |r|
          expect(r.stderr).to match(/Invalid value "test"./)
        end
      end
    end
  end

  describe 'ensure' do
    context 'default' do
      it 'applies' do
        pp = <<-EOS
          class { '::firewall': }
          firewall { '555 - test':
            proto  => tcp,
            port   => '555',
            action => accept,
          }
        EOS

        apply_manifest(pp, :catch_failures => true)
      end

      it 'should contain the rule' do
         shell('iptables-save') do |r|
           expect(r.stdout).to match(/-A INPUT -p tcp -m multiport --ports 555 -m comment --comment "555 - test" -j ACCEPT/)
         end
      end
    end

    context 'present' do
      it 'applies' do
        pp = <<-EOS
          class { '::firewall': }
          firewall { '555 - test':
            ensure => present,
            proto  => tcp,
            port   => '555',
            action => accept,
          }
        EOS

        apply_manifest(pp, :catch_failures => true)
      end

      it 'should contain the rule' do
         shell('iptables-save') do |r|
           expect(r.stdout).to match(/-A INPUT -p tcp -m multiport --ports 555 -m comment --comment "555 - test" -j ACCEPT/)
         end
      end
    end

    context 'absent' do
      it 'applies' do
        pp = <<-EOS
          class { '::firewall': }
          firewall { '555 - test':
            ensure => absent,
            proto  => tcp,
            port   => '555',
            action => accept,
          }
        EOS

        apply_manifest(pp, :catch_failures => true)
      end

      it 'should not contain the rule' do
         shell('iptables-save') do |r|
           expect(r.stdout).to_not match(/-A INPUT -p tcp -m multiport --ports 555 -m comment --comment "555 - test" -j ACCEPT/)
         end
      end
    end
  end

  describe 'source' do
    context '192.168.2.0/24' do
      it 'applies' do
        pp = <<-EOS
          class { '::firewall': }
          firewall { '556 - test':
            proto  => tcp,
            port   => '556',
            action => accept,
            source => '192.168.2.0/24',
          }
        EOS

        apply_manifest(pp, :catch_failures => true)
        apply_manifest(pp, :catch_changes => true)
      end

      it 'should contain the rule' do
         shell('iptables-save') do |r|
           expect(r.stdout).to match(/-A INPUT -s 192.168.2.0\/(24|255\.255\.255\.0) -p tcp -m multiport --ports 556 -m comment --comment "556 - test" -j ACCEPT/)
         end
      end
    end

    context '! 192.168.2.0/24' do
      it 'applies' do
        pp = <<-EOS
          class { '::firewall': }
          firewall { '556 - test':
            proto  => tcp,
            port   => '556',
            action => accept,
            source => '! 192.168.2.0/24',
          }
        EOS

        apply_manifest(pp, :catch_failures => true)
        apply_manifest(pp, :catch_changes => true)
      end

      it 'should contain the rule' do
         shell('iptables-save') do |r|
           expect(r.stdout).to match(/-A INPUT (! -s|-s !) 192.168.2.0\/(24|255\.255\.255\.0) -p tcp -m multiport --ports 556 -m comment --comment "556 - test" -j ACCEPT/)
         end
      end
    end

    # Invalid address
    context '256.168.2.0/24' do
      it 'applies' do
        pp = <<-EOS
          class { '::firewall': }
          firewall { '556 - test':
            proto  => tcp,
            port   => '556',
            action => accept,
            source => '256.168.2.0/24',
          }
        EOS

        apply_manifest(pp, :expect_failures => true) do |r|
          expect(r.stderr).to match(/host_to_ip failed for 256.168.2.0\/(24|255\.255\.255\.0)/)
        end
      end

      it 'should not contain the rule' do
         shell('iptables-save') do |r|
           expect(r.stdout).to_not match(/-A INPUT -s 256.168.2.0\/(24|255\.255\.255\.0) -p tcp -m multiport --ports 556 -m comment --comment "556 - test" -j ACCEPT/)
         end
      end
    end
  end

  describe 'src_range' do
    context '192.168.1.1-192.168.1.10' do
      it 'applies' do
        pp = <<-EOS
          class { '::firewall': }
          firewall { '557 - test':
            proto  => tcp,
            port   => '557',
            action => accept,
            src_range => '192.168.1.1-192.168.1.10',
          }
        EOS

        apply_manifest(pp, :catch_failures => true)
        apply_manifest(pp, :catch_changes => true)
      end

      it 'should contain the rule' do
         shell('iptables-save') do |r|
           expect(r.stdout).to match(/-A INPUT -p tcp -m iprange --src-range 192.168.1.1-192.168.1.10 -m multiport --ports 557 -m comment --comment "557 - test" -j ACCEPT/)
         end
      end
    end

    # Invalid IP
    context '392.168.1.1-192.168.1.10' do
      it 'applies' do
        pp = <<-EOS
          class { '::firewall': }
          firewall { '557 - test':
            proto  => tcp,
            port   => '557',
            action => accept,
            src_range => '392.168.1.1-192.168.1.10',
          }
        EOS

        apply_manifest(pp, :expect_failures => true) do |r|
          expect(r.stderr).to match(/Invalid value "392.168.1.1-192.168.1.10"/)
        end
      end

      it 'should not contain the rule' do
         shell('iptables-save') do |r|
           expect(r.stdout).to_not match(/-A INPUT -p tcp -m iprange --src-range 392.168.1.1-192.168.1.10 -m multiport --ports 557 -m comment --comment "557 - test" -j ACCEPT/)
         end
      end
    end
  end

  describe 'destination' do
    context '192.168.2.0/24' do
      it 'applies' do
        pp = <<-EOS
          class { '::firewall': }
          firewall { '558 - test':
            proto  => tcp,
            port   => '558',
            action => accept,
            destination => '192.168.2.0/24',
          }
        EOS

        apply_manifest(pp, :catch_failures => true)
        apply_manifest(pp, :catch_changes => true)
      end

      it 'should contain the rule' do
         shell('iptables-save') do |r|
           expect(r.stdout).to match(/-A INPUT -d 192.168.2.0\/(24|255\.255\.255\.0) -p tcp -m multiport --ports 558 -m comment --comment "558 - test" -j ACCEPT/)
         end
      end
    end

    context '! 192.168.2.0/24' do
      it 'applies' do
        pp = <<-EOS
          class { '::firewall': }
          firewall { '558 - test':
            proto  => tcp,
            port   => '558',
            action => accept,
            destination => '! 192.168.2.0/24',
          }
        EOS

        apply_manifest(pp, :catch_failures => true)
        apply_manifest(pp, :catch_changes => true)
      end

      it 'should contain the rule' do
         shell('iptables-save') do |r|
           expect(r.stdout).to match(/-A INPUT (! -d|-d !) 192.168.2.0\/(24|255\.255\.255\.0) -p tcp -m multiport --ports 558 -m comment --comment "558 - test" -j ACCEPT/)
         end
      end
    end

    # Invalid address
    context '256.168.2.0/24' do
      it 'applies' do
        pp = <<-EOS
          class { '::firewall': }
          firewall { '558 - test':
            proto  => tcp,
            port   => '558',
            action => accept,
            destination => '256.168.2.0/24',
          }
        EOS

        apply_manifest(pp, :expect_failures => true) do |r|
          expect(r.stderr).to match(/host_to_ip failed for 256.168.2.0\/(24|255\.255\.255\.0)/)
        end
      end

      it 'should not contain the rule' do
         shell('iptables-save') do |r|
           expect(r.stdout).to_not match(/-A INPUT -d 256.168.2.0\/(24|255\.255\.255\.0) -p tcp -m multiport --ports 558 -m comment --comment "558 - test" -j ACCEPT/)
         end
      end
    end
  end

  describe 'dst_range' do
    context '192.168.1.1-192.168.1.10' do
      it 'applies' do
        pp = <<-EOS
          class { '::firewall': }
          firewall { '559 - test':
            proto  => tcp,
            port   => '559',
            action => accept,
            dst_range => '192.168.1.1-192.168.1.10',
          }
        EOS

        apply_manifest(pp, :catch_failures => true)
        apply_manifest(pp, :catch_changes => true)
      end

      it 'should contain the rule' do
         shell('iptables-save') do |r|
           expect(r.stdout).to match(/-A INPUT -p tcp -m iprange --dst-range 192.168.1.1-192.168.1.10 -m multiport --ports 559 -m comment --comment "559 - test" -j ACCEPT/)
         end
      end
    end

    # Invalid IP
    context '392.168.1.1-192.168.1.10' do
      it 'applies' do
        pp = <<-EOS
          class { '::firewall': }
          firewall { '559 - test':
            proto  => tcp,
            port   => '559',
            action => accept,
            dst_range => '392.168.1.1-192.168.1.10',
          }
        EOS

        apply_manifest(pp, :expect_failures => true) do |r|
          expect(r.stderr).to match(/Invalid value "392.168.1.1-192.168.1.10"/)
        end
      end

      it 'should not contain the rule' do
         shell('iptables-save') do |r|
           expect(r.stdout).to_not match(/-A INPUT -p tcp -m iprange --dst-range 392.168.1.1-192.168.1.10 -m multiport --ports 559 -m comment --comment "559 - test" -j ACCEPT/)
         end
      end
    end
  end

  describe 'sport' do
    context 'single port' do
      it 'applies' do
        pp = <<-EOS
          class { '::firewall': }
          firewall { '560 - test':
            proto  => tcp,
            sport  => '560',
            action => accept,
          }
        EOS

        apply_manifest(pp, :catch_failures => true)
      end

      it 'should contain the rule' do
         shell('iptables-save') do |r|
           expect(r.stdout).to match(/-A INPUT -p tcp -m multiport --sports 560 -m comment --comment "560 - test" -j ACCEPT/)
         end
      end
    end

    context 'multiple ports' do
      it 'applies' do
        pp = <<-EOS
          class { '::firewall': }
          firewall { '560 - test':
            proto  => tcp,
            sport  => '560-561',
            action => accept,
          }
        EOS

        apply_manifest(pp, :catch_failures => true)
      end

      it 'should contain the rule' do
         shell('iptables-save') do |r|
           expect(r.stdout).to match(/-A INPUT -p tcp -m multiport --sports 560:561 -m comment --comment "560 - test" -j ACCEPT/)
         end
      end
    end

    context 'invalid ports' do
      it 'applies' do
        pp = <<-EOS
          class { '::firewall': }
          firewall { '560 - test':
            proto  => tcp,
            sport  => '9999560-561',
            action => accept,
          }
        EOS

        apply_manifest(pp, :expect_failures => true) do |r|
          expect(r.stderr).to match(/invalid port\/service `9999560' specified/)
        end
      end

      it 'should contain the rule' do
         shell('iptables-save') do |r|
           expect(r.stdout).to_not match(/-A INPUT -p tcp -m multiport --sports 9999560-561 -m comment --comment "560 - test" -j ACCEPT/)
         end
      end
    end
  end

  describe 'dport' do
    context 'single port' do
      it 'applies' do
        pp = <<-EOS
          class { '::firewall': }
          firewall { '561 - test':
            proto  => tcp,
            dport  => '561',
            action => accept,
          }
        EOS

        apply_manifest(pp, :catch_failures => true)
      end

      it 'should contain the rule' do
         shell('iptables-save') do |r|
           expect(r.stdout).to match(/-A INPUT -p tcp -m multiport --dports 561 -m comment --comment "561 - test" -j ACCEPT/)
         end
      end
    end

    context 'multiple ports' do
      it 'applies' do
        pp = <<-EOS
          class { '::firewall': }
          firewall { '561 - test':
            proto  => tcp,
            dport  => '561-562',
            action => accept,
          }
        EOS

        apply_manifest(pp, :catch_failures => true)
      end

      it 'should contain the rule' do
         shell('iptables-save') do |r|
           expect(r.stdout).to match(/-A INPUT -p tcp -m multiport --dports 561:562 -m comment --comment "561 - test" -j ACCEPT/)
         end
      end
    end

    context 'invalid ports' do
      it 'applies' do
        pp = <<-EOS
          class { '::firewall': }
          firewall { '561 - test':
            proto  => tcp,
            dport  => '9999561-562',
            action => accept,
          }
        EOS

        apply_manifest(pp, :expect_failures => true) do |r|
          expect(r.stderr).to match(/invalid port\/service `9999561' specified/)
        end
      end

      it 'should contain the rule' do
         shell('iptables-save') do |r|
           expect(r.stdout).to_not match(/-A INPUT -p tcp -m multiport --dports 9999561-562 -m comment --comment "560 - test" -j ACCEPT/)
         end
      end
    end
  end

  describe 'port' do
    context 'single port' do
      it 'applies' do
        pp = <<-EOS
          class { '::firewall': }
          firewall { '562 - test':
            proto  => tcp,
            port  => '562',
            action => accept,
          }
        EOS

        apply_manifest(pp, :catch_failures => true)
      end

      it 'should contain the rule' do
         shell('iptables-save') do |r|
           expect(r.stdout).to match(/-A INPUT -p tcp -m multiport --ports 562 -m comment --comment "562 - test" -j ACCEPT/)
         end
      end
    end

    context 'multiple ports' do
      it 'applies' do
        pp = <<-EOS
          class { '::firewall': }
          firewall { '562 - test':
            proto  => tcp,
            port  => '562-563',
            action => accept,
          }
        EOS

        apply_manifest(pp, :catch_failures => true)
      end

      it 'should contain the rule' do
         shell('iptables-save') do |r|
           expect(r.stdout).to match(/-A INPUT -p tcp -m multiport --ports 562:563 -m comment --comment "562 - test" -j ACCEPT/)
         end
      end
    end

    context 'invalid ports' do
      it 'applies' do
        pp = <<-EOS
          class { '::firewall': }
          firewall { '562 - test':
            proto  => tcp,
            port  => '9999562-563',
            action => accept,
          }
        EOS

        apply_manifest(pp, :expect_failures => true) do |r|
          expect(r.stderr).to match(/invalid port\/service `9999562' specified/)
        end
      end

      it 'should contain the rule' do
         shell('iptables-save') do |r|
           expect(r.stdout).to_not match(/-A INPUT -p tcp -m multiport --ports 9999562-563 -m comment --comment "562 - test" -j ACCEPT/)
         end
      end
    end
  end

  ['dst_type', 'src_type'].each do |type|
    describe "#{type}" do
      context 'MULTICAST' do
        it 'applies' do
          pp = <<-EOS
            class { '::firewall': }
            firewall { '563 - test':
              proto  => tcp,
              action => accept,
              #{type} => 'MULTICAST',
            }
          EOS

          apply_manifest(pp, :catch_failures => true)
        end

        it 'should contain the rule' do
          shell('iptables-save') do |r|
            expect(r.stdout).to match(/-A INPUT -p tcp -m addrtype\s.*\sMULTICAST -m comment --comment "563 - test" -j ACCEPT/)
          end
        end
      end

      context 'BROKEN' do
        it 'fails' do
          pp = <<-EOS
            class { '::firewall': }
            firewall { '563 - test':
              proto  => tcp,
              action => accept,
              #{type} => 'BROKEN',
            }
          EOS

          apply_manifest(pp, :expect_failures => true) do |r|
            expect(r.stderr).to match(/Invalid value "BROKEN"./)
          end
        end

        it 'should not contain the rule' do
          shell('iptables-save') do |r|
            expect(r.stdout).to_not match(/-A INPUT -p tcp -m addrtype\s.*\sBROKEN -m comment --comment "563 - test" -j ACCEPT/)
          end
        end
      end
    end
  end

  describe 'tcp_flags' do
    context 'FIN,SYN ACK' do
      it 'applies' do
        pp = <<-EOS
          class { '::firewall': }
          firewall { '564 - test':
            proto  => tcp,
            action => accept,
            tcp_flags => 'FIN,SYN ACK',
          }
        EOS

        apply_manifest(pp, :catch_failures => true)
      end

      it 'should contain the rule' do
        shell('iptables-save') do |r|
          expect(r.stdout).to match(/-A INPUT -p tcp -m tcp --tcp-flags FIN,SYN ACK -m comment --comment "564 - test" -j ACCEPT/)
        end
      end
    end
  end

  describe 'chain' do
    context 'INPUT' do
      it 'applies' do
        pp = <<-EOS
          class { '::firewall': }
          firewall { '565 - test':
            proto  => tcp,
            action => accept,
            chain  => 'FORWARD',
          }
        EOS

        apply_manifest(pp, :catch_failures => true)
      end

      it 'should contain the rule' do
        shell('iptables-save') do |r|
          expect(r.stdout).to match(/-A FORWARD -p tcp -m comment --comment "565 - test" -j ACCEPT/)
        end
      end
    end
  end

  describe 'table' do
    context 'mangle' do
      it 'applies' do
        pp = <<-EOS
          class { '::firewall': }
          firewall { '566 - test':
            proto  => tcp,
            action => accept,
            table  => 'mangle',
          }
        EOS

        apply_manifest(pp, :catch_failures => true)
      end

      it 'should contain the rule' do
        shell('iptables-save -t mangle') do |r|
          expect(r.stdout).to match(/-A INPUT -p tcp -m comment --comment "566 - test" -j ACCEPT/)
        end
      end
    end
    context 'nat' do
      it 'applies' do
        pp = <<-EOS
          class { '::firewall': }
          firewall { '566 - test2':
            proto  => tcp,
            action => accept,
            table  => 'nat',
            chain  => 'OUTPUT',
          }
        EOS

        apply_manifest(pp, :catch_failures => true)
      end

      it 'should not contain the rule' do
        shell('iptables-save -t nat') do |r|
          expect(r.stdout).to match(/-A OUTPUT -p tcp -m comment --comment "566 - test2" -j ACCEPT/)
        end
      end
    end
  end

  describe 'jump' do
    after :all do
      iptables_flush_all_tables
      expect(shell('iptables -t filter -X TEST').stderr).to eq("")
    end

    context 'MARK' do
      it 'applies' do
        pp = <<-EOS
          class { '::firewall': }
          firewallchain { 'TEST:filter:IPv4':
            ensure => present,
          }
          firewall { '567 - test':
            proto  => tcp,
            chain  => 'INPUT',
            jump  => 'TEST',
          }
        EOS

        apply_manifest(pp, :catch_failures => true)
      end

      it 'should contain the rule' do
        shell('iptables-save') do |r|
          expect(r.stdout).to match(/-A INPUT -p tcp -m comment --comment "567 - test" -j TEST/)
        end
      end
    end

    context 'jump and apply' do
      it 'applies' do
        pp = <<-EOS
          class { '::firewall': }
          firewallchain { 'TEST:filter:IPv4':
            ensure => present,
          }
          firewall { '568 - test':
            proto  => tcp,
            chain  => 'INPUT',
            action => 'accept',
            jump  => 'TEST',
          }
        EOS

        apply_manifest(pp, :expect_failures => true) do |r|
          expect(r.stderr).to match(/Only one of the parameters 'action' and 'jump' can be set/)
        end
      end

      it 'should not contain the rule' do
        shell('iptables-save') do |r|
          expect(r.stdout).to_not match(/-A INPUT -p tcp -m comment --comment "568 - test" -j TEST/)
        end
      end
    end
  end

  describe 'tosource' do
    context '192.168.1.1' do
      it 'applies' do
        pp = <<-EOS
          class { '::firewall': }
          firewall { '568 - test':
            proto  => tcp,
            table  => 'nat',
            chain  => 'POSTROUTING',
            jump  => 'SNAT',
            tosource => '192.168.1.1',
          }
        EOS

        apply_manifest(pp, :catch_failures => true)
      end

      it 'should contain the rule' do
        shell('iptables-save -t nat') do |r|
          expect(r.stdout).to match(/A POSTROUTING -p tcp -m comment --comment "568 - test" -j SNAT --to-source 192.168.1.1/)
        end
      end
    end
  end

  describe 'todest' do
    context '192.168.1.1' do
      it 'applies' do
        pp = <<-EOS
          class { '::firewall': }
          firewall { '569 - test':
            proto  => tcp,
            table  => 'nat',
            chain  => 'PREROUTING',
            jump   => 'DNAT',
            source => '200.200.200.200',
            todest => '192.168.1.1',
          }
        EOS

        apply_manifest(pp, :catch_failures => true)
      end

      it 'should contain the rule' do
        shell('iptables-save -t nat') do |r|
          expect(r.stdout).to match(/-A PREROUTING -s 200.200.200.200(\/32)? -p tcp -m comment --comment "569 - test" -j DNAT --to-destination 192.168.1.1/)
        end
      end
    end
  end

  describe 'toports' do
    context '192.168.1.1' do
      it 'applies' do
        pp = <<-EOS
          class { '::firewall': }
          firewall { '570 - test':
            proto  => icmp,
            table  => 'nat',
            chain  => 'PREROUTING',
            jump  => 'REDIRECT',
            toports => '2222',
          }
        EOS

        apply_manifest(pp, :catch_failures => true)
      end

      it 'should contain the rule' do
        shell('iptables-save -t nat') do |r|
          expect(r.stdout).to match(/-A PREROUTING -p icmp -m comment --comment "570 - test" -j REDIRECT --to-ports 2222/)
        end
      end
    end
  end

  # RHEL5 does not support --random
  if default['platform'] !~ /el-5/
    describe 'random' do
      context '192.168.1.1' do
        it 'applies' do
          pp = <<-EOS
            class { '::firewall': }
            firewall { '570 - test 2':
              proto  => all,
              table  => 'nat',
              chain  => 'POSTROUTING',
              jump   => 'MASQUERADE',
              source => '172.30.0.0/16',
              random => true
            }
          EOS

          apply_manifest(pp, :catch_failures => true)
          apply_manifest(pp, :catch_changes => true)
        end

        it 'should contain the rule' do
          shell('iptables-save -t nat') do |r|
            expect(r.stdout).to match(/-A POSTROUTING -s 172\.30\.0\.0\/16 -m comment --comment "570 - test 2" -j MASQUERADE --random/)
          end
        end
      end
    end
  end

  describe 'icmp' do
    context 'any' do
      it 'fails' do
        pp = <<-EOS
          class { '::firewall': }
          firewall { '571 - test':
            proto  => icmp,
            icmp   => 'any',
          }
        EOS

        apply_manifest(pp, :expect_failures => true) do |r|
          expect(r.stderr).to match(/This behaviour should be achieved by omitting or undefining the ICMP parameter/)
        end
      end

      it 'should not contain the rule' do
        shell('iptables-save') do |r|
          expect(r.stdout).to_not match(/-A INPUT -p icmp -m comment --comment "570 - test" -m icmp --icmp-type 11/)
        end
      end
    end
  end

  #iptables version 1.3.5 is not suppored by the ip6tables provider
  if default['platform'] !~ /el-5/
    describe 'hop_limit' do
      context '5' do
        it 'applies' do
          pp = <<-EOS
            class { '::firewall': }
            firewall { '571 - test':
              ensure => present,
              proto => tcp,
              port   => '571',
              action => accept,
              hop_limit => '5',
              provider => 'ip6tables',
            }
          EOS

          apply_manifest(pp, :catch_failures => true)
        end

        it 'should contain the rule' do
          shell('ip6tables-save') do |r|
            expect(r.stdout).to match(/-A INPUT -p tcp -m multiport --ports 571 -m comment --comment "571 - test" -m hl --hl-eq 5 -j ACCEPT/)
          end
        end
      end

      context 'invalid' do
        it 'applies' do
          pp = <<-EOS
            class { '::firewall': }
            firewall { '571 - test':
              ensure => present,
              proto => tcp,
              port   => '571',
              action => accept,
              hop_limit => 'invalid',
              provider => 'ip6tables',
            }
          EOS

          apply_manifest(pp, :expect_failures => true) do |r|
            expect(r.stderr).to match(/Invalid value "invalid"./)
          end
        end

        it 'should not contain the rule' do
          shell('ip6tables-save') do |r|
            expect(r.stdout).to_not match(/-A INPUT -p tcp -m multiport --ports 571 -m comment --comment "571 - test" -m hl --hl-eq invalid -j ACCEPT/)
          end
        end
      end
    end

    describe 'ishasmorefrags' do
      context 'true' do
        it 'applies' do
          pp = <<-EOS
            class { '::firewall': }
            firewall { '587 - test':
              ensure => present,
              proto => tcp,
              port   => '587',
              action => accept,
              ishasmorefrags => true,
              provider => 'ip6tables',
            }
          EOS

          apply_manifest(pp, :catch_failures => true)
        end

        it 'should contain the rule' do
          shell('ip6tables-save') do |r|
            expect(r.stdout).to match(/A INPUT -p tcp -m frag --fragid 0 --fragmore -m multiport --ports 587 -m comment --comment "587 - test" -j ACCEPT/)
          end
        end
      end

      context 'false' do
        it 'applies' do
          pp = <<-EOS
            class { '::firewall': }
            firewall { '588 - test':
              ensure => present,
              proto => tcp,
              port   => '588',
              action => accept,
              ishasmorefrags => false,
              provider => 'ip6tables',
            }
          EOS

          apply_manifest(pp, :catch_failures => true)
        end

        it 'should contain the rule' do
          shell('ip6tables-save') do |r|
            expect(r.stdout).to match(/-A INPUT -p tcp -m multiport --ports 588 -m comment --comment "588 - test" -j ACCEPT/)
          end
        end
      end
    end

    describe 'islastfrag' do
      context 'true' do
        it 'applies' do
          pp = <<-EOS
            class { '::firewall': }
            firewall { '589 - test':
              ensure => present,
              proto => tcp,
              port   => '589',
              action => accept,
              islastfrag => true,
              provider => 'ip6tables',
            }
          EOS

          apply_manifest(pp, :catch_failures => true)
        end

        it 'should contain the rule' do
          shell('ip6tables-save') do |r|
            expect(r.stdout).to match(/-A INPUT -p tcp -m frag --fragid 0 --fraglast -m multiport --ports 589 -m comment --comment "589 - test" -j ACCEPT/)
          end
        end
      end

      context 'false' do
        it 'applies' do
          pp = <<-EOS
            class { '::firewall': }
            firewall { '590 - test':
              ensure => present,
              proto => tcp,
              port   => '590',
              action => accept,
              islastfrag => false,
              provider => 'ip6tables',
            }
          EOS

          apply_manifest(pp, :catch_failures => true)
        end

        it 'should contain the rule' do
          shell('ip6tables-save') do |r|
            expect(r.stdout).to match(/-A INPUT -p tcp -m multiport --ports 590 -m comment --comment "590 - test" -j ACCEPT/)
          end
        end
      end
    end

    describe 'isfirstfrag' do
      context 'true' do
        it 'applies' do
          pp = <<-EOS
            class { '::firewall': }
            firewall { '591 - test':
              ensure => present,
              proto => tcp,
              port   => '591',
              action => accept,
              isfirstfrag => true,
              provider => 'ip6tables',
            }
          EOS

          apply_manifest(pp, :catch_failures => true)
        end

        it 'should contain the rule' do
          shell('ip6tables-save') do |r|
            expect(r.stdout).to match(/-A INPUT -p tcp -m frag --fragid 0 --fragfirst -m multiport --ports 591 -m comment --comment "591 - test" -j ACCEPT/)
          end
        end
      end

      context 'false' do
        it 'applies' do
          pp = <<-EOS
            class { '::firewall': }
            firewall { '592 - test':
              ensure => present,
              proto => tcp,
              port   => '592',
              action => accept,
              isfirstfrag => false,
              provider => 'ip6tables',
            }
          EOS

          apply_manifest(pp, :catch_failures => true)
        end

        it 'should contain the rule' do
          shell('ip6tables-save') do |r|
            expect(r.stdout).to match(/-A INPUT -p tcp -m multiport --ports 592 -m comment --comment "592 - test" -j ACCEPT/)
          end
        end
      end
    end
  end

  describe 'limit' do
    context '500/sec' do
      it 'applies' do
        pp = <<-EOS
          class { '::firewall': }
          firewall { '572 - test':
            ensure => present,
            proto => tcp,
            port   => '572',
            action => accept,
            limit => '500/sec',
          }
        EOS

        apply_manifest(pp, :catch_failures => true)
      end

      it 'should contain the rule' do
        shell('iptables-save') do |r|
          expect(r.stdout).to match(/-A INPUT -p tcp -m multiport --ports 572 -m comment --comment "572 - test" -m limit --limit 500\/sec -j ACCEPT/)
        end
      end
    end
  end

  describe 'burst' do
    context '500' do
      it 'applies' do
        pp = <<-EOS
          class { '::firewall': }
          firewall { '573 - test':
            ensure => present,
            proto => tcp,
            port   => '573',
            action => accept,
            limit => '500/sec',
            burst => '1500',
          }
        EOS

        apply_manifest(pp, :catch_failures => true)
      end

      it 'should contain the rule' do
        shell('iptables-save') do |r|
          expect(r.stdout).to match(/-A INPUT -p tcp -m multiport --ports 573 -m comment --comment "573 - test" -m limit --limit 500\/sec --limit-burst 1500 -j ACCEPT/)
        end
      end
    end

    context 'invalid' do
      it 'applies' do
        pp = <<-EOS
          class { '::firewall': }
          firewall { '571 - test':
            ensure => present,
            proto => tcp,
            port   => '571',
            action => accept,
            limit => '500/sec',
            burst => '1500/sec',
          }
        EOS

        apply_manifest(pp, :expect_failures => true) do |r|
          expect(r.stderr).to match(/Invalid value "1500\/sec"./)
        end
      end

      it 'should not contain the rule' do
        shell('iptables-save') do |r|
          expect(r.stdout).to_not match(/-A INPUT -p tcp -m multiport --ports 573 -m comment --comment "573 - test" -m limit --limit 500\/sec --limit-burst 1500\/sec -j ACCEPT/)
        end
      end
    end
  end

  describe 'uid' do
    context 'nobody' do
      it 'applies' do
        pp = <<-EOS
          class { '::firewall': }
          firewall { '574 - test':
            ensure => present,
            proto => tcp,
            chain => 'OUTPUT',
            port   => '574',
            action => accept,
            uid => 'nobody',
          }
        EOS

        apply_manifest(pp, :catch_failures => true)
      end

      it 'should contain the rule' do
        shell('iptables-save') do |r|
          expect(r.stdout).to match(/-A OUTPUT -p tcp -m owner --uid-owner (nobody|\d+) -m multiport --ports 574 -m comment --comment "574 - test" -j ACCEPT/)
        end
      end
    end
  end

  describe 'gid' do
    context 'root' do
      it 'applies' do
        pp = <<-EOS
          class { '::firewall': }
          firewall { '575 - test':
            ensure => present,
            proto => tcp,
            chain => 'OUTPUT',
            port   => '575',
            action => accept,
            gid => 'root',
          }
        EOS

        apply_manifest(pp, :catch_failures => true)
      end

      it 'should contain the rule' do
        shell('iptables-save') do |r|
          expect(r.stdout).to match(/-A OUTPUT -p tcp -m owner --gid-owner (root|\d+) -m multiport --ports 575 -m comment --comment "575 - test" -j ACCEPT/)
        end
      end
    end
  end

  #iptables version 1.3.5 does not support masks on MARK rules
  if default['platform'] !~ /el-5/
    describe 'set_mark' do
      context '0x3e8/0xffffffff' do
        it 'applies' do
          pp = <<-EOS
            class { '::firewall': }
            firewall { '580 - test':
              ensure => present,
              chain => 'OUTPUT',
              proto => tcp,
              port   => '580',
              jump => 'MARK',
              table => 'mangle',
              set_mark => '0x3e8/0xffffffff',
            }
          EOS

          apply_manifest(pp, :catch_failures => true)
        end

        it 'should contain the rule' do
          shell('iptables-save -t mangle') do |r|
            expect(r.stdout).to match(/-A OUTPUT -p tcp -m multiport --ports 580 -m comment --comment "580 - test" -j MARK --set-xmark 0x3e8\/0xffffffff/)
          end
        end
      end
    end
  end

  describe 'pkttype' do
    context 'multicast' do
      it 'applies' do
        pp = <<-EOS
          class { '::firewall': }
          firewall { '581 - test':
            ensure => present,
            proto => tcp,
            port   => '581',
            action => accept,
            pkttype => 'multicast',
          }
        EOS

        apply_manifest(pp, :catch_failures => true)
      end

      it 'should contain the rule' do
        shell('iptables-save') do |r|
          expect(r.stdout).to match(/-A INPUT -p tcp -m multiport --ports 581 -m pkttype --pkt-type multicast -m comment --comment "581 - test" -j ACCEPT/)
        end
      end
    end

    context 'test' do
      it 'applies' do
        pp = <<-EOS
          class { '::firewall': }
          firewall { '582 - test':
            ensure => present,
            proto => tcp,
            port   => '582',
            action => accept,
            pkttype => 'test',
          }
        EOS

        apply_manifest(pp, :expect_failures => true) do |r|
          expect(r.stderr).to match(/Invalid value "test"./)
        end
      end

      it 'should not contain the rule' do
        shell('iptables-save') do |r|
          expect(r.stdout).to_not match(/-A INPUT -p tcp -m multiport --ports 582 -m pkttype --pkt-type multicast -m comment --comment "582 - test" -j ACCEPT/)
        end
      end
    end
  end

  describe 'isfragment' do
    context 'true' do
      it 'applies' do
        pp = <<-EOS
          class { '::firewall': }
          firewall { '583 - test':
            ensure => present,
            proto => tcp,
            port   => '583',
            action => accept,
            isfragment => true,
          }
        EOS

        apply_manifest(pp, :catch_failures => true)
      end

      it 'should contain the rule' do
        shell('iptables-save') do |r|
          expect(r.stdout).to match(/-A INPUT -p tcp -f -m multiport --ports 583 -m comment --comment "583 - test" -j ACCEPT/)
        end
      end
    end

    context 'false' do
      it 'applies' do
        pp = <<-EOS
          class { '::firewall': }
          firewall { '584 - test':
            ensure => present,
            proto => tcp,
            port   => '584',
            action => accept,
            isfragment => false,
          }
        EOS

        apply_manifest(pp, :catch_failures => true)
      end

      it 'should contain the rule' do
        shell('iptables-save') do |r|
          expect(r.stdout).to match(/-A INPUT -p tcp -m multiport --ports 584 -m comment --comment "584 - test" -j ACCEPT/)
        end
      end
    end
  end

  # RHEL5/SLES does not support -m socket
  describe 'socket', :unless => (default['platform'] =~ /el-5/ or fact('operatingsystem') == 'SLES') do
    context 'true' do
      it 'applies' do
        pp = <<-EOS
          class { '::firewall': }
          firewall { '585 - test':
            ensure => present,
            proto => tcp,
            port   => '585',
            action => accept,
            chain  => 'PREROUTING',
            table  => 'nat',
            socket => true,
          }
        EOS

        apply_manifest(pp, :catch_failures => true)
      end

      it 'should contain the rule' do
        shell('iptables-save -t nat') do |r|
          expect(r.stdout).to match(/-A PREROUTING -p tcp -m multiport --ports 585 -m socket -m comment --comment "585 - test" -j ACCEPT/)
        end
      end
    end

    context 'false' do
      it 'applies' do
        pp = <<-EOS
          class { '::firewall': }
          firewall { '586 - test':
            ensure => present,
            proto => tcp,
            port   => '586',
            action => accept,
            chain  => 'PREROUTING',
            table  => 'nat',
            socket => false,
          }
        EOS

        apply_manifest(pp, :catch_failures => true)
      end

      it 'should contain the rule' do
        shell('iptables-save -t nat') do |r|
          expect(r.stdout).to match(/-A PREROUTING -p tcp -m multiport --ports 586 -m comment --comment "586 - test" -j ACCEPT/)
        end
      end
    end
  end

  describe 'ipsec_policy' do
    context 'ipsec' do
      it 'applies' do
        pp = <<-EOS
          class { '::firewall': }
          firewall { '593 - test':
            ensure       => 'present',
            action       => 'reject',
            chain        => 'OUTPUT',
            destination  => '20.0.0.0/8',
            ipsec_dir    => 'out',
            ipsec_policy => 'ipsec',
            proto        => 'all',
            reject       => 'icmp-net-unreachable',
            table        => 'filter',
          }
        EOS

        apply_manifest(pp, :catch_failures => true)
      end

      it 'should contain the rule' do
        shell('iptables-save') do |r|
          expect(r.stdout).to match(/-A OUTPUT -d 20.0.0.0\/(8|255\.0\.0\.0) -m comment --comment "593 - test" -m policy --dir out --pol ipsec -j REJECT --reject-with icmp-net-unreachable/)
        end
      end
    end

    context 'none' do
      it 'applies' do
        pp = <<-EOS
          class { '::firewall': }
          firewall { '594 - test':
            ensure       => 'present',
            action       => 'reject',
            chain        => 'OUTPUT',
            destination  => '20.0.0.0/8',
            ipsec_dir    => 'out',
            ipsec_policy => 'none',
            proto        => 'all',
            reject       => 'icmp-net-unreachable',
            table        => 'filter',
          }
        EOS

        apply_manifest(pp, :catch_failures => true)
      end

      it 'should contain the rule' do
        shell('iptables-save') do |r|
          expect(r.stdout).to match(/-A OUTPUT -d 20.0.0.0\/(8|255\.0\.0\.0) -m comment --comment "594 - test" -m policy --dir out --pol none -j REJECT --reject-with icmp-net-unreachable/)
        end
      end
    end
  end

  describe 'ipsec_dir' do
    context 'out' do
      it 'applies' do
        pp = <<-EOS
          class { '::firewall': }
          firewall { '595 - test':
            ensure       => 'present',
            action       => 'reject',
            chain        => 'OUTPUT',
            destination  => '20.0.0.0/8',
            ipsec_dir    => 'out',
            ipsec_policy => 'ipsec',
            proto        => 'all',
            reject       => 'icmp-net-unreachable',
            table        => 'filter',
          }
        EOS

        apply_manifest(pp, :catch_failures => true)
      end

      it 'should contain the rule' do
        shell('iptables-save') do |r|
          expect(r.stdout).to match(/-A OUTPUT -d 20.0.0.0\/(8|255\.0\.0\.0) -m comment --comment "595 - test" -m policy --dir out --pol ipsec -j REJECT --reject-with icmp-net-unreachable/)
        end
      end
    end

    context 'in' do
      it 'applies' do
        pp = <<-EOS
          class { '::firewall': }
          firewall { '596 - test':
            ensure       => 'present',
            action       => 'reject',
            chain        => 'INPUT',
            destination  => '20.0.0.0/8',
            ipsec_dir    => 'in',
            ipsec_policy => 'none',
            proto        => 'all',
            reject       => 'icmp-net-unreachable',
            table        => 'filter',
          }
        EOS

        apply_manifest(pp, :catch_failures => true)
      end

      it 'should contain the rule' do
        shell('iptables-save') do |r|
          expect(r.stdout).to match(/-A INPUT -d 20.0.0.0\/(8|255\.0\.0\.0) -m comment --comment "596 - test" -m policy --dir in --pol none -j REJECT --reject-with icmp-net-unreachable/)
        end
      end
    end
  end

  describe 'recent' do
    context 'set' do
      it 'applies' do
        pp = <<-EOS
          class { '::firewall': }
          firewall { '597 - test':
            ensure       => 'present',
            chain        => 'INPUT',
            destination  => '30.0.0.0/8',
            proto        => 'all',
            table        => 'filter',
            recent       => 'set',
            rdest        => true,
            rname        => 'list1',
          }
        EOS

        apply_manifest(pp, :catch_failures => true)
      end

      it 'should contain the rule' do
        shell('iptables-save') do |r|
          # Mask added as of Ubuntu 14.04.
          expect(r.stdout).to match(/-A INPUT -d 30.0.0.0\/(8|255\.0\.0\.0) -m comment --comment "597 - test" -m recent --set --name list1 (--mask 255.255.255.255 )?--rdest/)
        end
      end
    end

    context 'rcheck' do
      it 'applies' do
        pp = <<-EOS
          class { '::firewall': }
          firewall { '598 - test':
            ensure       => 'present',
            chain        => 'INPUT',
            destination  => '30.0.0.0/8',
            proto        => 'all',
            table        => 'filter',
            recent       => 'rcheck',
            rsource      => true,
            rname        => 'list1',
            rseconds     => 60,
            rhitcount    => 5,
            rttl         => true,
          }
        EOS

        apply_manifest(pp, :catch_failures => true)
      end

      it 'should contain the rule' do
        shell('iptables-save') do |r|
          expect(r.stdout).to match(/-A INPUT -d 30.0.0.0\/(8|255\.0\.0\.0) -m comment --comment "598 - test" -m recent --rcheck --seconds 60 --hitcount 5 --rttl --name list1 (--mask 255.255.255.255 )?--rsource/)
        end
      end
    end

    context 'update' do
      it 'applies' do
        pp = <<-EOS
          class { '::firewall': }
          firewall { '599 - test':
            ensure       => 'present',
            chain        => 'INPUT',
            destination  => '30.0.0.0/8',
            proto        => 'all',
            table        => 'filter',
            recent       => 'update',
          }
        EOS

        apply_manifest(pp, :catch_failures => true)
      end

      it 'should contain the rule' do
        shell('iptables-save') do |r|
          expect(r.stdout).to match(/-A INPUT -d 30.0.0.0\/(8|255\.0\.0\.0) -m comment --comment "599 - test" -m recent --update/)
        end
      end
    end

    context 'remove' do
      it 'applies' do
        pp = <<-EOS
          class { '::firewall': }
          firewall { '600 - test':
            ensure       => 'present',
            chain        => 'INPUT',
            destination  => '30.0.0.0/8',
            proto        => 'all',
            table        => 'filter',
            recent       => 'remove',
          }
        EOS

        apply_manifest(pp, :catch_failures => true)
      end

      it 'should contain the rule' do
        shell('iptables-save') do |r|
          expect(r.stdout).to match(/-A INPUT -d 30.0.0.0\/(8|255\.0\.0\.0) -m comment --comment "600 - test" -m recent --remove/)
        end
      end
    end
  end

  describe 'reset' do
    it 'deletes all rules' do
      shell('ip6tables --flush')
      shell('iptables --flush; iptables -t nat --flush; iptables -t mangle --flush')
    end
  end

end
