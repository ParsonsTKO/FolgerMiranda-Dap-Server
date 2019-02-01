<?php

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Dunglas\DoctrineJsonOdm\Bundle\DunglasDoctrineJsonOdmBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;

class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = [
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
            new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),
            new DunglasDoctrineJsonOdmBundle(),
            new ONGR\ElasticsearchBundle\ONGRElasticsearchBundle(),
            new IIIFBundle\IIIFBundle(),
            new Overblog\GraphQLBundle\OverblogGraphQLBundle(),
            new FOS\UserBundle\FOSUserBundle(),
            new Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle(),
            new EasyCorp\Bundle\EasyAdminBundle\EasyAdminBundle(),
            new AppBundle\AppBundle(),
            new DAPBundle\DAPBundle(),
            new DAPImportBundle\DAPImportBundle(),
            new AdminBundle\AdminBundle(),
        ];

        if (in_array($this->getEnvironment(), ['dev', 'test'], true)) {
            $bundles[] = new Symfony\Bundle\DebugBundle\DebugBundle();
            $bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
            $bundles[] = new Sensio\Bundle\DistributionBundle\SensioDistributionBundle();
        }

        return $bundles;
    }

    public function getRootDir()
    {
        return __DIR__;
    }

    public function getCacheDir()
    {
        return dirname(__DIR__).'/var/cache/'.$this->getEnvironment();
    }

    public function getLogDir()
    {
        return dirname(__DIR__).'/var/logs';
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load($this->getRootDir().'/config/'.$this->getEnvironment().'/config.yml');
        $customConfigFile = $this->getRootDir().'/config/'.$this->getEnvironment().'/custom.yml';
        if (is_readable($customConfigFile)) {
            $loader->load($customConfigFile);
        }
    }
}
