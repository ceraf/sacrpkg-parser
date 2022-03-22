<?php

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
use Symfony\Component\HttpKernel\KernelInterface;

class BackupCommand extends Command
{

    protected static $defaultName = 'app:backup:create';

    private $connection;
    private $kernel;

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
        
        parent::__construct();
    }

    public static function getCommanName(): string
    {
        return self::$defaultName;
    }

    protected function configure()
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Creates backup database.')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('This command create database backup...')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln([
            'Database backup',
            '===============',
            '',
        ]);

		if (!file_exists($this->kernel->getProjectdir().'/var/backup'))
			mkdir($this->kernel->getProjectdir().'/var/backup');
		
		chdir($this->kernel->getProjectdir().'/var/backup');
		preg_match ( '~^postgresql://(.+)@(.+):(.+)/(.+)$~', $_ENV['DATABASE_URL'], $res);
        if (strpos($res[4], '?') !== false) {
            $res[4] = substr($res[4], 0, strpos($res[4], '?'));
        
        }

		list($user, $pass) = explode(':', $res[1]);

		$file = 'backup_hour_'.date('Y_m_d_h_i').'.gz';
		exec('export PGPASSWORD=\''.$pass.'\';pg_dump -U '.$user.' -h '.$res[2].' -p '.$res[3].' '.$res[4].' | gzip > '.$file, $output, $return_var);
		
		if ($return_var) {
			unlink ($file);
			throw new \Exception('Не удалось создать backup');
		}
        
        $this->copyBackupToFtp($file);

		if (date('H') < 6) {
			echo "Run day backup\n";
            $name = 'backup_day_'.date('Y_m_d_h_i').'.gz';
			copy($file, $name);
            $this->copyBackupToFtp($name);
			$this->clearOldDayBackups();
			$this->clearOldDayFtpBackups();
		}	

		if (date('N') == 1) {
			echo "Run week backup\n";
            $name = 'backup_week_'.date('Y_m_d_h_i').'.gz';
			copy($file, $name);
            $this->copyBackupToFtp($name);
			$this->clearOldWeekBackups();
            $this->clearOldWeekFtpBackups();
		}		
		
		if (date('d') == 1) {
			echo "Run month backup\n";
			$name = 'backup_month_'. (new \DateTime('-1 month'))->format('F').'.gz';
            copy($file, $name);
            $this->copyBackupToFtp($name);
            $this->clearOldMonthFtpBackups();
			$this->clearOldMonthBackups();
		}	

		$this->clearOldHourBackups();
        $this->clearOldHourFtpBackups();
//$this->clearOldMonthFtpBackups();
        if ($_ENV['FTP_ADDRESS'] ?? null)
            ftp_close($this->connection);
        
        return 0;
    }
/*
    protected function clearOldMonthFtpBackups()
    {
        if (!($_ENV['FTP_ADDRESS'] ?? null))
            return;
        $connection = $this->getFtpConnection();
        $baseFolder = $_ENV['FTP_ADDRESS'];
        $res = ftp_nlist($connection, $baseFolder);
        if (!empty($res)) {
            foreach ($res as $item) {
                if (strpos($item, 'backup_month_') !== false) {
                    $str = substr($item, strpos($item, 'backup_month_') + strlen('backup_month_'));
                    $str = substr($str, 0, strlen($str) - 3);
                    $timest = \DateTime::createFromFormat('F', $str)->getTimestamp();
                    var_dump($timest);
                    if ($timest < strtotime('-8 month'))
                        ;//ftp_delete ($connection, $item);
                }
            }
        }
    }
*/
    protected function clearOldMonthFtpBackups()
    {
        if (!($_ENV['FTP_ADDRESS'] ?? null))
            return;
        $connection = $this->getFtpConnection();
        $baseFolder = $_ENV['FTP_ADDRESS'];
        $res = ftp_nlist($connection, $baseFolder);
	$num_month = 6;
	$month_names = [];
	for ($i = 1; $i <= $num_month; $i++ ) {
	    $month_names[] = (new \DateTime('-'.$i.' month'))->format('F');
	}
        if (!empty($res)) {
            foreach ($res as $item) {
                if (strpos($item, 'backup_month_') !== false) {
                    $str = substr($item, strpos($item, 'backup_month_') + strlen('backup_month_'));
                    $str = substr($str, 0, strlen($str) - 3);
                   // $timest = \DateTime::createFromFormat('Y_m_d_H_i', $str)->getTimestamp();
                   // if ($timest < strtotime('-8 month'))
		    if (!in_array($str, $month_names))
			//var_dump('Delete '.$item);
                        ftp_delete ($connection, $item);
                }
            }
        }
    }

    protected function clearOldWeekFtpBackups()
    {
        if (!($_ENV['FTP_ADDRESS'] ?? null))
            return;
        $connection = $this->getFtpConnection();
        $baseFolder = $_ENV['FTP_ADDRESS'];
        $res = ftp_nlist($connection, $baseFolder);
        if (!empty($res)) {
            foreach ($res as $item) {
                if (strpos($item, 'backup_week_') !== false) {
                    $str = substr($item, strpos($item, 'backup_week_') + strlen('backup_week_'));
                    $str = substr($str, 0, strlen($str) - 3);
                    $timest = \DateTime::createFromFormat('Y_m_d_H_i', $str)->getTimestamp();
                    if ($timest < strtotime('-30 day'))
                        ftp_delete ($connection, $item);
                }
            }
        }
    }

    protected function clearOldHourFtpBackups()
    {
        if (!($_ENV['FTP_ADDRESS'] ?? null))
            return;
        $connection = $this->getFtpConnection();
        $baseFolder = $_ENV['FTP_ADDRESS'];
        $res = ftp_nlist($connection, $baseFolder);
        if (!empty($res)) {
            foreach ($res as $item) {
                if (strpos($item, 'backup_hour_') !== false) {
                    $str = substr($item, strpos($item, 'backup_hour_') + strlen('backup_hour_'));
                    $str = substr($str, 0, strlen($str) - 3);
                    $timest = \DateTime::createFromFormat('Y_m_d_H_i', $str)->getTimestamp();
                    if ($timest < strtotime('-3 day'))
                        ftp_delete ($connection, $item);
                }
            }
        }
    }

    protected function clearOldDayFtpBackups()
    {
        if (!($_ENV['FTP_ADDRESS'] ?? null))
            return;
        $connection = $this->getFtpConnection();
        $baseFolder = $_ENV['FTP_ADDRESS'];
        $res = ftp_nlist($connection, $baseFolder);
        if (!empty($res)) {
            foreach ($res as $item) {
                if (strpos($item, 'backup_day_') !== false) {
                    $str = substr($item, strpos($item, 'backup_day_') + strlen('backup_day_'));
                    $str = substr($str, 0, strlen($str) - 3);
                    $timest = \DateTime::createFromFormat('Y_m_d_H_i', $str)->getTimestamp();
                    if ($timest < strtotime('-7 day'))
                        ftp_delete ($connection, $item);
                }
            }
        }
    }

	protected function clearOldHourBackups()
	{
		$res = glob('backup_hour_*');
		foreach ($res as $item) {
			if (filemtime($item) < strtotime('-2 day'))
				unlink ($item);
		}
	}

	protected function clearOldDayBackups()
	{
		$res = glob('backup_day_*');
		foreach ($res as $item) {
			if (filemtime($item) < strtotime('-4 day'))
				unlink ($item);
		}
	}
	
	protected function clearOldWeekBackups()
	{
		$res = glob('backup_week_*');
		foreach ($res as $item) {
			if (filemtime($item) < strtotime('-30 day'))
				unlink ($item);
		}
	}
	
	protected function clearOldMonthBackups()
	{
		$res = glob('backup_month_*');
		foreach ($res as $item) {
			if (filemtime($item) < strtotime('-4 month'))
				unlink ($item);
		}
	}
    
    protected function copyBackupToFtp($name)
    {
        if (!($_ENV['FTP_ADDRESS'] ?? null))
            return;
		$connection = $this->getFtpConnection();
		$baseFolder = $_ENV['FTP_ADDRESS'];
        if (!is_dir('ftp://' . $_ENV['FTP_LOGIN'] . ':' . $_ENV['FTP_PASSWORD'] . 
				'@' . $_ENV['FTP_URL'] . $baseFolder)) {
            ftp_mkdir($connection, $baseFolder);
        }	

        if ($connection && ftp_put($connection,
                $baseFolder . '/' . $name, $name,
                FTP_BINARY)) {
            //ftp_close($connection);

            echo "Upload to FTP successfully.\n";

            return $name;
        } else {
			echo 'Error upload to FTP.'."\n";
            return null; 
        }
    }
    
    private function getFtpConnection()
    {
        if (!($this->connection ?? null)) {
            $this->connection = ftp_connect($_ENV['FTP_URL']);
            $login_result = ftp_login($this->connection, $_ENV['FTP_LOGIN'],
                        $_ENV['FTP_PASSWORD']);
            ftp_pasv($this->connection, true);
        }
        
        return $this->connection;
    }
}