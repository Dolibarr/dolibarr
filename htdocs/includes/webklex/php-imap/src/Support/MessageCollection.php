<?php
/*
* File:     MessageCollection.php
* Category: Collection
* Author:   M. Goldenbaum
* Created:  16.03.18 03:13
* Updated:  -
*
* Description:
*  -
*/

namespace Webklex\PHPIMAP\Support;

use Illuminate\Support\Collection;
use Webklex\PHPIMAP\Message;

/**
 * Class MessageCollection
 *
 * @package Webklex\PHPIMAP\Support
 * @implements Collection<int, Message>
 */
class MessageCollection extends PaginatedCollection {

}
