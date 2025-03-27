<?php

namespace App\Controller\Admin;

use App\Entity\Portrait;
use App\Entity\Article;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;

class PortraitCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Portrait::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->setEntityLabelInSingular('Portrait de la semaine')
                    ->setEntityLabelInPlural('Portraits de la semaine')
                    ->setDefaultSort(['semaineDu' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            AssociationField::new('article')->setRequired(true),
            TextField::new('prenom'),
            TextField::new('nom'),
            TextareaField::new('description'),
            BooleanField::new('isActive', 'Actif ?'),
            DateField::new('semaineDu')->setRequired(true),
        ];
    }
}
