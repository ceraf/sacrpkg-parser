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

use Symfony\Component\BrowserKit\HttpBrowser;
use sacrpkg\ParserBundle\LoggerTrait;
use sacrpkg\ParserBundle\Logger;

/**
 * Abstarct class for serach data.
 */
abstract class SearcherAbstract implements SearcherInterface
{
    const CODE_OK = 0;
    const CODE_KILL = 10;
    
    protected $proxies;
    protected $browsers;
    
    use LoggerTrait; 
    
    public function __construct(string $proxy_list)
    {
        $this->initBrowsers($proxy_list);
		
		if (!$this->browsers) {
			$this->log(Logger::PRIORITY_ERROR, 'Not found browsers');
			exit;
		}
    }
    
    /**
     * {@inheritdoc}
     */
    abstract public function search(string $str, callable $item_process,
        callable $error_item = null, array $params = []): self;

    /**
     * {@inheritdoc}
     */
    abstract public function getTestUrl(): string;
    
    /**
     * Execute function with try catch block.
     *
     * @param callable $func Function to execute     
     * @param int $count Number of launch attempts
     * @param int $pause_every Pause between attempts
     * @param int $pause_every Pause between attempts     
     * @param callable $exep_func Function for execptio
     *
     * @return mixed result
     */
    protected function execFunctionWhithTry(callable $func, int $count = 50,
        int $pause_every = 5, int $pause = 5, callable $exep_func = null): mixed
    {
        $res = null;
        $i = 0;
        while ($i < $count) {
            try {
                $res = $func($i, $this);
                break;
            } catch (\Exception $e) {
                $this->log(Logger::PRIORITY_ERROR, 'Exec funtio with try error: '.$e->getMessage());
                $i++;
                if ($i % $pause_every) {
                    sleep($pause);
                }
                if (($i == $count) && $exep_func) {
                    $res = $exep_func($i, $this);
                }
            }
        }
        
        return $res;
    }
    
    /**
     * Init browsers.
     *
     * @param string $proxy_list List proxies     
     *
     * @return void
     */
    protected function initBrowsers($proxy_list): void
    {
        $this->proxies = array_map(function($item){
                    list($ip, $port, $login, $pass) = explode(':', trim($item));
                    return [
                        'ip' => $ip,
                        'port' => $port,
                        'login' => $login,
                        'pass' => $pass,
                    ];
                }, explode("\n", $proxy_list));

        foreach ($this->proxies as $proxy) {
            $user_agent = 'Mozilla/6.0 (Windows NT 6.3; Win64; x64; rv:93.0) Gecko/20100101 Firefox/93.0';
            $browser = new Browser($proxy, $user_agent);
			if ($this->isValidBrowser($browser)) {
				$this->browsers[] = $browser;
			}
        }
    }
    
    /**
     * Check browser.
     *
     * @param string $proxy_list List proxies     
     *
     * @return void
     */
	protected function isValidBrowser(HttpBrowser $browser): bool
	{
		try {
			$crawler = $browser->request('GET', $this->getTestUrl());
            $code = $browser->getResponse()->getStatusCode();
            
			if ($crawler->html() && ($code == 200)) {
				return true;
			} else {
                $this->log(Logger::PRIORITY_ERROR, 'Invalid browser: '.$code.': '.$crawler->html());
            }
		} catch (\Exception $e) {
            $this->log(Logger::PRIORITY_ERROR, 'Invalid browser: '.$e->getMessage());
		}
		
        return false;
	}
    
    /**
     * Get random browser from list.   
     *
     * @return HttpBrowser
     */
    protected function getRandomBrowser(): HttpBrowser
    {
        $brouser = $this->browsers[array_rand($this->browsers)];
        $brouser->restart();
        
        return $brouser;
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
    
	public function sendPostRequest($url, $data, ?array $cookies, array $proxy, string $user_agent)
	{
		$ch = curl_init();
		curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_COOKIESESSION, true);
		curl_setopt($ch,CURLOPT_POST, 1);                //0 for a get request
		curl_setopt($ch,CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch,CURLOPT_CONNECTTIMEOUT, 180);
		curl_setopt($ch,CURLOPT_TIMEOUT, 180);

$headers = <<<END
User-Agent: {USER_AGENT}
Cookie: {COOKIES}
END;
        $headers = str_replace(['{COOKIES}', '{USER_AGENT}'], [implode(';', $cookies),$user_agent], $headers);

		curl_setopt($ch, CURLOPT_PROXY, $proxy['ip'].':'.$proxy['port']);
		curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy['login'].':'.$proxy['pass']);
		curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, explode("\n", $headers));
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); 
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_NOBODY, false);

        
		$response = curl_exec($ch);
		$info = curl_getinfo($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close ($ch);
		return ['code' => $httpCode, 'response' => $response, 'info' => $info, 
		    'type' => 'POST', 'url' => $url];
	}
}
