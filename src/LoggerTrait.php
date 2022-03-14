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

trait LoggerTrait
{
	private $loggers;
	
    public function toLog(string $priority, ?string $mes, string $name = 'logger'): self
    {
        if (!($this->loggers[$name] ?? null)) {
            $this->loggers[$name] = new Logger($name);
        }
        $this->loggers[$name]->add($priority, $mes);
        
        return $this;
    }	
}
