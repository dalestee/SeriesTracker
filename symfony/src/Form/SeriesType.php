<?php

namespace App\Form;

use App\Entity\Actor;
use App\Entity\Country;
use App\Entity\Genre;
use App\Entity\Series;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SeriesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title')
            ->add('plot')
            ->add('imdb')
            ->add('poster')
            ->add('director')
            ->add('youtubeTrailer')
            ->add('awards')
            ->add('yearStart')
            ->add('yearEnd')
            ->add('user', EntityType::class, [
                'class' => User::class,
'choice_label' => 'id',
'multiple' => true,
            ])
            ->add('genre', EntityType::class, [
                'class' => Genre::class,
'choice_label' => 'id',
'multiple' => true,
            ])
            ->add('actor', EntityType::class, [
                'class' => Actor::class,
'choice_label' => 'id',
'multiple' => true,
            ])
            ->add('country', EntityType::class, [
                'class' => Country::class,
'choice_label' => 'id',
'multiple' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Series::class,
        ]);
    }
}
