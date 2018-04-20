<?php

namespace Sabre\Event;

/**
 * EventEmitter object.
 *
 * Instantiate this class, or subclass it for easily creating event emitters.
 *
 * @copyright Copyright (C) 2013-2015 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class EventEmitter implements EventEmitterInterface {

    use EventEmitterTrait;

}
