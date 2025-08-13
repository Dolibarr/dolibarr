#!/bin/bash

TEST_OK=0
TEST_FAIL=0

function test_end {
    echo ""
    echo ""
    printf "%-40s" "$1 test failures found"
    if [ $1 -gt 0 ]; then
        log_fail
    else
        log_ok
    fi
    return "$1"
}

function test_start {
    TEST_OK=0
    TEST_FAIL=0
}

function log_ok {
    echo -e "[ \033[0;32mPASS\033[0m ]"
}

function log_fail {
    echo -e "[ \033[0;31mFAIL\033[0m ]"
}

function expect_failure {
    temp_file=$(mktemp)
    printf "%-40s" "$1"
    shift
    $@ &> log.txt
    if [ $? -ne 0 ]; then
        log_ok
        TEST_OK=$((TEST_OK+1))
    else
        log_fail
        #cat ${temp_file}
        TEST_FAIL=$((TEST_FAIL+1))
    fi
    rm ${temp_file}
}

function expect_success {
    temp_file=$(mktemp)
    printf "%-40s" "$1"
    shift
    $@ &> ${temp_file}
    if [ $? -ne 0 ]; then
        log_fail
        #cat ${temp_file}
        TEST_FAIL=$((TEST_FAIL+1))
    else
        log_ok
        TEST_OK=$((TEST_OK+1))
    fi
    rm ${temp_file}
}

function test_commands {
    PNGSUITE="../resources/pngsuite/"
    for i in $(find $PNGSUITE -type f -name '*.png' | sort | grep -v pngsuite/x); do
        echo $0 expect_success "$(basename $i)" php read.php "$i"
    done
    for i in $(find $PNGSUITE -type f -name '*.png' | sort | grep pngsuite/x); do
        echo $0 expect_failure "$(basename $i)" php read.php "$i"&
    done
}

set -u -o pipefail
if [ $# -eq 0 ]; then

    mkdir -p out/
    test_start
    test_commands | parallel --no-notice
    test_end $?
else
    subcmd=$1
    shift
    test_start
    if [ $subcmd == "expect_success" ]; then
        expect_success $@
    elif [ $subcmd == "expect_failure" ]; then
        expect_failure $@
    fi
    exit $TEST_FAIL
fi
