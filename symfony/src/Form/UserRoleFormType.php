<?php

namespace App\Form;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class UserRoleFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->setMethod('POST')
            ->add('user', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'email',
                'label' => 'Utilisateur',
                'query_builder' => function (UserRepository $userRepository) {
                    return $userRepository->createQueryBuilder('u')
                        ->where('u.admin <> 2');
                },
                'required' => true,
                'constraints' => [
                    new Callback([$this, 'validateRoleChange']),
                ],
            ])
            ->add('role', ChoiceType::class, [
                'choices' => [
                    'Utilisateur' => '0',
                    'Administrateur' => '1',
                ],
                'label' => 'Rôle',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }

    public function validateRoleChange($value, ExecutionContextInterface $context)
    {
        $user = $value;
        if ($user && $user->isSuperAdmin() === 2) {
            $context->buildViolation('Il est impossible de changer le rôle d\'un super administrateur.')
                ->addViolation();
        }
        if ($user && $user->isAdmin() == ($context->getRoot()->getData()['role'] > 0)) {
            $context->buildViolation('Le rôle de l\'utilisateur est déjà celui-ci.')
                ->addViolation();
        }
    }
}
