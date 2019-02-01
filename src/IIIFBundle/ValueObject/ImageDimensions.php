<?php declare(strict_types=1);

namespace IIIFBundle\ValueObject;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use GuzzleHttp\Client;
use GuzzleHttp;

class ImageDimensions
{
    /**
     * @var string
     */
    private $uri;

    /**
     * @var int
     */
    private $height;

    /**
     * @var int
     */
    private $width;


    /**
     * @var bool
     */
    private $dimensionsFetched = false;

    /**
     * @param string $uri
     */
    public function __construct(string $uri)
    {
        $this->uri = $uri;

        $this->fetchDimensions();
    }

    /**
     * @return int
     * @throws \Exception
     */
    public function getHeight() : int
    {
        $this->fetchDimensions();

        return $this->height;
    }

    /**
     * @return int
     * @throws \Exception
     */
    public function getWidth() : int
    {
        $this->fetchDimensions();

        return $this->width;
    }


    /**
     * @throws \Exception
     */
    private function fetchDimensions()
    {

            if ($this->dimensionsFetched) {
                return;
            }

            $this->dimensionsFetched = true;

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->uri);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

            if (!$result = @curl_exec($ch)) {
                throw new BadRequestHttpException(sprintf(
                    'Uri "%s" is not callable',
                    $this->uri
                ));
            }

            $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

            curl_close($ch);

            if ($statusCode >= 400) {
                throw new BadRequestHttpException(sprintf(
                    'info.json call to "%s" failed with a status code of "%s"',
                    $this->uri,
                    $statusCode
                ));
            }

            if (!$json = json_decode($result, true)) {
                throw new BadRequestHttpException(sprintf(
                    'Response from info.json as uri "%s" is not valid JSON. %s',
                    $this->uri,
                    (string) $result
                ));
            }

            if (!isset($json['width']) || !isset($json['height'])) {
                throw new BadRequestHttpException(sprintf(
                    'JSON from uri "%s" does not have both width and height properties',
                    $this->uri,
                    $result
                ));
            }

            $this->height = (int) $json['height'];
            $this->width = (int) $json['width'];

    }

    /**
     * @throws \Exception
     */
    private function fetchDimensionsGuzzle()
    {

        if ($this->dimensionsFetched) {
            return;
        }

        $this->dimensionsFetched = true;

        $response = $this->sendHttpRequest($this->uri);

        if (!$response) {
            throw new BadRequestHttpException(sprintf(
                'JSON from uri "%s" does not have both width and height properties',
                $this->uri,
                $response
            ));
        }

        $this->height = (int) $response->height;
        $this->width = (int) $response->width;

    }

    private function sendHttpRequest($url)
    {
        $client = new Client();
        $res = $client->request('GET', $url, [
            'headers' => [
                'Accept' => 'application/json',
                'Content-type' => 'application/json'
            ]]);

        $response = json_decode($res->getBody()->getContents());

        if (!$response) {
            throw new BadRequestHttpException(sprintf(
                'Uri "%s" is not callable',
                $this->uri
            ));
        }

        $statusCode = (int) $res->getStatusCode();
        if ($statusCode >= 400) {
            throw new BadRequestHttpException(sprintf(
                'info.json call to "%s" failed with a status code of "%s"',
                $this->uri,
                $statusCode
            ));
        }

        return $response;
    }

}