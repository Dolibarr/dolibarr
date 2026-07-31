<?php
/* Copyright (C) 2026 MDW <mdeweerd@users.noreply.github.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

require_once DOL_DOCUMENT_ROOT.'/core/lib/handlers/shipping_handler.php';

/**
 * Trait for tracking functionality
 * Provides methods for setting and getting tracking information (AWB, carrier, URL)
 * Uses extrafields for storage
 *
 * @mixin CommonObject
 */
trait TrackingTrait
{
	/**
	 * Set tracking information for this object
	 *
	 * @param string $awb           Air Waybill / Tracking number
	 * @param string $carrier_code  ShippingMethod code (e.g., 'FX' for FedEx)
	 * @param string $tracking_link Optional: Direct tracking URL (if not provided, will be generated from carrier)
	 * @return bool True if successful, false otherwise
	 */
	public function setTrackingInfo($awb, $carrier_code, $tracking_link = '')
	{
		// Ensure extrafields exist
		ensure_tracking_extrafields($this->table_element);

		// Set the tracking data in array_options (extrafields)
		$this->array_options['options_tracking_awb'] = $awb;
		$this->array_options['options_carrier_code'] = $carrier_code;

		// Generate tracking link if not provided
		if (empty($tracking_link)) {
			$this->array_options['options_tracking_link'] = generate_tracking_link($awb, $carrier_code);
		} else {
			$this->array_options['options_tracking_link'] = $tracking_link;
		}

		return true;
	}

	/**
	 * Get tracking URL for this object
	 *
	 * @return string Tracking URL if available, empty string otherwise
	 */
	public function getTrackingUrl()
	{
		return !empty($this->array_options['options_tracking_link']) ? $this->array_options['options_tracking_link'] : '';
	}

	/**
	 * Get tracking AWB for this object
	 *
	 * @return string Tracking AWB if available, empty string otherwise
	 */
	public function getTrackingAWB()
	{
		return !empty($this->array_options['options_tracking_awb']) ? $this->array_options['options_tracking_awb'] : '';
	}

	/**
	 * Get carrier code for this object
	 *
	 * @return string ShippingMethod code if available, empty string otherwise
	 */
	public function getShippingMethodCode()
	{
		return !empty($this->array_options['options_carrier_code']) ? $this->array_options['options_carrier_code'] : '';
	}

	/**
	 * Get all tracking information for this object
	 *
	 * @return array{awb:string,carrier_code:string,tracking_link:string} Array with keys: 'awb', 'carrier_code', 'tracking_link'
	 */
	public function getTrackingInfo()
	{
		return array(
			'awb' => $this->getTrackingAWB(),
			'carrier_code' => $this->getShippingMethodCode(),
			'tracking_link' => $this->getTrackingUrl()
		);
	}
}
