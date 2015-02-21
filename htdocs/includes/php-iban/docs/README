php-iban README
---------------

php-iban is a library for parsing and validating International Bank
Account Number (IBAN) information in PHP.

It also validates Internet International Bank Account Number
(IIBAN) as specified at http://tools.ietf.org/html/draft-iiban-01
(see also http://www.ifex-project.org/our-proposals/iiban)

php-iban lives at http://code.google.com/p/php-iban

  What is an IBAN?
  ----------------
    An IBAN is basically a standardised way of explaining a bank
    account number that works across borders.  Its structure is:

     <Two Letter ISO Country Code> + <Two Digit Checksum] + <BBAN>
    
    BBAN is the term used to describe the national-level format
    for a bank account number, which varies between countries
    (and was sometimes created just to get IBAN connectivity!).
    Note that a BBAN may have its own checksum algorithm.

    IBAN provides basic protection, using the checksum, against
    transcription (ie: human copying) errors. It also provides
    a registry of valid destination countries and their BBAN
    formats. Thus, when you ask php-iban to 'validate' an IBAN
    it ensures that these checks are passed. However, it cannot
    ensure that a bank account actually exists - the only party
    who can do that is the receiving bank or country.

    IBAN was invented in Europe, however its usage is growing
    rapidly to other parts of the world. Thus, the future of
    this library looks pretty good.

    For further information, please see 'docs/ISO13616.pdf' or
    visit Wikipedia at http://en.wikipedia.org/wiki/IBAN

  What is an IIBAN?
  -----------------
   An Internet IBAN (IIBAN) identifies an internet-based financial
   endpoint in a manner that is superset-compatible with the existing
   European Committee for Banking Standards (ECBS) International Bank
   Account Number (IBAN) standard [ISO13616].

   For more information see http://tools.ietf.org/html/draft-iiban-00
   and http://www.ifex-project.org/our-proposals/iiban

   To disable IIBAN support from your installation, simply remove
   the second ('AA|...') line from the top of the registry.txt file.

  Execution environment
  ---------------------
    At present the library merely requires a PHP engine to be present
    and has no external dependencies.  It is recommended that your
    PHP engine is configured to disable verbose warnings for events
    such as access of unknown array indexes, though this should be
    standard on almost any PHP deployment today. Any PHP engine
    in use today should be compatible, though PHP3 or PHP4 execution
    environments may require minor modifications (specifically,
    some string functions may have changed).

  Installation
  ------------
    Simply copy 'php-iban.php' and 'registry.txt' to an appropriate
    location for your project. The location of the files will affect
    the 'require_once()' line used to load the library from your
    codebase, and may have relevance security (see 'Security' below).
    Note that 'php-iban.php' expects to find 'registry.txt' in the
    same directory as itself.

  Security
  --------
    Following best practice for library files, the location chosen 
    for the php-iban.php and registry.txt files should ideally be 
    outside of any web-accessible directories. Thus, if your 
    web project lives in /var/www/myproject/htdocs/ then it would
    be preferably to place php-iban in /var/www/myproject or some
    other directory that is not visible to regular web users.
    
    Secondly, neither file should be writable by the web server
    itself in order to prevent compromise of the execution path
    (ie: getting hacked). So, for example if your web server runs
    as user 'www', group 'www', you can ensure that the web server
    has minimal required privileges against the files as follows
    (note that you will need to be root to execute these commands):

     # chown <myuser> php-iban registry.txt  # where <myuser> is a
                                             # non-root user that
                                             # is not 'www'.
     # chgrp www php-iban registry.txt       # set group to 'www'
     # chmod ugo-rwx php-iban registry.txt   # remove privileges
     # chmod g+r php-iban registry.txt       # allow 'www' group
                                             # to read the files

    Obviously the above do not apply if you are using PHP in a 
    non web-oriented project (eg: a cronjob or daemon), a usage
    of the language that is apparently growing - but limited.

  Using the library
  -----------------
    Basic invocation is as follows:

      # include the library
      require_once('/path/to/php-iban.php'); # /path/to/ is optional

      # use some library function or other...
      if(!verify_iban($iban_to_verify)) {
       # blame the user...
      }

    Note that because the library is designed in a procedural manner
    rather than an object-oriented manner it should be easy to 
    integrate with a wide variety of established codebases and 
    PHP interpreter versions.

  Using the library's OO wrapper
  ------------------------------
    Because many new PHP programmers seems to learn the language via
    books that only teach OO based programming and are thus unfamiliar
    with procedural PHP (and often relatively inexperienced as 
    programmers, too) an OO wrapper-library has been provided.

    ======================= READ THIS =================================
    However *you should avoid excessive use of OO*. For some thought
    provoking discussions of the negative aspects of overusing OO,
    please refer to 'Coders at Work' and 'The Art of UNIX Programming'.
    (OO is great for some problems, but simply pointless for most.)
    ===================================================================

    Anyway, to use the OO wrapper supplied, invocation is as follows:

      # include the OO wrapper to the library
      require_once('/path/to/oophp-iban.php'); # /path/to is optional

      # instantiate an IBAN object
      $myIban = new IBAN('AZ12345678901234');
      if(!$myIban->Verify()) {
       # blame the user...
      }

  Documentation
  -------------
    There are three types of functions provided by the library:

     - IBAN-level functions

         These are functions that operate on an IBAN. All of these
         functions accept either machine format or human format
         IBANs as input. Typically they return facts about an IBAN
         as their output (for example whether it is valid or not,
         or the BBAN (national-level) portion of the IBAN), though
         some of them may perform other functions (eg: fixing a
         broken IBAN checksum). These functions are named 'iban_*'
         with the exception of the most commonly used function,
         'verify_iban()', and excepting the country-level functions.

         (Regarding the object oriented wrapper - all of these
          functions are implemented as methods on IBAN objects)

     - IBAN country-level functions
         These functions return information about countries that are
         part of the IBAN standard. They each take the two letter 
         ISO country code at the beginning of an IBAN as their
         argument. They are named 'iban_country_*', with the 
         exception of 'iban_countries()' which returns a list of
         the known IBAN countries. (For example, functions that 
         return an example IBAN or BBAN for the country, or the 
         name of the country.)

         (Regarding the object oriented wrapper - all of these
          functions are implemented as methods on IBANCountry
          objects, except for 'iban_countries()' which is 
          implemented as the Countries() method on the IBAN class)

     - Internal functions
         These functions begin with '_iban_*' and can be ignored.

         (Regarding the object oriented wrapper - these functions
          are not present)

    Please refer to either http://code.google.com/p/php-iban or the
    commented source code of php-iban itself for the complete list of
    which functions are available. Of course, in unix style one could
    achieve the same in a pinch as follows (instant documentation!):
     $ grep function php-iban.php
     $ egrep '(Class|function)' oophp-iban.php

  Community
  ---------
    You are encouraged to contribute bugs, feedback and suggestions 
    through the project's website.

    Particularly if you deploy php-iban in a commercial setting, you are
    STRONGLY encouraged to join the project's mailing list, which can
    be found via the website. Joining the mailing list ensures that you
    can be made aware of important updates. Important updates include:
     - Updates to new registry editions (support new countries that have
       been added to the IBAN system)
     - Bug fixes 
     - Security updates

    The email list receives almost no traffic and as a 'Google Group' is
    well protected from spam, so don't worry about junk in your inbox.

Thanks for choosing php-iban! You have excellent taste in software ;)
