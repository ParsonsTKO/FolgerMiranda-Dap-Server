<?php

namespace AdminBundle\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AdminController as BaseAdminController;

class AdminController extends BaseAdminController
{
    /**
     * {@inheritdoc}
     */
    public function createNewUserEntity()
    {
        return $this->get('fos_user.user_manager')->createUser();
    }

    /**
     * {@inheritdoc}
     */
    public function persistUserEntity($user)
    {
        $this->get('fos_user.user_manager')->updateUser($user, false);

        parent::persistEntity($user);
    }

    /**
     * {@inheritdoc}
     */
    public function updateUserEntity($user)
    {
        $this->get('fos_user.user_manager')->updateUser($user, false);

        parent::updateEntity($user);
    }

    //try display JSON action
    public function jsonViewAction()
    {
        $id = $this->request->query->get('id');
        $entity = $this->em->getRepository('AppBundle:Record')->find($id);
        $myJSON = json_encode($entity->metadata, JSON_PRETTY_PRINT);
        die($myJSON);
    }
}
