---
name: skill-github
description:
  Handles GitHub issues and pull requests of the project with the gh CLI - list, triage, analyze an issue, review a PR, diagnose a CI failure, comment, label, close or merge.
  Use when the user mentions an issue, a PR, a failing CI check or GitHub.
license: MIT
user-invocable: true
---

# Skill: Handle Dolibarr GitHub issues and pull requests

Repository: Use the same GitHub repository than the one of the project. Replace 'MyRepo/project' in this file with the correct value.
Default branch: `develop`. Stable branches are named `NN.0` (e.g. `21.0`).


## Rules

- **Read freely, write only after confirmation**: listing, reading, diffs and logs need no approval.
  Commenting, labeling, assigning, closing, reopening, approving, merging or pushing: show the exact
  text or action and wait for the user's explicit approval.
- Text published on GitHub (comments, reviews) is **in English**, short and friendly: we talk to a
  community of contributors. Answers to the user stay in the user's language.
- Never publish details of an unfixed security vulnerability in a public issue or comment: warn the
  user and suggest the private channel (GitHub Security Advisory).
- Do not claim a feature exists in Dolibarr without checking the code.


## Project conventions (.github/CONTRIBUTING.md, .github/PULL_REQUEST_TEMPLATE.md)

- PR title/description prefixed with `FIX`, `CLOSE`, `NEW`, `UXUI`, `PERF`, `QUAL`
  (uppercase = goes into the ChangeLog, lowercase = does not). Security PRs use `SEC`.
- A bugfix targets the oldest affected stable branch; a new feature targets `develop`.
- The contributor must fix any CI error and any conflict, even outside their own lines (solidarity
  principle). There is a daily PR quota per user (check `Check PR quota per user`, workflow
  `ci-on-pull-checkprquota.yml`).
- Issues: in English, with the version used and steps to reproduce. GitHub is not a support forum
  (redirect to https://www.dolibarr.org/forum.php).


## Useful commands

To filter JSON output (`--json ...`), write the Python filter into a temporary script file and run
it with `python3 -I script.py`, rather than a one-liner full of escaped quotes.

* To find open PRs (draft PRs excluded) that are approved by at least one maintainer (an approving review from an `OWNER`, `MEMBER` or `COLLABORATOR` of the repository):

```bash
gh pr list -R Repo/project --state open --limit 50 --json number,title,author,isDraft,latestReviews \
  --jq '.[] | select(.isDraft | not) | select(any(.latestReviews[]; .state == "APPROVED")) | "#\(.number) \(.author.login) - \(.title) (approved by: \([.latestReviews[] | select(.state == "APPROVED") | .author.login] | join(", ")))"'
# Draft PRs are excluded with select(.isDraft | not): they are still work in progress.
# Do not use --search "review:approved": it relies on the review decision of the branch protection
# rules and does not tell who approved.
```

* List open PRs (draft PRs excluded) with a failing CI:

```bash
gh pr list -R Repo/project --state open --limit 200 --json number,title,author,isDraft,updatedAt,statusCheckRollup \
  --jq '.[] | select(.isDraft | not) | . as $pr | ($pr.statusCheckRollup | group_by(.name // .context) | map(max_by(.startedAt // .createdAt // .updatedAt // ""))) as $latest | {n: $pr.number, a: $pr.author.login, d: $pr.updatedAt[:10], t: $pr.title, f: ([$latest[] | select((.conclusion // .state) | IN("FAILURE","ERROR","TIMED_OUT","STARTUP_FAILURE","ACTION_REQUIRED")) | (.name // .context)] | unique)} | select(.f | length > 0) | "#\(.n) \(.d) \(.a) - \(.t) (failing: \(.f | join(", ")))"'
# Draft PRs are excluded with select(.isDraft | not): their CI failures are expected while in progress.
# statusCheckRollup can contain several runs of the same check (re-runs, matrix jobs). The
# group_by(name) | map(max_by(startedAt)) keeps only the latest run per check, so a check fixed
# since the last commit no longer appears as failing.
# Increase --limit if the repo has many open PRs (Dolibarr has 400+ open PRs).
# Output is sorted by most recently created PR first; add "| head -N" to keep only the N last ones.
# GitHub Actions checks have a .name/.conclusion, external statuses (Travis) a .context/.state.
# "Check PR quota per user" is not a code error: it means the author has too many open PRs.
```

* To launch the "Update branch" on a PR, the same way as the button on the GitHub interface

```bash
gh api -X PUT repos/MyRepo/project/pulls/IDOfPR/update-branch -f expected_head_sha=shaOfCommit
```

## Workflows

### Triage an issue
1. `gh issue view N --comments`; identify the version, the module and the steps to reproduce.
2. Look for duplicates (`gh issue list --search`, `gh pr list --search`, with `--state all`).
3. Find the related code in the local repository (`htdocs/<module>/...`) and check whether the bug
   still exists on `develop` and on the stable branch reported.
4. Report to the user: diagnosis, files/lines involved, target branch for a fix, suggested labels
   and, if useful, a draft answer in English.
5. If the issue is a duplicate, close it with a comment.

### Review a pull request
1. `gh pr view N`, `gh pr diff N`, `gh pr checks N`.
2. Check: title prefix, consistent target branch (fix → oldest affected stable branch),
   description, no conflict (`mergeable`).
3. Review the code itself with the `skill-doli-code-review` skill (rights, `GETPOST` filters,
   output escaping, SQL escaping, CSRF token, translations, PHP compatibility of the target branch,
   hooks rather than specific code).
4. For a CI failure, tell apart: error introduced by the PR, pre-existing error, infrastructure
   issue (Travis, PR quota). Read the logs before concluding.
5. Report a summary: verdict (mergeable / changes requested), blocking points as `file:line`, and
   the draft review in English to publish if the user approves.

### Fix an issue yourself
1. Start from the right branch (`git fetch`, then branch from `origin/NN.0` or `develop`).
2. Fix, then run the relevant local checks (`php -l`, phpstan/phpcs if available, and the
   `skill-doli-test-*` skills when tests apply).
3. Commit with a prefix (`FIX #N ...`); push and `gh pr create` only after approval, following
   `.github/PULL_REQUEST_TEMPLATE.md`.
