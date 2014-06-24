require 'spec_helper_acceptance'

describe 'apt::unattended_upgrades class', :unless => UNSUPPORTED_PLATFORMS.include?(fact('osfamily')) do
  context 'defaults' do
    it 'should work with no errors' do
      pp = <<-EOS
      include apt
      include apt::unattended_upgrades
      EOS

      # Attempted workaround for problems seen on debian with
      # something holding the package database open.
      #shell('killall -9 apt-get')
      #shell('killall -9 dpkg')
      apply_manifest(pp, :catch_failures => true)
    end

    describe file('/etc/apt/apt.conf.d/10periodic') do
      it { should be_file }
    end
    describe file('/etc/apt/apt.conf.d/50unattended-upgrades') do
      it { should be_file }
    end
  end

  context 'origins' do
    it 'should work with no errors' do
      pp = <<-EOS
      include apt
      class { 'apt::unattended_upgrades':
        origins => ['${distro_id}:${distro_codename}-test'],
      }
      EOS

      apply_manifest(pp, :catch_failures => true)
    end

    describe file('/etc/apt/apt.conf.d/50unattended-upgrades') do
      it { should be_file }
      it { should contain '${distro_id}:${distro_codename}-test' }
    end
  end

  context 'blacklist' do
    it 'should work with no errors' do
      pp = <<-EOS
      include apt
      class { 'apt::unattended_upgrades':
        blacklist => ['puppet']
      }
      EOS

      apply_manifest(pp, :catch_failures => true)
    end

    describe file('/etc/apt/apt.conf.d/50unattended-upgrades') do
      it { should be_file }
      it { should contain 'puppet' }
    end
  end

  context 'update' do
    it 'should work with no errors' do
      pp = <<-EOS
      include apt
      class { 'apt::unattended_upgrades':
        update => '99'
      }
      EOS

      apply_manifest(pp, :catch_failures => true)
    end

    describe file('/etc/apt/apt.conf.d/10periodic') do
      it { should be_file }
      it { should contain 'APT::Periodic::Update-Package-Lists "99";' }
    end
  end

  context 'download' do
    it 'should work with no errors' do
      pp = <<-EOS
      include apt
      class { 'apt::unattended_upgrades':
        download => '99'
      }
      EOS

      apply_manifest(pp, :catch_failures => true)
    end

    describe file('/etc/apt/apt.conf.d/10periodic') do
      it { should be_file }
      it { should contain 'APT::Periodic::Download-Upgradeable-Packages "99";' }
    end
  end

  context 'upgrade' do
    it 'should work with no errors' do
      pp = <<-EOS
      include apt
      class { 'apt::unattended_upgrades':
        upgrade => '99'
      }
      EOS

      apply_manifest(pp, :catch_failures => true)
    end

    describe file('/etc/apt/apt.conf.d/10periodic') do
      it { should be_file }
      it { should contain 'APT::Periodic::Unattended-Upgrade "99";' }
    end
  end

  context 'autoclean' do
    it 'should work with no errors' do
      pp = <<-EOS
      include apt
      class { 'apt::unattended_upgrades':
        autoclean => '99'
      }
      EOS

      apply_manifest(pp, :catch_failures => true)
    end

    describe file('/etc/apt/apt.conf.d/10periodic') do
      it { should be_file }
      it { should contain 'APT::Periodic::AutocleanInterval "99";' }
    end
  end

  context 'auto_fix' do
    context 'true' do
      it 'should work with no errors' do
        pp = <<-EOS
        include apt
        class { 'apt::unattended_upgrades':
          auto_fix => true
        }
        EOS

        apply_manifest(pp, :catch_failures => true)
      end

      describe file('/etc/apt/apt.conf.d/50unattended-upgrades') do
        it { should be_file }
        it { should contain 'Unattended-Upgrade::AutoFixInterruptedDpkg "true";' }
      end
    end

    context 'false' do
      it 'should work with no errors' do
        pp = <<-EOS
        include apt
        class { 'apt::unattended_upgrades':
          auto_fix => false
        }
        EOS

        apply_manifest(pp, :catch_failures => true)
      end

      describe file('/etc/apt/apt.conf.d/50unattended-upgrades') do
        it { should be_file }
        it { should contain 'Unattended-Upgrade::AutoFixInterruptedDpkg "false";' }
      end
    end
  end

  context 'minimal_steps' do
    context 'true' do
      it 'should work with no errors' do
        pp = <<-EOS
        include apt
        class { 'apt::unattended_upgrades':
          minimal_steps => true
        }
        EOS

        apply_manifest(pp, :catch_failures => true)
      end

      describe file('/etc/apt/apt.conf.d/50unattended-upgrades') do
        it { should be_file }
        it { should contain 'Unattended-Upgrade::MinimalSteps "true";' }
      end
    end

    context 'false' do
      it 'should work with no errors' do
        pp = <<-EOS
        include apt
        class { 'apt::unattended_upgrades':
          minimal_steps => false
        }
        EOS

        apply_manifest(pp, :catch_failures => true)
      end

      describe file('/etc/apt/apt.conf.d/50unattended-upgrades') do
        it { should be_file }
        it { should contain 'Unattended-Upgrade::MinimalSteps "false";' }
      end
    end
  end

  context 'install_on_shutdown' do
    context 'true' do
      it 'should work with no errors' do
        pp = <<-EOS
        include apt
        class { 'apt::unattended_upgrades':
          install_on_shutdown => true
        }
        EOS

        apply_manifest(pp, :catch_failures => true)
      end

      describe file('/etc/apt/apt.conf.d/50unattended-upgrades') do
        it { should be_file }
        it { should contain 'Unattended-Upgrade::InstallOnShutdown "true";' }
      end
    end

    context 'false' do
      it 'should work with no errors' do
        pp = <<-EOS
        include apt
        class { 'apt::unattended_upgrades':
          install_on_shutdown => false
        }
        EOS

        apply_manifest(pp, :catch_failures => true)
      end

      describe file('/etc/apt/apt.conf.d/50unattended-upgrades') do
        it { should be_file }
        it { should contain 'Unattended-Upgrade::InstallOnShutdown "false";' }
      end
    end
  end

  context 'mail_to' do
    it 'should work with no errors' do
      pp = <<-EOS
      include apt
      class { 'apt::unattended_upgrades':
        mail_to => 'test@example.com'
      }
      EOS

      apply_manifest(pp, :catch_failures => true)
    end

    describe file('/etc/apt/apt.conf.d/50unattended-upgrades') do
      it { should be_file }
      it { should contain 'Unattended-Upgrade::Mail "test@example.com";' }
    end
  end

  context 'mail_only_on_error' do
    context 'true' do
      it 'should work with no errors' do
        pp = <<-EOS
        include apt
        class { 'apt::unattended_upgrades':
          mail_to            => 'test@example.com',
          mail_only_on_error => true
        }
        EOS

        apply_manifest(pp, :catch_failures => true)
      end

      describe file('/etc/apt/apt.conf.d/50unattended-upgrades') do
        it { should be_file }
        it { should contain 'Unattended-Upgrade::MailOnlyOnError "true";' }
      end
    end

    context 'false' do
      it 'should work with no errors' do
        pp = <<-EOS
        include apt
        class { 'apt::unattended_upgrades':
          mail_to            => 'test@example.com',
          mail_only_on_error => false,
        }
        EOS

        apply_manifest(pp, :catch_failures => true)
      end

      describe file('/etc/apt/apt.conf.d/50unattended-upgrades') do
        it { should be_file }
        it { should contain 'Unattended-Upgrade::MailOnlyOnError "false";' }
      end
    end

    context 'mail_to missing' do
      it 'should work with no errors' do
        pp = <<-EOS
        include apt
        class { 'apt::unattended_upgrades':
          mail_only_on_error => true,
        }
        EOS

        apply_manifest(pp, :catch_failures => true)
      end

      describe file('/etc/apt/apt.conf.d/50unattended-upgrades') do
        it { should be_file }
        it { should_not contain 'Unattended-Upgrade::MailOnlyOnError "true";' }
      end
    end
  end

  context 'remove_unused' do
    context 'true' do
      it 'should work with no errors' do
        pp = <<-EOS
        include apt
        class { 'apt::unattended_upgrades':
          remove_unused => true
        }
        EOS

        apply_manifest(pp, :catch_failures => true)
      end

      describe file('/etc/apt/apt.conf.d/50unattended-upgrades') do
        it { should be_file }
        it { should contain 'Unattended-Upgrade::Remove-Unused-Dependencies "true";' }
      end
    end

    context 'false' do
      it 'should work with no errors' do
        pp = <<-EOS
        include apt
        class { 'apt::unattended_upgrades':
          remove_unused => false,
        }
        EOS

        apply_manifest(pp, :catch_failures => true)
      end

      describe file('/etc/apt/apt.conf.d/50unattended-upgrades') do
        it { should be_file }
        it { should contain 'Unattended-Upgrade::Remove-Unused-Dependencies "false";' }
      end
    end
  end

  context 'auto_reboot' do
    context 'true' do
      it 'should work with no errors' do
        pp = <<-EOS
        include apt
        class { 'apt::unattended_upgrades':
          auto_reboot => true
        }
        EOS

        apply_manifest(pp, :catch_failures => true)
      end

      describe file('/etc/apt/apt.conf.d/50unattended-upgrades') do
        it { should be_file }
        it { should contain 'Unattended-Upgrade::Automatic-Reboot "true";' }
      end
    end

    context 'false' do
      it 'should work with no errors' do
        pp = <<-EOS
        include apt
        class { 'apt::unattended_upgrades':
          auto_reboot => false,
        }
        EOS

        apply_manifest(pp, :catch_failures => true)
      end

      describe file('/etc/apt/apt.conf.d/50unattended-upgrades') do
        it { should be_file }
        it { should contain 'Unattended-Upgrade::Automatic-Reboot "false";' }
      end
    end
  end

  context 'dl_limit' do
    it 'should work with no errors' do
      pp = <<-EOS
      include apt
      class { 'apt::unattended_upgrades':
        dl_limit => '99'
      }
      EOS

      apply_manifest(pp, :catch_failures => true)
    end

    describe file('/etc/apt/apt.conf.d/50unattended-upgrades') do
      it { should be_file }
      it { should contain 'Acquire::http::Dl-Limit "99"' }
    end
  end

  context 'enable' do
    it 'should work with no errors' do
      pp = <<-EOS
      include apt
      class { 'apt::unattended_upgrades':
        enable => '2'
      }
      EOS

      apply_manifest(pp, :catch_failures => true)
    end

    describe file('/etc/apt/apt.conf.d/10periodic') do
      it { should be_file }
      it { should contain 'APT::Periodic::Enable "2"' }
    end
  end

  context 'backup_interval' do
    it 'should work with no errors' do
      pp = <<-EOS
      include apt
      class { 'apt::unattended_upgrades':
        backup_interval => '2'
      }
      EOS

      apply_manifest(pp, :catch_failures => true)
    end

    describe file('/etc/apt/apt.conf.d/10periodic') do
      it { should be_file }
      it { should contain 'APT::Periodic::BackUpArchiveInterval "2";' }
    end
  end

  context 'backup_level' do
    it 'should work with no errors' do
      pp = <<-EOS
      include apt
      class { 'apt::unattended_upgrades':
        backup_level => '2'
      }
      EOS

      apply_manifest(pp, :catch_failures => true)
    end

    describe file('/etc/apt/apt.conf.d/10periodic') do
      it { should be_file }
      it { should contain 'APT::Periodic::BackUpLevel "2";' }
    end
  end

  context 'max_age' do
    it 'should work with no errors' do
      pp = <<-EOS
      include apt
      class { 'apt::unattended_upgrades':
        max_age => '2'
      }
      EOS

      apply_manifest(pp, :catch_failures => true)
    end

    describe file('/etc/apt/apt.conf.d/10periodic') do
      it { should be_file }
      it { should contain 'APT::Periodic::MaxAge "2";' }
    end
  end

  context 'min_age' do
    it 'should work with no errors' do
      pp = <<-EOS
      include apt
      class { 'apt::unattended_upgrades':
        min_age => '2'
      }
      EOS

      apply_manifest(pp, :catch_failures => true)
    end

    describe file('/etc/apt/apt.conf.d/10periodic') do
      it { should be_file }
      it { should contain 'APT::Periodic::MinAge "2";' }
    end
  end

  context 'max_size' do
    it 'should work with no errors' do
      pp = <<-EOS
      include apt
      class { 'apt::unattended_upgrades':
        max_size => '2'
      }
      EOS

      apply_manifest(pp, :catch_failures => true)
    end

    describe file('/etc/apt/apt.conf.d/10periodic') do
      it { should be_file }
      it { should contain 'APT::Periodic::MaxSize "2";' }
    end
  end

  context 'download_delta' do
    it 'should work with no errors' do
      pp = <<-EOS
      include apt
      class { 'apt::unattended_upgrades':
        download_delta => '2'
      }
      EOS

      apply_manifest(pp, :catch_failures => true)
    end

    describe file('/etc/apt/apt.conf.d/10periodic') do
      it { should be_file }
      it { should contain 'APT::Periodic::Download-Upgradeable-Packages-Debdelta "2";' }
    end
  end

  context 'verbose' do
    it 'should work with no errors' do
      pp = <<-EOS
      include apt
      class { 'apt::unattended_upgrades':
        verbose => '2'
      }
      EOS

      apply_manifest(pp, :catch_failures => true)
    end

    describe file('/etc/apt/apt.conf.d/10periodic') do
      it { should be_file }
      it { should contain 'APT::Periodic::Verbose "2";' }
    end
  end

end
