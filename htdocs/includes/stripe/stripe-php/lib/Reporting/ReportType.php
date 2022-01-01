<?php

namespace Stripe\Reporting;

/**
 * Class ReportType
 *
 * @property string $id
 * @property string $object
 * @property int $data_available_end
 * @property int $data_available_start
 * @property string $name
 * @property int $updated
 * @property string $version
 *
 * @package Stripe\Reporting
 */
class ReportType extends \Stripe\ApiResource
{
    const OBJECT_NAME = "reporting.report_type";

    use \Stripe\ApiOperations\All;
    use \Stripe\ApiOperations\Retrieve;
}
