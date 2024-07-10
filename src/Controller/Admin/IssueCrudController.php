<?php

namespace App\Controller\Admin;

use App\Entity\Issue;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;

class IssueCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Issue::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Issue')
            ->setEntityLabelInPlural('Issues')
            ->setSearchFields(['author', 'text', 'email']);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters->add(EntityFilter::new('user', 'Created by'));
    }

    public function configureFields(string $pageName): iterable {
        yield TextField::new('title');
        yield TextareaField::new('description');
        yield ChoiceField::new('priority')->setChoices([
            'Low' => 'priority_low',
            'Medium' => 'priority_medium',
            'High' => 'priority_high',
            'Critical' => 'priority_critical',
        ])->renderExpanded();
    }
}
