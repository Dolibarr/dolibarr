How to contribute to Dolibarr
=============================

Bug reports and feature requests
--------------------------------

<a name="not-a-support-forum"></a>*Note*: Issues are not a support forum. If you need help using the software, please use [the forums](http://www.dolibarr.org/forum).

Issues are managed on [GitHub](https://github.com/Dolibarr/dolibarr/Issues).

1. Please [use the search engine](https://help.github.com/articles/searching-issues) to check if nobody's already reported your problem.
2. [Create an issue](https://help.github.com/articles/creating-an-issue). Choose an appropriate title. Prepend appropriately with Bug or Feature Request.
4. Tell us the version you are using!
3. Write a report with as much detail as possible (Use [screenshots](https://help.github.com/articles/issue-attachments) or even screencasts and provide logging and debugging informations whenever possible).



<a name="code"></a>Code
---------------------

### Basic workflow

1. [Fork](https://help.github.com/articles/fork-a-repo) the [GitHub repository](https://github.com/Dolibarr/dolibarr).
2. Clone your fork.
3. Choose a branch(See the [Branches](#branches) section below).
4. Commit and push your changes.
5. [Make a pull request](https://help.github.com/articles/creating-a-pull-request).

### <a name="branches"></a>Branches

Unless you're fixing a bug, all pull requests should be made against the *develop* branch.

If you're fixing a bug, it is preferred that you cook your fix and pull request it
against the oldest version affected that's still supported.

We officially support versions N, N − 1 and N − 2 for N the latest version available.

Choose your base branch accordingly.

### General rules
Please don't edit the ChangeLog file. File will be generated from your commit messages during release process by the project manager.

### <a name="commits"></a>Commits
Use clear commit messages with the following structure:

```
[KEYWORD] [ISSUENUM] DESC

LONGDESC
```

#### Keyword
In uppercase if you want to have the log comment appears into the generated ChangeLog file.

The keyword can be ommitted if your commit does not fit in any of the following categories:
- Fix: for a bug fix
- Close: for closing a referenced feature request
- New: for an unreferenced new feature (Opening a feature request and using close is prefered)

#### Issuenum
If your commit fixes a referenced bug or feature request.

In the form of a # followed by the GitHub issue number.

#### Desc
A short description of the commit content.

This should ideally be less than 50 characters.

#### LongDesc
A long description of the commit content.

You can really go to town here and explain in depth what you've been doing.

Feel free to express technical details, use cases or anything relevant to the current commit.

This section can span multiple lines.

Try to keep lines under 120 characters.

#### Samples
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
When submitting a pull request, use same rule as [Commits](#commits) for the message.

If your pull request only contains one commit, GitHub will be smart enough to fill it for you.
Otherwise, please be a bit verbose about what you're providing.

Your Pull Request must pass the Continuous Integration checks.
Also, some code changes need a prior approbation:

* if you want to include a new external library (into htdocs/includes directory), please ask before to the project leader to see if such a library can be accepted.

* if you add a new table, you must first create a page on http://wiki.dolibarr.org/index.php/Category:Table_SQL (copy an existing page changing its name to see it into this index page). Than ask the project leader if the new data model you plan to add can be accepted as you suggest.

### Resources
[Developer documentation](http://wiki.dolibarr.org/index.php/Developer_documentation)

Translations
------------
The source language (en_US) is maintained in the repository. See the [Code](#code) section above.

All other translations are managed online at [Transifex](https://www.transifex.com/dolibarr-association/dolibarr/).

Join an existing translation team or create your own and translate into the interface.

Your translations will be available in the next major release.

### Resources
[Translator documentation](http://wiki.dolibarr.org/index.php/Translator_documentation)

Documentation
-------------
The project's documentation is maintained on the [Wiki](http://wiki.dolibarr.org/index.php).

*Note*: to help prevent spam, you need to create an account before being able to edit.

