<?php
/**
 * File containing the RecordResolver class.
 *
 * (c) http://parsonstko.com/
 * (c) Developer jdiaz
 */

namespace DAPBundle\Resolver;

use AppBundle\Entity\Record;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\Mapping\Builder;
use Doctrine\ORM\QueryBuilder;

class RecordResolver extends AbstractResolver
{
    /**
     * @var UUIDv4Pattern
     */
    public $UUIDv4Pattern = '/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i';

    public function findByNativeQuery($args)
    {
        $selectSQL = 'SELECT id, dapid, record_type, metadata::jsonb AS metadata';
        
        $fromSQL = 'FROM record';
        $whereSQL = $this->buildWhere($args);
        $sql = "$selectSQL $fromSQL $whereSQL;";
        $rsm = new ResultSetMapping();
        $rsm->addEntityResult('AppBundle:Record', 'record');
        $rsm->addFieldResult('record', 'id', 'id');
        $rsm->addFieldResult('record', 'dapid', 'dapID');
        $rsm->addFieldResult('record', 'record_type', 'recordType');
        $rsm->addFieldResult('record', 'metadata', 'metadata');
        $query = $this->em->createNativeQuery($sql, $rsm);
        $result = $query->getResult();

        return $result;
    }

    public function findByNativeQueryFirstRecords($args)
    {
        $selectSQL = 'SELECT id, dapid, record_type, metadata::jsonb AS metadata';

        $fromSQL = 'FROM record';
        $whereSQL = $this->buildWhere($args);
        $limitSQL = "LIMIT 10";
        $sql = "$selectSQL $fromSQL $whereSQL $limitSQL;";
        $rsm = new ResultSetMapping();
        $rsm->addEntityResult('AppBundle:Record', 'record');
        $rsm->addFieldResult('record', 'id', 'id');
        $rsm->addFieldResult('record', 'dapid', 'dapID');
        $rsm->addFieldResult('record', 'record_type', 'recordType');
        $rsm->addFieldResult('record', 'metadata', 'metadata');
        $query = $this->em->createNativeQuery($sql, $rsm);
        $result = $query->getResult();

        return $result;
    }

    public function findAll()
    {
        return $this->em->getRepository('AppBundle:Record')->findAll();
    }

    public function findBy($args)
    {
        return $this->em->getRepository('AppBundle:Record')->findBy($args);
    }

    public function create($title)
    {
        $record = Record::createFromTitle($title);
        $this->em->persist($record);
        $this->em->flush();

        return $this->findAll();
    }

    public function toggleAll($checked)
    {
        $records = $this->em->getRepository('AppBundle:Record')->findAllOrdered();

        foreach ($records as $record) {
            $record->setCompleted($checked);
        }

        $this->em->flush();

        return $records;
    }

    public function toggle($id)
    {
        $record = $this->em->getRepository('AppBundle:Record')->find($id);

        if (!$record) {
            throw $this->createNotFoundException();
        }

        $record->setCompleted(!$record->getCompleted());
        $this->em->flush();

        return $this->findAll();
    }

    public function destroy($id)
    {
        $record = $this->em->getRepository('AppBundle:Record')->find($id);

        if (!$record) {
            throw $this->createNotFoundException();
        }

        $this->em->remove($record);
        $this->em->flush();

        return $this->findAll();
    }

    public function save($id, $title)
    {
        $record = $this->em->getRepository('AppBundle:Record')->find($id);

        if (!$record) {
            throw $this->createNotFoundException();
        }

        $record->setTitle($title);
        $this->em->flush();

        return $this->findAll();
    }

    public function clearCompleted()
    {
        $this->em->getRepository('AppBundle:Record')->removeCompleted();

        return $this->findAll();
    }

    public function buildWhere($args)
    {
        $whereSQL = '';

        if (!empty($args)) {
            $buildedWhereSQL = array();

            foreach ($args as $identifier => $value) {
                if ($identifier == 'dapID') {
                    if (preg_match($this->UUIDv4Pattern, $value)) {
                        $buildedWhereSQL[] = 'dapid = '."'".$value."'";
                    } else {
                        throw $this->createNotFoundException();
                    }
                } elseif ($identifier == 'rootfile') {
                    $buildedWhereSQL[] = "metadata->>'rootfile' = '".$value."'";
                } elseif ($identifier == 'searchText') {
                    $buildedWhereSQL[] = "metadata->'title'->>'displayTitle' LIKE '%".$value."%'";
                } elseif ($identifier == 'foreign-collection') {
                    if(is_array($value)) {
                        $value = "'".join("', '", value)."'";
                    }
                    $buildedWhereSQL[] = "metadata->>'RemoteUniqueID' in ( ".$value." );";
                } else {
                    $buildedWhereSQL[] = $identifier.' = '.$value;
                }
            }

            if (!empty($buildedWhereSQL)) {
                $whereSQL = 'WHERE '.implode(', ', $buildedWhereSQL);
            }
        }

        return $whereSQL;
    }

    public function getRelatedImages($relatedItems)
    {
        $dapIds = array();
        foreach($relatedItems as $index => $item){
            $dapIds[$index] = $item;
        }
    }

    /**
     * Exists record With New Schema RemoteID.
     *
     * @param
     *
     * Validate if exists record
     */
    public function getRelatedItemsDapID($remoteId)
    {
        try {
            $field = 'remoteUniqueID';
            $record = array();
            $selectSQL = 'SELECT id, dapid';
            $fromSQL = 'FROM record';
            $whereSQL = "WHERE metadata->'".$field."'->>'remoteID' = '".$remoteId."'";
            $limitSQL = "LIMIT 1";
            $sql = "$selectSQL $fromSQL $whereSQL $limitSQL;";
            $rsm = new ResultSetMapping();
            $rsm->addEntityResult('AppBundle:Record', 'record');
            $rsm->addFieldResult('record', 'id', 'id');
            $rsm->addFieldResult('record', 'dapid', 'dapID');
            $query = $this->em->createNativeQuery($sql, $rsm);
            $result = $query->getResult();

            if ($result) {
                foreach (reset($result) as $item => $value) {
                    if ($item == "dapID") {
                        $record['dapID'] = $value;
                    }
                }
            }

            return $record;
        } catch (\Exception $e) {
            if (isset($this->dapImportLogger)) {
                $this->dapImportLogger->error($e->getMessage());
            }

            throw new \Exception('Error: '.$e->getMessage());
        }
    }

    /**
     * Exists record With New Schema RemoteID and RemoteSystem (remoteUniqueID).
     *
     * @param
     *
     * Validate if exists record
     */
    public function getRelatedItemsRemoteUniqueID($remoteId,$remoteSystem)
    {
        try {
            $field = 'remoteUniqueID';
            $record = array();
            $selectSQL = 'SELECT id, dapid';
            $fromSQL = 'FROM record';
            $whereSQL = "WHERE metadata->'".$field."'->>'remoteID' = '".$remoteId."' AND metadata->'".$field."'->>'remoteSystem' = '".$remoteSystem."'";
            $limitSQL = "LIMIT 1";
            $sql = "$selectSQL $fromSQL $whereSQL $limitSQL;";
            $rsm = new ResultSetMapping();
            $rsm->addEntityResult('AppBundle:Record', 'record');
            $rsm->addFieldResult('record', 'id', 'id');
            $rsm->addFieldResult('record', 'dapid', 'dapID');
            $query = $this->em->createNativeQuery($sql, $rsm);
            $result = $query->getResult();

            if ($result) {
                foreach (reset($result) as $item => $value) {
                    if ($item == "dapID") {
                        $record['dapID'] = $value;
                    }
                }
            }

            return $record;
        } catch (\Exception $e) {
            if (isset($this->dapImportLogger)) {
                $this->dapImportLogger->error($e->getMessage());
            }

            throw new \Exception('Error: '.$e->getMessage());
        }
    }

}
