# CI Workflows

The 2 main CI workflows are:
----------------------------

- ci-on-pull.yml
- ci-on-push.yml

This run the actions:

- pre-commit.yml
- translations.yml
- phan.yml
- phpstan.yml

See https://docs.github.com/en/actions/using-workflows/events-that-trigger-workflows#running-a-workflow-based-on-the-conclusion-of-another-workflow


The other worklows are:
-----------------------

- ci-on-closepr-cache-clean			to autoclose old issues.
- ci-on-comment-stale-issues-safe	to autoclose old issues.
- ci-on-cron-phpstan_baseline 		to update the phpstan baseline file.
- ci-on-closepr-cache-clean-pr 		to clean cache when closing a PR.
- ci-on-pushpull-checkfiltesetlock 	to check we do not modify a file that is locked by a signature in dev/lockedfiles.txt
- ci-on-pull-checkpr 				to check the title of a PR starts with a valid keyword (Fix, Close, New, Perf, Doc, Qual, Sec) and its description does not contain the mention @eldy
- ci-on-comment-moderation			to experiment automatic moderation of comments (bot).
- ci-on-comment-unstale 			to remove the stale label of an issue when a comment is added.
- ci-on-pull-v18-autoassign 		to set the reviewers and add the label on PRs targeting the branch 18.0.
- ci-on-release 					to trigger the build of the docker image when a release is published.
- ci-on-cron-windows.yml

Some tests workflows are (disabled):
------------------------------------

- ci-on-pushpull-github_php71_pgsql.yml.disabled	to make CI tests with PHP 7.1 and PostgreSQL.
- ci-on-pushpull-github_php81_mysql.yml.disabled	to make CI tests with PHP 8.1 and MySQL.
