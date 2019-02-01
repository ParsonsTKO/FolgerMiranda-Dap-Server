<?php declare(strict_types=1);

namespace IIIFBundle\ValueObject;

use DAPImportBundle\Record\DAPAsset;

class DemoAsset
{
    /**
     * @var array|string[]
     */
    private static $assets = [
        'https://media.nga.gov/iiif/public/objects/1/0/6/3/8/2/106382-primary-0-nativeres.ptif',
        'https://media.nga.gov/iiif/public/objects/6/1/3/7/1/61371-primary-0-nativeres.ptif',
        'https://media.nga.gov/iiif/public/objects/5/2/1/7/8/52178-primary-0-nativeres.ptif',
        'https://media.nga.gov/iiif/public/objects/4/6/6/2/6/46626-primary-0-nativeres.ptif',
        'https://media.nga.gov/iiif/public/objects/1/1/3/8/1138-primary-0-nativeres.ptif',
        'https://media.nga.gov/iiif/public/objects/4/6/1/5/9/46159-primary-0-nativeres.ptif',
        'https://media.nga.gov/iiif/public/objects/3/3/2/5/3/33253-primary-0-nativeres.ptif',
        'https://media.nga.gov/iiif/public/objects/1/2/3/1/1231-primary-0-nativeres.ptif',
        'https://media.nga.gov/iiif/public/objects/3/7/0/0/3/37003-primary-0-nativeres.ptif',
        'https://media.nga.gov/iiif/public/objects/5/7/6/576-primary-0-nativeres.ptif',
        'https://media.nga.gov/iiif/public/objects/7/9/79-primary-0-nativeres.ptif',
        'https://media.nga.gov/iiif/public/objects/1/2/3/6/1236-primary-0-nativeres.ptif',
        'https://media.nga.gov/iiif/public/objects/4/6/3/0/3/46303-primary-0-nativeres.ptif',
        'https://media.nga.gov/iiif/public/objects/4/6/4/7/1/46471-primary-0-nativeres.ptif',
        'https://media.nga.gov/iiif/public/objects/4/6/1/1/4/46114-primary-0-nativeres.ptif',
        'https://media.nga.gov/iiif/public/objects/1/1/4/7/1147-primary-0-nativeres.ptif',
        'https://media.nga.gov/iiif/public/objects/1/2/2/5/1225-primary-0-nativeres.ptif',
        'https://media.nga.gov/iiif/public/objects/5/2/4/5/0/52450-primary-0-nativeres.ptif',
        'https://media.nga.gov/iiif/public/objects/1/2/1/9/8/12198-primary-0-nativeres.ptif',
        'https://media.nga.gov/iiif/public/objects/4/3/6/2/4/43624-primary-0-nativeres.ptif',
        'https://media.nga.gov/iiif/public/objects/7/4/7/9/6/74796-primary-0-nativeres.ptif',
        'https://media.nga.gov/iiif/public/objects/4/6/6/2/7/46627-primary-0-nativeres.ptif',
        'https://media.nga.gov/iiif/public/objects/1/3/4/4/8/5/134485-primary-0-nativeres.ptif',
        'https://media.nga.gov/iiif/public/objects/6/6/4/0/9/66409-primary-0-nativeres.ptif',
        'https://media.nga.gov/iiif/public/objects/3/9/8/398-primary-0-nativeres.ptif',
        'https://media.nga.gov/iiif/public/objects/1/6/4/9/2/3/164923-primary-0-nativeres.ptif',
    ];

    /**
     * @return string|null
     */
    public static function getAsset() : ?string
    {
        if (empty(self::$assets)) {
            return null;
        }

        $key = rand(0, count(self::$assets) - 1);
        $asset = self::$assets[$key];
        unset(self::$assets[$key]);

        self::$assets = array_values(self::$assets);

        return $asset;
    }

    /**
     * @return DAPAsset|null
     */
    public static function getDAPAsset() : ?DAPAsset
    {
        if (null === $asset = self::getAsset()) {
            return null;
        }

        $object = new DAPAsset();

        $object->iiifInfo = sprintf('%s/info.json', $asset);
        $object->format = 'image';
        $object->mimeType = 'image/jpg';
        $object->iiifFull = sprintf('%s/full/full/0/default.jpg', $asset);
        
        return $object;
    }
}