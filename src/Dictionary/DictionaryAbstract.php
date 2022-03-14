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
 * Abstract for the dictionary for selected language.
 */
abstract class DictionaryAbstract implements DictionaryInterface
{
    /**
     * {@inheritdoc}
     */
    abstract public function getAlphabet(): array;
    
    /**
     * {@inheritdoc}
     */
    public function getStringOptions(string $str): array
    {
        $options = [];
        
        foreach ($this->getAlphabet() as $letter) {
            $options[] = $str.$letter;
        }
        
        return $options;
    }
}
