<?php
// src/Form/SeriesSearchType.php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use App\Repository\SeriesRepository;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\OptionsResolver\OptionsResolver; // Add this line

class SeriesSearchType extends AbstractType
{
    private $seriesRepository;

    public function __construct(SeriesRepository $seriesRepository)
    {
        $this->seriesRepository = $seriesRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $genres = $this->seriesRepository->findUniqueGenres()->getQuery()->getResult();
        $genres = array_map(function ($genre) {
            return $genre['name'];
        }, $genres);
        $choices = array_combine($genres, $genres);

        $builder
            ->add('genre', ChoiceType::class, [
                'label' => "Filter by genre",
                'choices' => $choices,
                'required' => false,
                'multiple' => true,
                'expanded' => true,
                'placeholder' => 'Select a genre',
                'attr' => [
                    'name' => 'genres[]',
                    'id' => 'genres'
                ],
            ])
            ->add('ratings', ChoiceType::class, [
                'label' => 'Sort by rating',
                'choices' => [
                    'Ascending' => 'asc',
                    'Descending' => 'desc'
                ],
                'required' => false,
                'expanded' => true,
                'multiple' => false,
                'attr' => [
                    'name' => 'ratingSort[]',
                    'id' => 'ratingSort'
                ],
                'label_attr' => [
                    'id' => 'ratingSortLabel', // Ajouter un ID au label
                ],
            ])
            ->add('startDate', IntegerType::class, [
                'label' => false,
                'required' => false,
                'attr' => [
                    'placeholder' => 'Start Date',
                    'name' => 'startDate',
                    'id' => 'startDate'

                ],
            ])
            ->add('endDate', IntegerType::class, [
                'label' => false,
                'required' => false,
                'attr' => [
                    'placeholder' => 'End Date',
                    'name' => 'endDate',
                    'id' => 'endDate'
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver) // Update this line
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
            'method' => 'GET',
        ]);
    }
}
