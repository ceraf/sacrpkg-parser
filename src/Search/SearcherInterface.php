<?php

/*
 * This file is part of the Sacrpkg ParserBundle package.
 *
 * (c) Oleg Bruyako <jonsm2184@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace sacrpkg\ParserBundle\Search;

/**
 * Interface for serach data.
 */
interface SearcherInterface
{
    /**
     * Finding data by string.
     *
     * @param string $str String for search
     * @param callable $item_process Function to process any finding item
     * @param callable $item_error Function to error during process
     * @param array $params Parameters 
     *
     * @return $this
     */
    public function search(string $str, callable $item_process,
        callable $item_error = null, array $params = []): self;
        
    /**
     * Get url for test browser.
     *
     * @return string
     */        
    public function getTestUrl(): string;
}
