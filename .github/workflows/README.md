# CI Workflows

The 2 main CI workflows are:
----------------------------

- ci-on-pull_request.yml
- ci-on-push.yml

This run the actions:

- pre-commit.yml
- phan.yml
- phpstan.yml
When all succeed, start:
- Windows-ci;

See https://docs.github.com/en/actions/using-workflows/events-that-trigger-workflows#running-a-workflow-based-on-the-conclusion-of-another-workflow


The other worklows are:
-----------------------

- stale-issues-safe	to autoclose old issues.
- phpstan_baseline to update the phpstan baseline file.
- cache-clean-pr to clean cache when closing a PR.


Some tests workflows are:
------------------------

- pr-18
- pr-18-autolbal
- test
