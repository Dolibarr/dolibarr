#!/bin/bash
# Wrapper to run 'fixaltlanguages.sh' from pre-commit hook
#
# Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>


# Note: regex in pre-commit based on list of fixed alt languages when run for
#       all cases (or all languages with more than one code in the project).
#
# Perl script:
#
#     ```perl
#     use Regexp::Optimizer;
#     my $o = Regexp::Optimizer->new;
#     my $re= "am|ar|bn|br|bs|ca|cs|cy|da|de|el|en|es|et|eu|fa|fr|gl|he|hi"
#           ."|it|ja|ka|kk|km|kn|ko|lo|ms|my|nb|ne|nl|pt|ru|sl|sq|sr|sv|ta"
#           ."|tg|uk|ur|vi|zh";
#     my $newRe=$o->optimize(qr/$re/);
#     print $newRe;
#     ```

MYDIR=$(dirname "$(realpath "$0")")

exit_code=0
for file in "$@" ; do
	if ! "${MYDIR}/fixaltlanguages.sh" fix "$file" ; then
		exit_code=$?
	fi
done
exit $exit_code
