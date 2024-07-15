<?php

namespace App\MessageHandler;

use App\Message\CommentMessage;
use App\Repository\CommentRepository;
use App\Service\SpamChecker;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\NotificationEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Workflow\WorkflowInterface;

#[AsMessageHandler]
class CommentMessageHandler
{
    public function __construct(
        private EntityManagerInterface              $entityManager,
        private SpamChecker                         $spamChecker,
        private CommentRepository                   $commentRepository,
        private MessageBusInterface                 $bus,
        private WorkflowInterface                   $commentStateMachine,
        private ?LoggerInterface                    $logger = null,
        private MailerInterface                     $mailer,
        #[Autowire('%admin_email%')] private string $adminEmail,
    )
    {
    }

    public function __invoke(CommentMessage $message)
    {
        $comment = $this->commentRepository->find($message->getId());
        if (!$comment) {
            return;
        }

        if ($this->commentStateMachine->can($comment, 'accept')) {
            $score = $this->spamChecker->getSpamScore($comment, $message->getContext());

            $transition = match ($score) {
                2 => 'reject_spam',
                1 => 'might_be_spam',
                default => 'accept',
            };

            $this->commentStateMachine->apply($comment, $transition);
            $this->entityManager->flush();
            $this->bus->dispatch($message);
        } elseif (
            $this->commentStateMachine->can($comment, 'publish')
            || $this->commentStateMachine->can($comment, 'publish_ham')
        ) {
            $this->sendEmail($this->adminEmail, $this->adminEmail, ['comment' => $comment]);
        } elseif ($this->logger) {
            $this->logger->debug('Dropping comment message', [
                'comment' => $comment->getId(),
                'state' => $comment->getState()
            ]);
        }
    }

    public function sendEmail(string $from, string $to, array $context)
    {
        $email = (new NotificationEmail())->subject('New comment posted')
            ->htmlTemplate('emails/comment_notification.html.twig')
            ->from($from)
            ->to($to)
            ->context($context);

        $this->mailer->send($email);
    }
}