# hurling Dolibarr

## What’s Hurl?
Hurl is an Open Source command line tool that makes http requests. https://hurl.dev/

### Why

We can use hurl to make (thorough and comprehensive) **automatic tests** for Dolibarr to ensure that our changes do not unintended break stuff. We can and should test both the API, as well as the GUI part of Dolibarr. Doing this will allow us to release faster and better quality software. It will also allow us to do **test driven development** where we write the (hurl) tests before we write our code to satisfy the (hurl) tests, which again will allow us to release faster and better quality software.

### Getting hurl

We defer you to https://hurl.dev/docs/installation.html

### Why hurl?

This author wanted a http request test tool which was Open Source and CLI based for possible CI/CD integration. Hurl stood out as easy to get started, yet powerful and capable. There may very well be other tools, but it is more important to get started using A tool, because the biggest work lies in writing all the tests, and that work is almost identical regardless of the tool used to do the tests.

## When and where to test

Hurl tests should be run **early and often** to give the maximum value - and all should pass.

### Locally on your _development_ computer

**!! DO NOT RUN AGAINST YOUR PRODUCTION DOLIBARR !!**

At some time in the future you should preferably as a Dolibarr developer, tester, engineer, QA, ... have run these hurl tests before you make a commit, but we are not there yet!

The hurl tests may very well **be destructive**, in fact some **are destructive** because we do need to test all the functionality of Dolibarr, including **breaking and deleting** stuff, so please be careful about the target Dolibarr you run it against.

#### Using hurl for health/configuration testing

You may want to look into using hurl to regularly test the integrity, health and configuration of your production Dolibarr, but that is something you would have to write yourself, and perhaps avoid destructive tests? In the future we may provide a hurl configuration to get started.

### During CI/CD

At some time in the future we should run these hurl tests as part of the CI/CD chain during commit checks, but we are not there yet!

## Timeline

This is a prelininary timeline for hurling Dolibarr

### Pre 2026

We discuss and try out hurl for manual testing pre commit.

### 2026

During the various developer camps we introduce hurl to devcamp participants and they help expand the tests so we collectively get a thorough and comprehensive automatic test suite for Dolibarr.

Perhaps we starting using it as part of the CI/CD chain, though probably only as a warning. Maybe it is run against releases only?

We figure out how to only do those hurl tests which a relevant for the commit.

### 2027

We enable strict automatic testing during the CI/CD chain such that the checks will fail if hurl tests fail.

All changes and new development must come with relevant hurl tests.

DoliStore modules should come with hurl tests.

We start exploring the duration part of hurl to do performance improvements.

### 2028

We have a very thorough and comprehensive automatic QA test for each and every part of Dolibarr.

All changes and new development must come with thorough and comprehensive hurl tests.

DoliStore modules must come with hurl tests.

## Howto Usage

1. Create the .settings/ directory
2. Set these environment variables to know how to authenticate and where to reach your Dolibarr installation

   DOLAPIKEY="DOLAPIKEY: _replace_with_your_Dolibarr_Token_for_API_"

   DOLIHOST="http://example.net/"

   DOLIPORT="8080"

   DOLISUBURL="/dolibarr" # if you're not just using / and no need to set /

3. On Linux and mac execute ./run.sh

   On Windows? Please test and submit a PR on this file documenting how to run it on Windows incl. a script like run.sh - but just for Windows.

4. Write new hurl tests during your development and submit using a PR - preferably using the very same PR that submits your changes, improvements and refinements of Dolibarr.

## Directory and file structure for hurl tests

To avoid ending up with thousands of files in the same directory, we should use directories and possible subdirectories where relevant.

Broadly the hurl tests are split in api/ tests, gui/ tests and public/ tests respectively.

Below that the directory name should be the first part of the endpoint, this could be the module name, but the orders/ API endpoint does not match the module name 'commande', and public/onlinesign/ might be used by multiple modules.

### Undecided discrepancy

It is currently undecided how we have to handle the discrepancy between the http endpoint and the module name! Let's together try, learn, discuss and finally agree on a decision!


## Self contained .hurl files

Each individual .hurl file should be entirely self contained, and where that is impractical (or impossible), this is where you use numbers bigger than 00 (no authentication needed) and 10+ (authentication needed) for the start of the filename. Any files with a bigger number than 10 are assumed to require authentication headers.

- 00 in the beginning of the filename means that no authentication is required to run this test
- 10 in the beginning of the filename means that authentication is required to run the test
- 20 is reserved for future usage
- 30 in the beginning of the filename is where you would POST data for later tests
- 40 in the beginning of the filename is where you would GET data that was POST'ed in 30_'s files
- 50 in the beginning of the filename is where you would PUT data that was POST'ed in 30_'s files
- 60 in the beginning of the filename is where you would GET data that was PUT'ed in 50_'s files
- 70 in the beginning of the filename is where you would DELETE data
- 80 in the beginning of the filename is where you would GET data that was just DELETE'd to make sure it is DELETE'd
- 90 is reserved for future usage

You may use x[0-9] file names if absolutely needed.

### Endpoint should be part of filename

To make it quicker to see what the file does, the endpoint should be part of the filename, but make it after the number.

### HTTP method may be part of the filename

You may add the HTTP method as part of the filename, but make it after the number, and just before .hurl

### Separator _ is more readable

_ was chosen as separator, because it is more readable than .

### Examples:

- api/00_explorer.hurl
- api/setup/10_setup_modules.hurl
- api/setup/10_setup_conf.hurl
- api/setup/10_setup_company.hurl
- api/status/00_status.hurl
- api/status/10_status.hurl
- api/00_foobar.hurl
- api/login/00_login_POST.hurl
- api/login/00_login_GET.hurl
- gui/00_HOME.hurl
- public/payment/00_payment_newpayment.hurl
- public/onlinesign/00_onlinesign_newonlinesign.hurl

## Translations and Number separator

Given that Dolibarr may respond differently depending on the language configuration of Dolibarr and/or the language configuration of the webbrowser, we should have that in mind when writing out hurl test files, such that they **must** be able to be used on any Dolibarr language configuration.

Thus the check in `public/payment/00_payment_newpayment.hurl` might fail on your Dolibarr installation, if so, please submit a PR with a fix.
