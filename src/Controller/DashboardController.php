<?php

namespace App\Controller;

use App\Entity\Faction;
use App\Repository\DivisionRepository;
use App\Repository\FactionRepository;
use App\Repository\RosterDivisionRepository;
use App\Repository\UnitBuildRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Controller\Attribute\AsController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[AsController]
class DashboardController extends AbstractController
{
    #[Route('/app', name: 'app_dashboard', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function dashboard(FactionRepository $factionRepository): Response
    {
        $factions = $factionRepository->findBy([], ['name' => 'ASC']);

        return $this->render('page/dashboard.html.twig', [
            'factions' => $factions,
        ]);
    }

    #[Route('/app/factions/{id}', name: 'app_faction_divisions', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function factionDivisions(
        Faction $faction,
        DivisionRepository $divisionRepository,
        RosterDivisionRepository $rosterDivisionRepository,
        UnitBuildRepository $unitBuildRepository
    ): Response {
        $divisions = $divisionRepository->findBy(['faction' => $faction], ['name' => 'ASC']);
        $divisions = array_filter($divisions, static fn($d) => $d->getName() !== 'Engineer Platoon');

        $divisionTotals = [];
        $factionTotal = 0;
        $rosterCount = 0;
        $user = $this->getUser();
        if ($user instanceof \App\Entity\User) {
            $rostersForFaction = $rosterDivisionRepository->findByOwnerAndFaction($user, $faction->getId());
            $rosterCount = count($rostersForFaction);
            foreach ($divisions as $division) {
                $sum = 0;
                foreach ($rostersForFaction as $rd) {
                    if ($rd->getDivision()?->getId() !== $division->getId()) {
                        continue;
                    }
                    foreach ($unitBuildRepository->findByOwnerAndRosterDivision($user, $rd->getId()) as $build) {
                        $sum += $build->getTotalCost();
                    }
                }
                $divisionTotals[$division->getId()] = $sum;
                $factionTotal += $sum;
            }
        }

        return $this->render('page/faction_divisions.html.twig', [
            'faction' => $faction,
            'divisions' => $divisions,
            'divisionTotals' => $divisionTotals,
            'factionTotal' => $factionTotal,
            'rosterCount' => $rosterCount,
        ]);
    }
}
