<?php

namespace sacrpkg\ParserBundle\DependencyInjection;

use sacrpkg\ParserBundle\Command\TestCommand;
use sacrpkg\ParserBundle\Command\BackupCommand;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\KernelInterface;

class SacrpkgParserExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');
        
        // создание определения команды
        $commandDefinition = new Definition(TestCommand::class);
        $commandDefinitionBackup = new Definition(BackupCommand::class);
        // добавление ссылок на отправителей в конструктор комманды
        
      //  foreach ($config['senders'] as $serviceId) {
      //      $commandDefinition->addArgument(new Reference($serviceId));
      //  }
        // регистрация сервиса команды как консольной команды
        $commandDefinition->addTag('console.command', ['command' => TestCommand::getCommanName()]);
        // установка определения в контейнер
        $container->setDefinition(TestCommand::class, $commandDefinition);
    
        $commandDefinitionBackup->addTag('console.command', ['command' => BackupCommand::getCommanName()])
            ->addArgument(new Reference('kernel'));
        // установка определения в контейнер
        $container->setDefinition(BackupCommand::class, $commandDefinitionBackup);
    }
}
