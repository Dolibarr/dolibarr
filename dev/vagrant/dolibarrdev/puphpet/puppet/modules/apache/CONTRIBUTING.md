Checklist (and a short version for the impatient)
=================================================

  * Commits:

    - Make commits of logical units.

    - Check for unnecessary whitespace with "git diff --check" before
      committing.

    - Commit using Unix line endings (check the settings around "crlf" in
      git-config(1)).

    - Do not check in commented out code or unneeded files.

    - The first line of the commit message should be a short
      description (50 characters is the soft limit, excluding ticket
      number(s)), and should skip the full stop.

    - Associate the issue in the message. The first line should include
      the issue number in the form "(#XXXX) Rest of message".

    - The body should provide a meaningful commit message, which:

      - uses the imperative, present tense: "change", not "changed" or
        "changes".

      - includes motivation for the change, and contrasts its
        implementation with the previous behavior.

    - Make sure that you have tests for the bug you are fixing, or
      feature you are adding.

    - Make sure the test suites passes after your commit:
      `bundle exec rspec spec/acceptance` More information on [testing](#Testing) below

    - When introducing a new feature, make sure it is properly
      documented in the README.md

  * Submission:

    * Pre-requisites:

      - Sign the [Contributor License Agreement](https://cla.puppetlabs.com/)

      - Make sure you have a [GitHub account](https://github.com/join)

      - [Create a ticket](http://projects.puppetlabs.com/projects/modules/issues/new), or [watch the ticket](http://projects.puppetlabs.com/projects/modules/issues) you are patching for.

    * Preferred method:

      - Fork the repository on GitHub.

      - Push your changes to a topic branch in your fork of the
        repository. (the format ticket/1234-short_description_of_change is
        usually preferred for this project).

      - Submit a pull request to the repository in the puppetlabs
        organization.

The long version
================

  1.  Make separate commits for logically separate changes.

      Please break your commits down into logically consistent units
      which include new or changed tests relevant to the rest of the
      change.  The goal of doing this is to make the diff easier to
      read for whoever is reviewing your code.  In general, the easier
      your diff is to read, the more likely someone will be happy to
      review it and get it into the code base.

      If you are going to refactor a piece of code, please do so as a
      separate commit from your feature or bug fix changes.

      We also really appreciate changes that include tests to make
      sure the bug is not re-introduced, and that the feature is not
      accidentally broken.

      Describe the technical detail of the change(s).  If your
      description starts to get too long, that is a good sign that you
      probably need to split up your commit into more finely grained
      pieces.

      Commits which plainly describe the things which help
      reviewers check the patch and future developers understand the
      code are much more likely to be merged in with a minimum of
      bike-shedding or requested changes.  Ideally, the commit message
      would include information, and be in a form suitable for
      inclusion in the release notes for the version of Puppet that
      includes them.

      Please also check that you are not introducing any trailing
      whitespace or other "whitespace errors".  You can do this by
      running "git diff --check" on your changes before you commit.

  2.  Sign the Contributor License Agreement

      Before we can accept your changes, we do need a signed Puppet
      Labs Contributor License Agreement (CLA).

      You can access the CLA via the [Contributor License Agreement link](https://cla.puppetlabs.com/)

      If you have any questions about the CLA, please feel free to
      contact Puppet Labs via email at cla-submissions@puppetlabs.com.

  3.  Sending your patches

      To submit your changes via a GitHub pull request, we _highly_
      recommend that you have them on a topic branch, instead of
      directly on "master".
      It makes things much easier to keep track of, especially if
      you decide to work on another thing before your first change
      is merged in.

      GitHub has some pretty good
      [general documentation](http://help.github.com/) on using
      their site.  They also have documentation on
      [creating pull requests](http://help.github.com/send-pull-requests/).

      In general, after pushing your topic branch up to your
      repository on GitHub, you can switch to the branch in the
      GitHub UI and click "Pull Request" towards the top of the page
      in order to open a pull request.


  4.  Update the related GitHub issue.

      If there is a GitHub issue associated with the change you
      submitted, then you should update the ticket to include the
      location of your branch, along with any other commentary you
      may wish to make.

Testing
=======

Getting Started
---------------

Our puppet modules provide [`Gemfile`](./Gemfile)s which can tell a ruby
package manager such as [bundler](http://bundler.io/) what Ruby packages,
or Gems, are required to build, develop, and test this software.

Please make sure you have [bundler installed](http://bundler.io/#getting-started)
on your system, then use it to install all dependencies needed for this project,
by running

```shell
% bundle install
Fetching gem metadata from https://rubygems.org/........
Fetching gem metadata from https://rubygems.org/..
Using rake (10.1.0)
Using builder (3.2.2)
-- 8><-- many more --><8 --
Using rspec-system-puppet (2.2.0)
Using serverspec (0.6.3)
Using rspec-system-serverspec (1.0.0)
Using bundler (1.3.5)
Your bundle is complete!
Use `bundle show [gemname]` to see where a bundled gem is installed.
```

NOTE some systems may require you to run this command with sudo.

If you already have those gems installed, make sure they are up-to-date:

```shell
% bundle update
```

With all dependencies in place and up-to-date we can now run the tests:

```shell
% rake spec
```

This will execute all the [rspec tests](http://rspec-puppet.com/) tests
under [spec/defines](./spec/defines), [spec/classes](./spec/classes),
and so on. rspec tests may have the same kind of dependencies as the
module they are testing. While the module defines in its [Modulefile](./Modulefile),
rspec tests define them in [.fixtures.yml](./fixtures.yml).

Some puppet modules also come with [beaker](https://github.com/puppetlabs/beaker)
tests. These tests spin up a virtual machine under
[VirtualBox](https://www.virtualbox.org/)) with, controlling it with
[Vagrant](http://www.vagrantup.com/) to actually simulate scripted test
scenarios. In order to run these, you will need both of those tools
installed on your system.

You can run them by issuing the following command

```shell
% rake spec_clean
% rspec spec/acceptance
```

This will now download a pre-fabricated image configured in the [default node-set](./spec/acceptance/nodesets/default.yml),
install puppet, copy this module and install its dependencies per [spec/spec_helper_acceptance.rb](./spec/spec_helper_acceptance.rb)
and then run all the tests under [spec/acceptance](./spec/acceptance).

Writing Tests
-------------

XXX getting started writing tests.

If you have commit access to the repository
===========================================

Even if you have commit access to the repository, you will still need to
go through the process above, and have someone else review and merge
in your changes.  The rule is that all changes must be reviewed by a
developer on the project (that did not write the code) to ensure that
all changes go through a code review process.

Having someone other than the author of the topic branch recorded as
performing the merge is the record that they performed the code
review.


Additional Resources
====================

* [Getting additional help](http://projects.puppetlabs.com/projects/puppet/wiki/Getting_Help)

* [Writing tests](http://projects.puppetlabs.com/projects/puppet/wiki/Development_Writing_Tests)

* [Patchwork](https://patchwork.puppetlabs.com)

* [Contributor License Agreement](https://projects.puppetlabs.com/contributor_licenses/sign)

* [General GitHub documentation](http://help.github.com/)

* [GitHub pull request documentation](http://help.github.com/send-pull-requests/)

