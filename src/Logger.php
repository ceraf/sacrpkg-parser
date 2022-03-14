<?php

/*
 * This file is part of the Sacrpkg ParserBundle package.
 *
 * (c) Oleg Bruyako <jonsm2184@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace sacrpkg\ParserBundle;

use App\Kernel;

class Logger
{
    const PRIORITY_FATAL = 'fatal';
    const PRIORITY_ERROR = 'error';
    const PRIORITY_WARN = 'warn';
    const PRIORITY_INFO = 'info';
    const PRIORITY_DEBUG = 'debug';

    private $proc;
	private $logname;

    public function __construct ($logname)
    {
		$this->logname = $logname;
        return $this;
    }
    
    public function add($priority, $mes): self
    {
        $str = date('Y-m-d H:i:s').':'."\t".getmypid()."\t".$priority.' - '.$mes."\n";
        $this->writeToFile($str);
        return $this;
    }
    
    public function getContent(): ?string
    {
        $res = '';
        if (file_exists($this->getFilename()))
            $res = file_get_contents($this->getFilename());
        else 
            $res = "Файл не найден.";
        return $res;
    }
    
    private function writeToFile($mes)
    {
        $f = fopen($this->getFilename(), 'a');
        fwrite($f, $mes);
        fclose($f);
    }
    
    private function getFilename(): string
    {
        return $this->getPath().$this->logname;
    }
    
    private function getPath(): string
    {
		$kernel = new Kernel ('dev', false);
        return $kernel->getProjectDir ().'/var/log/';
    }
}
