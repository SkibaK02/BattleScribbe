<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Roster;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class RosterStateProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private readonly ProcessorInterface $persistProcessor,
        private readonly Security $security
    ) {
    }

    /**
     * @param Roster $data
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if ($data instanceof Roster) {
            $user = $this->security->getUser();
            if (!$user instanceof User) {
                throw new AccessDeniedException();
            }

            if (null === $data->getOwner()) {
                $data->setOwner($user);
            } elseif ($data->getOwner() !== $user) {
                throw new AccessDeniedException('You cannot modify another user\'s roster.');
            }
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}

