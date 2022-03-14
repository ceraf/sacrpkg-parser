<?php

/*
 * This file is part of the Sacrpkg ParserBundle package.
 *
 * (c) Oleg Bruyako <jonsm2184@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace sacrpkg\ParserBundle\Command;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\HttpClient\CurlHttpClient;
use Symfony\Component\DomCrawler\Crawler;
use App\Parser\MaltaParser;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\HttpClient\HttpClient;
use Doctrine\Persistence\ManagerRegistry;

abstract class ParserProcess extends Command
{
    protected $parser;
    protected $doctrine;

    abstract protected function getProcessClassName(): string;
    
    protected function configure()
    {
        $this
            ->addArgument('str', InputArgument::REQUIRED, 'Search str')
            ->addArgument('pos_num', InputArgument::REQUIRED, 'Number symbol')
			->addArgument('task_id', InputArgument::REQUIRED, 'Task ID')
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $proc_id = $this->doctrine->getRepository('App:'.$this->getProcessClassName())
            ->createProcess(
                $input->getArgument('task_id'),
                $input->getArgument('str'),
                $input->getArgument('pos_num')
            );
        $this->doctrine->getManager()->getUnitOfWork()->clear();
        
        if ($proc_id) {
            try {
                $this->parser->execute($input->getArgument('str'), ['num_pos' => $input->getArgument('pos_num'),
							'task_id' => $input->getArgument('task_id')]);
                $this->doctrine->getRepository('App:'.$this->getProcessClassName())
                    ->endProcess($proc_id);
            } catch (\Exception $e) {
                $this->doctrine->getRepository('App:'.$this->getProcessClassName())
                    ->errorProcess($proc_id, $e->getMessage());
            }
        }
        
        return 0;
    }
}
