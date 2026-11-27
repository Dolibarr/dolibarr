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
  (choosing **m) Merge** or **d) Close** is such an explicit request).
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
   failing check), using `gh pr view N -R Dolibarr/dolibarr --json baseRefName,createdAt,changedFiles,mergeable,mergeStateStatus,statusCheckRollup`
   (keep only the latest run of each check).
   This loop is mostly run from a **smartphone**: do NOT use the AskUserQuestion tool (its links
   are neither clickable nor copyable) and do not open a browser with `xdg-open`. Display the PR in
   a normal text message. Start the message with the PR number and title (e.g. `**#N - Title**`),
   then give the links as Markdown links, so they are clickable:
   - PR link, followed by the target branch of the PR in parentheses (`baseRefName`), e.g.
     `- PR : [https://github.com/Dolibarr/dolibarr/pull/N](https://github.com/Dolibarr/dolibarr/pull/N) (develop)`
   - If the PR is mergeable and the CI is OK, also give the link to the diff of the files, with the
     number of modified files in parentheses in its label (`changedFiles` of `gh pr view`), e.g.
     `- Diff des fichiers (3) : [https://github.com/Dolibarr/dolibarr/pull/N/files](https://github.com/Dolibarr/dolibarr/pull/N/files)`

   Then, in this order: one line with the creation date of the PR (`createdAt`) and its author,
   e.g. `- Création : 2026-11-10 - login`, then the mergeable status and the CI status.
   When the PR is not mergeable, or the CI is in error,
   put the ❌ emoji at the start of the value of that line (e.g. `- CI : ❌ erreur`).

   End the message with the choices below and wait for the user to reply with a letter:
   - **a) Code review**. When the current branch is not `develop` and the PR is mergeable with the
     CI OK, this choice becomes **a) Code review and check if it is already fixed in develop (must
     be a backport in this case)**.
   - **m) Merge** → only proposed when the PR is mergeable and the CI is OK. Run the action
     **Merge**, then go back to step 3 with the next PR.
   - **n) Next one** → go back to step 3 with the next PR.
   - **r) 🟢 Re-run the CI** → only proposed when it is useful, and always displayed with the 🟢
     emoji in front of it (Markdown has no color). Run the action **Re-run the CI**, then display
     the same PR again with its choices.
   - **b) 🟢 Update branch** → displayed with the 🟢 emoji like **r)**, only proposed in one of
     these cases:
     - the Travis status is `ACTION_REQUIRED` (it can not be restarted from here) and all the
       other checks are OK;
     - no CI ran at all on the head commit (empty `statusCheckRollup` and no run returned by
       `gh run list --commit HEADREFOID`).

     Updating the branch pushes a new commit, so the whole CI, Travis included, runs again.
     Run the action **Update branch**, then display the same PR again with its choices.

   When **m)**, **r)** or **b)** is not proposed, keep the same letters (a, n) so that a letter always
   means the same choice. There is no Stop choice: when the user asks to stop, go back to the
   initial branch if needed and give a short summary of the session (PRs processed and actions done).
4. If **a)**, run the "Process a PR" procedure below. At its end, after the verdict, do not move
   to the next PR on your own: propose again the choices for the same PR, **m) Merge** (only if it
   is still mergeable with the CI OK) and **n) Next one**, and wait for the user's answer.
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
    cases, report them to the user.
  - Make the smallest fix possible, respecting the rules of `AGENTS.md`. Do not change the
    behaviour intended by the PR.
- **Other failing checks** (e.g. `Check PR quota per user`, PR description check, pre-commit):
  report them to the user, propose the action **Comment** if the author must do something,
  otherwise end the procedure.

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
4. If the base branch is not `develop`, check whether the bug is already fixed in `develop`:
   `git fetch origin develop`, then compare the lines changed by the PR with the same code in
   `origin/develop` (`git show origin/develop:FILE`), and look for the develop fix
   (`git log origin/develop -S'...' -- FILE`, the issue number, or "backport of #NNN" in the PR body).
   - Not fixed in `develop`: no backport needed, go on with the review.
   - Fixed in `develop` in the same way: tell the user that the PR **is a backport** and give the
     develop fix (PR number or commit), then go on with the review.
   - Fixed in `develop` in a different way: report both fixes to the user, show the exact text of
     the closing comment (see the action **Close as not qualified for maintenance branch**), and
     propose **d) Close the PR with the label "PR not qualified for maintenance branch" and a
     comment** or **n) Next one**. Wait for the user's answer (the review stops here).
5. Give the user a short verdict: OK / minor remarks / **serious problem**.
6. If a **serious problem** is found (security vulnerability, blocking bug, data loss, regression,
   broken upgrade): run the action **Comment** to describe the problem.
   For minor remarks, only report them to the user.


## Actions

### Comment (confirmation required)

1. Write a short comment in English: the problem, the `file:line` involved, and a suggested fix.
   For a security vulnerability, do not give the exploitation details in public.
2. Show the exact text to the user and wait for approval (the user can amend it).
3. Publish: `gh pr comment N -R Dolibarr/dolibarr --body-file FILE` (file in the scratchpad
   directory).

### Merge (the choice m) is the confirmation)

1. Check again that the PR is still mergeable and the CI OK, with the same `headRefOid` as the one
   displayed (a new commit may have been pushed meanwhile). Otherwise, tell the user and do not merge.
2. Merge with a merge commit, as done for the other PRs of the project:

   ```bash
   gh pr merge N -R Dolibarr/dolibarr --merge --match-head-commit HEADREFOID
   ```
3. Report the result to the user (merged, or the error returned by GitHub).

### Close as not qualified for maintenance branch (the choice d) is the confirmation)

1. The comment, in English, shown to the user before the choice d), says that the bug is already
   fixed in `develop` (give the PR number or commit), and that when a fix for a maintenance branch
   already has a fix in `develop`, it must be designed as a backport of the `develop` fix (same
   code), so the branches stay consistent; the contributor can submit such a backport instead.
2. Add the label, publish the comment (file in the scratchpad directory), then close the PR:

   ```bash
   gh pr edit N -R Dolibarr/dolibarr --add-label "PR not qualified for maintenance branch"
   gh pr comment N -R Dolibarr/dolibarr --body-file FILE
   gh pr close N -R Dolibarr/dolibarr
   ```
3. Report the result to the user, then go back to step 3 with the next PR.

### Update branch (no confirmation)

Same action as the "Update branch" button of the GitHub interface:

```bash
gh api -X PUT repos/Dolibarr/dolibarr/pulls/N/update-branch -f expected_head_sha=HEADREFOID
```

If GitHub refuses because of a merge conflict, do not try to resolve it here: report it to the user
and propose the action **Comment** to ask the author to rebase and resolve the conflict.
If GitHub refuses because the branch is already up to date with its base branch (no new commit on the
base branch), tell the user: no new CI run can be started this way.

### Re-run the CI (the choice r) is the confirmation)

Propose the choice **r)** only in one of these cases (runs of the head commit:
`gh run list -R Dolibarr/dolibarr --commit HEADREFOID --json databaseId,workflowName,status,conclusion`):

- Workflows waiting for the approval of a maintainer (conclusion `action_required`, first-time
  contributor), and the PR modifies no file of `.github/`. Approve each run:
  `gh api -X POST repos/Dolibarr/dolibarr/actions/runs/RUNID/approve`
- Failed jobs whose cause is gone (e.g. the PR title was fixed, so `Wait for the cheap PR checks to
  be green` can pass now): `gh run rerun RUNID --failed -R Dolibarr/dolibarr`. A Travis build can
  not be restarted from here: give its link to the user.

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
