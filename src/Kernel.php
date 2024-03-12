<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    protected function configureContainer(ContainerConfigurator $container): void
    {
        // Load configuration files
        $confDir = $this->getProjectDir().'/config';

        // Here you can specify configuration files or directories
        $container->import($confDir.'/{packages}/*.yaml');
        $container->import($confDir.'/{packages}/'.$this->environment.'/*.yaml');

        if (is_file($confDir.'/services.yaml')) {
            $container->import($confDir.'/services.yaml');
            $container->import($confDir.'/{services}_'.$this->environment.'.yaml');
        } elseif (is_file($path = $confDir.'/services.php')) {
            (require $path)($container->withPath($path), $this);
        }
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $confDir = $this->getProjectDir().'/config';

        // Here you can specify routing files
        $routes->import($confDir.'/{routes}/'.$this->environment.'/*.yaml');
        $routes->import($confDir.'/{routes}/*.yaml');

        if (is_file($confDir.'/routes.yaml')) {
            $routes->import($confDir.'/routes.yaml');
        } elseif (is_file($path = $confDir.'/routes.php')) {
            (require $path)($routes->withPath($path), $this);
        }
    }
}

