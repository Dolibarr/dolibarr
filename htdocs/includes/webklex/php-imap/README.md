
# IMAP Library for PHP

[![Latest release on Packagist][ico-release]][link-packagist]
[![Latest prerelease on Packagist][ico-prerelease]][link-packagist]
[![Software License][ico-license]][link-license]
[![Total Downloads][ico-downloads]][link-downloads]
[![Hits][ico-hits]][link-hits]
[![Discord][ico-discord]][link-discord]
[![Snyk][ico-snyk]][link-snyk]


## Description
PHP-IMAP is a wrapper for common IMAP communication without the need to have the php-imap module installed / enabled.
The protocol is completely integrated and therefore supports IMAP IDLE operation and the "new" oAuth authentication
process as well.
You can enable the `php-imap` module in order to handle edge cases, improve message decoding quality and is required if
you want to use legacy protocols such as pop3.

Official documentation: [php-imap.com](https://www.php-imap.com/)

Laravel wrapper: [webklex/laravel-imap](https://github.com/Webklex/laravel-imap)

Discord: [discord.gg/rd4cN9h6][link-discord]

## Table of Contents
- [Documentations](#documentations)
- [Compatibility](#compatibility)
- [Basic usage example](#basic-usage-example)
- [Sponsors](#sponsors)
- [Testing](#testing)
- [Known issues](#known-issues)
- [Support](#support)
- [Features & pull requests](#features--pull-requests)
- [Alternatives & Different Flavors](#alternatives--different-flavors)
- [Security](#security)
- [Credits](#credits)
- [License](#license)


## Documentations
- Legacy (< v2.0.0): [legacy documentation](https://github.com/Webklex/php-imap/tree/1.4.5)
- Core documentation: [php-imap.com](https://www.php-imap.com/)


## Compatibility
| Version | PHP 5.6 | PHP 7 | PHP 8 |
|:--------|:-------:|:-----:|:-----:|
| v6.x    |    /    |   /   |   X   |
| v5.x    |    /    |   /   |   X   |
| v4.x    |    /    |   X   |   X   |
| v3.x    |    /    |   X   |   /   |
| v2.x    |    X    |   X   |   /   |
| v1.x    |    X    |   /   |   /   |

## Basic usage example
This is a basic example, which will echo out all Mails within all imap folders
and will move every message into INBOX.read. Please be aware that this should not be
tested in real life and is only meant to give an impression on how things work.

```php
use Webklex\PHPIMAP\ClientManager;

require_once "vendor/autoload.php";

$cm = new ClientManager('path/to/config/imap.php');

/** @var \Webklex\PHPIMAP\Client $client */
$client = $cm->account('account_identifier');

//Connect to the IMAP Server
$client->connect();

//Get all Mailboxes
/** @var \Webklex\PHPIMAP\Support\FolderCollection $folders */
$folders = $client->getFolders();

//Loop through every Mailbox
/** @var \Webklex\PHPIMAP\Folder $folder */
foreach($folders as $folder){

    //Get all Messages of the current Mailbox $folder
    /** @var \Webklex\PHPIMAP\Support\MessageCollection $messages */
    $messages = $folder->messages()->all()->get();
    
    /** @var \Webklex\PHPIMAP\Message $message */
    foreach($messages as $message){
        echo $message->getSubject().'<br />';
        echo 'Attachments: '.$message->getAttachments()->count().'<br />';
        echo $message->getHTMLBody();
        
        //Move the current Message to 'INBOX.read'
        if($message->move('INBOX.read') == true){
            echo 'Message has been moved';
        }else{
            echo 'Message could not be moved';
        }
    }
}
```

## Sponsors
[![elb-BIT][ico-sponsor-elb-bit]][link-sponsor-elb-bit]
[![Feline][ico-sponsor-feline]][link-sponsor-feline]


## Testing
To run the tests, please execute the following command:
```bash
composer test
```

### Quick-Test / Static Test
To disable all test which require a live mailbox, please copy the `phpunit.xml.dist` to `phpunit.xml` and adjust the configuration:
```xml
<php>
    <env name="LIVE_MAILBOX" value="false"/>
</php>
```

### Full-Test / Live Mailbox Test
To run all tests, you need to provide a valid imap configuration.

To provide a valid imap configuration, please copy the `phpunit.xml.dist` to `phpunit.xml` and adjust the configuration:
```xml
<php>
    <env name="LIVE_MAILBOX" value="true"/>
    <env name="LIVE_MAILBOX_DEBUG" value="true"/>
    <env name="LIVE_MAILBOX_HOST" value="mail.example.local"/>
    <env name="LIVE_MAILBOX_PORT" value="993"/>
    <env name="LIVE_MAILBOX_VALIDATE_CERT" value="false"/>
    <env name="LIVE_MAILBOX_QUOTA_SUPPORT" value="true"/>
    <env name="LIVE_MAILBOX_ENCRYPTION" value="ssl"/>
    <env name="LIVE_MAILBOX_USERNAME" value="root@example.local"/>
    <env name="LIVE_MAILBOX_PASSWORD" value="foobar"/>
</php>
```

The test account should **not** contain any important data, as it will be deleted during the test.
Furthermore, the test account should be able to create new folders, move messages and should **not** be used by any other
application during the test.

It's recommended to use a dedicated test account for this purpose. You can use the provided `Dockerfile` to create an imap server used for testing purposes.

Build the docker image:
```bash
cd .github/docker

docker build -t php-imap-server .
```
Run the docker image:
```bash
docker run --name imap-server -p 993:993 --rm -d php-imap-server
```
Stop the docker image:
```bash
docker stop imap-server
```


### Known issues
| Error                                                                      | Solution                                                                                |
|:---------------------------------------------------------------------------|:----------------------------------------------------------------------------------------|
| Kerberos error: No credentials cache file found (try running kinit) (...)  | Uncomment "DISABLE_AUTHENTICATOR" inside your config and use the `legacy-imap` protocol |


## Support
If you encounter any problems or if you find a bug, please don't hesitate to create a new [issue](https://github.com/Webklex/php-imap/issues).
However, please be aware that it might take some time to get an answer.
Off-topic, rude or abusive issues will be deleted without any notice.

If you need **commercial** support, feel free to send me a mail at github@webklex.com.


##### A little notice
If you write source code in your issue, please consider to format it correctly. This makes it so much nicer to read  
and people are more likely to comment and help :)

&#96;&#96;&#96;php

echo 'your php code...';

&#96;&#96;&#96;

will turn into:
```php 
echo 'your php code...'; 
``` 


## Features & pull requests
Everyone can contribute to this project. Every pull request will be considered, but it can also happen to be declined.  
To prevent unnecessary work, please consider to create a [feature issue](https://github.com/Webklex/php-imap/issues/new?template=feature_request.md)  
first, if you're planning to do bigger changes. Of course, you can also create a new [feature issue](https://github.com/Webklex/php-imap/issues/new?template=feature_request.md)
if you're just wishing a feature ;)


## Alternatives & Different Flavors
This library and especially the code flavor It's written in, is certainly not for everyone. If you are looking for a 
different approach, you might want to check out the following libraries:
- [ddeboer/imap](https://github.com/ddeboer/imap)
- [barbushin/php-imap](https://github.com/barbushin/php-imap)
- [stevebauman/php-imap](https://github.com/stevebauman/php-imap)


## Change log
Please see [CHANGELOG][link-changelog] for more information what has changed recently.


## Security
If you discover any security related issues, please email github@webklex.com instead of using the issue tracker.


## Credits
- [Webklex][link-author]
- [All Contributors][link-contributors]


## License
The MIT License (MIT). Please see [License File][link-license] for more information.


[ico-release]: https://img.shields.io/packagist/v/Webklex/php-imap.svg?style=flat-square&label=version
[ico-prerelease]: https://img.shields.io/github/v/release/webklex/php-imap?include_prereleases&style=flat-square&label=pre-release
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/Webklex/php-imap.svg?style=flat-square
[ico-hits]: https://hits.webklex.com/svg/webklex/php-imap
[ico-snyk]: https://snyk-widget.herokuapp.com/badge/composer/webklex/php-imap/badge.svg
[ico-discord]: https://img.shields.io/static/v1?label=discord&message=open&color=5865f2&style=flat-square

[link-packagist]: https://packagist.org/packages/Webklex/php-imap
[link-downloads]: https://packagist.org/packages/Webklex/php-imap
[link-author]: https://github.com/webklex
[link-contributors]: https://github.com/Webklex/php-imap/graphs/contributors
[link-license]: https://github.com/Webklex/php-imap/blob/master/LICENSE
[link-changelog]: https://github.com/Webklex/php-imap/blob/master/CHANGELOG.md
[link-hits]: https://hits.webklex.com
[link-snyk]: https://snyk.io/vuln/composer:webklex%2Fphp-imap
[link-discord]: https://discord.gg/vUHrbfbDr9


[ico-sponsor-feline]: https://cdn.feline.dk/public/feline.png
[link-sponsor-feline]: https://www.feline.dk
[ico-sponsor-elb-bit]: https://www.elb-bit.de/user/themes/deliver/images/logo_small.png
[link-sponsor-elb-bit]: https://www.elb-bit.de?ref=webklex/php-imap