<?php

/*
 * This file is part of the Sacrpkg ParserBundle package.
 *
 * (c) Oleg Bruyako <jonsm2184@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace sacrpkg\ParserBundle\Mapper;

use Doctrine\Persistence\ManagerRegistry;
use sacrpkg\ParserBundle\LoggerTrait;
use sacrpkg\ParserBundle\Logger;

abstract class MapperAbstract implements MapperInterface
{
    /**
     * @var ManagerRegistry
     */
    protected $doctrine;
        
    /**
     * {@inheritdoc}
     */
    abstract public function save(array $data): MapperInterface;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }
    
    /**
     * Logging event.
     *
     *
     * @param string $priority String proxies
     * @param string $mes String proxies
     * @return void
     */
	protected function log(string $priority, ?string $mes): void
	{
		$log_name = 'Parser.log';
        $this->toLog($priority, $mes, $log_name);
	}
}
