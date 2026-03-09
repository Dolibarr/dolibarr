# Workflow run order

To reduce run minutes, the following order is put in place:

On PR & Merge, always run:

- pre-commit;
- phan.

When both succeed, start:

- phpstan;
- Windows-ci;
- travis.

See https://docs.github.com/en/actions/using-workflows/events-that-trigger-workflows#running-a-workflow-based-on-the-conclusion-of-another-workflow
