<?php declare(strict_types=1);

namespace IIIFBundle\Controller;

use AppBundle\Entity\Record;
use AppBundle\Repository\RecordRepository;
use DAPImportBundle\Record\AssetDetails;
use DAPImportBundle\Record\DAPAsset;
use DAPImportBundle\Services\getAssetDetailsService;
use DAPImportBundle\Services\ImportService;
use IIIF\PresentationAPI\Links\Service;
use IIIF\PresentationAPI\Parameters\DCType;
use IIIF\PresentationAPI\Properties\Logo;
use IIIF\PresentationAPI\Resources\Annotation;
use IIIF\PresentationAPI\Resources\Canvas;
use IIIF\PresentationAPI\Resources\Content;
use IIIF\PresentationAPI\Resources\Manifest;
use IIIF\PresentationAPI\Resources\Sequence;
use IIIFBundle\Uri\UriBuilder;
use IIIFBundle\ValueObject\AttributeType;
use IIIFBundle\ValueObject\DemoAsset;
use IIIFBundle\ValueObject\ImageDimensions;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use GuzzleHttp\Client;
use GuzzleHttp;

class ManifestController extends Controller
{
    /**
     * @var RecordRepository
     */
    private $repository;

    /**
     * @return Response
     * @throws \Twig\Error\Error
     */
    public function infoAction() : Response
    {
        return $this->container->get('templating')->renderResponse('IIIFBundle::info.html.twig');
    }

    /**
     * @return AssetDetails Object
     *
     */
    public function  getAssetImages($dapId)
    {
        $assetsService = $this->getAssetDetailsService();
        $resultByDapID = $assetsService->getRecordDetails($dapId);
        return $resultByDapID->details;
    }

    /**
     * @return Response
     */
    public function hardcodedAction() : Response
    {
        $manifest = new Manifest(true);

        $manifest->setID(Uuid::uuid4()->toString());
        $manifest->addLabel('Test');
        $manifest->setViewingDirection('left-to-right');

        $licenseValue =  $this->container->getParameter('iiif.manifest.licence');

        if(isset($metadata['license'])) {
            $licenseValue =  $metadata['license'];
            if (strpos($licenseValue, 'http') === false) {
                $licenseValue = preg_replace('/[-., ]/', '_', $licenseValue);
                $licenseValue = $this->container->getParameter('iiif.manifest.licence');
            }
        }

        $manifest->addLicense($licenseValue);
        $manifest->addLogo($logo = new Logo());
        $logo->setID($this->container->getParameter('iiif.manifest.logo'));
        $manifest->addAttribution($this->container->getParameter('iiif.manifest.attribution'));

        $manifest->addSequence($sequence = new Sequence());
        $sequence->setID('https://media.nga.gov/public/manifests/sequence/normal.json');
        $sequence->addLabel('Normal Order');
        $sequence->addCanvas($canvas = new Canvas());

        $canvas->setID('https://media.nga.gov/public/manifests/canvas/576.json');
        $canvas->setHeight(298);
        $canvas->setWidth(442);
        $canvas->addLabel('The Maas at Dordrecht');

        $canvas->addImage($annotation = new Annotation());
        $annotation->setOn('http://0f97d1c4-3574-4cb8-9a42-e6b22fd5e34f');

        $annotation->setContent($content = new Content());
        $content->setFormat('image/jpeg');
        $content->setType(DCType::IMAGE);
        $content->setID('https://media.nga.gov/iiif/public/objects/5/7/6/576-primary-0-nativeres.ptif/full/full/0/default.jpg');
        $content->setHeight(402);
        $content->setWidth(307);

        $content->addService($service = new Service());
        $service->setID('https://media.nga.gov/iiif/public/objects/5/7/6/576-primary-0-nativeres.ptif');
        $service->setProfile('http://iiif.io/api/image/2/level1.json');

        $response = new JsonResponse();
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->setSharedMaxAge(7200);
        $response->setMaxAge(7200);
        $response->setData($manifest->toArray());

        return $response;
    }

    /**
     * @param Request $request
     * @param string $dapId
     * @return Response
     */
    public function baseFromRecordAction(Request $request, string $dapId) : Response
    {
        if (!Uuid::isValid($dapId)) {
            throw new BadRequestHttpException(sprintf(
                'DapId "%s" is not a valid UUID',
                $dapId
            ));
        }

        /** @var Record $record */
        if (null === $record = $this->getRepository()->findOneBy(['dapID' => $dapId])) {
            throw new NotFoundHttpException(sprintf(
                'Record with dapId "%s" can not be found',
                $dapId
            ));
        }

        /** @var array $metadata */
        $metadata = $record->getMetadata();
        $manifest = new Manifest(true);
        $manifest->setID($dapId);
        $licenseValue =  $this->container->getParameter('iiif.manifest.licence');

        if(isset($metadata['license'])) {
            $licenseValue =  $metadata['license'];
            if (strpos($licenseValue, 'http') === false) {
                $licenseValue = preg_replace('/[-., ]/', '_', $licenseValue);
                $licenseValue = $this->container->getParameter('iiif.manifest.licence')[$licenseValue];
            }
        }

        $manifest->addLicense($licenseValue);
        $manifest->addLogo($logo = new Logo());
        $logo->setID($this->container->getParameter('iiif.manifest.logo'));
        $manifest->addAttribution($this->container->getParameter('iiif.manifest.attribution'));

        if (array_key_exists('description', $metadata)) {
            $manifest->addDescription($metadata['description']);
        }

        if (array_key_exists('name', $metadata)) {
            $manifest->addLabel($metadata['name']);
        }

        $manifest->addLabel($metadata['title']['displayTitle'] ?? '');
        $manifest->setViewingDirection('left-to-right');

        $manifest->addSequence($sequence = new Sequence());
        $sequence->setID('https://media.nga.gov/public/manifests/sequence/normal.json');
        $sequence->addLabel('Normal Order');
        $sequence->addCanvas($canvas = new Canvas());

        $canvas->setID('https://media.nga.gov/public/manifests/canvas/576.json');
        $canvas->setHeight(298);
        $canvas->setWidth(442);
        $canvas->addLabel('Unknown');

        $response = new JsonResponse();
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->setSharedMaxAge(2419200);
        $response->setMaxAge(2419200);
        $response->setData($manifest->toArray());

        return $response;
    }

    /**
     * @param Request $request
     * @param string $dapId
     * @return Response
     */
    public function fromDapIdAction(Request $request, string $dapId) : Response
    {
        ini_set('max_execution_time', '9000');
        $maximum = (int) $request->query->get('maximum', 5);
        $importService = $this->getImportService();

        if (!Uuid::isValid($dapId)) {
            throw new BadRequestHttpException(sprintf(
                'DapId "%s" is not a valid UUID',
                $dapId
            ));
        }

        /** @var Record $record */
        if (null === $record = $this->getRepository()->findOneBy(['dapID' => $dapId])) {
            throw new NotFoundHttpException(sprintf(
                'Record with dapId "%s" can not be found',
                $dapId
            ));
        }

        if ($request->query->get('print', false)) {
            return new JsonResponse($record->getMetadata());
        }

        /** @var array $metadata */
        $metadata = $record->getMetadata();
        $manifest = new Manifest(true);
        $manifest->setID(str_replace('http://', 'https://', $this->getRouter()->generate(
            'iiif_manifest_from_dap_id',
            ['dapId' => $dapId],
            UrlGeneratorInterface::ABSOLUTE_URL
        )));
        $licenseValue =  $this->container->getParameter('iiif.manifest.licence');

        if(isset($metadata['license'])) {
            $licenseValue =  $metadata['license'];
            if (strpos($licenseValue, 'http') === false) {
                $licenseValue = preg_replace('/[-., ]/', '_', $licenseValue);
                $licenseValue = $this->container->getParameter('iiif.manifest.licence');
            }
        }

        $manifest->addLicense($licenseValue);
        $manifest->addLogo($logo = new Logo());
        $logo->setID($this->container->getParameter('iiif.manifest.logo'));
        $manifest->addAttribution($this->container->getParameter('iiif.manifest.attribution'));

        if (array_key_exists('description', $metadata)) {
            $manifest->addDescription($metadata['description']);
        }

        if (array_key_exists('name', $metadata)) {
            $manifest->addLabel($metadata['name']);
        }

        $manifest->addLabel($metadata['title']['displayTitle'] ?? '');
        $manifest->setViewingDirection('left-to-right');

        $manifest->addSequence($sequence = new Sequence());
        $sequence->setID($this->getUriBuilder()->getSequenceId('normal'));
        $sequence->addLabel('_sequence_label_');
        $format = array_map('strtolower', $metadata['format']);

        $count = 0;
        $referenceWidth  = 1;
        $referenceHeight = 1;

        if (array_key_exists('folgerRelatedItems', $metadata)) {
            foreach ((array) $metadata['folgerRelatedItems'] as $item) {
                $count += 1;
                if($count == 40) break;
                if ($metadata <= $count) {
                    break;
                }

                if (!array_key_exists('folgerObjectType', $item) || 'image' !== $item['folgerObjectType']) {
                    continue;
                }

                if (!isset($item['remoteUniqueID']['remoteID']) or !isset($item['remoteUniqueID']['remoteSystem'])) {
                    continue;
                }


                $remoteId = $item['remoteUniqueID']['remoteID'];
                $remoteSystem = $item['remoteUniqueID']['remoteSystem'];

                /** @var $relatedItem Record */
                if (null === $relatedItem = $this->getRepository()->findOneByRemoteIdRemoteSystem((string) $remoteId, (string) $remoteSystem)) {
                    continue;
                }

                $filename = $importService->validateEncodingFormat($relatedItem->metadata['fileInfo']['encodingFormat']);

                try {

                    /**** Uncomment and replace the $asset declaration if want to validate with S3 ****
                    if (null === $asset = $this->getAsset($relatedItem, $request->query->has('use-demo'))) {
                        continue;
                    }
                    **** Uncomment and replace the $asset declaration if want to validate with S3 ****/

                    if (null === $asset = $this->getAssetFromIIIF($relatedItem->getDapID(),$filename)) {
                        continue;
                    }

                    if($asset->iiifInfo != null and $count <= 40){
                        $dimensions = new ImageDimensions($this->container->getParameter('iiif.endpoint')."/".$asset->assetFileName."/info.json");


                        if(isset($dimensions)){
                            $referenceWidth = $dimensions->getWidth();
                            $referenceHeight = $dimensions->getHeight();
                        }

                        /*** Remove Success Canvas log
                        $message = array(
                            'validation' => array(
                                'success' => true,
                                'remoteID' => $item['remoteUniqueID']['remoteID'],
                                'remoteSystem' => $item['remoteUniqueID']['remoteSystem'],
                                'message' => 'Asset call success',
                                'count' => $count,
                                'width' => $referenceWidth,
                                'height' => $referenceHeight,
                                'iiifInfo' => $asset->iiifInfo,
                            ),
                        );
                        $this->container->get('monolog.logger.dap')->notice(json_encode($message));
                         */
                    }
                } catch (BadRequestHttpException $e) {
                    $message = array(
                        'validation' => array(
                            'success' => false,
                            'remoteID' => $item['remoteUniqueID']['remoteID'],
                            'remoteSystem' => $item['remoteUniqueID']['remoteSystem'],
                            'count' => $count + 1,
                            'countRelatedItems' => count($metadata['folgerRelatedItems']),
                            'message' => 'Asset call failed',
                            'errors' => $e->getMessage(),
                        ),
                    );
                    $this->container->get('monolog.logger.dap')->error(json_encode($message));
                    continue;
                } catch (\Exception $e) {
                    $message = array(
                        'validation' => array(
                            'success' => false,
                            'remoteID' => $item['remoteUniqueID']['remoteID'],
                            'remoteSystem' => $item['remoteUniqueID']['remoteSystem'],
                            'message' => 'Asset call failed',
                            'errors' => $e->getMessage(),
                        ),
                    );
                    $this->container->get('monolog.logger.dap')->error(json_encode($message));
                    continue;
                }

                $iiifImage = preg_replace('/\/info\.json$/', '', $asset->iiifInfo);

                if ('image' !== $asset->format) {
                    continue;
                }

                try {
                    $sequence->addCanvas($canvas = new Canvas());
                    $canvas->setID($this->getUriBuilder()->getCanvasId(Uuid::uuid4()->toString()));
                    $canvas->setWidth($referenceWidth);
                    $canvas->setHeight($referenceHeight);
                    $canvas->addLabel($item['label'] ?? '');

                    $canvas->addImage($annotation = new Annotation());
                    $annotation->setOn($canvas->getID());
                    $annotation->setContent($content = new Content());
                    $content->setFormat($asset->mimeType);
                    $content->setType(AttributeType::fromString($asset->format));
                    $content->setID($asset->iiifFull);

                    $content->addService($service = new Service());
                    $service->setID($iiifImage);
                    $service->setProfile('http://iiif.io/api/image/2/level2.json');

                    /*** Remove Success Canvas log
                    if (!empty($sequence->getCanvases())) {
                        $message = array(
                            'validation' => array(
                                'success' => true,
                                'remoteID' => $item['remoteUniqueID']['remoteID'],
                                'remoteSystem' => $item['remoteUniqueID']['remoteSystem'],
                                'message' => 'Canvases success',
                                //'width' => $dimensions->getWidth(),
                                //'height' => $dimensions->getHeight(),
                                'iiifInfo' => $asset->iiifInfo,
                                'canvasID' => $canvas->getID(),
                            ),
                        );
                        $this->container->get('monolog.logger.dap')->notice(json_encode($message));
                    }
                    */

                } catch (\Exception $e) {
                        $message = array(
                            'validation' => array(
                                'success' => false,
                                'remoteID' => $item['remoteUniqueID']['remoteID'],
                                'remoteSystem' => $item['remoteUniqueID']['remoteSystem'],
                                'message' => 'Failed to generate Canvas',
                                'canvases' => $sequence->getCanvases(),
                                'errors' => $e->getMessage(),
                            ),
                        );
                        $this->container->get('monolog.logger.dap')->error(json_encode($message));
                }

            }

        }
        if (array_key_exists('fileInfo', $metadata) and empty($sequence->getCanvases())) {
            if (!empty($metadata['fileInfo']['fileURL']) and in_array('image', $format)) {
                $imageData = $this->getAssetImages($dapId);
                if(isset($imageData) and is_object($imageData)){
                    try {
                        if (null === $asset = $this->getAsset($record, $request->query->has('use-demo'))) {
                        }

                        if(@fopen($this->container->getParameter('iiif.endpoint')."/".$asset->assetFileName."/info.json","r")==true){
                            if($asset->iiifInfo != null) {
                                $dimensions = new ImageDimensions($this->container->getParameter('iiif.endpoint')."/".$asset->assetFileName."/info.json");
                                $iiifImage = preg_replace('/\/info\.json$/', '', $asset->iiifInfo);
                                $sequence->addCanvas($canvas = new Canvas());

                                $canvas->setID($this->getUriBuilder()->getCanvasId(Uuid::uuid4()->toString()));
                                $canvas->setWidth($dimensions->getWidth());
                                $canvas->setHeight($dimensions->getHeight());
                                $canvas->addLabel($metadata['description'] ?? '');

                                $canvas->addImage($annotation = new Annotation());
                                $annotation->setOn($canvas->getID());
                                $annotation->setContent($content = new Content());
                                $content->setFormat($asset->mimeType);
                                $content->setType(AttributeType::fromString($asset->format));
                                $content->setID($asset->iiifFull);

                                $content->addService($service = new Service());
                                $service->setID($iiifImage);
                                $service->setProfile('http://iiif.io/api/image/2/level2.json');
                            }
                        }
                    } catch (\Exception $e) {
                        throw new \Exception(sprintf(
                            'Image Not Found'."\n",
                            $e->getMessage()
                        ));
                    }
                }
            }
    }
    if (in_array('image', $format) and empty($sequence->getCanvases())) {
        $imageData = $this->getAssetImages($dapId);
        if(isset($imageData) and is_object($imageData)){
            try {
                if (null === $asset = $this->getAsset($record, $request->query->has('use-demo'))) {
                }
                if($asset->iiifInfo != null) {
                    $dimensions = new ImageDimensions($this->container->getParameter('iiif.endpoint')."/".$asset->assetFileName."/info.json");
                }
            } catch (\Exception $e) {
                throw new \Exception(sprintf(
                    'Image Not Found'."\n",
                    $e->getMessage()
                ));
            }

            $iiifImage = preg_replace('/\/info\.json$/', '', $asset->iiifInfo);
            $sequence->addCanvas($canvas = new Canvas());

            $canvas->setID($this->getUriBuilder()->getCanvasId(Uuid::uuid4()->toString()));
            $canvas->setWidth($dimensions->getWidth());
            $canvas->setHeight($dimensions->getHeight());
            $canvas->addLabel($metadata['description'] ?? '');

            $canvas->addImage($annotation = new Annotation());
            $annotation->setOn($canvas->getID());
            $annotation->setContent($content = new Content());
            $content->setFormat($asset->mimeType);
            $content->setType(AttributeType::fromString($asset->format));
            $content->setID($asset->iiifFull);

            $content->addService($service = new Service());
            $service->setID($iiifImage);
            $service->setProfile('http://iiif.io/api/image/2/level2.json');

        }
    }

    if (empty($sequence->getCanvases())) {
        $sequence->addCanvas($canvas = new Canvas());

        $canvas->setID($this->getUriBuilder()->getCanvasId(Uuid::uuid4()->toString()));
        $canvas->setHeight(1);
        $canvas->setWidth(1);
        $canvas->addLabel('_canvas_label_');

        $message = array(
            'validation' => array(
                'success' => false,
                'message' => 'Failed to generate Canvas',
                'canvases' => $sequence->getCanvases(),
            ),
        );
        $this->container->get('monolog.logger.dap')->error(json_encode($message));

    }

        $response = new JsonResponse();
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->setSharedMaxAge(2419200);
        $response->setMaxAge(2419200);
        $response->setData($manifest->toArray());

        return $response;
    }

    /**
     * @return RecordRepository
     */
    private function getRepository() : RecordRepository
    {
         if (null === $this->repository) {
             $this->repository = $this->container
                 ->get('doctrine')
                 ->getManagerForClass(Record::class)
                 ->getRepository(Record::class);
         }

         return $this->repository;
    }

    /**
     * @return getAssetDetailsService|object
     */
    private function getAssetDetailsService() : getAssetDetailsService
    {
        return $this->container->get('dap_import.service.asset_details');
    }

    /**
     * @return ImportService|object
     */
    private function getImportService() : ImportService
    {
        return $this->container->get('dap_import.service.import');
    }

    /**
     * @return UriBuilder|object
     */
    private function getUriBuilder() : UriBuilder
    {
        return $this->container->get('iiif.builder.uri');
    }

    /**
     * @return UrlGeneratorInterface|object
     */
    private function getRouter() : UrlGeneratorInterface
    {
        return $this->container->get('router');
    }

    /**
     * @param Record $relatedItem
     * @param bool $useDemo
     * @return DAPAsset
     * @throws \Doctrine\ORM\NoResultException
     */
    private function getAsset(Record $relatedItem, bool $useDemo = false) : ?DAPAsset
    {
        if (!$useDemo) {
            return $this->getAssetDetailsService()->getRecordDetails($relatedItem->getDapID());
        }

        return DemoAsset::getDAPAsset();
    }


    /**
     * @return DAPAsset
     * @throws \Doctrine\ORM\NoResultException
     */
    private function getAssetFromIIIF($dapId, $assetFormat) : ?DAPAsset
    {
        return $this->getAssetDetailsService()->getRecordDetailsFromIIIF($dapId, $assetFormat);
    }

}
