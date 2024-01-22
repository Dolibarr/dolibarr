## 'pre-commit' ("replaces" precommit)

### Introduction

[`pre-commit`](https://pre-commit.org) is a framework for managing and
maintaining multi-language pre-commit hooks.

"pre-commit hooks" integrate with `git` and are run when you perform a
`git commit` for instance.

Historically there was `precommit` for Dolibarr which you can find in this
directory. That script runs `phplint`, `phpcs` and `phpcbf` upon commit.

`pre-commit` is not specific to Dolibarr and is deployed on many projects -
mostly Python projects, but it is applicable to most (or *all*) code and
documentation development.

You can find documentation at https://pre-commit.com/ .

Now you can use `pre-commit` which uses the configuration found at the root of
the project: `pre-commit-config.yaml`.

### How to

1. Install `pre-commit`.\
   If you do not have python installed, install
   [python](https://www.python.org) first.\
   If you do not have
   [`pip`](https://pypi.org/project/pip), install that as well.\\

   Then you can install pre-commit: `python -m pip install pre-commit`.

1. In you local git clone of the project, run `pre-commit install`. That will
   add the hooks.

### Tips

After installing `pre-commit` onto your local git clone, pre-commit will run
on every commit.

When it finds some issue, the git commit will be aborted, so you can fix it,
or verify it.

The tools run by `pre-commit` may modify your code: format PHP code
(`phpcbf`), fix line endings, end of files, etc.

They may also alert about potential issues: syntax errors, spelling errors,
code quality, an execute bit that was (not) set in the git repository, etc.

One way to use it is this:

```bash
cd PROJECT_DIR
pre-commit install  # Only needed once
# Repeat until success.
git commit -a -m "My message"
# `pre-commit` is run and reports
# Check the results, make fixes, and re-commit (=repeat above line).
```

In some cases you may want to commit despite the changes.\
You can just add
`--no-verify` to your git command:

```bash
git commit -a -m "My message" --no-verify
```

If you want to skip certain checks for whatever reason, you can set the SKIP
environment variable:

```bash
SKIP=no-commit-to-branch git commit -a -m "My message"

or

export SKIP=no-commit-to-branch   # In your .bashrc or session.
```

There is much more you can do with pre-commit, check out its
[documentation](https://pre-commit.com).

Now your commit is less likely to fail in the Continuous Intagration (CI) run
on github.\
CI also runs pre-commit to help maintain code quality.
