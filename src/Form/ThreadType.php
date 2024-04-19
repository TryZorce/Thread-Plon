<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Thread;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bundle\SecurityBundle\Security;

class ThreadType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title')
            ->add('description')
            ->add('body', TextareaType::class)
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'title',
                'multiple' => true,
                'expanded' => true, 
            ]);

            if ($options['isAdmin']) {
                $builder->add('status', ChoiceType::class, [
                    'choices' => [
                        'Ouvert' => 'ouvert',
                        'Fermé' => 'fermé',
                        'Bloqué' => 'bloqué',
                    ],
                    'expanded' => false,
                    'multiple' => false,
                    'placeholder' => 'Sélectionner un statut',
                ]);
            }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Thread::class,
            'isAdmin' => false,
        ]);
    }
}
