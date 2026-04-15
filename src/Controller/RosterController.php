<?php

namespace App\Controller;

use App\Entity\Faction;
use App\Entity\RosterDivision;
use App\Repository\RosterDivisionRepository;
use App\Repository\ArmyInstanceRepository;
use App\Repository\DivisionRepository;
use App\Repository\UnitBuildRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Controller\Attribute\AsController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Doctrine\ORM\EntityManagerInterface;

#[AsController]
class RosterController extends AbstractController
{
    #[Route('/app/factions/{id}/rosters', name: 'app_faction_rosters', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function listFactionRosters(
        Faction $faction,
        Request $request,
        ArmyInstanceRepository $armyInstanceRepository,
        RosterDivisionRepository $rosterDivisionRepository,
        UnitBuildRepository $unitBuildRepository,
        DivisionRepository $divisionRepository
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException();
        }

        $armyId = $request->query->get('army');
        $army = null;
        if ($armyId) {
            $army = $armyInstanceRepository->find($armyId);
            if (!$army || $army->getOwner()?->getId() !== $user->getId() || $army->getFaction()?->getId() !== $faction->getId()) {
                throw $this->createAccessDeniedException();
            }
        } else {
            $armies = $armyInstanceRepository->findByOwnerAndFaction($user, $faction->getId());
            $army = $armies[0] ?? null;
        }

        $rosters = [];
        $totals = [];
        $rosterDetails = [];
        if ($army) {
            $rosters = $rosterDivisionRepository->findBy(['armyInstance' => $army], ['createdAt' => 'ASC']);
            foreach ($rosters as $rd) {
                $sum = 0;
                $units = [];
                foreach ($unitBuildRepository->findByOwnerAndRosterDivision($user, $rd->getId()) as $build) {
                    $sum += $build->getTotalCost();
                    $units[] = [
                        'name' => $build->getUnitTemplate()->getName(),
                        'experience' => $build->getExperience(),
                        'cost' => $build->getTotalCost(),
                    ];
                }
                $totals[$rd->getId()] = $sum;
                $rosterDetails[$rd->getId()] = [
                    'name' => $rd->getName(),
                    'division' => $rd->getDivision()?->getName(),
                    'points' => $sum,
                    'units' => $units,
                ];
            }
        }

        return $this->render('page/roster_list.html.twig', [
            'faction' => $faction,
            'rosters' => $rosters,
            'totals' => $totals,
            'divisions' => $divisionRepository->findBy(['faction' => $faction], ['name' => 'ASC']),
            'army' => $army,
            'rosterDetails' => $rosterDetails,
        ]);
    }

    #[Route('/app/roster-division/{id}/delete', name: 'app_roster_division_delete', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function deleteRosterDivision(
        Request $request,
        RosterDivision $rosterDivision,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User || $rosterDivision->getOwner()?->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }

        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete_roster_division_'.$rosterDivision->getId(), $token)) {
            throw $this->createAccessDeniedException('Invalid token.');
        }

        $factionId = $rosterDivision->getDivision()?->getFaction()?->getId();
        $armyId = $rosterDivision->getArmyInstance()?->getId();

        $entityManager->remove($rosterDivision);
        $entityManager->flush();

        $this->addFlash('success', 'Roster removed.');

        if ($factionId) {
            $redirectParams = ['id' => $factionId];
            if ($armyId) {
                $redirectParams['army'] = $armyId;
            }

            return $this->redirectToRoute('app_faction_rosters', $redirectParams);
        }
        return $this->redirectToRoute('app_dashboard');
    }

    #[Route('/app/factions/{id}/rosters/create', name: 'app_faction_roster_create', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function createRosterFromFaction(
        Request $request,
        Faction $faction,
        DivisionRepository $divisionRepository,
        EntityManagerInterface $entityManager,
        RosterDivisionRepository $rosterDivisionRepository
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException();
        }

        $divisionId = (int)$request->request->get('division_id', 0);
        $division = $divisionRepository->find($divisionId);
        if (
            !$division
            || $division->getFaction()?->getId() !== $faction->getId()
            || $division->getName() === 'Engineer Platoon'
        ) {
            $this->addFlash('error', 'Invalid division selected.');
            return $this->redirectToRoute('app_faction_rosters', ['id' => $faction->getId()]);
        }

        $armyId = $request->request->get('army_id');
        $army = null;
        if ($armyId) {
            $army = $entityManager->getRepository(\App\Entity\ArmyInstance::class)->find($armyId);
            if (!$army || $army->getOwner()?->getId() !== $user->getId() || $army->getFaction()?->getId() !== $faction->getId()) {
                $this->addFlash('error', 'Invalid army selected.');
                return $this->redirectToRoute('app_faction_rosters', ['id' => $faction->getId()]);
            }
        }

        $name = trim((string)$request->request->get('name', ''));
        if ($name === '') {
            $existing = $rosterDivisionRepository->findByOwnerAndDivision($user, $division->getId());
            $name = sprintf('%s #%d', $division->getName(), count($existing) + 1);
        }

        $rd = new RosterDivision();
        $rd->setOwner($user)
            ->setDivision($division)
            ->setArmyInstance($army)
            ->setName($name);

        $entityManager->persist($rd);
        $entityManager->flush();

        $this->addFlash('success', 'Roster created.');

        return $this->redirectToRoute('app_roster_division', ['id' => $rd->getId()]);
    }
}
