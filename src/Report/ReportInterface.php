<?php

/*
 * This file is part of the Sacrpkg ParserBundle package.
 *
 * (c) Oleg Bruyako <jonsm2184@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace sacrpkg\ParserBundle\Report;

/**
 * Interface for parser report.
 */
interface ReportInterface
{
    /**
     * Add line to report.
     *
     * @param string $str Report string     
     *
     * @return self
     */
    public function addLine(string $str): self;
    
    /**
     * Set report id.
     *
     * @param int $report_id Report Id     
     *
     * @return self
     */
    public function setReportId(int $report_id): self;
    
    /**
     * Get path report file. 
     *
     * @return string
     */
    public function getPath(): string;
}
