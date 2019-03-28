<?php
/*
 * This file is part of the DebugBar package.
 *
 * (c) 2013 Maxime Bouroumeau-Fuseau
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DebugBar\DataCollector;

/**
 * Collects info about memory usage
 */
class MemoryCollector extends DataCollector implements Renderable
{
    protected $peakUsage = 0;

    /**
     * Returns the peak memory usage
     *
     * @return integer
     */
    public function getPeakUsage()
    {
        return $this->peakUsage;
    }

    /**
     * Updates the peak memory usage value
     */
    public function updatePeakUsage()
    {
        $this->peakUsage = memory_get_peak_usage(true);
    }

    public function collect()
    {
        $this->updatePeakUsage();
        return array(
            'peak_usage' => $this->peakUsage,
            'peak_usage_str' => $this->getDataFormatter()->formatBytes($this->peakUsage)
        );
    }

    public function getName()
    {
        return 'memory';
    }

    public function getWidgets()
    {
        return array(
            "memory" => array(
                "icon" => "cogs",
                "tooltip" => "Memory Usage",
                "map" => "memory.peak_usage_str",
                "default" => "'0B'"
            )
        );
    }
}
