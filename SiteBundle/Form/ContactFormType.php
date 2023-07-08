<?php

namespace Magiseo\SiteBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ContactFormType extends AbstractType
{
        /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
	  ->add('email', 'email')
	  ->add('phoneNumber', 'number', array('label' => 'Téléphone'))
	  ->add('content', 'textarea', array('label' => 'Commentaire', 'attr' => array('rows' => 10)))
        ;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Magiseo\SiteBundle\Entity\ContactForm'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'magiseo_sitebundle_contactform';
    }
}
