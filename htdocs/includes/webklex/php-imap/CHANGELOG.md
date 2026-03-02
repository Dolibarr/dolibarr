# Changelog

All notable changes to `webklex/php-imap` will be documented in this file.

Updates should follow the [Keep a CHANGELOG](http://keepachangelog.com/) principles.

## [UNRELEASED]
### Fixed
- NaN

### Added
- NaN

### Breaking changes
- NaN

## [6.2.0] - 2025-04-25
### Fixed
- When using the chunk function, some messages do not have an element with index 0 #552 #553 (thanks @zeddmaster)
- Get folders list in hierarchical order #560 #561 (thanks @rskrzypczak)
- Fix remaining implicit marking of parameters as nullable (PHP 8.4) #566 (thanks @steffenweber)
- Fix case sensitivity of folder attribute parsing (\NoSelect, \NoInferiors) #469 #571 (thanks @smajti1)
- Fix error on getUid(null) with 0 results (#499) #573 (thanks @pierement)
- Fix Date parsing on non-standard format from Aqua Mail #574 #575 (thanks @lm-cmxkonzepte)

### Added
- SSL stream context options added #238 #546 (thanks @llemoine)
- Support copy/move Message with utf7 folder path #559 (thanks @loc4l)
- Public `Query::search()` method #565 (Thanks @madbob)

## [6.1.0] - 2025-01-19
### Fixed
- Filename sanitization is now optional (enabled via default)
- Address parsing improved and extended to include more cases
- Boundary parsing fixed and improved to support more formats #544
- Decode partially encoded address names #511
- Enforce RFC822 parsing if enabled #462

### Added
- Security configuration options added
- Spoofing detection added #40
- RFC4315 MOVE fallback added #123 (thanks @freescout-help-desk)
- Content fetching RFC standard support added #510 (thanks @ybizeul)
- Support unescaped dates inside the search conditions #542
- `Client::clone()` looses account configuration #521 (thanks @netpok)

## [6.0.0] - 2025-01-17
### Fixed
- Fixed date issue if timezone is UT and a 2 digit year #429 (thanks @ferrisbuellers)
- Make the space optional after a comma separator #437 (thanks @marc0adam)
- Fix bug when multipart message getHTMLBody() method returns null #455 (thanks @michalkortas)
- Fix: Improve return type hints and return docblocks for query classes #470 (thanks @olliescase)
- Fix - Query - Chunked - Resolved infinite loop when start chunk > 1 #477 (thanks @NeekTheNook)
- Attachment with symbols in filename #436 (thanks @nuernbergerA)
- Ignore possible untagged lines after IDLE and DONE commands #445 (thanks @gazben)
- Fix Empty Child Folder Error #474 (thanks @bierpub)
- Filename sanitization improved #501 (thanks @neolip)
- `Client::getFolderPath()` return null if folder is not set #506 (thanks @arnolem)
- Fix implicit marking of parameters as nullable, deprecated in PHP 8.4 #518 (thanks @campbell-m)

### Added
- IMAP STATUS command support added `Folder::status()` #424 (thanks @InterLinked1)
- Add attributes and special flags #428 (thanks @sazanof)
- Better connection check for IMAP #449 (thanks @thin-k-design)
- Config handling moved into a new class `Config::class` to allow class serialization (sponsored by elb-BIT GmbH)
- Support for Carbon 3 added #483
- Custom decoder support added
- Decoding filename with non-standard encoding #535 (thanks @grnsv)

### Breaking changes
- The decoder config has been moved from `options.decoder` to `decoding` and contains now the `decoder` class to used as well as their decoding fallbacks
- `Folder::getStatus()` no longer returns the results of `EXAMINE` but `STATUS` instead. If you want to use `EXAMINE` you can use the `Folder::examine()` method instead.
- `ClientManager::class` has now longer access to all configs. Config handling has been moved to its own class `Config::class`. If you want to access the config you can use the retriever method `::getConfig()` instead. Example: `$client->getConfig()` or `$message->getConfig()`, etc.
- `ClientManager::get` isn't available anymore. Use the regular config accessor instead. Example: `$cm->getConfig()->get($key)`
- `M̀essage::getConfig()` now returns the client configuration instead of the fetching options configuration. Please use `$message->getOptions()` instead.
- `Attachment::getConfig()` now returns the client configuration instead of the fetching options configuration. Please use `$attachment->getOptions()` instead.
- `Header::getConfig()` now returns the client configuration instead of the fetching options configuration. Please use `$header->getOptions()` instead.
- `M̀essage::setConfig` now expects the client configuration instead of the fetching options configuration. Please use `$message->setOptions` instead.
- `Attachment::setConfig` now expects the client configuration instead of the fetching options configuration. Please use `$attachment->setOptions` instead.
- `Header::setConfig` now expects the client configuration instead of the fetching options configuration. Please use `$header->setOptions` instead.
- All protocol constructors now require a `Config::class` instance
- The `Client::class` constructor now require a `Config::class` instance
- The `Part::class` constructor now require a `Config::class` instance
- The `Header::class` constructor now require a `Config::class` instance
- The `Message::fromFile` method now requires a `Config::class` instance
- The `Message::fromString` method now requires a `Config::class` instance
- The `Message::boot` method now requires a `Config::class` instance
- The `Message::decode` method has been removed. Use `Message::getDecoder()->decode($str)` instead.
- The `Message::getEncoding` method has been removed. Use `Message::getDecoder()->getEncoding($str)` instead.
- The `Message::convertEncoding` method has been removed. Use `Message::getDecoder()->convertEncoding()` instead.
- The `Header::decode` method has been removed. Use `Header::getDecoder()->decode($str)` instead.

## [5.5.0] - 2023-06-28
### Fixed
- Error token length mismatch in `ImapProtocol::readResponse` #400
- Attachment name parsing fixed #410 #421 (thanks @nuernbergerA)
- Additional Attachment name fallback added to prevent missing attachments
- Attachment id is now static (based on the raw part content) instead of random
- Always parse the attachment description if it is available

### Added
- Attachment content hash added


## [5.4.0] - 2023-06-24
### Fixed
- Legacy protocol support fixed (object to array conversion) #411
- Header value decoding improved #410
- Protocol exception handling improved (bad response message added) #408
- Prevent fetching singular rfc partials from running indefinitely #407
- Subject with colon ";" is truncated #401
- Catching and handling iconv decoding exception #397

### Added
- Additional timestamp formats added #198 #392 (thanks @esk-ap)


## [5.3.0] - Security patch - 2023-06-20
### Fixed
- Potential RCE through path traversal fixed #414 (special thanks @angelej)

### Security Impact and Mitigation
Impacted are all versions below v5.3.0.
If possible, update to >= v5.3.0 as soon as possible. Impacted was the `Attachment::save`
method which could be used to write files to the local filesystem. The path was not
properly sanitized and could be used to write files to arbitrary locations.

However, the `Attachment::save` method is not used by default and has to be called
manually. If you are using this method without providing a sanitized path, you are
affected by this vulnerability.
If you are not using this method or are providing a sanitized path, you are not affected
by this vulnerability and no immediate action is required.

If you have any questions, please feel welcome to join this issue: https://github.com/Webklex/php-imap/issues/416
#### Timeline
- 17.06.23 21:30: Vulnerability reported
- 18.06.23 19:14: Vulnerability confirmed
- 19.06.23 18:41: Vulnerability fixed via PR #414
- 20.06.23 13:45: Security patch released
- 21.06.23 20:48: CVE-2023-35169 got assigned
- 21.06.23 20:58: Advisory released https://github.com/Webklex/php-imap/security/advisories/GHSA-47p7-xfcc-4pv9


## [5.2.0] - 2023-04-11
### Fixed
- Use all available methods to detect the attachment extension instead of just one
- Allow the `LIST` command response to be empty #393
- Initialize folder children attributes on class initialization

### Added
- Soft fail option added to all folder fetching methods. If soft fail is enabled, the method will return an empty collection instead of throwing an exception if the folder doesn't exist


## [5.1.0] - 2023-03-16
### Fixed
- IMAP Quota root command fixed
- Prevent line-breaks in folder path caused by special chars
- Partial fix for #362 (allow overview response to be empty)
- `Message::setConfig()` config parameter type set to array
- Reset the protocol uid cache if the session gets expunged
- Set the "seen" flag only if the flag isn't set and the fetch option isn't `IMAP::FT_PEEK`
- `Message::is()` date comparison fixed
- `Message::$client` could not be set to null
- `in_reply_to` and `references` parsing fixed
- Prevent message body parser from injecting empty lines
- Don't parse regular inline message parts without name or filename as attachment
- `Message::hasTextBody()` and `Message::hasHtmlBody()` should return `false` if the body is empty
- Imap-Protocol "empty response" detection extended to catch an empty response caused by a broken resource stream
- `iconv_mime_decode()` is now used with `ICONV_MIME_DECODE_CONTINUE_ON_ERROR` to prevent the decoding from failing
- Date decoding rules extended to support more date formats
- Unset the currently active folder if it gets deleted (prevent infinite loop)
- Attachment name and filename parsing fixed and improved to support more formats
- Check if the next uid is available (after copying or moving a message) before fetching it #381
- Default pagination `$total` attribute value set to 0 #385 (thanks @hhniao)
- Use attachment ID as fallback filename for saving an attachment
- Address decoding error detection added #388

### Added
- Extended UTF-7 support added (RFC2060) #383
- `Protocol::sizes()` support added (fetch the message byte size via RFC822.SIZE). Accessible through `Message::getSize()` #379 (thanks @didi1357)
- `Message::hasFlag()` method added to check if a message has a specific flag
- `Message::getConfig()` method added to get the current message configuration
- `Folder::select()` method added to select a folder
- `Message::getAvailableFlags()` method added to get all available flags
- Live mailbox and fixture tests added
- `Attribute::map()` method added to map all attribute values
- `Header::has()` method added to check if a header attribute / value exist
- All part attributes are now accessible via linked attribute
- Restore a message from string `Message::fromString()`


## [5.0.1] - 2023-03-01
### Fixed
- More unique ID generation to prevent multiple attachments with same ID #363 (thanks @Guite)
- Not all attachments are pushed to the collection #372 (thanks @AdrianKuriata)
- Partial fix for #362 (allow search response to be empty)
- Unsafe usage of switch case. #354 #366 (thanks @shuergab)
- Fix use of ST_MSGN as sequence method #356 (thanks @gioid)
- Prevent infinite loop in ImapProtocol #316 (thanks @thin-k-design)


## [5.0.0] - 2023-01-18
### Fixed
- The message uid and message number will only be fetched if accessed and wasn't previously set #326 #285 (thanks @szymekjanaczek)
- Fix undefined attachment name when headers use "filename*=" format #301 (thanks @JulienChavee)
- Fixed `ImapProtocol::logout` always throws 'not connected' Exception after upgraded to 4.1.2 #351
- Protocol interface and methods unified
- Strict attribute and return types introduced where ever possible
- Parallel messages during idle #338
- Idle timeout / stale resource stream issue fixed
- Syntax updated to support php 8 features
- Get the attachment file extension from the filename if no mimetype detection library is available
- Prevent the structure parsing from parsing an empty part
- Convert all header keys to their lower case representation
- Restructure the decode function #355 (thanks @istid)

### Added
- Unit tests added #347 #242 (thanks @sergiy-petrov, @boekkooi-lengoo)
- `Client::clone()` method added to clone a client instance
- Save an entire message (including its headers) `Message::save()`
- Restore a message from a local or remote file `Message::fromFile()`
- Protocol resource stream accessor added `Protocol::getStream()`
- Protocol resource stream meta data accessor added `Protocol::meta()`
- ImapProtocol resource stream reset method added `ImapProtocol::reset()`
- Protocol `Response::class` introduced to handle and unify all protocol requests
- Static mask config accessor added `ClientManager::getMask()` added
- An `Attribute::class`  instance can be treated as array
- Get the current client account configuration via `Client::getConfig()`
- Delete a folder via `Client::deleteFolder()`

### Breaking changes
- PHP ^8.0.2 required
- `nesbot/carbon` version bumped to ^2.62.1
- `phpunit/phpunit` version bumped to ^9.5.10
- `Header::get()` always returns an `Attribute::class` instance
- `Attribute::class` accessor methods renamed to shorten their names and improve the readability
- All protocol methods that used to return `array|bool` will now always return a `Response::class` instance.
- `ResponseException::class` gets thrown if a response is empty or contains errors
- Message client is optional and can be null (e.g. if used in combination with `Message::fromFile()`)
- The message text or html body is now "" if its empty and not `null`


## [4.1.2] - 2022-12-14
### Fixed
- Attachment ID can return an empty value #318
- Additional message date format added #345 (thanks @amorebietakoUdala)


## [4.1.1] - 2022-11-16
### Fixed
- Fix for extension recognition #325 (thanks @pwoszczyk)
- Missing null check added #327 (thanks @spanjeta)
- Leading white-space in response causes an infinite loop #321 (thanks @thin-k-design)
- Fix error when creating folders with special chars #319 (thanks @thin-k-design)
- `Client::getFoldersWithStatus()` recursive loading fixed #312 (thanks @szymekjanaczek)
- Fix Folder name encoding error in `Folder::appendMessage()` #306 #307 (thanks @rskrzypczak)


## [4.1.0] - 2022-10-18
### Fixed
- Fix assumedNextTaggedLine bug #288 (thanks @Blear)
- Fix empty response error for blank lines #274 (thanks @bierpub)
- Fix empty body #233 (thanks @latypoff)
- Fix imap_reopen folder argument #234 (thanks @latypoff)

### Added
- Added possibility of loading a Folder status #298 (thanks @szymekjanaczek)


## [4.0.2] - 2022-08-26
### Fixed
- RFC 822 3.1.1. long header fields regular expression fixed #268 #269 (thanks @hbraehne)


## [4.0.1] - 2022-08-25
### Fixed
- Type casting added to several ImapProtocol return values #261
- Remove IMAP::OP_READONLY flag from imap_reopen if POP3 or NNTP protocol is selected #135 (thanks @xianzhe18)
- Several statements optimized and redundant checks removed
- Check if the Protocol supports the fetch method if extensions are present
- Detect `NONEXISTENT` errors while selecting or examining a folder #266
- Missing type cast added to `PaginatedCollection::paginate` #267 (thanks @rogerb87)
- Fix multiline header unfolding #250 (thanks @sulgie-eitea)
- Fix problem with illegal offset error #226 (thanks @szymekjanaczek)
- Typos fixed

### Affected Classes
- [Query::class](src/Query/Query.php)
- [ImapProtocol::class](src/Connection/Protocols/ImapProtocol.php)
- [LegacyProtocol::class](src/Connection/Protocols/LegacyProtocol.php)
- [PaginatedCollection::class](src/Support/PaginatedCollection.php)


## [4.0.0] - 2022-08-19
### Fixed
- PHP dependency updated to support php v8.0 #212 #214 (thanks @freescout-helpdesk)
- Method return and argument types added
- Imap `DONE` method refactored
- UID cache loop fixed
- `HasEvent::getEvent` return value set to mixed to allow multiple event types
- Protocol line reader changed to `fread` (stream_context timeout issue fixed)
- Issue setting the client timeout fixed
- IMAP Connection debugging improved
- `Folder::idle()` method reworked and several issues fixed #170 #229 #237 #249 #258
- Datetime conversion rules extended #189 #173

### Affected Classes
- [Client::class](src/Client.php)
- [Folder::class](src/Folder.php)
- [ImapProtocol::class](src/Connection/Protocols/ImapProtocol.php)
- [HasEvents::class](src/Traits/HasEvents.php)

### Breaking changes
- No longer supports php >=5.5.9 but instead requires at least php v7.0.0.
- `HasEvent::getEvent` returns a mixed result. Either an `Event` or a class string representing the event class.
- The error message, if the connection fails to read the next line, is now `empty response` instead of `failed to read - connection closed?`.
- The `$auto_reconnect` used with `Folder::indle()` is deprecated and doesn't serve any purpose anymore.


## [3.2.0] - 2022-03-07
### Fixed
- Fix attribute serialization #179 (thanks @netpok)
- Use real tls instead of starttls #180 (thanks @netpok)
- Allow to fully overwrite default config arrays #194 (thanks @laurent-rizer)
- Query::chunked does not loop over the last chunk #196 (thanks @laurent-rizer)
- Fix isAttachment that did not properly take in consideration dispositions options #195 (thanks @laurent-rizer)
- Extend date parsing error message #173
- Fixed 'Where' method replaces the content with uppercase #148
- Don't surround numeric search values with quotes
- Context added to `InvalidWhereQueryCriteriaException`
- Redundant `stream_set_timeout()` removed

### Added
- UID Cache added #204 (thanks @HelloSebastian)
- Query::class extended with `getByUidLower`, `getByUidLowerOrEqual` , `getByUidGreaterOrEqual` , `getByUidGreater` to fetch certain ranges of uids #201 (thanks @HelloSebastian)
- Check if IDLE is supported if `Folder::idle()` is called #199 (thanks @HelloSebastian)
- Fallback date support added. The config option `options.fallback_date` is used as fallback date is it is set. Otherwise, an exception will be thrown #198
- UID filter support added
- Make boundary regex configurable #169 #150 #126 #121 #111 #152 #108 (thanks @EthraZa)
- IMAP ID support added #174
- Enable debug mode via config
- Custom UID alternative support added
- Fetch additional extensions using `Folder::query(["FEATURE_NAME"])`
- Optionally move a message during "deletion" instead of just "flagging" it #106 (thanks @EthraZa)
- `WhereQuery::where()` accepts now a wide range of criteria / values. #104

### Affected Classes
- [Part::class](src/Part.php)
- [Query::class](src/Query/Query.php)
- [Client::class](src/Client.php)
- [Header::class](src/Header.php)
- [Protocol::class](src/Connection/Protocols/Protocol.php)
- [ClientManager::class](src/ClientManager.php)

### Breaking changes
- If you are using the legacy protocol to search, the results no longer return false if the search criteria could not be interpreted but instead return an empty array. This will ensure it is compatible to the rest of this library and no longer result in a potential type confusion.
- `Folder::idle` will throw an `Webklex\PHPIMAP\Exceptions\NotSupportedCapabilityException` exception if IMAP isn't supported by the mail server
- All protocol methods which had a `boolean` `$uid` option no longer support a boolean value. Use `IMAP::ST_UID` or `IMAP::NIL` instead. If you want to use an alternative to `UID` just use the string instead.
- Default config option `options.sequence` changed from `IMAP::ST_MSGN` to `IMAP::ST_UID`.
- `Folder::query()` no longer accepts a charset string. It has been replaced by an extension array, which provides the ability to automatically fetch additional features.


## [3.1.0-alpha] - 2022-02-03
### Fixed
- Fix attribute serialization #179 (thanks @netpok)
- Use real tls instead of starttls #180 (thanks @netpok)
- Allow to fully overwrite default config arrays #194 (thanks @laurent-rizer)
- Query::chunked does not loop over the last chunk #196 (thanks @laurent-rizer)
- Fix isAttachment that did not properly take in consideration dispositions options #195 (thanks @laurent-rizer)

### Affected Classes
- [Header::class](src/Header.php)
- [Protocol::class](src/Connection/Protocols/Protocol.php)
- [Query::class](src/Query/Query.php)
- [Part::class](src/Part.php)
- [ClientManager::class](src/ClientManager.php)

## [3.0.0-alpha] - 2021-11-04
### Fixed
- Extend date parsing error message #173
- Fixed 'Where' method replaces the content with uppercase #148
- Don't surround numeric search values with quotes
- Context added to `InvalidWhereQueryCriteriaException`
- Redundant `stream_set_timeout()` removed

### Added
- Make boundary regex configurable #169 #150 #126 #121 #111 #152 #108 (thanks @EthraZa)
- IMAP ID support added #174
- Enable debug mode via config
- Custom UID alternative support added
- Fetch additional extensions using `Folder::query(["FEATURE_NAME"])`
- Optionally move a message during "deletion" instead of just "flagging" it #106 (thanks @EthraZa)
- `WhereQuery::where()` accepts now a wide range of criteria / values. #104

### Affected Classes
- [Header::class](src/Header.php)
- [Protocol::class](src/Connection/Protocols/Protocol.php)
- [Query::class](src/Query/Query.php)
- [WhereQuery::class](src/Query/WhereQuery.php)
- [Message::class](src/Message.php)

### Breaking changes
- All protocol methods which had a `boolean` `$uid` option no longer support a boolean. Use `IMAP::ST_UID` or `IMAP::NIL` instead. If you want to use an alternative to `UID` just use the string instead.
- Default config option `options.sequence` changed from `IMAP::ST_MSGN` to `IMAP::ST_UID`.
- `Folder::query()` no longer accepts a charset string. It has been replaced by an extension array, which provides the ability to automatically fetch additional features.

## [2.7.2] - 2021-09-27
### Fixed
- Fixed problem with skipping last line of the response. #166 (thanks @szymekjanaczek)

## [2.7.1] - 2021-09-08
### Added
- Added `UID` as available search criteria #161 (thanks @szymekjanaczek)

## [2.7.0] - 2021-09-04
### Fixed
- Fixes handling of long header lines which are seperated by `\r\n\t` (thanks @Oliver-Holz)
- Fixes to line parsing with multiple addresses (thanks @Oliver-Holz)

### Added
- Expose message folder path #154 (thanks @Magiczne)
- Adds mailparse_rfc822_parse_addresses integration (thanks @Oliver-Holz)
- Added moveManyMessages method (thanks @Magiczne)
- Added copyManyMessages method (thanks @Magiczne)

### Affected Classes
- [Header::class](src/Header.php)
- [Message::class](src/Message.php)

## [2.6.0] - 2021-08-20
### Fixed
- POP3 fixes #151 (thanks @Korko)

### Added
- Added imap 4 handling. #146 (thanks @szymekjanaczek)
- Added laravel's conditionable methods. #147  (thanks @szymekjanaczek)

### Affected Classes
- [Query::class](src/Query/Query.php)
- [Client::class](src/Client.php)

## [2.5.1] - 2021-06-19
### Fixed
- Fix setting default mask from config #133 (thanks @shacky)
- Chunked fetch fails in case of less available mails than page size #114
- Protocol::createStream() exception information fixed #137
- Legacy methods (headers, content, flags) fixed #125
- Legacy connection cycle fixed #124 (thanks @zssarkany)

### Added
- Disable rfc822 header parsing via config option #115

## [2.5.0] - 2021-02-01
### Fixed
- Attachment saving filename fixed
- Unnecessary parameter removed from `Client::getTimeout()`
- Missing encryption variable added - could have caused problems with unencrypted communications
- Prefer attachment filename attribute over name attribute #82
- Missing connection settings added to `Folder:idle()` auto mode #89
- Message move / copy expect a folder path #79
- `Client::getFolder()` updated to circumvent special edge cases #79
- Missing connection status checks added to various methods
- Unused default attribute `message_no` removed from `Message::class`

### Added
- Dynamic Attribute access support added (e.g `$message->from[0]`)
- Message not found exception added #93
- Chunked fetching support added `Query::chunked()`. Just in case you can't fetch all messages at once
- "Soft fail" support added
- Count method added to `Attribute:class`
- Convert an Attribute instance into a Carbon date object #95

### Affected Classes
- [Attachment::class](src/Attachment.php)
- [Attribute::class](src/Attribute.php)
- [Query::class](src/Query/Query.php)
- [Message::class](src/Message.php)
- [Client::class](src/Client.php)
- [Folder::class](src/Folder.php)

### Breaking changes
- A new exception can occur if a message can't be fetched (`\Webklex\PHPIMAP\Exceptions\MessageNotFoundException::class`)
- `Message::move()` and `Message::copy()` no longer accept folder names as folder path
- A `Message::class` instance might no longer have a `message_no` attribute

## [2.4.4] - 2021-01-22
### Fixed
- Boundary detection simplified #90
- Prevent potential body overwriting #90
- CSV files are no longer regarded as plain body
- Boundary detection overhauled to support "related" and "alternative" multipart messages #90 #91

### Affected Classes
- [Structure::class](src/Structure.php)
- [Message::class](src/Message.php)
- [Header::class](src/Header.php)
- [Part::class](src/Part.php)

## [2.4.3] - 2021-01-21
### Fixed
- Attachment detection updated #82 #90
- Timeout handling improved
- Additional utf-8 checks added to prevent decoding of unencoded values #76

### Added
- Auto reconnect option added to `Folder::idle()` #89

### Affected Classes
- [Folder::class](src/Folder.php)
- [Part::class](src/Part.php)
- [Client::class](src/Client.php)
- [Header::class](src/Header.php)

## [2.4.2] - 2021-01-09
### Fixed
- Attachment::save() return error 'A facade root has not been set' #87
- Unused dependencies removed
- Fix PHP 8 error that changes null back in to an empty string. #88 (thanks @mennovanhout)
- Fix regex to be case insensitive #88 (thanks @mennovanhout)

### Affected Classes
- [Attachment::class](src/Attachment.php)
- [Address::class](src/Address.php)
- [Attribute::class](src/Attribute.php)
- [Structure::class](src/Structure.php)

## [2.4.1] - 2021-01-06
### Fixed
- Debug line position fixed
- Handle incomplete address to string conversion #83
- Configured message key gets overwritten by the first fetched message #84

### Affected Classes
- [Address::class](src/Address.php)
- [Query::class](src/Query/Query.php)

## [2.4.0] - 2021-01-03
### Fixed
- Get partial overview when `IMAP::ST_UID` is set #74
- Unnecessary "'" removed from address names
- Folder referral typo fixed
- Legacy protocol fixed
- Treat message collection keys always as strings

### Added
- Configurable supported default flags added
- Message attribute class added to unify value handling
- Address class added and integrated
- Alias `Message::attachments()` for `Message::getAttachments()` added
- Alias `Message::addFlag()` for `Message::setFlag()` added
- Alias `Message::removeFlag()` for `Message::unsetFlag()` added
- Alias `Message::flags()` for `Message::getFlags()` added
- New Exception `MessageFlagException::class` added
- New method `Message::setSequenceId($id)` added
- Optional Header attributizion option added

### Affected Classes
- [Folder::class](src/Folder.php)
- [Header::class](src/Header.php)
- [Message::class](src/Message.php)
- [Address::class](src/Address.php)
- [Query::class](src/Query/Query.php)
- [Attribute::class](src/Attribute.php)

### Breaking changes
- Stringified message headers are now separated by ", " instead of " ".
- All message header values such as subject, message_id, from, to, etc now consists of an `Àttribute::class` instance (should behave the same way as before, but might cause some problem in certain edge cases)
- The formal address object "from", "to", etc now consists of an `Address::class` instance  (should behave the same way as before, but might cause some problem in certain edge cases)
- When fetching or manipulating message flags a `MessageFlagException::class` exception can be thrown if a runtime error occurs
- Learn more about the new `Attribute` class here: [www.php-imap.com/api/attribute](https://www.php-imap.com/api/attribute)
- Learn more about the new `Address` class here: [www.php-imap.com/api/address](https://www.php-imap.com/api/address)
- Folder attribute "referal" is now called "referral"

## [2.3.1] - 2020-12-30
### Fixed
- Missing RFC attributes added
- Set the message sequence when idling
- Missing UID commands added #64

### Added
- Get a message by its message number
- Get a message by its uid #72 #66 #63

### Affected Classes
- [Message::class](src/Message.php)
- [Folder::class](src/Folder.php)
- [Query::class](src/Query/Query.php)

## [2.3.0] - 2020-12-21
### Fixed
- Cert validation issue fixed
- Allow boundaries ending with a space or semicolon (thanks [@smartilabs](https://github.com/smartilabs))
- Ignore IMAP DONE command response #57
- Default `options.fetch` set to `IMAP::FT_PEEK`
- Address parsing fixed #60
- Alternative rfc822 header parsing fixed #60
- Parse more than one Received: header #61
- Fetch folder overview fixed
- `Message::getTextBody()` fallback value fixed

### Added
- Proxy support added
- Flexible disposition support added #58
- New `options.message_key` option `uid` added
- Protocol UID support added
- Flexible sequence type support added

### Affected Classes
- [Structure::class](src/Structure.php)
- [Query::class](src/Query/Query.php)
- [Client::class](src/Client.php)
- [Header::class](src/Header.php)
- [Folder::class](src/Folder.php)
- [Part::class](src/Part.php)

### Breaking changes
- Depending on your configuration, your certificates actually get checked. Which can cause an aborted connection if the certificate can not be validated.
- Messages don't get flagged as read unless you are using your own custom config.
- All `Header::class` attribute keys are now in a snake_format and no longer minus-separated.
- `Message::getTextBody()` no longer returns false if no text body is present. `null` is returned instead.

## [2.2.5] - 2020-12-11
### Fixed
- Missing array decoder method added #51 (thanks [@lutchin](https://github.com/lutchin))
- Additional checks added to prevent message from getting marked as seen #33
- Boundary parsing improved #39 #36 (thanks [@AntonioDiPassio-AppSys](https://github.com/AntonioDiPassio-AppSys))
- Idle operation updated #44

### Added
- Force a folder to be opened

### Affected Classes
- [Header::class](src/Header.php)
- [Folder::class](src/Folder.php)
- [Query::class](src/Query/Query.php)
- [Message::class](src/Message.php)
- [Structure::class](src/Structure.php)

## [2.2.4] - 2020-12-08
### Fixed
- Search performance increased by fetching all headers, bodies and flags at once #42
- Legacy protocol support updated
- Fix Query pagination. (#52 [@mikemiller891](https://github.com/mikemiller891))

### Added
- Missing message setter methods added
- `Folder::overview()` method added to fetch all headers of all messages in the current folder

### Affected Classes
- [Message::class](src/Message.php)
- [Folder::class](src/Folder.php)
- [Query::class](src/Query/Query.php)
- [PaginatedCollection::class](src/Support/PaginatedCollection.php)

## [2.2.3] - 2020-11-02
### Fixed
- Text/Html body fetched as attachment if subtype is null #34
- Potential header overwriting through header extensions #35
- Prevent empty attachments #37

### Added
- Set fetch order during query #41 [@Max13](https://github.com/Max13)

### Affected Classes
- [Message::class](src/Message.php)
- [Part::class](src/Part.php)
- [Header::class](src/Header.php)
- [Query::class](src/Query/Query.php)


## [2.2.2] - 2020-10-20
### Fixed
- IMAP::FT_PEEK removing "Seen" flag issue fixed #33

### Affected Classes
- [Message::class](src/Message.php)

## [2.2.1] - 2020-10-19
### Fixed
- Header decoding problem fixed #31

### Added
- Search for messages by message-Id
- Search for messages by In-Reply-To
- Message threading added `Message::thread()`
- Default folder locations added

### Affected Classes
- [Query::class](src/Query/Query.php)
- [Message::class](src/Message.php)
- [Header::class](src/Header.php)


## [2.2.0] - 2020-10-16
### Fixed
- Prevent text bodies from being fetched as attachment #27
- Missing variable check added to prevent exception while parsing an address [webklex/laravel-imap #356](https://github.com/Webklex/laravel-imap/issues/356)
- Missing variable check added to prevent exception while parsing a part subtype #27
- Missing variable check added to prevent exception while parsing a part content-type [webklex/laravel-imap #356](https://github.com/Webklex/laravel-imap/issues/356)
- Mixed message header attribute `in_reply_to` "unified" to be always an array  #26
- Potential message moving / copying problem fixed #29
- Move messages by using `Protocol::moveMessage()` instead of `Protocol::copyMessage()` and `Message::delete()` #29

### Added
- `Protocol::moveMessage()` method added #29

### Affected Classes
- [Message::class](src/Message.php)
- [Header::class](src/Header.php)
- [Part::class](src/Part.php)

### Breaking changes
- Text bodies might no longer get fetched as attachment
- `Message::$in_reply_to` type changed from mixed to array

## [2.1.13] - 2020-10-13
### Fixed
-  Boundary detection problem fixed (#28  [@DasTobbel](https://github.com/DasTobbel))
-  Content-Type detection problem fixed (#28  [@DasTobbel](https://github.com/DasTobbel))

### Affected Classes
- [Structure::class](src/Structure.php)

## [2.1.12] - 2020-10-13
### Fixed
- If content disposition is multiline, implode the array to a simple string (#25 [@DasTobbel](https://github.com/DasTobbel))

### Affected Classes
- [Part::class](src/Part.php)

## [2.1.11] - 2020-10-13
### Fixed
- Potential problematic prefixed white-spaces removed from header attributes

### Added
- Expended `Client::getFolder($name, $deleimiter = null)` to accept either a folder name or path ([@DasTobbel](https://github.com/DasTobbel))
- Special MS-Exchange header decoding support added

### Affected Classes
- [Client::class](src/Client.php)
- [Header::class](src/Header.php)

## [2.1.10] - 2020-10-09
### Added
- `ClientManager::make()` method added to support undefined accounts

### Affected Classes
- [ClientManager::class](src/ClientManager.php)

## [2.1.9] - 2020-10-08
### Fixed
- Fix inline attachments and embedded images (#22 [@dwalczyk](https://github.com/dwalczyk))

### Added
- Alternative attachment names support added (#20 [@oneFoldSoftware](https://github.com/oneFoldSoftware))
- Fetch message content without leaving a "Seen" flag behind

### Affected Classes
- [Attachment::class](src/Attachment.php)
- [Message::class](src/Message.php)
- [Part::class](src/Part.php)
- [Query::class](src/Query/Query.php)

## [2.1.8] - 2020-10-08
### Fixed
- Possible error during address decoding fixed (#16 [@Slauta](https://github.com/Slauta))
- Flag event dispatching fixed #15

### Added
- Support multiple boundaries (#17, #19 [@dwalczyk](https://github.com/dwalczyk))

### Affected Classes
- [Structure::class](src/Structure.php)

## [2.1.7] - 2020-10-03
### Fixed
- Fixed `Query::paginate()` (#13 #14 by [@Max13](https://github.com/Max13))

### Affected Classes
- [Query::class](src/Query/Query.php)

## [2.1.6] - 2020-10-02
### Fixed
- `Message::getAttributes()` hasn't returned all parameters

### Affected Classes
- [Message::class](src/Message.php)

### Added
- Part number added to attachment
- `Client::getFolderByPath()` added (#12 by [@Max13](https://github.com/Max13))
- `Client::getFolderByName()` added (#12 by [@Max13](https://github.com/Max13))
- Throws exceptions if the authentication fails  (#11 by [@Max13](https://github.com/Max13))

### Affected Classes
- [Client::class](src/Client.php)

## [2.1.5] - 2020-09-30
### Fixed
- Wrong message content property reference fixed (#10)

## [2.1.4] - 2020-09-30
### Fixed
- Fix header extension values
- Part header detection method changed (#10)

### Affected Classes
- [Header::class](src/Header.php)
- [Part::class](src/Part.php)

## [2.1.3] - 2020-09-29
### Fixed
- Possible decoding problem fixed
- `Str::class` dependency removed from `Header::class`

### Affected Classes
- [Header::class](src/Header.php)

## [2.1.2] - 2020-09-28
### Fixed
- Dependency problem in `Attachement::getExtension()` fixed (#9)

### Affected Classes
- [Attachment::class](src/Attachment.php)

## [2.1.1] - 2020-09-23
### Fixed
- Missing default config parameter added

### Added
- Default account config fallback added

### Affected Classes
- [Client::class](src/Client.php)

## [2.1.0] - 2020-09-22
### Fixed
- Quota handling fixed

### Added
- Event system and callbacks added

### Affected Classes
- [Client::class](src/Client.php)
- [Folder::class](src/Folder.php)
- [Message::class](src/Message.php)

## [2.0.1] - 2020-09-20
### Fixed
- Carbon dependency fixed

## [2.0.0] - 2020-09-20
### Fixed
- Missing pagination item records fixed

### Added
- php-imap module replaced by direct socket communication
- Legacy support added
- IDLE support added
- oAuth support added
- Charset detection method updated
- Decoding fallback charsets added

### Affected Classes
- All

## [1.4.5] - 2019-01-23
### Fixed
- .csv attachement is not processed
- mail part structure property comparison changed to lowercase
- Replace helper functions for Laravel 6.0 #4 (@koenhoeijmakers)
- Date handling in Folder::appendMessage() fixed
- Carbon Exception Parse Data
- Convert sender name from non-utf8 to uf8 (@hwilok)
- Convert encoding of personal data struct

### Added
- Path prefix option added to Client::getFolder() method
- Attachment size handling added
- Find messages by custom search criteria

### Affected Classes
- [Query::class](src/Query/WhereQuery.php)
- [Mask::class](src/Support/Masks/Mask.php)
- [Attachment::class](src/Attachment.php)
- [Client::class](src/Client.php)
- [Folder::class](src/Folder.php)
- [Message::class](src/Message.php)

## [1.4.2.1] - 2019-07-03
### Fixed
- Error in Attachment::__construct #3
- Examples added

## [1.4.2] - 2019-07-02
### Fixed
- Pagination count total bug #213
- Changed internal message move and copy methods #210
- Query::since() query returning empty response #215
- Carbon Exception Parse Data #45
- Reading a blank body (text / html) but only from this sender #203
- Problem with Message::moveToFolder() and multiple moves #31
- Problem with encoding conversion #203
- Message null value attribute problem fixed
- Client connection path handling changed to be handled inside the calling method #31
- iconv(): error suppressor for //IGNORE added #184
- Typo Folder attribute fullName changed to full_name
- Query scope error fixed #153
- Replace embedded image with URL #151
- Fix sender name in non-latin emails sent from Gmail (#155)
- Fix broken non-latin characters in body in ASCII (us-ascii) charset #156
- Message::getMessageId() returns wrong value #197
- Message date validation extended #45 #192
- Removed "-i" from "iso-8859-8-i" in Message::parseBody #146

### Added
- Message::getFolder() method
- Create a fast count method for queries #216
- STARTTLS encryption alias added
- Mailbox fetching exception added #201
- Message::moveToFolder() fetches new Message::class afterwards #31
- Message structure accessor added #182
- Shadow Imap const class added #188
- Connectable "NOT" queries added
- Additional where methods added
- Message attribute handling changed
- Attachment attribute handling changed
- Message flag handling updated
- Message::getHTMLBody($callback) extended
- Masks added (take look at the examples for more information on masks)
- More examples added
- Query::paginate() method added
- Imap client timeout can be modified and read #186
- Decoder config options added #175
- Message search criteria "NOT" added #181
- Invalid message date exception added
- Blade examples

### Breaking changes
- Message::moveToFolder() returns either a Message::class instance or null and not a boolean
- Folder::fullName is now Folder::full_name
- Attachment::image_src might no longer work as expected - use Attachment::getImageSrc() instead

### Affected Classes
- [Folder::class](src/Folder.php)
- [Client::class](src/Client.php)
- [Message::class](src/Message.php)
- [Attachment::class](src/Attachment.php)
- [Query::class](src/Query/Query.php)
- [WhereQuery::class](src/Query/WhereQuery.php)

## 0.0.3 - 2018-12-02
### Fixed
- Folder delimiter check added #137
- Config setting not getting loaded
- Date parsing updated

### Affected Classes
- [Folder::class](src/IMAP/Client.php)
- [Folder::class](src/IMAP/Message.php)

## 0.0.1 - 2018-08-13
### Added
- new php-imap package (fork from [webklex/laravel-imap](https://github.com/Webklex/laravel-imap))
