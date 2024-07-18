<?php

namespace App\Controller\Admin;

use App\Entity\Comment;
use App\Entity\Issue;
use App\Form\CommentFormType;
use App\Message\CommentMessage;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Factory\EntityFactory;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\NotifierInterface;

class IssueCrudController extends AbstractCrudController
{
    public function __construct(
        private AdminUrlGenerator      $adminUrlGenerator,
        private EntityManagerInterface $entityManager,
        private CommentRepository      $commentRepository,
        private MessageBusInterface    $bus,
        private NotifierInterface      $notifier,
    )
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

    public function configureFields(string $pageName): iterable
    {
        $adminUrlGenerator = $this->adminUrlGenerator;

        yield TextField::new('title')
            ->formatValue(function ($value, $entity) use ($adminUrlGenerator) {
                $url = $adminUrlGenerator
                    ->setController(self::class)
                    ->setAction('detail')
                    ->setEntityId($entity->getId())
                    ->generateUrl();

                return sprintf('<a href="%s">%s</a>', $url, $value);
            });

        yield TextField::new('author')->onlyOnIndex();
        yield TextareaField::new('description');
        yield DateTimeField::new('created_at')->onlyOnIndex();
        yield ChoiceField::new('priority')->setChoices([
            'Low' => 'Low',
            'Medium' => 'Medium',
            'High' => 'High',
            'Critical' => 'Medium',
        ])->renderExpanded();
    }

    public function createEntity(string $entityFqcn): Issue
    {
        $issue = new Issue();
        $current_user_id = $this->getUser();
        $issue->setAuthor($current_user_id);

        return $issue;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function detail(AdminContext $context)
    {
        $request = $context->getRequest();
        $issue_dto = $context->getEntity();
        $issue_entity = $issue_dto->getInstance();

        $comment = new Comment();
        $form = $this->createForm(CommentFormType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $comment->setIssue($issue_entity);

            $this->entityManager->persist($comment);
            $this->entityManager->flush();

            $spam_context = [
                'user_ip' => $request->getClientIp(),
                'user_agent' => $request->headers->get('user-agent'),
                'referrer' => $request->headers->get('referer'),
                'permalink' => $request->getUri(),
            ];

            $this->bus->dispatch(new CommentMessage($comment->getId(), $spam_context));
            $this->notifier->send(new Notification('Comment left!', ['browser']));

            return $this->redirectToRoute('user_homepage', [
                'crudAction' => 'detail',
                'crudControllerFqcn' => self::class,
                'entityId' => $issue_entity->getId(),
            ]);
        }

        if ($form->isSubmitted()) {
            $this->notifier->send(new Notification('Can you check your submission? There are some problems with it.', ['browser']));
        }

        $this->container->get(EntityFactory::class)->processActions($issue_dto, $context->getCrud()->getActionsConfig());

        return $this->render('admin/issue_detail.html.twig', [
            'pageName' => Crud::PAGE_DETAIL,
            'entity' => $issue_entity,
            'actions' => $issue_dto->getActions(),
            'comments' => $this->commentRepository->findBy(['issue' => $issue_entity]),
            'commentForm' => $form,
        ]);
    }
}
