<?php

namespace App\Controller;

use App\Entity\Division;
use App\Entity\RosterDivision;
use App\Repository\RosterDivisionRepository;
use App\Repository\UnitBuildRepository;
use App\Repository\UnitTemplateRepository;
use App\Service\Unit\UnitConfigProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Controller\Attribute\AsController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[AsController]
class DivisionController extends AbstractController
{
    public function __construct(
        private readonly UnitConfigProvider $unitConfigProvider
    ) {
    }

    #[Route('/app/divisions/{id}', name: 'app_division_slots', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function divisionSlots(
        Division $division,
        RosterDivisionRepository $rosterDivisionRepository,
        UnitBuildRepository $unitBuildRepository
    ): Response {
        $user = $this->getUser();
        $rosterDivisions = [];
        $rosterTotals = [];
        $divisionTotal = 0;
        if ($user instanceof \App\Entity\User) {
            $rosterDivisions = $rosterDivisionRepository->findByOwnerAndDivision($user, $division->getId());
            foreach ($rosterDivisions as $rd) {
                $sum = 0;
                foreach ($unitBuildRepository->findByOwnerAndRosterDivision($user, $rd->getId()) as $build) {
                    $sum += $build->getTotalCost();
                }
                $rosterTotals[$rd->getId()] = $sum;
                $divisionTotal += $sum;
            }
        }

        return $this->render('page/division_slots.html.twig', [
            'division' => $division,
            'rosterDivisions' => $rosterDivisions,
            'rosterTotals' => $rosterTotals,
            'divisionTotal' => $divisionTotal,
        ]);
    }

    #[Route('/app/divisions/{id}/roster/create', name: 'app_roster_division_create', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function createRosterDivision(
        Request $request,
        Division $division,
        EntityManagerInterface $entityManager,
        RosterDivisionRepository $rosterDivisionRepository
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException();
        }

        $name = trim((string)$request->request->get('name', ''));
        if ($name === '') {
            $existing = $rosterDivisionRepository->findByOwnerAndDivision($user, $division->getId());
            $name = sprintf('%s #%d', $division->getName(), count($existing) + 1);
        }

        $rd = new RosterDivision();
        $rd->setOwner($user)
            ->setDivision($division)
            ->setName($name);

        $entityManager->persist($rd);
        $entityManager->flush();

        return $this->redirectToRoute('app_roster_division', ['id' => $rd->getId()]);
    }

    #[Route('/app/roster-division/{id}', name: 'app_roster_division', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function rosterDivision(
        RosterDivision $rosterDivision,
        UnitTemplateRepository $unitTemplateRepository,
        UnitBuildRepository $unitBuildRepository
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User || $rosterDivision->getOwner()?->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }

        $division = $rosterDivision->getDivision();
        $templates = $unitTemplateRepository->findBy(['division' => $division], ['type' => 'ASC', 'name' => 'ASC']);
        $templates = array_values(array_filter(
            $templates,
            fn($t) => $this->unitConfigProvider->isTemplateAllowedForFaction($t, $division->getFaction())
                && $this->unitConfigProvider->isTemplateAllowedForDivision($t)
                && $this->unitConfigProvider->getConfig($t) !== null
        ));

        $buildsByTemplate = [];
        $totalPoints = 0;
        $singleBlocked = [];
        $singleLimit = ['Platoon Commander', 'Field Medic'];
        $builds = $unitBuildRepository->findByOwnerAndRosterDivision($user, $rosterDivision->getId());
        foreach ($builds as $build) {
            $templateId = $build->getUnitTemplate()->getId();
            $buildsByTemplate[$templateId][] = $build;
            $totalPoints += $build->getTotalCost();
            $name = $build->getUnitTemplate()->getName();
            if (in_array($name, $singleLimit, true)) {
                $singleBlocked[$name] = true;
            }
        }

        return $this->render('page/roster_division.html.twig', [
            'rosterDivision' => $rosterDivision,
            'templates' => $templates,
            'builds' => $buildsByTemplate,
            'totalPoints' => $totalPoints,
            'singleBlocked' => $singleBlocked,
        ]);
    }
}
