By unix tradition, this file outlines information that may be useful to
people who wish to modify the php-iban project. It outlines some basic
information around design decisions and other considerations.

 Procedural style
 ----------------
   The code is written in PHP's original procedural style, and does
   not require or assume any OO model. This is unix tradition and
   should ease any integration pains due to objectspace mismatches.
   In addition, it should make it easy for users both within an OO
   or a procedural codebase to make use of the library. An OO wrapper
   has been supplied to make things more familiar for those who are
   only exposed to OO PHP: please try to keep it in synch with the
   procedural (main) library where possible.

 Registry maintenance
 --------------------
   The 'convert-registry.php' tool found in the 'utils/' subdirectory
   is intended to assist with the automatic conversion of the SWIFT-
   provided 'IBAN Registry' text files to the format required to
   support php-iban execution. Why is there a new format, and why is it
   distributed with php-iban instead of being generated on the fly
   from SWIFT-provided data files? There are a few reasons:

    - Error correction
      If errors are discovered in the official specification then they
      can be resolved by us. There are (or have been) known errors
      with the official IBAN Registry. (See COMEDY-OF-ERRORS)

    - Exclusion correction
      If exclusions are discovered in the official specification then
      they can be resolved by us. There are (or have been) known 
      exclusions from the official IBAN Registry. (See COMEDY-OF-ERRORS)

    - Efficiency
      Because pattern matching is a core part of the functionality of
      php-iban, and the pattern algorithms distributed by SWIFT are
      (rather strangely) not in regular expression format, using their
      files directly would result in a fairly significant startup
      penalty as pattern conversion would be required (at each
      invocation!) unless a caching strategy were deployed, which would
      create additional complexity and sources of bugs (in addition,
      due to the previous two points automatic conversion is not
      presently possible ... and may never be!)

    - Maintainability
      Distribution of a modified registry along with php-iban places
      the burden of registry maintenance on with the package 
      maintainer(s) rather than with the user. This is better for
      users who, if they really want, can still hack their local copy.

   Note that due to points one and two, the 'convert-registry.php' tool
   is insufficient to produce a correct 'registry.txt' file.  (You may
   wish to review the differences between your newly generated file
   and the original with the 'diff' tool in order to ascertain what
   has changed.)

   A closing point on the registry: obviously, if any new fields are
   added, then it is best to append them to the end of the registry
   (rightmost, new field) in order to preserve backwards compatibility
   instead of re-ordering the fields which would break older installs.
   (The internal '_iban_load_registry()' function re-orders these fields
   at load time in order to simplify runtime debugging, anyway.)
