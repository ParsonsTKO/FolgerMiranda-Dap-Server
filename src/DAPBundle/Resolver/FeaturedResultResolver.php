<?php
/**
 * File containing the FeaturedResultResolver class.
 *
 * (c) http://parsonstko.com/
 * (c) Developer jc
 */

namespace DAPBundle\Resolver;

use AppBundle\Entity\FeaturedResult;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\Mapping\Builder;
use Doctrine\ORM\QueryBuilder;

class FeaturedResultResolver extends AbstractResolver
{

    public function findBySearchTerm($args) {

        //make sure  our input is prepared
        $featuredResultForWhat = strtolower($args); //lower case
        $featuredResultForWhat =  preg_replace('/[[:punct:]]/u', '', $featuredResultForWhat); //get rid of all punctuation (unicode safe)
        $featuredResultForWhat = explode(' ', $featuredResultForWhat); // split on spaces
        $featuredResultForWhat = array_filter($featuredResultForWhat, function($value){ return $value != '';}); //get rid of empy spots
        $featuredResultForWhat = implode(' ', $featuredResultForWhat); //reassemble into string


        if(trim($featuredResultForWhat) == '') return array();

        //find all the featured results which match or contain the search term
        //order each by longest length trigger, then by highest priority
        //get the best match for exact, then best match for contains, take best of those, favoring the exact match
        $sql =  "SELECT id, trigger, teaser, thumbnail, title, link, priority, length(trigger) as triggerlength, a from (
            (SELECT id, trigger, teaser, thumbnail, title, link, priority, length(trigger) as triggerlength, 1 as a FROM featured_result WHERE trigger like :search ORDER BY triggerlength DESC, priority DESC LIMIT 1)
            UNION
            (SELECT id, trigger, teaser, thumbnail, title, link, priority, length(trigger) as triggerlength, 2 as a FROM featured_result WHERE trigger like :searchcontains ORDER BY triggerlength DESC, priority DESC LIMIT 1)
        ) as tt order by a ASC, triggerlength DESC LIMIT 1;";

        $rsm = new ResultSetMapping;
        $rsm->addEntityResult('AppBundle\Entity\FeaturedResult', 'fr');
        $rsm->addFieldResult('fr', 'id', 'id');
        $rsm->addFieldResult('fr', 'trigger', 'trigger');
        $rsm->addFieldResult('fr', 'teaser', 'teaser');
        $rsm->addFieldResult('fr', 'thumbnail', 'thumbnail');
        $rsm->addFieldResult('fr', 'title', 'title');
        $rsm->addFieldResult('fr', 'link', 'link');
        $rsm->addFieldResult('fr', 'priority', 'priority');

        $query = $this->em->createNativeQuery($sql, $rsm);
        $query->setParameter('search', $featuredResultForWhat);
        $query->setParameter('searchcontains', "%$featuredResultForWhat%");


        $featuredResults = $query->getResult();

        return $featuredResults;
    }

    public function findAll()
    {
        return $this->em->getRepository('AppBundle:FeaturedResult')->findAll();
    }

}
