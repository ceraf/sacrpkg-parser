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

use sacrpkg\ParserBundle\Search\SearcherInterface;

/**
 * Parser interface.
 */
interface ParserInterface
{
    /**
     * Execute parser with search string.
     *
     * @param string $str Search string
     * @param array $params Search params
     *
     * @return int Result code
     */
    public function execute(string $str, array $params = []): int;
    
    /**
     * Execute parser with search string.
     *
     * @return ParserInterface
     */
    public function prepareFile(): self;
    
    /**
     * Get name entity task class name.
     *
     * @return string
     */
    public function getTaskClassName(): string;
    
    /**
     * Function for save parsing item.
     *
     * @param Array $item Parsing item for save
     * @param SearcherInterface $searcher Searcher object
     * @param Array $params Search params
     *
     * @return void
     */
    public function saveData(Array $item, SearcherInterface $searcher, array $params = []): void;
    
    /**
     * Function when error during save parsing item.
     *
     * @param Exception $e
     * @param SearcherInterface $searcher Searcher object
     *
     * @return void
     */
    public function onErrorData(\Exception $e, SearcherInterface $searcher): void;
}

