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
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;

class IssueCrudController extends AbstractCrudController
{
    public function __construct(private AdminUrlGenerator $adminUrlGenerator)
    {
    }

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

    // @todo: Add configureFilters method implementation.

    public function configureFields(string $pageName): iterable {
        $adminUrlGenerator = $this->adminUrlGenerator;
        $current_user_id = $this->getUser()->getUserIdentifier();

        yield TextField::new('title')
            ->formatValue(function ($value, $entity) use ($adminUrlGenerator) {
                $url = $adminUrlGenerator
                    ->setController(self::class)
                    ->setAction('detail')
                    ->setEntityId($entity->getId())
                    ->generateUrl();

                return sprintf('<a href="%s">%s</a>', $url, $value);
            });

        yield TextField::new('author')->setValue($current_user_id)->onlyOnIndex();
        yield TextareaField::new('description');
        yield ChoiceField::new('priority')->setChoices([
            'Low' => 'priority_low',
            'Medium' => 'priority_medium',
            'High' => 'priority_high',
            'Critical' => 'priority_critical',
        ])->renderExpanded();
    }

    public function createEntity(string $entityFqcn): Issue
    {
        $issue = new Issue();
        $current_user_id = $this->getUser();
        $issue->setAuthor($current_user_id);

        return $issue;
    }
}
