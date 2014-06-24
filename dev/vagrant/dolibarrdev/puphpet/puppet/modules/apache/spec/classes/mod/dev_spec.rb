require 'spec_helper'

describe 'apache::mod::dev', :type => :class do
  [
    ['RedHat',  '6', 'Santiago'],
    ['Debian',  '6', 'squeeze'],
    ['FreeBSD', '9', 'FreeBSD'],
  ].each do |osfamily, operatingsystemrelease, lsbdistcodename|
    if osfamily == 'FreeBSD'
      let :pre_condition do
        'include apache::package'
      end
    end
    context "on a #{osfamily} OS" do
      let :facts do
        {
          :lsbdistcodename        => lsbdistcodename,
          :osfamily               => osfamily,
          :operatingsystem        => osfamily,
          :operatingsystemrelease => operatingsystemrelease,
        }
      end
      it { should contain_class('apache::dev') }
    end
  end
end
