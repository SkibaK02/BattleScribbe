<?php

namespace App\Doctrine\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Roster;
use App\Entity\User;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;

class CurrentUserExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
    public function __construct(
        private readonly Security $security
    ) {
    }

    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, Operation $operation = null, array $context = []): void
    {
        $this->restrictToCurrentUser($queryBuilder, $resourceClass);
    }

    public function applyToItem(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, array $identifiers, Operation $operation = null, array $context = []): void
    {
        $this->restrictToCurrentUser($queryBuilder, $resourceClass);
    }

    private function restrictToCurrentUser(QueryBuilder $queryBuilder, string $resourceClass): void
    {
        if ($resourceClass !== Roster::class) {
            return;
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            // no user, force empty result
            $queryBuilder->andWhere('1 = 0');
            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];
        $parameterName = sprintf('%s_owner', $rootAlias);

        $queryBuilder
            ->andWhere(sprintf('%s.owner = :%s', $rootAlias, $parameterName))
            ->setParameter($parameterName, $user->getId());
    }
}

