<?php declare(strict_types=1);

namespace AppBundle\Form\Type;

use FOS\UserBundle\Form\Type\RegistrationFormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class UserRegistrationFormType extends RegistrationFormType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('displayName', TextType::class, [
                'label'                 => 'form.displayName',
                'translation_domain'    => 'FOSUserBundle',
                'required'              => true,
            ])
        ;
    }
}