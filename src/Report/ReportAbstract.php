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

use App\Kernel;

/**
 * Abstract class for parser report.
 */
abstract class ReportAbstract implements ReportInterface
{
    
    protected $dst_path;
    protected $report_id = 0;
    
    public function __construct(Kernel $kernel)
    {
        $this->kernel = $kernel;
        
		if (!file_exists($this->kernel->getProjectdir().'/var/reports'))
			mkdir($this->kernel->getProjectdir().'/var/reports');
        
        $this->dst_path = $this->kernel->getProjectDir().'/var/reports/'.
            strtolower($_ENV['PARSER_NAME'] ?? 'parcer').'_report_';
    }

    /**
     * {@inheritdoc}
     */
    abstract public function addLine(string $str): ReportInterface;
    
    /**
     * {@inheritdoc}
     */
    public function setReportId(int $report_id): ReportInterface
    {
        $this->report_id = $report_id;
        
        return $this;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getPath(): string
    {
        return $this->dst_path.$this->report_id;
    }
    
    /**
     * Write line to report file.
     *
     * @param string $mes Line to report    
     *
     * @return void
     */
    protected function writeToFile($mes): void
    {
        $f = fopen($this->dst_path.$this->report_id, 'a');
        fwrite($f, $mes);
        fclose($f);
    }
}
