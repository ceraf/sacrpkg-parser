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

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Mime\Part\AbstractPart;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;
use Symfony\Component\Mime\Part\TextPart;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\BrowserKit\HttpBrowser;

/**
 * Browser class for parsing.
 */
class Browser extends HttpBrowser
{
    /**
     * Proxy data.
     *
     * @var array
     */  
    private $proxy;
    
    /**
     * User agent string.
     *
     * @var string
     */      
    private $user_agent;

    public function __construct(array $proxy, string $user_agent)
    {
        $this->proxy = $proxy;
        $this->user_agent = $user_agent;

        parent::__construct(HttpClient::create([
                'proxy' => $proxy['login'].':'.$proxy['pass'].'@'.$proxy['ip'].':'.$proxy['port'],
                'headers' => ['User-Agent' => $user_agent],
                'timeout' => 180
            ]));
    }
    
    /**
     * {@inheritdoc}
     */
    public function setServerParameters(array $server)
    {
        $this->server = array_merge([
            'HTTP_USER_AGENT' => $this->user_agent,
        ], $server);
    }
	
    /**
     * Get proxy data.
     *
     * @return Array
     */
    public function getProxy(): ?array
    {
        return $this->proxy;
    }
    
    /**
     * Get user agent data.
     *
     * @return String
     */
    public function getUserAgent(): string
    {
        return $this->user_agent;
    }    
}