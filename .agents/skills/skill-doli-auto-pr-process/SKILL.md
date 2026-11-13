---
name: skill-doli-auto-pr-process
description:
  Process the open pull requests of the current branch one by one with an AI agent - review, comment a blocking problem, update a branch not mergeable, or push a complementary commit fixing a phan/phpstan/Travis CI error.
  Use when the user asks to process, handle, triage or go through the PRs (pull requests) in a loop, or to "traiter les PR".
license: MIT
user-invocable: true
---

# Skill: Process Dolibarr pull requests one by one (AI agent loop)

Repository: the GitHub repository of the remote `origin` (`Dolibarr/dolibarr`).
This skill completes `skill-doli-github` (read it for the conventions and useful `gh` commands)
and uses `skill-doli-code-review` for the code review.


## Rules

- Text published on GitHub (comments, commit messages) is **in English**, short and friendly.
  Answers to the user stay in the user's language.
- **Before each action, ask for confirmation** (show the exact comment text or the exact diff and
  commit message). The only exception is the action **Update branch**, run without confirmation.
- Never publish the details of an unfixed security vulnerability in a public comment: warn the
  user and suggest the private channel (GitHub Security Advisory) instead.
- Never merge, approve, close or label a PR in this loop, unless the user explicitly asks for it
  (choosing **b) Merge** is such an explicit request).
- Remember the PR already proposed during the session (processed or skipped) so they are never
  proposed twice.
- To filter JSON output, write the Python filter into a temporary script file in the scratchpad
  directory and run it with `python3 -I script.py`, rather than a one-liner full of escaped quotes.


## Main loop

1. Get the current branch: `git branch --show-current` (e.g. `develop` or `22.0`). Remember it
   to come back to it after each PR.
2. List the open PRs targeting this branch, most recent first, draft PRs excluded. By default, PRs
   with the label `PR defered - Not priority` are excluded too (include them only if the user asks):

   ```bash
   gh pr list -R Dolibarr/dolibarr --base BRANCH --state open --limit 200 \
     --json number,title,author,url,isDraft,createdAt,labels \
     --jq '[.[] | select(.isDraft | not) | select([.labels[].name] | index("PR defered - Not priority") | not)] | sort_by(.createdAt) | reverse | .[] | "#\(.number) \(.author.login) - \(.title) \(.url)"'
   ```

3. Take the most recent PR not yet proposed in this session. Display its number, title, author,
   **link**, its **mergeable status** (mergeable / not mergeable, from `mergeable` and
   `mergeStateStatus`) and its **CI status** (OK / running / error, and if error, the name of each
   failing check), using `gh pr view N -R Dolibarr/dolibarr --json createdAt,changedFiles,mergeable,mergeStateStatus,statusCheckRollup`
   (keep only the latest run of each check).
   This loop is mostly run from a **smartphone**: do NOT use the AskUserQuestion tool (its links
   are neither clickable nor copyable) and do not open a browser with `xdg-open`. Display the PR in
   a normal text message. Start the message with the PR number and title (e.g. `**#N - Title**`),
   then give the links as Markdown links, so they are clickable:
   - PR link: `[https://github.com/Dolibarr/dolibarr/pull/N](https://github.com/Dolibarr/dolibarr/pull/N)`
   - If the PR is mergeable and the CI is OK, also give the link to the diff of the files, with the
     number of modified files in parentheses in its label (`changedFiles` of `gh pr view`), e.g.
     `- Diff des fichiers (3) : [https://github.com/Dolibarr/dolibarr/pull/N/files](https://github.com/Dolibarr/dolibarr/pull/N/files)`

   Then, in this order: the author, the creation date of the PR (`createdAt`, e.g. `2026-11-10`),
   the mergeable status and the CI status.

   End the message with the choices below and wait for the user to reply with a letter:
   - **a) Code review**
   - **b) Merge** → only proposed when the PR is mergeable and the CI is OK. Run the action
     **Merge**, then go back to step 3 with the next PR.
   - **c) Next one** → go back to step 3 with the next PR.
   - **d) Stop** → go back to the initial branch if needed and give a short summary of the
     session (PRs processed and actions done).

   When **b) Merge** is not proposed, keep the same letters (a, c, d) so that a letter always
   means the same choice.
4. If **a)**, run the "Process a PR" procedure below, then go back to step 3 with the next PR.
5. When the list is empty, say so and stop.


## Process a PR

Collect the PR state:

```bash
gh pr view N -R Dolibarr/dolibarr --json number,title,body,author,baseRefName,headRefName,headRefOid,headRepository,headRepositoryOwner,maintainerCanModify,mergeable,mergeStateStatus,statusCheckRollup,files
gh pr checks N -R Dolibarr/dolibarr
```

For `statusCheckRollup`, keep only the latest run of each check (see `skill-doli-github`).
GitHub Actions checks have a `.name/.conclusion`, external statuses (Travis) a `.context/.state`.
Checks still running (`PENDING`, `IN_PROGRESS`, `QUEUED`) are not errors: tell the user.

Then apply the first matching case:

### Case 1 - The PR is not mergeable

`mergeable` is `CONFLICTING` or `mergeStateStatus` is `BEHIND` / `DIRTY`:
run the action **Update branch** (no confirmation).

### Case 2 - There is a CI error

- **phan**, **phpstan** or **Travis** error (including the emulated Travis workflow): analyze the
  logs and build a fix, then run the action **Push a complementary commit**.
  - Logs of a GitHub Actions job: `gh run view RUNID -R Dolibarr/dolibarr --log-failed`
    (the run id is in the link given by `gh pr checks`).
  - Logs of a Travis job: open the link given by the status (`targetUrl`), or
    `curl -s https://api.travis-ci.com/v3/job/JOBID/log.txt`.
  - Tell apart: error introduced by the PR (to fix), error already existing on the base branch,
    infrastructure error (timeout, network). Fix only the errors introduced by the PR. For other
    cases, report them to the user and go to the next PR.
  - Make the smallest fix possible, respecting the rules of `AGENTS.md`. Do not change the
    behaviour intended by the PR.
- **Other failing checks** (e.g. `Check PR quota per user`, PR description check, pre-commit):
  report them to the user, propose the action **Comment** if the author must do something,
  otherwise go to the next PR.

### Case 3 - No CI error: review

1. Get the diff: `gh pr diff N -R Dolibarr/dolibarr`.
2. Identify the type of the PR from the title prefix and adapt the review:
   - `FIX`: the fix is the right one, minimal, on the right branch (oldest affected stable
     branch), with no new feature or refactoring mixed in.
   - `NEW`: target branch is `develop`, feature is consistent with Dolibarr modular architecture,
     translations in `en_US` only, SQL migration files if the database structure changes.
   - `SEC`: the fix closes the vulnerability completely (check other entry points).
   - `QUAL`, `PERF`, `UXUI`, `CLOSE`: no change of behaviour except the one announced.
3. Review the code with the `skill-doli-code-review` skill (rights, `GETPOST` filters, output
   escaping, SQL escaping, CSRF tokens, PHP compatibility of the target branch...).
4. Give the user a short verdict: OK / minor remarks / **serious problem**.
5. If a **serious problem** is found (security vulnerability, blocking bug, data loss, regression,
   broken upgrade): run the action **Comment** to describe the problem.
   For minor remarks, only report them to the user and go to the next PR.


## Actions

### Comment (confirmation required)

1. Write a short comment in English: the problem, the `file:line` involved, and a suggested fix.
   For a security vulnerability, do not give the exploitation details in public.
2. Show the exact text to the user and wait for approval (the user can amend it).
3. Publish: `gh pr comment N -R Dolibarr/dolibarr --body-file FILE` (file in the scratchpad
   directory).

### Merge (the choice b) is the confirmation)

1. Check again that the PR is still mergeable and the CI OK, with the same `headRefOid` as the one
   displayed (a new commit may have been pushed meanwhile). Otherwise, tell the user and do not merge.
2. Merge with a merge commit, as done for the other PRs of the project:

   ```bash
   gh pr merge N -R Dolibarr/dolibarr --merge --match-head-commit HEADREFOID
   ```
3. Report the result to the user (merged, or the error returned by GitHub).

### Update branch (no confirmation)

Same action as the "Update branch" button of the GitHub interface:

```bash
gh api -X PUT repos/Dolibarr/dolibarr/pulls/N/update-branch -f expected_head_sha=HEADREFOID
```

If GitHub refuses because of a merge conflict, do not try to resolve it here: report it to the user
and propose the action **Comment** to ask the author to rebase and resolve the conflict.

### Push a complementary commit (confirmation required)

1. Check that `maintainerCanModify` is `true` (or that the head repository is `Dolibarr`).
   Otherwise, propose the action **Comment** with the suggested fix instead.
2. Make sure the working tree is clean (`git status`), then `gh pr checkout N -R Dolibarr/dolibarr`.
3. Apply the fix and check it locally on the modified files:
   - `php -l FILE`
   - phpstan: `phpstan analyse --allow-older --no-progress -a dev/build/phpstan/bootstrap_action.php FILES`
   - phan only if the error came from phan:
     `phan -k .phan/config.php -B dev/tools/phan/baseline.txt --quick --minimum-target-php-version 7.2 FILES`
4. Show the diff (`git diff`) and the commit message to the user and wait for approval. Commit
   message format: `QUAL Fix phpstan error` / `FIX ...` (short title, then the AI agent line, e.g.
   `Co-Authored-By: ...`).
5. Commit and push to the PR branch: `git push` (the branch created by `gh pr checkout` already
   tracks the contributor branch). If the push over SSH is refused (`Permission denied (publickey)`),
   push over HTTPS with the credentials of `gh` (OWNER/REPO and HEADREFNAME from `headRepositoryOwner`,
   `headRepository` and `headRefName` of `gh pr view`):

   ```bash
   git -c credential.helper= -c credential.helper='!gh auth git-credential' push https://github.com/OWNER/REPO.git HEAD:HEADREFNAME
   ```
6. Go back to the initial branch: `git checkout BRANCH`.
