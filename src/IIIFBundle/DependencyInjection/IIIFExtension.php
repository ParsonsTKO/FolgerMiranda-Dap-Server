<?php declare(strict_types=1);

namespace IIIFBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class IIIFExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $config = $this->processConfiguration(new Configuration(), $configs);

        foreach ([
            'manifest'  => [
                'attribution',
                'licence',
                'logo',
                'uri',
            ]
        ] as $heading => $properties) {
            foreach ($properties as $property) {
                $container->setParameter(
                    sprintf('iiif.%s.%s', $heading, $property),
                    $config[$heading][$property]
                );
            }
        }

        $container->setParameter(
            'iiif.endpoint',
            $config['endpoint']
        );

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yaml');
    }
}

