<?php declare(strict_types=1);

namespace AppBundle\Form\Type;

use FOS\UserBundle\Form\Type\ProfileFormType;
use FOS\UserBundle\Form\Type\RegistrationFormType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class UserProfileFormType extends ProfileFormType
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
                'required'              => false,
            ])
            ->add('apiKey', TextType::class, [
                'label'                 => 'form.apiKey',
                'translation_domain'    => 'FOSUserBundle',
                'required'              => false,
                'disabled'              => true,
            ])
            ->add('resetApiKey', CheckboxType::class, [
                'label'                 => 'form.resetApiKey',
                'translation_domain'    => 'FOSUserBundle',
                'required'              => false,
            ])
        ;
    }
}