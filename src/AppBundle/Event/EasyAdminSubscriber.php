<?php

namespace AppBundle\Event;

use EasyCorp\Bundle\EasyAdminBundle\Event\EasyAdminEvents;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use AppBundle\Entity\Record;

use DAPBundle\Services\ElasticIndexService;
//use DAPBundle\Services\FilterRecordsService;
use DAPBundle\Resolver\RecordResolver;

class EasyAdminSubscriber implements EventSubscriberInterface
{
	private $elasticService;

	private $recordService;

	public function __construct(ElasticIndexService $es, \DAPBundle\Resolver\RecordResolver $rs) {
		$this->elasticService = $es;
		$this->recordService = $rs;
	}

	public static function getSubscribedEvents()
	{
		//this tells Symfony what events we're listening for, and what functions we run when the events happen
		return [
			EasyAdminEvents::PRE_DELETE => 'onPreDelete',
		];
	}
	public static function getSubscribedServices()
	{
		//this tells Symfony what services we'll need, and ensures they're loaded
		return array_merge(parent::getSubscribedServices(), [
			'em' => Doctrine\ORM\EntityManager::class,
		]);
	}

	public function onPreDelete(GenericEvent $event)
	{
		//figure out what we're working on
		$entity = $event->getSubject();
		switch ($entity['class']) {
			case 'AppBundle\Entity\Record':
				//figure out which record we've just deleted
				$req = $event->getArguments()['request'];
				$recordid = $req->query->get('id');
				//get the dapid of that record from its internal doctrine/PSQL id (an incremented integer)
				$therecord = $this->recordService->findByNativeQuery(['id' => $recordid]);
				$dapid = $therecord[0]->getDapID();
				//remove it from the search index		
				$this->elasticService->deIndexbyDapID($dapid);
				return;
				break;
			
			default: //if it wasn't a delete we care about here, just move along 
				return;
				break;
		}
	}
}