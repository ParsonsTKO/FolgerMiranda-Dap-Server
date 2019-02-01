<?php declare(strict_types=1);

namespace IIIFBundle\Uri;

class UriBuilder
{
    /**
     * @var string
     */
    private $uri;

    /**
     * @param string $uri
     */
    public function __construct(
        string $uri
    ) {
        $this->uri = $uri;
    }

    /**
     * @param string $name
     * @return string
     */
    public function getSequenceId(string $name) : string
    {
        return $this->getIdFromTypeAndName('sequence', $name);
    }

    /**
     * @param string $name
     * @return string
     */
    public function getCanvasId(string $name) : string
    {
        return $this->getIdFromTypeAndName('canvas', $name);
    }

    /**
     * @param string $name
     * @return string
     */
    public function getAnnotationId(string $name) : string
    {
        return $this->getIdFromTypeAndName('annotation', $name);
    }

    /**
     * @param string $type
     * @param string $name
     * @return string
     */
    private function getIdFromTypeAndName(string $type, string $name) : string
    {
        return sprintf(
            '%s/%s/%s',
            $this->uri,
            $type,
            $name
        );
    }
}