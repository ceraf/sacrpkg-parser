<?php

/*
 * This file is part of the Sacrpkg ParserBundle package.
 *
 * (c) Oleg Bruyako <jonsm2184@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace sacrpkg\ParserBundle\Dictionary;

/**
 * Interface for the dictionary for selected language.
 */
interface DictionaryInterface
{
    /**
     * Get a list of characters.
     *
     * @return array
     */
    public function getAlphabet(): array;
    
    /**
     * Get substring options.
     *
     * @param string $str String for get options
     *
     * @return array
     */
    public function getStringOptions(string $str): array;
}
