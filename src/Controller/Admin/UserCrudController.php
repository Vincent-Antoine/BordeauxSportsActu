<?php

namespace App\Controller\Admin;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserCrudController extends AbstractCrudController
{
    private UserPasswordHasherInterface $passwordHasher;
    private RequestStack $requestStack;

    public function __construct(UserPasswordHasherInterface $passwordHasher, RequestStack $requestStack)
    {
        $this->passwordHasher = $passwordHasher;
        $this->requestStack = $requestStack;
    }

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            EmailField::new('email', 'Adresse e-mail'),
            ArrayField::new('roles', 'Rôles')
                ->setHelp('Utilisez "ROLE_ADMIN" pour un administrateur ou "ROLE_USER" pour un utilisateur standard.'),
            // Pas de champ mot de passe ici, on l'ajoute plus bas via le formBuilder
        ];
    }

    public function createEntityFormBuilder(string $pageName, $entityInstance, array $entityProperties): FormBuilderInterface
    {
        $formBuilder = parent::createEntityFormBuilder($pageName, $entityInstance, $entityProperties);

        // Ajout manuel d’un champ mot de passe non mappé
        $formBuilder->add('plainPassword', PasswordType::class, [
            'required' => $pageName === Crud::PAGE_NEW,
            'mapped' => false,
            'label' => 'Mot de passe',
            'help' => 'Laissez vide pour ne pas modifier le mot de passe.',
        ]);

        return $formBuilder;
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof User) {
            return;
        }

        $plainPassword = $this->getPlainPasswordFromRequest();
        if (!empty($plainPassword)) {
            $hashedPassword = $this->passwordHasher->hashPassword($entityInstance, $plainPassword);
            $entityInstance->setPassword($hashedPassword);
        }

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof User) {
            return;
        }

        $plainPassword = $this->getPlainPasswordFromRequest();
        if (!empty($plainPassword)) {
            $hashedPassword = $this->passwordHasher->hashPassword($entityInstance, $plainPassword);
            $entityInstance->setPassword($hashedPassword);
        }

        parent::updateEntity($entityManager, $entityInstance);
    }

    private function getPlainPasswordFromRequest(): ?string
    {
        $request = $this->requestStack->getCurrentRequest();
        $formData = $request->request->all();

        return $formData['User']['plainPassword'] ?? null;
    }
}
