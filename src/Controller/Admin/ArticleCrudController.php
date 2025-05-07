<?php

namespace App\Controller\Admin;

use App\Entity\Article;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;


// Import du type CKEditor
use FOS\CKEditorBundle\Form\Type\CKEditorType;

class ArticleCrudController extends AbstractCrudController
{
    private SluggerInterface $slugger;

    public function __construct(SluggerInterface $slugger)
    {
        $this->slugger = $slugger;
    }

    public static function getEntityFqcn(): string
    {
        return Article::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('title', 'Titre'),
            SlugField::new('slug', 'Slug')
                ->setTargetFieldName('title')
                ->setUnlockConfirmationMessage('Changer le slug n’est pas recommandé.'),
            ChoiceField::new('category', 'Catégorie')
                ->setChoices([
                     'Football' => 'football',
                    'Basketball' => 'basketball',
                    'Voleyball' => 'volleyball',
                    'Rugby' => 'rugby',
                    'Hockey sur Glace' => 'hockey-sur-glace',
                ]),
            AssociationField::new('teams', 'Clubs associés')
                ->setFormTypeOptions([
                    'by_reference' => false,
                ])
                ->setHelp('Sélectionne un ou plusieurs clubs à associer à cet article.'),
            // Le champ CKEditor
            TextareaField::new('content')
                ->setFormType(CKEditorType::class)
                ->setFormTypeOptions([
                    'config_name' => 'default', // pour utiliser la config "default" ci-dessus
                ]),
            Field::new('imageFile', 'Image de l\'article (upload)')
                ->setFormType(FileType::class)
                ->setRequired($pageName === 'new'),
            Field::new('imageName', 'Image actuelle')
                ->setTemplatePath('admin/image_field.html.twig')
                ->onlyOnIndex(),
        ];
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Article) {
            return;
        }

        // Génération du slug si absent
        if (!$entityInstance->getSlug()) {
            $slug = $this->slugger->slug($entityInstance->getTitle())->lower();
            $entityInstance->setSlug($slug);
        }

        $entityInstance->setUpdatedAt(new \DateTimeImmutable());

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Article) {
            return;
        }

        $entityInstance->setUpdatedAt(new \DateTimeImmutable());

        parent::updateEntity($entityManager, $entityInstance);
    }
}
