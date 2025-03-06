<?php

namespace App\Controller\Admin;

use App\Entity\Team;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;

class TeamCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Team::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('name', 'Nom de l\'équipe'),
            ImageField::new('image', 'Logo')
                ->setBasePath('/uploads/teams')
                ->setUploadDir('public/uploads/teams')
                ->setUploadedFileNamePattern('[randomhash].[extension]')
                ->setRequired(false),
            ChoiceField::new('sport', 'Sport')
                ->setChoices([
                    'Football' => 'football',
                    'Rugby' => 'rugby',
                    'Rugby féminin' => 'rugby_f',
                    'Hockey' => 'hockey',
                    'Basketball' => 'basket',
                    'Volley' => 'volley',
                    'Basketball-Ambitions-Girondines' => 'basket-ambitions-girondines',

                ])
                ->setRequired(false),
        ];
    }
}
