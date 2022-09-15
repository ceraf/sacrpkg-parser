<?php

/*
 * This file is part of the Sacrpkg ParserBundle package.
 *
 * (c) Oleg Bruyako <jonsm2184@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace sacrpkg\ParserBundle\Parser;

use sacrpkg\ParserBundle\Dictionary\DictionaryInterface;
use sacrpkg\ParserBundle\Search\SearcherInterface;
use sacrpkg\ParserBundle\Search\SearcherAbstract;
use Doctrine\Persistence\ManagerRegistry;
use sacrpkg\ParserBundle\LoggerTrait;
use sacrpkg\ParserBundle\Logger;
use sacrpkg\ParserBundle\Mapper\MapperInterface;
use sacrpkg\ParserBundle\Report\ReportInterface;
use App\Kernel;
use sacrpkg\ParserBundle\Exceptions\TaskException;

/**
 * Abstract class for parser.
 */
abstract class ParserAbstract implements ParserInterface
{
    const RESULT_CODE_OK = 0;
    const RESULT_CODE_ERROR = 1;
    const RESULT_CODE_NOT_FOUND = 2;
    
    const PAUSE_VALUE = 60;

    /**
     * @var DictionaryInterface
     */
    protected $dictionary;
    
    /**
     * @var ManagerRegistry
     */
    protected $doctrine;
    
    /**
     * @var SearcherInterface
     */    
    protected $searcher;
    
    /**
     * @var MapperInterface
     */       
    protected $mapper;
    
    /**
     * @var Kernel
     */      
    protected $kernel;
    
    /**
     * Conter items for generate json file.
     *
     * @var int
     */    
    protected $json_count = 0;
    
    /**
     * Conter items for search by string.
     *
     * @var int
     */   
    protected $count_search_items = 0;

    use LoggerTrait; 
    
    public function __construct(DictionaryInterface $dictionary,
        ManagerRegistry $doctrine, SearcherInterface $searcher,
        MapperInterface $mapper, ReportInterface $report, Kernel $kernel)
    {
        $this->dictionary = $dictionary;
        $this->doctrine = $doctrine;
        $this->searcher = $searcher;
        $this->mapper = $mapper;
        $this->report = $report;
        $this->kernel = $kernel;
    }

    /**
     * {@inheritdoc}
     */
    abstract public function getTaskClassName(): string;

    /**
     * {@inheritdoc}
     */
    public function saveData(Array $item, SearcherInterface $searcher, array $params = []): void
    {
        $this->mapper->save($item);
        $this->count_search_items++;
      
        $this->checkTaskSignal($params);
        
        $this->doctrine->getManager()->getUnitOfWork()->clear();
    }
    
    /**
     * {@inheritdoc}
     */
    public function onErrorData(\Exception $e, SearcherInterface $searcher): void
    {
        ;
    }
    
    /**
     * {@inheritdoc}
     */
    public function prepareFile(): ParserInterface
    {
		if (!file_exists($this->kernel->getProjectdir().'/public/files'))
			mkdir($this->kernel->getProjectdir().'/public/files');
        
        $dst_path = $this->kernel->getProjectDir().'/public/files/'.strtolower($_ENV['FILE_NAME'] ?? $_ENV['PARSER_NAME'] ?? 'parcer').'.json';
        $f = fopen ($dst_path, 'w');
        if ($f) {
            $count = 0;
            fwrite($f, '{"data":[');
            $this->mapper->toArray(function(array $item) use ($f){
                if ($this->json_count) {
                    fwrite($f, ',');
                }   
                fwrite($f, json_encode($item, JSON_UNESCAPED_UNICODE));
                $this->json_count++;
            });
            fwrite($f, ']}');
            fclose($f);
        }
        
        return $this;
    }
    
    /**
     * {@inheritdoc}
     */
    public function execute(string $str, array $params = []): int
    {
        $this->report->setReportId($params['task_id'] ?? 0);
        
        $num_pos = $params['num_pos'] ?? 0;
		$this->task_id = $params['task_id'] ?? 0;
		
		$this->log(Logger::PRIORITY_INFO, 'Start with string \''.$str.'\' and params '.serialize($params));

        if ($str == 'ARRAY') {
            $ranges = $this->dictionary->getAlphabet();
            $count_num = ceil(count($ranges) / $_ENV['NUM_FLOW']);

            $ranges = array_slice($ranges, $count_num*$params['proc_num'], $count_num);
            foreach ($ranges as $str_data) {
                if ($num_pos) {
                    $this->executeRecursive($str_data, $num_pos, 1, 'executeByStr', $params);
                } else {
                    $this->executeByStr($str_data, $params);
                }
            }
        } else {
            if ($str == 'NULL') {
                $str = '';
            }
                if ($num_pos) {
                    $this->executeRecursive($str, $num_pos, 1, 'executeByStr', $params);
                } else {
                    $this->executeByStr($str, $params);
                }

        }
        
		$this->log(Logger::PRIORITY_INFO, 'End');
		
        return self::RESULT_CODE_OK;
    }

    protected function executeRecursive(string $str, int $num_pos, 
            int $curr_pos, string $func, array $params = []): int
    {
        $ranges = $this->dictionary->getAlphabet();
        $num = 0;
        foreach ($ranges as $letter) {
            $new_str = $str.$letter;
            if ($num_pos > $curr_pos) {
                $add_num = $this->$func($new_str, $params);
                $num += $add_num;
                if ($add_num) {
                    $this->executeRecursive($new_str, $num_pos, $curr_pos + 1, $func, $params);
                }
            } else {
                $add_num = $this->$func($new_str, $params);
                $num += $add_num;
            }
        }
        
        return $num;
    }
    
    protected function checkTaskSignal(array $params): void
    {
        $signal = $this->doctrine->getManager()
            ->getRepository('App:'.$this->getTaskClassName())
            ->getSignalTask($params['task_id']);

        if ($signal == 'pause') {
            while($signal == 'pause') {
                sleep(self::PAUSE_VALUE);
                $signal = $this->doctrine->getManager()
                    ->getRepository('App:'.$this->getTaskClassName())
                    ->getSignalTask($params['task_id']);
            }
            
        }

        if ($signal == 'kill') {
            throw new TaskException('Kill process');
        }
    }
    
    protected function executeByStr(string $str, array $params = []): int
    {
        $this->count_search_items = 0;
        $this->searcher->search($str, [$this, 'saveData'], [$this, 'onErrorData'], $params);
        $this->report->addLine(date('Y-m-d H:i:s').':'."\t".getmypid()."\t".$str."\t".$this->count_search_items);
        
        return $this->count_search_items;
    } 
    
    /**
     * Logging event.
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
