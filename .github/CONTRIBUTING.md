How to contribute to Dolibarr
=============================

Bug reports and feature requests
--------------------------------

<a name="not-a-support-forum"></a>*Note*: Issues are not a support forum. If you need help using the software, please use [the forums](https://www.dolibarr.org/forum).

Issues are managed on [GitHub](https://github.com/Dolibarr/dolibarr/issues).

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
Please don't edit the ChangeLog file. File will be generated from all commit messages during release process by the project manager.

### <a name="commits"></a>Commits
Use clear commit messages with the following structure:

```
[KEYWORD] [ISSUENUM] DESC

LONGDESC
```

We provide a [.gitmessage](/.gitmessage) file to help you fit the template.

You can add it to your git configuration using:
```
git config --local commit.template .gitmessage
```

where 

#### Keyword
In uppercase if you want to have the log comment appears into the generated ChangeLog file.

The keyword can be ommitted if your commit does not fit in any of the following categories:
- Fix/FIX: for a bug fix
- New/NEW: for an unreferenced new feature (Opening a feature request and using close is prefered)
- Close/CLOSE: for closing a referenced feature request

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

#### Examples
<pre>
FIX|Fix #456 Short description (where #456 is number of bug fix, if it exists. In upper case to appear into ChangeLog)
or
NEW|New Short description (In upper case to appear into ChangeLog, use this if you add a feature not tracked, otherwise use CLOSE #456)
or
CLOSE|Close #456 Short description (where #456 is number of feature request, if it exists. In upper case to appear into ChangeLog)
or
Short description (when the commit is not introducing feature nor closing a bug)

Long description (Can span accross multiple lines).
</pre>

### Pull Requests
Pull Request (PR) process is the process to submit a change (enhancement, bug fix, ...) into the code of the project. There is some rules to know and 
a process to follow to optimize the chance to have PRs merged efficiently...

* A PR must be atomic. It means it must contains the lower possible changes for 1 need (1 bug fix or 1 new feature) without breaking usability of code. If a PR can be split into several PRs, it often means your PR is not atomic.

* Your Pull Request (PR) must pass the Continuous Integration checks and code quality checks.

* When submitting a pull request, use same rule as [Commits](#commits) for the message. If your pull request only contains 1 commit, GitHub will be smart enough to fill it for you. Otherwise, please be a bit verbose about what you're providing.

Also, some code changes need a prior approbation:

* if you want to include a new external library (into htdocs/includes directory), please ask before to the core project manager (mention @dolibarr-yoda in your issue) to see if such a library can be accepted.

* if you add a new table, you must first create a page on https://wiki.dolibarr.org/index.php/Category:Table_SQL (copy an existing page changing its name to see it into this index page). Then ask the project manager (@dolibarr-yoda) if the new data model you plan to add is compatible with curent and future works in progress and can be accepted as you suggest.

Once a PR has been submitted, you may need to wait for its integration. It is common that the project leader let the PR open for a long delay to allow every developer discuss about the PR (A label is added in such a case).

If the label of PR start with "Draft" or "WIP" (Work In Progress), it will not be analyzed for merging until you change the label of PR (but it can be analyzed for discussion).

If your PR has errors reported by the Continuous Integration Platform, it means your PR is not valid and nothing will be done with it. It will be kept open to allow developers to fix this, or it may be closed several month later. Don't expect anything on your PR if you have such errors, you MUST first fix the Continuous Integration error to have it taken into consideration. 

If the PR is valid, and is kept open for a long time, a tag will also be added on the PR to describe the status of your PR and why the PR is kept open. By putting your mouse on the tag, you will get a full explanation of the tag/status that explain why your PR has not been integrated yet.
In most cases, it gives you information of things you have to do to have the PR taken into consideration (for example a change is requested, a conflict is expected to be solved, some questions were asked). If you have a yellow, red flag of purple flag, don't expect to have your PR validated. You must first provide the answer the tag ask you. The majority of open PR are waiting an action of the author of the PR.

Statistics on Dolibarr project shows that 95% of submitted PR are reviewed and tagged. Average answer delay is also one of the best among Open source projects (just few days before having the Answer Tag set). This is one of the most important ratio of answered PR in Open Source world for a major project. Don't expect the core team to reach the 100%. A so high ratio is very rare on a so popular project and with the increasing popularity of Dolibarr, this ratio will probably decrease in future to a more common level.


### Resources
[Developer documentation](https://wiki.dolibarr.org/index.php/Developer_documentation)

Translations
------------
The source language (en_US) is maintained in the repository. See the [Code](#code) section above.

All other translations are managed online at [Transifex](https://www.transifex.com/dolibarr-association/dolibarr/).

Translations done on transifex are available in the next major release.

Note: Sometimes, the source text (english) is modified. In such a case, the translation is reset. Transifex assume that if the original source
has changed, the translation is surely no more correct so must be done again. But old translation is not lost and you can use the tab "History"
to retreive all old translation of a source text, and restore the translation in one click with no need to retranslate it if there is no need to. 


### Resources
[Translator documentation](https://wiki.dolibarr.org/index.php/Translator_documentation)

Documentation
-------------
The project's documentation is maintained on the [Wiki](https://wiki.dolibarr.org/index.php).

*Note*: to help prevent spam, you need to create an account before being able to edit. Everybody is welcome to contribute to its content.

