# Changelog

## 6.4.1 - 2018-03-02
* [#455](https://github.com/stripe/stripe-php/pull/455) Fix namespaces in PHPDoc
* [#456](https://github.com/stripe/stripe-php/pull/456) Fix namespaces for some exceptions

## 6.4.0 - 2018-02-28
* [#453](https://github.com/stripe/stripe-php/pull/453) Add constants for `reason` (`REASON_*`) and `status` (`STATUS_*`) on `\Stripe\Dispute`

## 6.3.2 - 2018-02-27
* [#452](https://github.com/stripe/stripe-php/pull/452) Add PHPDoc for `amount_paid` and `amount_remaining` on `\Stripe\Invoice`

## 6.3.1 - 2018-02-26
* [#443](https://github.com/stripe/stripe-php/pull/443) Add event types as constants to `\Stripe\Event` class

## 6.3.0 - 2018-02-23
* [#450](https://github.com/stripe/stripe-php/pull/450) Add support for `code` attribute on all Stripe exceptions

## 6.2.0 - 2018-02-21
* [#440](https://github.com/stripe/stripe-php/pull/440) Add support for topups
* [#442](https://github.com/stripe/stripe-php/pull/442) Fix PHPDoc for `\Stripe\Error\SignatureVerification`

## 6.1.0 - 2018-02-12
* [#435](https://github.com/stripe/stripe-php/pull/435) Fix header persistence on `Collection` objects
* [#436](https://github.com/stripe/stripe-php/pull/436) Introduce new `Idempotency` error class

## 6.0.0 - 2018-02-07
Major version release. List of backwards incompatible changes to watch out for:
+ The minimum PHP version is now 5.4.0. If you're using PHP 5.3 or older, consider upgrading to a more recent version.
* `\Stripe\AttachedObject` no longer exists. Attributes that used to be instances of `\Stripe\AttachedObject` (such as `metadata`) are now instances of `\Stripe\StripeObject`.
+ Attributes that used to be PHP arrays (such as `legal_entity->additional_owners` on `\Stripe\Account` instances) are now instances of `\Stripe\StripeObject`, except when they are empty. `\Stripe\StripeObject` has array semantics so this should not be an issue unless you are actively checking types.
* `\Stripe\Collection` now derives from `\Stripe\StripeObject` rather than from `\Stripe\ApiResource`.

Pull requests included in this release:
* [#410](https://github.com/stripe/stripe-php/pull/410) Drop support for PHP 5.3
* [#411](https://github.com/stripe/stripe-php/pull/411) Use traits for common API operations
* [#414](https://github.com/stripe/stripe-php/pull/414) Use short array syntax
* [#404](https://github.com/stripe/stripe-php/pull/404) Fix serialization logic
* [#417](https://github.com/stripe/stripe-php/pull/417) Remove `ExternalAccount` class
* [#418](https://github.com/stripe/stripe-php/pull/418) Increase test coverage
* [#421](https://github.com/stripe/stripe-php/pull/421) Update CA bundle and add script for future updates
* [#422](https://github.com/stripe/stripe-php/pull/422) Use vendored CA bundle for all requests
* [#428](https://github.com/stripe/stripe-php/pull/428) Support for automatic request retries

## 5.9.2 - 2018-02-07
* [#431](https://github.com/stripe/stripe-php/pull/431) Update PHPDoc @property tags for latest API version

## 5.9.1 - 2018-02-06
* [#427](https://github.com/stripe/stripe-php/pull/427) Add and update PHPDoc @property tags on all API resources

## 5.9.0 - 2018-01-17
* [#421](https://github.com/stripe/stripe-php/pull/421) Updated bundled CA certificates
* [#423](https://github.com/stripe/stripe-php/pull/423) Escape unsanitized input in OAuth example

## 5.8.0 - 2017-12-20
* [#403](https://github.com/stripe/stripe-php/pull/403) Add `__debugInfo()` magic method to `StripeObject`

## 5.7.0 - 2017-11-28
* [#390](https://github.com/stripe/stripe-php/pull/390) Remove some unsupported API methods
* [#391](https://github.com/stripe/stripe-php/pull/391) Alphabetize the list of API resources in `Util::convertToStripeObject()` and add missing resources
* [#393](https://github.com/stripe/stripe-php/pull/393) Fix expiry date update for card sources

## 5.6.0 - 2017-10-31
* [#386](https://github.com/stripe/stripe-php/pull/386) Support for exchange rates APIs

## 5.5.1 - 2017-10-30
* [#387](https://github.com/stripe/stripe-php/pull/387) Allow `personal_address_kana` and `personal_address_kanji` to be updated on an account

## 5.5.0 - 2017-10-27
* [#385](https://github.com/stripe/stripe-php/pull/385) Support for listing source transactions

## 5.4.0 - 2017-10-24
* [#383](https://github.com/stripe/stripe-php/pull/383) Add static methods to manipulate resources from parent
    * `Account` gains methods for external accounts and login links (e.g. `createExternalAccount`, `createLoginLink`)
    * `ApplicationFee` gains methods for refunds
    * `Customer` gains methods for sources
    * `Transfer` gains methods for reversals

## 5.3.0 - 2017-10-11
* [#378](https://github.com/stripe/stripe-php/pull/378) Rename source `delete` to `detach` (and deprecate the former)

## 5.2.3 - 2017-09-27
* Add PHPDoc for `Card`

## 5.2.2 - 2017-09-20
* Fix deserialization mapping of `FileUpload` objects

## 5.2.1 - 2017-09-14
* Serialized `shipping` nested attribute

## 5.2.0 - 2017-08-29
* Add support for `InvalidClient` OAuth error

## 5.1.3 - 2017-08-14
* Allow `address_kana` and `address_kanji` to be updated for custom accounts

## 5.1.2 - 2017-08-01
* Fix documented return type of `autoPagingIterator()` (was missing namespace)

## 5.1.1 - 2017-07-03
* Fix order returns to use the right URL `/v1/order_returns`

## 5.1.0 - 2017-06-30
* Add support for OAuth

## 5.0.0 - 2017-06-27
* `pay` on invoice now takes params as well as opts

## 4.13.0 - 2017-06-19
* Add support for ephemeral keys

## 4.12.0 - 2017-06-05
* Clients can implement `getUserAgentInfo()` to add additional user agent information

## 4.11.0 - 2017-06-05
* Implement `Countable` for `AttachedObject` (`metadata` and `additional_owners`)

## 4.10.0 - 2017-05-25
* Add support for login links

## 4.9.1 - 2017-05-10
* Fix docs to include arrays on `$id` parameter for retrieve methods

## 4.9.0 - 2017-04-28
* Support for checking webhook signatures

## 4.8.1 - 2017-04-24
* Allow nested field `payout_schedule` to be updated

## 4.8.0 - 2017-04-20
* Add `\Stripe\Stripe::setLogger()` to support an external PSR-3 compatible logger

## 4.7.0 - 2017-04-10
* Add support for payouts and recipient transfers

## 4.6.0 - 2017-04-06
* Please see 4.7.0 instead (no-op release)	

## 4.5.1 - 2017-03-22
* Remove hard dependency on cURL

## 4.5.0 - 2017-03-20
* Support for detaching sources from customers

## 4.4.2 - 2017-02-27
* Correct handling of `owner` parameter when updating sources

## 4.4.1 - 2017-02-24
* Correct the error check on a bad JSON decoding

## 4.4.0 - 2017-01-18
* Add support for updating sources

## 4.3.0 - 2016-11-30
* Add support for verifying sources

## 4.2.0 - 2016-11-21
* Add retrieve method for 3-D Secure resources

## 4.1.1 - 2016-10-21
* Add docblock with model properties for `Plan`

## 4.1.0 - 2016-10-18
* Support for 403 status codes (permission denied)

## 4.0.1 - 2016-10-17
* Fix transfer reversal materialization
* Fixes for some property definitions in docblocks

## 4.0.0 - 2016-09-28
* Support for subscription items
* Drop attempt to force TLS 1.2: please note that this could be breaking if you're using old OS distributions or packages and upgraded recently (so please make sure to test your integration!)

## 3.23.0 - 2016-09-15
* Add support for Apple Pay domains

## 3.22.0 - 2016-09-13
* Add `Stripe::setAppInfo` to allow plugins to register user agent information

## 3.21.0 - 2016-08-25
* Add `Source` model for generic payment sources

## 3.20.0 - 2016-08-08
* Add `getDeclineCode` to card errors

## 3.19.0 - 2016-07-29
* Opt requests directly into TLS 1.2 where OpenSSL >= 1.0.1 (see #277 for context)

## 3.18.0 - 2016-07-28
* Add new `STATUS_` constants for subscriptions

## 3.17.1 - 2016-07-28
* Fix auto-paging iterator so that it plays nicely with `iterator_to_array`

## 3.17.0 - 2016-07-14
* Add field annotations to model classes for better editor hinting

## 3.16.0 - 2016-07-12
* Add `ThreeDSecure` model for 3-D secure payments

## 3.15.0 - 2016-06-29
* Add static `update` method to all resources that can be changed.

## 3.14.3 - 2016-06-20
* Make sure that cURL never sends `Expects: 100-continue`, even on large request bodies

## 3.14.2 - 2016-06-03
* Add `inventory` under `SKU` to list of keys that have nested data and can be updated

## 3.14.1 - 2016-05-27
* Fix some inconsistencies in PHPDoc

## 3.14.0 - 2016-05-25
* Add support for returning Relay orders

## 3.13.0 - 2016-05-04
* Add `list`, `create`, `update`, `retrieve`, and `delete` methods to the Subscription class

## 3.12.1 - 2016-04-07
* Additional check on value arrays for some extra safety

## 3.12.0 - 2016-03-31
* Fix bug `refreshFrom` on `StripeObject` would not take an `$opts` array
* Fix bug where `$opts` not passed to parent `save` method in `Account`
* Fix bug where non-existent variable was referenced in `reverse` in `Transfer`
* Update CA cert bundle for compatibility with OpenSSL versions below 1.0.1

## 3.11.0 - 2016-03-22
* Allow `CurlClient` to be initialized with default `CURLOPT_*` options

## 3.10.1 - 2016-03-22
* Fix bug where request params and options were ignored in `ApplicationFee`'s `refund.`

## 3.10.0 - 2016-03-15
* Add `reject` on `Account` to support the new API feature

## 3.9.2 - 2016-03-04
* Fix error when an object's metadata is set more than once

## 3.9.1 - 2016-02-24
* Fix encoding behavior of nested arrays for requests (see #227)

## 3.9.0 - 2016-02-09
* Add automatic pagination mechanism with `autoPagingIterator()`
* Allow global account ID to be set with `Stripe::setAccountId()`

## 3.8.0 - 2016-02-08
* Add `CountrySpec` model for looking up country payment information

## 3.7.1 - 2016-02-01
* Update bundled CA certs

## 3.7.0 - 2016-01-27
* Support deleting Relay products and SKUs

## 3.6.0 - 2016-01-05
* Allow configuration of HTTP client timeouts

## 3.5.0 - 2015-12-01
* Add a verification routine for external accounts

## 3.4.0 - 2015-09-14
* Products, SKUs, and Orders -- https://stripe.com/relay

## 3.3.0 - 2015-09-11
* Add support for 429 Rate Limit response

## 3.2.0 - 2015-08-17
* Add refund listing and retrieval without an associated charge

## 3.1.0 - 2015-08-03
* Add dispute listing and retrieval
* Add support for manage account deletion

## 3.0.0 - 2015-07-28
* Rename `\Stripe\Object` to `\Stripe\StripeObject` (PHP 7 compatibility)
* Rename `getCode` and `getParam` in exceptions to `getStripeCode` and `getStripeParam`
* Add support for calling `json_encode` on Stripe objects in PHP 5.4+
* Start supporting/testing PHP 7

## 2.3.0 - 2015-07-06
* Add request ID to all Stripe exceptions

## 2.2.0 - 2015-06-01
* Add support for Alipay accounts as sources
* Add support for bank accounts as sources (private beta)
* Add support for bank accounts and cards as external_accounts on Account objects

## 2.1.4 - 2015-05-13
* Fix CA certificate file path (thanks @lphilps & @matthewarkin)

## 2.1.3 - 2015-05-12
* Fix to account updating to permit `tos_acceptance` and `personal_address` to be set properly
* Fix to Transfer reversal creation (thanks @neatness!)
* Network requests are now done through a swappable class for easier mocking

## 2.1.2 - 2015-04-10
* Remove SSL cert revokation checking (all pre-Heartbleed certs have expired)
* Bug fixes to account updating

## 2.1.1 - 2015-02-27
* Support transfer reversals

## 2.1.0 - 2015-02-19
* Support new API version (2015-02-18)
* Added Bitcoin Receiever update and delete actions
* Edited tests to prefer "source" over "card" as per new API version

## 2.0.1 - 2015-02-16
* Fix to fetching endpoints that use a non-default baseUrl (`FileUpload`)

## 2.0.0 - 2015-02-14
* Bumped minimum version to 5.3.3
* Switched to Stripe namespace instead of Stripe_ class name prefiexes (thanks @chadicus!)
* Switched tests to PHPUnit (thanks @chadicus!)
* Switched style guide to PSR2 (thanks @chadicus!)
* Added $opts hash to the end of most methods: this permits passing 'idempotency_key', 'stripe_account', or 'stripe_version'. The last 2 will persist across multiple object loads.
* Added support for retrieving Account by ID

## 1.18.0 - 2015-01-21
* Support making bitcoin charges through BitcoinReceiver source object

## 1.17.5 - 2014-12-23
* Adding support for creating file uploads.

## 1.17.4 - 2014-12-15
* Saving objects fetched with a custom key now works (thanks @JustinHook & @jpasilan)
* Added methods for reporting charges as safe or fraudulent and for specifying the reason for refunds

## 1.17.3 - 2014-11-06
* Better handling of HHVM support for SSL certificate blacklist checking.

## 1.17.2 - 2014-09-23
* Coupons now are backed by a `Stripe_Coupon` instead of `Stripe_Object`, and support updating metadata
* Running operations (`create`, `retrieve`, `all`) on upcoming invoice items now works

## 1.17.1 - 2014-07-31
* Requests now send Content-Type header

## 1.17.0 - 2014-07-29
* Application Fee refunds now a list instead of array
* HHVM now works
* Small bug fixes (thanks @bencromwell & @fastest963)
* `__toString` now returns the name of the object in addition to its JSON representation

## 1.16.0 - 2014-06-17
* Add metadata for refunds and disputes

## 1.15.0 - 2014-05-28
* Support canceling transfers

## 1.14.1 - 2014-05-21
* Support cards for recipients.

## 1.13.1 - 2014-05-15
* Fix bug in account resource where `id` wasn't in the result

## 1.13.0 - 2014-04-10
* Add support for certificate blacklisting
* Update ca bundle
* Drop support for HHVM (Temporarily)

## 1.12.0 - 2014-04-01
* Add Stripe_RateLimitError for catching rate limit errors.
* Update to Zend coding style (thanks,  @jpiasetz)

## 1.11.0 - 2014-01-29
* Add support for multiple subscriptions per customer

## 1.10.1 - 2013-12-02
* Add new ApplicationFee

## 1.9.1 - 2013-11-08
* Fix a bug where a null nestable object causes warnings to fire.

## 1.9.0 - 2013-10-16
* Add support for metadata API.

## 1.8.4 - 2013-09-18
* Add support for closing disputes.

## 1.8.3 - 2013-08-13
* Add new Balance and BalanceTransaction

## 1.8.2 - 2013-08-12
* Add support for unsetting attributes by updating to NULL. Setting properties to a blank string is now an error.

## 1.8.1 - 2013-07-12
* Add support for multiple cards API (Stripe API version 2013-07-12: https://stripe.com/docs/upgrades#2013-07-05)

## 1.8.0 - 2013-04-11
* Allow Transfers to be creatable
* Add new Recipient resource

## 1.7.15 - 2013-02-21
* Add 'id' to the list of permanent object attributes

## 1.7.14 - 2013-02-20

* Don't re-encode strings that are already encoded in UTF-8. If you were previously using plan or coupon objects with UTF-8 IDs, they may have been treated as ISO-8859-1 (Latin-1) and encoded to UTF-8 a 2nd time. You may now need to pass the IDs to utf8_encode before passing them to Stripe_Plan::retrieve or Stripe_Coupon::retrieve.
* Ensure that all input is encoded in UTF-8 before submitting it to Stripe's servers. (github issue #27)

## 1.7.13 - 2013-02-01
* Add support for passing options when retrieving Stripe objects e.g., Stripe_Charge::retrieve(array("id"=>"foo", "expand" => array("customer"))); Stripe_Charge::retrieve("foo") will continue to work

## 1.7.12 - 2013-01-15
* Add support for setting a Stripe API version override

## 1.7.11 - 2012-12-30
* Version bump to cleanup constants and such (fix issue #26)

## 1.7.10 - 2012-11-08
* Add support for updating charge disputes.
* Fix bug preventing retrieval of null attributes

## 1.7.9 - 2012-11-08
* Fix usage under autoloaders such as the one generated by composer (fix issue #22)

## 1.7.8 - 2012-10-30
* Add support for creating invoices.
* Add support for new invoice lines return format
* Add support for new list objects

## 1.7.7 - 2012-09-14
* Get all of the various version numbers in the repo in sync (no other changes)

## 1.7.6 - 2012-08-31
* Add update and pay methods to Invoice resource

## 1.7.5 - 2012-08-23
* Change internal function names so that Stripe_SingletonApiRequest is E_STRICT-clean (github issue #16)

## 1.7.4 - 2012-08-21
* Bugfix so that Stripe objects (e.g. Customer, Charge objects) used in API calls are transparently converted to their object IDs

## 1.7.3 - 2012-08-15
* Add new Account resource

## 1.7.2 - 2012-06-26
* Make clearer that you should be including lib/Stripe.php, not test/Stripe.php (github issue #14)

## 1.7.1 - 2012-05-24
* Add missing argument to Stripe_InvalidRequestError constructor in Stripe_ApiResource::instanceUrl. Fixes a warning when Stripe_ApiResource::instanceUrl is called on a resource with no ID (fix issue #12)

## 1.7.0 - 2012-05-17
* Support Composer and Packagist (github issue #9)
* Add new deleteDiscount method to Stripe_Customer
* Add new Transfer resource
* Switch from using HTTP Basic auth to Bearer auth. (Note: Stripe will support Basic auth for the indefinite future, but recommends Bearer auth when possible going forward)
* Numerous test suite improvements
