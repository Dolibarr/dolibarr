Puppet::Type.type(:a2mod).provide :modfix do
    desc "Dummy provider for A2mod.

    Fake nil resources when there is no crontab binary available. Allows
    puppetd to run on a bootstrapped machine before a Cron package has been
    installed. Workaround for: http://projects.puppetlabs.com/issues/2384
    "

    def self.instances
        []
    end
end