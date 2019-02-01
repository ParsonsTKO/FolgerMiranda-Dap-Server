<?php declare(strict_types=1);

namespace IIIFBundle\ValueObject;

class AttributeType
{
    const IMAGE = 'dctypes:Image';
    const TEXT  = 'dctypes:Text';
    const SOUND = 'dctypes:Sound';

    private function __construct()
    {
        // Do not instantiate
    }

    /**
     * @param string $type
     * @return string
     * @throws \Exception
     */
    public static function fromString(string $type) : string
    {
        $type = mb_strtolower($type);

        switch ($type) {
            case 'image':
                return self::IMAGE;
            case 'text':
                return self::TEXT;
            case 'sound':
                return self::SOUND;
        }

        throw new \Exception(sprintf(
            'Type "%s" is not valid. Valid types are: "image", "text" and "sound"',
            $type
        ));
    }
}