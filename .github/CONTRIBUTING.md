How to contribute to Dolibarr
=============================

Submit a Bug report or a Feature request
---------------------------------------

<a name="not-a-support-forum"></a>*Note*: **GitHub Issues is not a support forum.**  
If you have questions about Dolibarr or need help on using the software, please use [the forums](https://www.dolibarr.org/forum.php). Forums exist in different languages.

Issues to inform about a bug or a development trouble and requests for a new feature, are managed on [GitHub](https://github.com/Dolibarr/dolibarr/issues).
Default **language here is English**. So please prepare your contributions in English (we recommend using an online translation service if you don't speak English).

1. Please [use the search engine](https://help.github.com/articles/searching-issues) to check if anyone else has already reported your issue.
2. [Create an issue](https://help.github.com/articles/creating-an-issue). Choose an appropriate title. Prepend appropriately with Bug or Feature Request.
3. Tell us the version you are using!   (look at  /htdocs/admin/system/dolibarr.php?  and check if you are using the latest version) 
4. Write a report with as much detail as possible (Use [screenshots](https://help.github.com/articles/issue-attachments) or even screencasts and provide logging and debugging information whenever possible).
5. Delete unnecessary submissions.
6. **Check your Message at Preview before submitting.**



<a name="code"></a>

Submit code
---------------------

This process describes how a Developer can submit code to the project so it can be analyzed and validated by the PR Maintainer (we call this a Pull Request).

Definition:
- Developer: is the human knowing the development language of the application that wants to change some part of the code by modifying the sources of the project.
- PR Maintainer: is the human knowing the development language and code who checks that the code submitted for approbation is correct to validate it, in other words, the PR Maintainer is the approbator of commits. 
- Release Maintainer: is the human that validates that a freeze/beta version is ok to be released officially as a stable version.


### Basic workflow

As the Developer:

1. Check you agree with the terms of the [DCO - Developer's Certificate of Origin](https://github.com/Dolibarr/dolibarr/blob/develop/DCO)
2. [Fork](https://help.github.com/articles/fork-a-repo) the [GitHub repository](https://github.com/Dolibarr/dolibarr).
3. Clone your fork.
4. Choose a branch(See the [Branches](#branches) section below).
5. Read our developer documentation on the [Dolibarr Wiki](https://wiki.dolibarr.org/index.php?title=Developer_documentation).
6. Commit and push your changes.
7. [Make a pull request](https://help.github.com/articles/creating-a-pull-request).

As the PR Maintainer:

7. The PR Maintainer will check and decide if he approves or not the commits. During this step, the PR Maintainer can modify your own code to make it valid for approbation or ask you to make the change yourself. For this the PR Maintainer may add commits to a PR. Depending on the tools used (can be done from github directly or from an IDE), such commits may be done directly after validating your PR (for example to complete it).

As the Release Maintainer:

8. A tag will be added to take a snapshot of the code with all the changes approved by PR Maintainers, when ready to do a release.


Note: Project leader(Master Yoda and BDFL) retains all above roles and can directly commit to the project without a PR. Of course anyone can check commit history and comment!


<span id="branches" name="branches"></span>

### Branches

Unless you're fixing a bug, all pull requests should be made against the *develop* branch.

If you're fixing a bug, it is preferred that you make a pull request against the oldest version affected.

We recommend to push it into N - 2 where N is the latest version available, if not possible into version N - 1, and finally into develop.

The rule N - 2 is just a tip if you don't know which version to choose to get the best compromise between ease of correction and number of potential beneficiaries of the correction.

If you push a bug fix on a very old version it is still going to be merged and propagated into newer versions(choose wisely because old versions depend on old deprecated/unsupported versions of PHP and external libraries). 



### General rules

- As the Developer, please don't edit the ChangeLog file. This file is generated from all commit messages during the release process by the Project releaser.

- As the Developer: Do not submit changes into files xx_XX/afile.lang. They are language files and are updated/synced automatically from Transifex. If you need to add a new language file, just add it for the en_US language.

- As the Release Maintainer: The Release Maintainer will decide to make a new release as soon as the planning of the release is reached and the code in the branch to release reach the status of "No more known serious bugs". 


<a name="commits"></a>

### Commits

Use clear commit messages with the following structure:

```plaintext
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

The keyword can be omitted if your commit does not fit in any of the following categories:

- Fix/FIX: for a bug fix
- Close/CLOSE: for closing a referenced feature request
- New/NEW: for an unreferenced new feature (Opening a feature request and using close is preferred)
- Perf/PERF: for a performance enhancement
- Qual/QUAL: for quality code enhancement or re-engineering

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

If your PR is a change on interface, you must also paste a screenshot showing the new screen.

#### Examples
<pre>
FIX|Fix #456 Short description (where #456 is number of bug fix, if it exists. In upper case to appear into ChangeLog)
or
CLOSE|Close #456 Short description (where #456 is number of feature request, if it exists. In upper case to appear into ChangeLog)
or
NEW|New|QUAL|Qual|PERF|Perf Short description (In upper case to appear into ChangeLog, use this if you add a feature not tracked, otherwise use CLOSE #xxx)
or
Short description (when the commit is not introducing a feature nor closing a bug)

Long description (Can span across multiple lines).
</pre>


### Pull Requests

Pull Request (PR) process is the process to submit a change (enhancement, bug fix, ...) into the code of the project. There is some rules to know and
a process to follow to optimize the chance to have PRs merged efficiently...

* A PR must be atomic. It means it must contains the lower possible changes for 1 need (1 bug fix or 1 new feature) without breaking usability of code. If a PR can be split into several PRs, it often means your PR is not atomic.

* Your Pull Request (PR) must pass the Continuous Integration checks and code quality checks.

* When submitting a pull request, use same rule as [Commits](#commits) for the message. If your pull request only contains 1 commit, GitHub will be smart enough to fill it for you. Otherwise, please be a bit verbose about what you're providing.

* A screenshot will be always required for any PR of change/addition of a GUI behavior.

Also, some code changes need a prior approbation:

* if you want to include a new external library (into htdocs/includes directory), please contact the core project manager first (mention @dolibarr-jedi in your issue) to see if such a library can be accepted.

* if you add new tables or fields, you MUST first submit a standalone PR with the data structure changes you plan to add/modify (and only data structure changes). Start development only once this data structure has been accepted.

Once a PR has been submitted, you may need to wait for its integration. It is common that the project leader let the PR open for a long delay to allow every developer discuss about the PR (A label is added in such a case).

If the label of PR start with "Draft" or "WIP" (Work In Progress), it will not be analyzed for merging until you change the label of the PR (but it can be analyzed for discussion).

If your PR has errors reported by the Continuous Integration Platform, it means your PR is not valid and nothing will be done with it. It will be kept open to allow developers to fix this, or it may be closed several months later. Don't expect anything on your PR if you have such errors, you MUST first fix the Continuous Integration error to have it taken into consideration.

If the PR is valid, and is kept open for a long time, a tag will also be added on the PR to describe the status of your PR and why the PR is kept open. By putting your mouse on the tag, you will get a full explanation of the tag/status that explains why your PR has not been integrated yet.
In most cases, it gives you information of things you have to do to have the PR taken into consideration (for example a change is requested, a conflict is expected to be solved, some questions were asked). If you have a yellow, red flag of purple flag, don't expect to have your PR validated. You must first provide the answer the tag asks you. The majority of open PRs are waiting an action of the author of the PR.

Statistics on Dolibarr project shows that 95% of submitted PRs are reviewed and tagged. Average answer delay is also one of the best among Open source projects (just few days before having the Answer Tag set). This is one of the most important ratio of answered PRs in Open Source world for a major project. Don't expect the core team to reach 100%. 
A so high ratio is very rare on a so popular project and with the increasing popularity of Dolibarr, this ratio will probably decrease in future to a more common level.


### Resources
[Developer documentation](https://wiki.dolibarr.org/index.php/Developer_documentation)

Translations
------------
The source language (en_US) is maintained in the repository. See the [Code](#code) section above.

All other translations are managed online at [Transifex](https://www.transifex.com/dolibarr-association/dolibarr/).

Translations done on transifex are available in the next major release.

Note: Sometimes, the source text (English) is modified. In such a case, the translation is reset. Transifex assumes that if the original source
has changed, the translation is surely no more correct so must be done again. But old translation is not lost and you can use the tab "History"
to retrieve all old translations of a source text and restore the translation in one click with no need to retranslate it if there is no need to.


### Resources
[Translator documentation](https://wiki.dolibarr.org/index.php/Translator_documentation)

Documentation
-------------
The project's documentation is maintained on the [Wiki](https://wiki.dolibarr.org/index.php).

*Note*: to help prevent spam, you need to create an account before being able to edit. Everybody is welcome to contribute to its content.

