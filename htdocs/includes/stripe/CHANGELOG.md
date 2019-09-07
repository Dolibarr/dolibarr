# Changelog

## 6.41.0 - 2019-07-31
* [#683](https://github.com/stripe/stripe-php/pull/683) Move the List Balance History API to `/v1/balance_transactions`

## 6.40.0 - 2019-06-27
* [#675](https://github.com/stripe/stripe-php/pull/675) Add support for `SetupIntent` resource and APIs

## 6.39.2 - 2019-06-26
* [#676](https://github.com/stripe/stripe-php/pull/676) Fix exception message in `CustomerBalanceTransaction::update()`

## 6.39.1 - 2019-06-25
* [#674](https://github.com/stripe/stripe-php/pull/674) Add new constants for `collection_method` on `Invoice`

## 6.39.0 - 2019-06-24
* [#673](https://github.com/stripe/stripe-php/pull/673) Enable request latency telemetry by default

## 6.38.0 - 2019-06-17
* [#649](https://github.com/stripe/stripe-php/pull/649) Add support for `CustomerBalanceTransaction` resource and APIs

## 6.37.2 - 2019-06-17
* [#671](https://github.com/stripe/stripe-php/pull/671) Add new PHPDoc
* [#672](https://github.com/stripe/stripe-php/pull/672) Add constants for `submit_type` on Checkout `Session`

## 6.37.1 - 2019-06-14
* [#670](https://github.com/stripe/stripe-php/pull/670) Add new PHPDoc

## 6.37.0 - 2019-05-23
* [#663](https://github.com/stripe/stripe-php/pull/663) Add support for `radar.early_fraud_warning` resource

## 6.36.0 - 2019-05-22
* [#661](https://github.com/stripe/stripe-php/pull/661) Add constants for new TaxId types
* [#662](https://github.com/stripe/stripe-php/pull/662) Add constants for BalanceTransaction types

## 6.35.2 - 2019-05-20
* [#655](https://github.com/stripe/stripe-php/pull/655) Add constants for payment intent statuses
* [#659](https://github.com/stripe/stripe-php/pull/659) Fix PHPDoc for various nested Account actions
* [#660](https://github.com/stripe/stripe-php/pull/660) Fix various PHPDoc

## 6.35.1 - 2019-05-20
* [#658](https://github.com/stripe/stripe-php/pull/658) Use absolute value when checking timestamp tolerance

## 6.35.0 - 2019-05-14
* [#651](https://github.com/stripe/stripe-php/pull/651) Add support for the Capability resource and APIs

## 6.34.6 - 2019-05-13
* [#654](https://github.com/stripe/stripe-php/pull/654) Fix typo in definition of `Event::PAYMENT_METHOD_ATTACHED` constant

## 6.34.5 - 2019-05-06
* [#647](https://github.com/stripe/stripe-php/pull/647) Set the return type to static for more operations

## 6.34.4 - 2019-05-06
* [#650](https://github.com/stripe/stripe-php/pull/650) Add missing constants for Event types

## 6.34.3 - 2019-05-01
* [#644](https://github.com/stripe/stripe-php/pull/644) Update return type to `static` to improve static analysis
* [#645](https://github.com/stripe/stripe-php/pull/645) Fix constant for `payment_intent.payment_failed`

## 6.34.2 - 2019-04-26
* [#642](https://github.com/stripe/stripe-php/pull/642) Fix an issue where existing idempotency keys would be overwritten when using automatic retries

## 6.34.1 - 2019-04-25
* [#640](https://github.com/stripe/stripe-php/pull/640) Add missing phpdocs 

## 6.34.0 - 2019-04-24
* [#626](https://github.com/stripe/stripe-php/pull/626) Add support for the `TaxRate` resource and APIs
* [#639](https://github.com/stripe/stripe-php/pull/639) Fix multiple phpdoc issues

## 6.33.0 - 2019-04-22
* [#630](https://github.com/stripe/stripe-php/pull/630) Add support for the `TaxId` resource and APIs

## 6.32.1 - 2019-04-19
* [#636](https://github.com/stripe/stripe-php/pull/636) Correct type of `$personId` in PHPDoc

## 6.32.0 - 2019-04-18
* [#621](https://github.com/stripe/stripe-php/pull/621) Add support for `CreditNote`

## 6.31.5 - 2019-04-12
* [#628](https://github.com/stripe/stripe-php/pull/628) Add constants for `person.*` event types
* [#628](https://github.com/stripe/stripe-php/pull/628) Add missing constants for `Account` and `Person`

## 6.31.4 - 2019-04-05
* [#624](https://github.com/stripe/stripe-php/pull/624) Fix encoding of nested parameters in multipart requests

## 6.31.3 - 2019-04-02
* [#623](https://github.com/stripe/stripe-php/pull/623) Only use HTTP/2 with curl >= 7.60.0

## 6.31.2 - 2019-03-25
* [#619](https://github.com/stripe/stripe-php/pull/619) Fix PHPDoc return types for list methods for nested resources

## 6.31.1 - 2019-03-22
* [#612](https://github.com/stripe/stripe-php/pull/612) Add a lot of constants
* [#614](https://github.com/stripe/stripe-php/pull/614) Add missing subscription status constants

## 6.31.0 - 2019-03-18
* [#600](https://github.com/stripe/stripe-php/pull/600) Add support for the `PaymentMethod` resource and APIs
* [#606](https://github.com/stripe/stripe-php/pull/606) Add support for retrieving a Checkout `Session`
* [#611](https://github.com/stripe/stripe-php/pull/611) Add support for deleting a Terminal `Location` and `Reader`

## 6.30.5 - 2019-03-11
* [#607](https://github.com/stripe/stripe-php/pull/607) Correctly handle case where a metadata key is called `metadata`

## 6.30.4 - 2019-02-27
* [#602](https://github.com/stripe/stripe-php/pull/602) Add `subscription_schedule` to `Subscription` for PHPDoc.

## 6.30.3 - 2019-02-26
* [#603](https://github.com/stripe/stripe-php/pull/603) Improve PHPDoc on the `Source` object to cover all types of Sources currently supported.

## 6.30.2 - 2019-02-25
* [#601](https://github.com/stripe/stripe-php/pull/601) Fix PHPDoc across multiple resources and add support for new events.

## 6.30.1 - 2019-02-16
* [#599](https://github.com/stripe/stripe-php/pull/599) Fix PHPDoc for `SubscriptionSchedule` and `SubscriptionScheduleRevision`

## 6.30.0 - 2019-02-12
* [#590](https://github.com/stripe/stripe-php/pull/590) Add support for `SubscriptionSchedule` and `SubscriptionScheduleRevision`

## 6.29.3 - 2019-01-31
* [#592](https://github.com/stripe/stripe-php/pull/592) Some more PHPDoc fixes

## 6.29.2 - 2019-01-31
* [#591](https://github.com/stripe/stripe-php/pull/591) Fix PHPDoc for nested resources

## 6.29.1 - 2019-01-25
* [#566](https://github.com/stripe/stripe-php/pull/566) Fix dangling message contents
* [#586](https://github.com/stripe/stripe-php/pull/586) Don't overwrite `CURLOPT_HTTP_VERSION` option

## 6.29.0 - 2019-01-23
* [#579](https://github.com/stripe/stripe-php/pull/579) Rename `CheckoutSession` to `Session` and move it under the `Checkout` namespace. This is a breaking change, but we've reached out to affected merchants and all new merchants would use the new approach.

## 6.28.1 - 2019-01-21
* [#580](https://github.com/stripe/stripe-php/pull/580) Properly serialize `individual` on `Account` objects

## 6.28.0 - 2019-01-03
* [#576](https://github.com/stripe/stripe-php/pull/576) Add support for iterating directly over `Collection` instances

## 6.27.0 - 2018-12-21
* [#571](https://github.com/stripe/stripe-php/pull/571) Add support for the `CheckoutSession` resource

## 6.26.0 - 2018-12-11
* [#568](https://github.com/stripe/stripe-php/pull/568) Enable persistent connections

## 6.25.0 - 2018-12-10
* [#567](https://github.com/stripe/stripe-php/pull/567) Add support for account links

## 6.24.0 - 2018-11-28
* [#562](https://github.com/stripe/stripe-php/pull/562) Add support for the Review resource
* [#564](https://github.com/stripe/stripe-php/pull/564) Add event name constants for subscription schedule aborted/expiring

## 6.23.0 - 2018-11-27
* [#542](https://github.com/stripe/stripe-php/pull/542) Add support for `ValueList` and `ValueListItem` for Radar 

## 6.22.1 - 2018-11-20
* [#561](https://github.com/stripe/stripe-php/pull/561) Add cast and some docs to telemetry introduced in 6.22.0/#549

## 6.22.0 - 2018-11-15
* [#549](https://github.com/stripe/stripe-php/pull/549) Add support for client telemetry

## 6.21.1 - 2018-11-12
* [#548](https://github.com/stripe/stripe-php/pull/548) Don't mutate `Exception` class properties from `OAuthBase` error

## 6.21.0 - 2018-11-08
* [#537](https://github.com/stripe/stripe-php/pull/537) Add new API endpoints for the `Invoice` resource.

## 6.20.1 - 2018-11-07
* [#546](https://github.com/stripe/stripe-php/pull/546) Drop files from the Composer package that aren't needed in the release

## 6.20.0 - 2018-10-30
* [#536](https://github.com/stripe/stripe-php/pull/536) Add support for the `Person` resource
* [#541](https://github.com/stripe/stripe-php/pull/541) Add support for the `WebhookEndpoint` resource

## 6.19.5 - 2018-10-17
* [#539](https://github.com/stripe/stripe-php/pull/539) Fix methods on `\Stripe\PaymentIntent` to properly pass arguments to the API.

## 6.19.4 - 2018-10-11
* [#534](https://github.com/stripe/stripe-php/pull/534) Fix PSR-4 autoloading for `\Stripe\FileUpload` class alias

## 6.19.3 - 2018-10-09
* [#530](https://github.com/stripe/stripe-php/pull/530) Add constants for `flow` (`FLOW_*`), `status` (`STATUS_*`) and `usage` (`USAGE_*`) on `\Stripe\Source`

## 6.19.2 - 2018-10-08
* [#531](https://github.com/stripe/stripe-php/pull/531) Store HTTP response headers in case-insensitive array

## 6.19.1 - 2018-09-25
* [#526](https://github.com/stripe/stripe-php/pull/526) Ignore null values in request parameters

## 6.19.0 - 2018-09-24
* [#523](https://github.com/stripe/stripe-php/pull/523) Add support for Stripe Terminal

## 6.18.0 - 2018-09-24
* [#520](https://github.com/stripe/stripe-php/pull/520) Rename `\Stripe\FileUpload` to `\Stripe\File`

## 6.17.2 - 2018-09-18
* [#522](https://github.com/stripe/stripe-php/pull/522) Fix warning when adding a new additional owner to an existing array

## 6.17.1 - 2018-09-14
* [#517](https://github.com/stripe/stripe-php/pull/517) Integer-index encode all sequential arrays

## 6.17.0 - 2018-09-05
* [#514](https://github.com/stripe/stripe-php/pull/514) Add support for reporting resources

## 6.16.0 - 2018-08-23
* [#509](https://github.com/stripe/stripe-php/pull/509) Add support for usage record summaries

## 6.15.0 - 2018-08-03
* [#504](https://github.com/stripe/stripe-php/pull/504) Add cancel support for topups

## 6.14.0 - 2018-08-02
* [#505](https://github.com/stripe/stripe-php/pull/505) Add support for file links

## 6.13.0 - 2018-07-31
* [#502](https://github.com/stripe/stripe-php/pull/502) Add `isDeleted()` method to `\Stripe\StripeObject`

## 6.12.0 - 2018-07-28
* [#501](https://github.com/stripe/stripe-php/pull/501) Add support for scheduled query runs (`\Stripe\Sigma\ScheduledQueryRun`) for Sigma

## 6.11.0 - 2018-07-26
* [#500](https://github.com/stripe/stripe-php/pull/500) Add support for Stripe Issuing

## 6.10.4 - 2018-07-19
* [#498](https://github.com/stripe/stripe-php/pull/498) Internal improvements to the `\Stripe\ApiResource.classUrl()` method

## 6.10.3 - 2018-07-16
* [#497](https://github.com/stripe/stripe-php/pull/497) Use HTTP/2 only for HTTPS requests

## 6.10.2 - 2018-07-11
* [#494](https://github.com/stripe/stripe-php/pull/494) Enable HTTP/2 support

## 6.10.1 - 2018-07-10
* [#493](https://github.com/stripe/stripe-php/pull/493) Add PHPDoc for `auto_advance` on `\Stripe\Invoice`

## 6.10.0 - 2018-06-28
* [#488](https://github.com/stripe/stripe-php/pull/488) Add support for `$appPartnerId` to `Stripe::setAppInfo()`

## 6.9.0 - 2018-06-28
* [#487](https://github.com/stripe/stripe-php/pull/487) Add support for payment intents

## 6.8.2 - 2018-06-24
* [#486](https://github.com/stripe/stripe-php/pull/486) Make `Account.deauthorize()` return the `StripeObject` from the API

## 6.8.1 - 2018-06-13
* [#472](https://github.com/stripe/stripe-php/pull/472) Added phpDoc for `ApiRequestor` and others, especially regarding thrown errors

## 6.8.0 - 2018-06-13
* [#481](https://github.com/stripe/stripe-php/pull/481) Add new `\Stripe\Discount` and `\Stripe\OrderItem` classes, add more PHPDoc describing object attributes

## 6.7.4 - 2018-05-29
* [#480](https://github.com/stripe/stripe-php/pull/480) PHPDoc changes for API version 2018-05-21 and the addition of the new `CHARGE_EXPIRED` event type

## 6.7.3 - 2018-05-28
* [#479](https://github.com/stripe/stripe-php/pull/479) Fix unnecessary traits on `\Stripe\InvoiceLineItem`

## 6.7.2 - 2018-05-28
* [#471](https://github.com/stripe/stripe-php/pull/471) Add `OBJECT_NAME` constant to all API resource classes, add `\Stripe\InvoiceLineItem` class

## 6.7.1 - 2018-05-13
* [#468](https://github.com/stripe/stripe-php/pull/468) Update fields in PHP docs for accuracy

## 6.7.0 - 2018-05-09
* [#466](https://github.com/stripe/stripe-php/pull/466) Add support for issuer fraud records

## 6.6.0 - 2018-04-11
* [#460](https://github.com/stripe/stripe-php/pull/460) Add support for flexible billing primitives

## 6.5.0 - 2018-04-05
* [#461](https://github.com/stripe/stripe-php/pull/461) Don't zero keys on non-`metadata` subobjects

## 6.4.2 - 2018-03-17
* [#458](https://github.com/stripe/stripe-php/pull/458) Add PHPDoc for `account` on `\Stripe\Event`

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
