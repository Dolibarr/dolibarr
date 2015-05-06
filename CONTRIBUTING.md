How to contribute to Dolibarr
=============================

Bug reports and feature requests
--------------------------------

*Note*: Issues are not a support forum. If you need help using the software, please use [the forums](http://www.dolibarr.org/forum).

**NEW**

Issues are now managed on [GitHub](https://github.com/Dolibarr/dolibarr/Issues).

1. Please [use the search engine](https://help.github.com/articles/searching-issues) to check if nobody's already reported your problem.
2. [Create an issue](https://help.github.com/articles/creating-an-issue). Choose an appropriate title. Prepend appropriately with Bug or Feature Request.
3. Report with as much detail as possible ([Use screenshots or even screencasts whenever possible](https://help.github.com/articles/issue-attachments)).

We're still figuring out how to migrate old issues to GitHub. In the meantime, they are still available at [Doliforge](https://doliforge.org/projects/dolibarr).

<a name=code></a>Code
---------------------

### Basic workflow

1. [Fork](https://help.github.com/articles/fork-a-repo) the [GitHub repository](https://github.com/Dolibarr/dolibarr).
2. Clone your fork.
3. Choose a branch(See the [Branches](#branches) section below).
4. Commit and push your changes.
5. [Make a pull request](https://help.github.com/articles/creating-a-pull-request).

### <a name=branches></a>Branches

Unless you're fixing a bug, all pull requests should be made against the *develop* branch.

If you're fixing a bug, it is preferred that you cook your fix and pull request it
against the oldest version affected that's still supported.

We officially support versions N, N − 1 and N − 2 for N the latest version available.

Choose your base branch accordingly.

### General rules
Please don't edit the ChangeLog file. A project manager will update it from your commit messages.

### Commits
Use clear commit messages with the following structure:

<pre>
FIX|Fix #456 Short description (where #456 is number of bug fix, if it exists. In upper case to appear into ChangeLog)
or
CLOSE|Close #456 Short description (where #456 is number of feature request, if it exists. In upper case to appear into ChangeLog)
or
NEW|New Short description (In upper case to appear into ChangeLog, use this if you add a feature not tracked, otherwise use CLOSE #456)
or
Short description (when the commit is not introducing feature nor closing a bug)

Long description (Can span accross multiple lines).
</pre>

### Pull Requests
When submitting a pull request, use same rule than Commits. With upper case keyword to appear into ChangeLog.


### Resources
[Developer documentation](http://wiki.dolibarr.org/index.php/Developer_documentation)

Translations
------------
The source language (en_US) is maintained in the repository. See the [Code](#code) section above.

All other translations are managed online at [Transifex](https://www.transifex.com/projects/p/dolibarr).

Join an existing translation team or create your own and translate into the interface.

Your translations will be available in the next major release.

### Resources
[Translator documentation](http://wiki.dolibarr.org/index.php/Developer_documentation)

Documentation
-------------
The project's documentation is maintained on the [Wiki](http://wiki.dolibarr.org/index.php).

*You need to create an account before being able to edit.*

