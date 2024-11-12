### Static Code Checks using [phan]

#### Installation, running

`run-phan.sh` can install and run `phan`.

See instructions in `run-phan.sh` for installing (or just run it).

The configuration file in `PROJECT_DIR/.phan/config.php` also allows you to run
`phan` independently from the script.

#### Run options:

No option : Runs the minimum checks

Option 'full' : Runs all an extensive set of checks

Option '1' : Writes the baseline

Examples:

- `run-phan.sh` runs the default checks
- `run-phan.sh 1` updates the baseline for the default checks
- `run-phan.sh full` runs the extended checks
- `run-phan.sh full 1` updates the baseline for the extended checks

#### Baseline

The `baseline.txt` file in this directory defines the issues that are currently
excluded from the final report. In principle you should not add any more
exceptions to that file, but rather fix the issues or add [phan annotations]
that provide more information or to exclude specific cases.

#### Configuration

`config.php` : Default configuration file

`config_extended.php` : Configuration that enables more checks.

`baseline.txt` : Ignored issues (with `config.php`)

`baseline_extended.txt` : Ignored issues (with `config_extended.php`), not
currently in git

[phan]: https://github.com/phan/phan/wiki/Getting-Started
[phan annotations]: https://github.com/phan/phan/wiki/Annotating-Your-Source-Code
