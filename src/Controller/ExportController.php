<?php

namespace App\Controller;

use App\Entity\Division;
use App\Entity\Faction;
use App\Repository\DivisionRepository;
use App\Repository\RosterDivisionRepository;
use App\Repository\ArmyInstanceRepository;
use App\Repository\UnitBuildRepository;
use App\Service\Unit\UnitPdfExporter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Controller\Attribute\AsController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[AsController]
class ExportController extends AbstractController
{
    public function __construct(
        private readonly UnitPdfExporter $unitPdfExporter
    ) {
    }

    #[Route('/app/divisions/{id}/export/pdf', name: 'app_division_export_pdf', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function exportDivisionPdf(
        Division $division,
        UnitBuildRepository $unitBuildRepository,
        RosterDivisionRepository $rosterDivisionRepository
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException();
        }

        $rosters = $rosterDivisionRepository->findByOwnerAndDivision($user, $division->getId());
        $rows = [];
        $totalPoints = 0;
        $unitCount = 0;
        foreach ($rosters as $rd) {
            foreach ($unitBuildRepository->findByOwnerAndRosterDivision($user, $rd->getId()) as $build) {
                $rows[] = ['build' => $build, 'roster' => $rd->getName()];
                $totalPoints += $build->getTotalCost();
                $unitCount++;
            }
        }

        $pdfContent = $this->unitPdfExporter->renderDivisionBuilds($division, $rows, $totalPoints, $unitCount);
        $filename = sprintf('roster-%s.pdf', preg_replace('/[^a-z0-9]+/i', '-', strtolower($division->getName())));

        return new Response(
            $pdfContent,
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ]
        );
    }

    #[Route('/app/factions/{id}/export/pdf', name: 'app_faction_export_pdf', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function exportFactionPdf(
        Faction $faction,
        DivisionRepository $divisionRepository,
        UnitBuildRepository $unitBuildRepository,
        RosterDivisionRepository $rosterDivisionRepository
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException();
        }

        $divisions = $divisionRepository->findBy(['faction' => $faction]);
        $divisions = array_filter($divisions, static fn(Division $d) => $d->getName() !== 'Engineer Platoon');

        $divisionData = [];
        $totalPoints = 0;
        $unitCount = 0;
        foreach ($divisions as $division) {
            $rosters = $rosterDivisionRepository->findByOwnerAndDivision($user, $division->getId());
            $rows = [];
            foreach ($rosters as $rd) {
                foreach ($unitBuildRepository->findByOwnerAndRosterDivision($user, $rd->getId()) as $build) {
                    $rows[] = ['build' => $build, 'roster' => $rd->getName()];
                    $totalPoints += $build->getTotalCost();
                    $unitCount++;
                }
            }
            if ($rows) {
                $divisionData[$division->getName()] = $rows;
            }
        }

        $pdfContent = $this->unitPdfExporter->renderFactionBuilds($faction, $divisionData, $totalPoints, $unitCount);
        $filename = sprintf('roster-%s.pdf', preg_replace('/[^a-z0-9]+/i', '-', strtolower($faction->getName())));

        return new Response(
            $pdfContent,
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ]
        );
    }

    #[Route('/app/armies/{id}/export/pdf', name: 'app_army_export_pdf', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function exportArmyPdf(
        int $id,
        ArmyInstanceRepository $armyInstanceRepository,
        DivisionRepository $divisionRepository,
        UnitBuildRepository $unitBuildRepository,
        RosterDivisionRepository $rosterDivisionRepository
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException();
        }

        $army = $armyInstanceRepository->find($id);
        if (
            !$army
            || $army->getOwner()?->getId() !== $user->getId()
        ) {
            throw $this->createAccessDeniedException();
        }
        $faction = $army->getFaction();

        $divisions = $divisionRepository->findBy(['faction' => $faction]);
        $divisions = array_filter($divisions, static fn(Division $d) => $d->getName() !== 'Engineer Platoon');

        $divisionData = [];
        $totalPoints = 0;
        $unitCount = 0;
        $rosters = $rosterDivisionRepository->findBy(['armyInstance' => $army]);
        $rostersByDivision = [];
        foreach ($rosters as $rd) {
            $rostersByDivision[$rd->getDivision()->getId()][] = $rd;
        }

        foreach ($divisions as $division) {
            $rows = [];
            foreach ($rostersByDivision[$division->getId()] ?? [] as $rd) {
                foreach ($unitBuildRepository->findByOwnerAndRosterDivision($user, $rd->getId()) as $build) {
                    $rows[] = ['build' => $build, 'roster' => $rd->getName()];
                    $totalPoints += $build->getTotalCost();
                    $unitCount++;
                }
            }
            if ($rows) {
                $divisionData[$division->getName()] = $rows;
            }
        }

        $pdfContent = $this->unitPdfExporter->renderFactionBuilds($faction, $divisionData, $totalPoints, $unitCount);
        $slugFaction = preg_replace('/[^a-z0-9]+/i', '-', strtolower($faction?->getName() ?? 'faction'));
        $slugArmy = preg_replace('/[^a-z0-9]+/i', '-', strtolower($army->getName()));
        $filename = sprintf('%s-%s.pdf', $slugFaction, $slugArmy);

        return new Response(
            $pdfContent,
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ]
        );
    }
}
