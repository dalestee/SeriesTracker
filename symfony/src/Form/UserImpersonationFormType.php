<?php

namespace App\Form;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class UserImpersonationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->setMethod('POST')
            ->add('user', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'email',
                'label' => 'Utilisateur à incarner',
                'query_builder' => function (UserRepository $userRepository) {
                    return $userRepository->createQueryBuilder('u')
                        ->where('u.admin = 0');
                },
                'required' => true,
                'constraints' => [
                    new Callback([$this, 'validateUserImpersonation']),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
        ]);
    }

    public function validateUserImpersonation($value, ExecutionContextInterface $context)
    {
        // Impossible to impersonate a super admin and a admin with admin rights
        $currentUser = $context->getRoot()->getData()['user'];

        if ($value && $value->isAdmin() && !$currentUser->isSuperAdmin()) {
            $context->buildViolation('Vous ne pouvez pas incarner un administrateur.')
                ->addViolation();
        }

        if ($value && $value->isSuperAdmin()) {
            $context->buildViolation('Vous ne pouvez pas incarner un super administrateur.')
                ->addViolation();
        }
    }
}
