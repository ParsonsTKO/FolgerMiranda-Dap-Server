<?php
/**
 * Created by PhpStorm.
 * User: johnc
 * Date: 5/11/17
 * Time: 1:50 PM
 */

namespace DAPBundle\ElasticDocs;


use Doctrine\Common\Collections\ArrayCollection;
use ONGR\ElasticsearchBundle\Collection\Collection;
use ONGR\ElasticsearchBundle\Annotation as ES;
/**
 * @ES\Document(type="daprecord")
 */
class DAPRecord
{

    /**
     * @var string
     *
     * @ES\Id()
     */
    public $dapid;

    /**
     * @var string
     *
     * @ES\Property(type="keyword")
     */
    public $dapidagain;

    /**
     * @var string
     *
     * @ES\Property(type="keyword")
     */
    public $isBasedOn;


    /**
     * @var string
     *
     * @ES\Property(type="text")
     */
    public $creator;

    /**
     * @var string
     *
     * @ES\Property(type="text")
     */
    public $abstract;

    /**
     * @var string
     *
     * @ES\Property(type="text")
     */
    public $sortOrder;

    /**
     * @var DAPTitle
     *
     * @ES\Embedded(class="DAPBundle:DAPTitle")
     */
    public $title;

    /**
     * @var DAPIdentifiers
     *
     * @ES\Embedded(class="DAPBundle:DAPIdentifiers")
     */
    public $identifiers;

    /**
     * @var DAPSubjects
     *
     * @ES\Embedded(class="DAPBundle:DAPSubjects")
     */
    public $subjects;

    /**
     * @var DAPDateCreated
     *
     * @ES\Embedded(class="DAPBundle:DAPDateCreated")
     *
     */

    public $dateCreated;

    /**
     * @var string
     *
     * @ES\Property(type="text")
     */
    public $publisher;

    /**
     * @var DAPLocation
     *
     * @ES\Embedded(class="DAPBundle:DAPLocation")
     *
     */
    public $locationCreated;

    /**
     * @var string
     *
     * @ES\Property(type="text")
     */
    public $extent;

    /**
     * @var string
     *
     * @ES\Property(type="text")
     */
    public $size;

    /**
     * @var DAPGenre
     *
     * @ES\Embedded(class="DAPBundle:DAPGenre", multiple=true)
     *
     */
    public $genre;

    /**
     * @var string
     *
     * @ES\Property(type="keyword")
     */
    public $mirandaGenre;

    /**
     * @var string
     *
     * @ES\Property(type="keyword")
     */
    public $folgerDisplayIdentifier;


    /**
     * @var string
     *
     * @ES\Property(type="keyword")
     */
    public $format;

    /**
     * @var DAPAgent
     *
     * @ES\Embedded(class="DAPBundle:DAPAgent", multiple=true)
     */
    public $agent;

    /**
     * @var string
     *
     * @ES\Property(type="keyword")
     */
    public $language;

    /**
     * @var string
     *
     * @ES\Property(type="text")
     */
    public $folgerProvenance;

    /**
     * @var DAPRelatedItems
     *
     * @ES\Embedded(class="DAPBundle:DAPRelatedItems", multiple=true)
     */
    public $folgerRelatedItems;

    /**
     * @var DAPFileInfo
     *
     * @ES\Embedded(class="DAPBundle:DAPFileInfo")
     *
     */
    public $fileInfo;

    /**
     * @var DAPRelationships
     *
     * @ES\Embedded(class="DAPBundle:DAPRelationships", multiple=false)
     *
     */
    public $relationships;

    /**
     * @var DAPRelationshipsAgents
     *
     * @ES\Embedded(class="DAPBundle:DAPRelationshipsAgents", multiple=true)
     *
     */
    public $relationshipsAgents;

    /**
     * @var DAPRelationshipsWorks
     *
     * @ES\Embedded(class="DAPBundle:DAPRelationshipsWorks", multiple=true)
     *
     */
    public $relationshipsWorks;


    /**
     * @var string
     *
     * @ES\Property(type="text")
     */
    public $searchText;

    /**
     * @var string
     *
     * @ES\Property(type="text")
     */
    public $license;

    /**
     * @var string
     *
     * @ES\Property(type="text")
     */
    public $lastUpdate;

    /**
     * @var DAPHoldingInstitution
     *
     * @ES\Embedded(class="DAPBundle:DAPHoldingInstitution")
     */
    public $holdingInstitution;

    /**
     * @var boolean
     *
     * @ES\Property(type="boolean")
     */
    public $isUpdate;

    /**
     * @var DAPPermissions
     *
     * @ES\Embedded(class="DAPBundle:DAPPermissions")
     */
    public $permissions;

    /**
     * @var string
     *
     * @ES\Property(type="text")
     */
    public $caption;

    /**
     * @var DAPNotes
     *
     * @ES\Embedded(class="DAPBundle:DAPNotes", multiple=true)
     */
    public $notes;

    /**
     * @var string
     *
     * @ES\Property(type="keyword")
     */
    public $groupings;

    /**
     * @var string
     *
     * @ES\Property(type="keyword")
     */
    public $locus;

    /**
     * @var string
     *
     * @ES\Property(type="keyword")
     */
    public $preferredCitation;

    /**
     * @var string
     *
     * @ES\Property(type="keyword")
     */
    public $simplifiedTranscription;


    public function __construct() {
        $this->genre = new Collection();
        $this->agent = new Collection();
        $this->notes = new Collection();
        $this->folgerRelatedItems = new Collection();
        $this->relationshipsAgents = new Collection();
        $this->relationshipsWorks = new Collection();
    }

    public function setMy($setMe, $withVal)
    {
        if(!isset($withVal) || is_null($withVal)) { return false; }

        $this->$setMe = $withVal;

        if($setMe == 'dapid') {
            $this->dapidagain = $withVal;
        }

        return true;
    }

    public function fill($invar) {

        if(!isset($invar) || is_null($invar)) { return false; }
        try {
            //don't put LUNA records into the search
            if($invar->recordType != 1) return -2;

            //postgres info
            $this->setMy('dapid', $invar->dapID);

            //reset to deal with just metadata
            $invar = (object)$invar->metadata;

            if (isset($invar->abstract)) {
                $this->setMy('abstract', $invar->abstract);
            }

            if (isset($invar->sortOrder)) {
                $this->setMy('sortOrder', intval($invar->sortOrder));
            }

            if (isset($invar->title)) {
                $invar->title = (object)$invar->title;
                $tdisplayTitleTitle = isset($invar->title->displayTitle) ? $invar->title->displayTitle : null;
                $textendedTitle = isset($invar->title->extendedTitle) ? $invar->title->extendedTitle : null;
                $talternateTitles = isset($invar->title->alternateTitles) ? $invar->title->alternateTitles : null;
                $tuniformTitle = isset($invar->title->uniformTitle) ? $invar->title->uniformTitle : null;
                $this->setMy('title', new DAPTitle($tdisplayTitleTitle, $textendedTitle, $talternateTitles, $tuniformTitle));
            }

            //Identifiers
            if (isset($invar->identifiers)) {
                $tIdentifiers = $invar->identifiers;
                $myIdentifiers = array();
                if (gettype($tIdentifiers) == 'array') {
                    for ($i = 0; $i < count($tIdentifiers); $i++) {
                        $tkey = isset($tIdentifiers[$i]['key']) ? $tIdentifiers[$i]['key'] : null;
                        $tvalue = isset($tIdentifiers[$i]['value']) ? $tIdentifiers[$i]['value'] : null;
                        array_push($myIdentifiers, new DAPIdentifiers($tkey, $tvalue));
                    }
                }
            }
            //end Identifiers
            //Subjects
            if (isset($invar->subjects)) {
                $tSubjects = $invar->subjects;
                $mySubjects = array();
                if (gettype($tSubjects) == 'array') {
                    for ($i = 0; $i < count($tSubjects); $i++) {
                        $turi = isset($tSubjects[$i]['uri']) ? $tSubjects[$i]['uri'] : null;
                        $tdescription = isset($tSubjects[$i]['description']) ? $tSubjects[$i]['description'] : null;
                        array_push($mySubjects, new DAPSubjects($turi, $tdescription));
                    }
                }
            }
            //end Subjects

            if (isset($invar->format)) {
                $this->setMy('format', $invar->format);
            }

            if (isset($invar->dateCreated)) {
                $invar->dateCreated = (object)$invar->dateCreated;
                $tdisplayDate = isset($invar->dateCreated->displayDate) ? $invar->dateCreated->displayDate : null;
                $tisoDate = isset($invar->dateCreated->isoDate) ? $invar->dateCreated->isoDate : null;

                $tdc = new DAPDateCreated($tdisplayDate, $tisoDate);
                $this->setMy('dateCreated', $tdc);
            }

            if (isset($invar->publisher)) {
                $this->setMy('publisher', $invar->publisher);
            }

            if (isset($invar->locationCreated)) {
                $invar->locationCreated = (object)$invar->locationCreated;
                $taddressLocality = isset($invar->locationCreated->addressLocality) ? $invar->locationCreated->addressLocality : null;
                $taddressCountry = isset($invar->locationCreated->addressCountry) ? $invar->locationCreated->addressCountry : null;
                $taddressRegion = isset($invar->locationCreated->addressRegion) ? $invar->locationCreated->addressRegion : null;
                $tlocationDescriptor = isset($invar->locationCreated->locationDescriptor) ? $invar->locationCreated->locationDescriptor : null;
                $this->setMy('locationCreated', new DAPLocation($taddressLocality, $taddressCountry, $taddressRegion, $tlocationDescriptor));
            }

            if (isset($invar->license)) {
                $this->setMy('license', $invar->license);
            }

            if (isset($invar->extent)) {
                $this->setMy('extent', $invar->extent);
            }

            if (isset($invar->size)) {
                $this->setMy('size', $invar->size);
            }

            if (isset($invar->mirandaGenre)) {
                $this->setMy('mirandaGenre', $invar->mirandaGenre);
            }

            //genre
            if (isset($invar->genre)) {
                $tgenre = $invar->genre;
                if (isset($tgenre)) {
                    $myGenre = array();
                    if (gettype($tgenre) == 'array') {
                        //build array
                        for ($i = 0; $i < count($tgenre); $i++) {
                            if(isset($tgenre[$i]['name'])) {
                                $tname = $tgenre[$i]['name'];
                            } else {
                                $tname = "";
                            }
                            if(isset($tgenre[$i]['uri'])) {
                                $turi = $tgenre[$i]['uri'];
                            } else {
                                $turi = '';
                            }
                            array_push($myGenre, new DAPGenre($tname, $turi));
                        }
                    }
                    if (count($myGenre) > 0) { //if we've added some description(s)
                        $this->setMy('genre', new Collection($myGenre));
                    }
                }
            }
            //end folger genre

            //agent
            if (isset($invar->Agent)) {
                $tAgent = $invar->Agent;
                if (isset($tAgent)) {
                    $myAgent = array();
                    if (gettype($tAgent) == 'array') {
                        //build array
                        for ($i = 0; $i < count($tAgent); $i++) {
                            $tname = isset($tAgent[$i]['name']) ? $tAgent[$i]['name'] : null;
                            $tdescription = isset($tAgent[$i]['description']) ? $tAgent[$i]['description'] : null;
                            $turi = isset($tAgent[$i]['uri']) ? $tAgent[$i]['uri'] : null;
                            $tDAPAgent = new DAPAgent($tname, $tdescription, $turi);
                            array_push($myAgent, $tDAPAgent);
                        }

                    } else {
                        // not array, not workable
                    }
                    if (count($myAgent) > 0) { //if we've added some description(s)
                        $this->setMy('agent', new Collection($myAgent));
                    }
                }
            }
            //end agent

            if (isset($invar->language)) {
                $this->setMy('language', $invar->language);
            }

            if (isset($invar->folgerProvenance)) {
                $this->setMy('folgerProvenance', $invar->folgerProvenance);
            }

            //related items
            if (isset($invar->folgerRelatedItems)) {
                $myRelated = array();
                    for ($i = 0; $i < count($invar->folgerRelatedItems); $i++) {
                        $tRelated = (object)$invar->folgerRelatedItems[$i];
                        $tdapID = 'dapID';
                        $tid = isset($tRelated->$tdapID) ? $tRelated->$tdapID : null;
                        $tfolgerRelationshipType = isset($tRelated->folgerRelationshipType) ? $tRelated->folgerRelationshipType : null;
                        $tfolgerObjectType = isset($tRelated->folgerObjectType) ? $tRelated->folgerObjectType : null;
                        $tlabel = isset($tRelated->label) ? $tRelated->label : null;
                        $tmpso = isset($tRelated->mpso) ? $tRelated->mpso : null;
                        $tremoteUniqueID = isset($tRelated->remoteUniqueID) ? $tRelated->remoteUniqueID : null;
                        $tremoteSystem = $tremoteUniqueID['remoteSystem'];
                        $tremoteID = $tremoteUniqueID['remoteID'];
                        $remoteUniqueID['remoteSystem'] = $tremoteSystem;
                        $remoteUniqueID['remoteID'] = $tremoteID;
                        array_push($myRelated, new DAPRelatedItems($tid, $tfolgerRelationshipType, $tlabel, $tfolgerObjectType, $tmpso, $remoteUniqueID));
                    }
                if (count($myRelated) > 0) { //if we've added some description(s)
                    $this->setMy('folgerRelatedItems', new Collection($myRelated));
                }
            }
            //end related

            if (isset($invar->fileInfo)) {
                    $tFileInfo = (object)$invar->fileInfo;
                    $fileURL = isset($tFileInfo->fileURL) ? $tFileInfo->fileURL : null;
                    $contentSize = isset($tFileInfo->contentSize) ? $tFileInfo->contentSize : null;
                    $fieldListItems = isset($tFileInfo->fieldList) ? $tFileInfo->fieldList : null;
                    $numberOfRows = isset($tFileInfo->numberOfRows) ? $tFileInfo->numberOfRows : null;
                    $duration = isset($tFileInfo->duration) ? $tFileInfo->duration : null;
                    $height = isset($tFileInfo->height) ? $tFileInfo->height : null;
                    $width = isset($tFileInfo->width) ? $tFileInfo->width : null;
                    $encodingFormat = isset($tFileInfo->encodingFormat) ? $tFileInfo->encodingFormat : null;
                    $myFileInfo = new DAPFileInfo($fileURL, $contentSize, $fieldListItems, $numberOfRows, $duration, $height,$width,$encodingFormat);
                    $this->setMy('fileInfo', $myFileInfo);
            }

            if (isset($invar->searchText)) {
                $this->setMy('searchtext', $invar->searchText);
            }

            /** New Schema Fields **/
            if (isset($invar->lastUpdate)) {
                $this->setMy('lastUpdate', $invar->lastUpdate);
            }

            if (isset($invar->holdingInstitution)) {
                $invar->holdingInstitution = (object)$invar->holdingInstitution;
                $name = isset($invar->holdingInstitution->name) ? $invar->holdingInstitution->name : null;
                $contactPerson = isset($invar->holdingInstitution->contactPerson) ? $invar->holdingInstitution->contactPerson : null;
                $exhibitionCode = isset($invar->holdingInstitution->exhibitionCode) ? $invar->holdingInstitution->exhibitionCode : null;
                $notes = isset($invar->holdingInstitution->notes) ? $invar->holdingInstitution->notes : null;
                $this->setMy('holdingInstitution', new DAPHoldingInstitution($name, $contactPerson, $exhibitionCode, $notes));
            }

            if (isset($invar->isUpdate)) {
                $this->setMy('isUpdate', $invar->isUpdate);
            }

            if (isset($invar->permissions)) {
                $invar->permissions = (object)$invar->permissions;
                $readPermitted = isset($invar->permissions->readPermitted) ? $invar->permissions->readPermitted : null;
                $writePermitted = isset($invar->permissions->writePermitted) ? $invar->permissions->writePermitted : null;
                $startTime = isset($invar->permissions->startTime) ? $invar->permissions->startTime : null;
                $endTime = isset($invar->permissions->endTime) ? $invar->permissions->endTime : null;
                $this->setMy('permissions', new DAPPermissions($readPermitted, $writePermitted, $startTime, $endTime));
            }

            if (isset($invar->creator)) {
                $this->setMy('creator', $invar->creator);
            }

            if (isset($invar->caption)) {
                $this->setMy('caption', $invar->caption);
            }


            if (isset($invar->notes)) {
                $myNotes = array();
                for ($i = 0; $i < count($invar->notes); $i++) {
                    $tNotes = (object)$invar->notes[$i];
                    $tlabel = isset($tNotes->label) ? $tNotes->label : null;
                    $tnote = isset($tNotes->note) ? $tNotes->note : null;
                    array_push($myNotes, new DAPNotes($tlabel, $tnote));
                }
                if (count($myNotes) > 0) { //if we've added some description(s)
                    $this->setMy('notes', new Collection($myNotes));
                }
            }

            if (isset($invar->folgerDisplayIdentifier)) {
                $this->setMy('folgerDisplayIdentifier', $invar->folgerDisplayIdentifier);
            }

            if (isset($invar->groupings)) {
                $this->setMy('groupings', $invar->groupings);
            }

            if (isset($invar->locus)) {
                $this->setMy('locus', $invar->locus);
            }

            if (isset($invar->preferredCitation)) {
                $this->setMy('preferredCitation', $invar->preferredCitation);
            }

            if (isset($invar->simplifiedTranscription)) {
                $this->setMy('simplifiedTranscription', $invar->simplifiedTranscription);
            }

            if (isset($invar->relationships)) {

                $myAgents = array();
                $myWorks = array();

                if(isset($invar->relationships['agents'])){

                    for ($i = 0; $i < count($invar->relationships['agents']); $i++) {
                        $tAgents = (object)$invar->relationships['agents'][$i];
                        $tAgentName = isset($tAgents->agentName) ? $tAgents->agentName : null;
                        $tAgentURI = isset($tAgents->agentURI) ? $tAgents->agentURI : null;
                        $tRelationship = isset($tAgents->relationship) ? $tAgents->relationship : null;
                        array_push($myAgents, new DAPRelationshipsAgents($tAgentName,$tAgentURI,$tRelationship));
                    }

                    if (count($myAgents) > 0) {
                        $this->setMy('relationshipsAgents', new Collection($myAgents));
                    }
                }

                if(isset($invar->relationships['works'])){
                    for ($i = 0; $i < count($invar->relationships['works']); $i++) {
                        $tWorks = (object)$invar->relationships['works'][$i];
                        $tWorkTitle = isset($tWorks->workTitle) ? $tWorks->workTitle : null;
                        $tWorkURI = isset($tWorks->workURI) ? $tWorks->workURI : null;
                        $tRelationship = isset($tWorks->relationship) ? $tWorks->relationship : null;
                        array_push($myWorks, new DAPRelationshipsWorks($tWorkTitle,$tWorkURI,$tRelationship));
                    }

                    if (count($myWorks) > 0) {
                        $this->setMy('relationshipsWorks', new Collection($myWorks));
                    }
                }


                $invar->relationships = (object)$invar->relationships;
                $parents = isset($invar->relationships->parents) ? $invar->relationships->parents : null;
                $locations = isset($invar->relationships->locations) ? $invar->relationships->locations : null;
                $agents = isset($invar->relationships->agents) ? $invar->relationships->agents : null;
                $works = isset($invar->relationships->works) ? $invar->relationships->works : null;

                $arrayRelationships = array('parents' => $parents, 'agents' => $agents, 'works' => $works, "locations" => $locations);
                $this->setMy('relationships', new DAPRelationships($arrayRelationships));

            }

            /** New Schema Fields **/
            return isset($this->dapid) ? $this->dapid : -1;
        } catch (\Exception $ex) {
            return -1;
        }

    }
}
