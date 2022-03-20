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
 * English dictionary.
 */
class NumericDictionary extends DictionaryAbstract
{
    /**
     * {@inheritdoc}
     */
    public function getAlphabet(): array
    {
        return range(0, 9);
    }
}
