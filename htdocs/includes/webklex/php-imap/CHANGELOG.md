# Changelog

All notable changes to `webklex/php-imap` will be documented in this file.

Updates should follow the [Keep a CHANGELOG](http://keepachangelog.com/) principles.

## [UNRELEASED]
### Fixed
- NaN

### Added
- NaN

### Affected Classes
- NaN

### Breaking changes
- NaN

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
