<?php

namespace App\Controller;

use App\Entity\ArmyInstance;
use App\Entity\Faction;
use App\Repository\ArmyInstanceRepository;
use App\Repository\RosterDivisionRepository;
use App\Repository\UnitBuildRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Controller\Attribute\AsController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[AsController]
class ArmyController extends AbstractController
{
    #[Route('/app/factions/{id}/armies', name: 'app_faction_armies', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function listArmies(
        Faction $faction,
        ArmyInstanceRepository $armyInstanceRepository,
        RosterDivisionRepository $rosterDivisionRepository,
        UnitBuildRepository $unitBuildRepository
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException();
        }

        $armies = $armyInstanceRepository->findByOwnerAndFaction($user, $faction->getId());
        $totals = [];
        $armiesDetails = [];
        foreach ($armies as $army) {
            $sum = 0;
            $rosterDetails = [];
            foreach ($rosterDivisionRepository->findBy(['armyInstance' => $army]) as $rd) {
                foreach ($unitBuildRepository->findByOwnerAndRosterDivision($user, $rd->getId()) as $build) {
                    $sum += $build->getTotalCost();
                    $rosterDetails[$rd->getId()]['units'][] = [
                        'name' => $build->getUnitTemplate()->getName(),
                        'experience' => $build->getExperience(),
                        'cost' => $build->getTotalCost(),
                    ];
                }
                $rosterDetails[$rd->getId()]['name'] = $rd->getName();
                $rosterDetails[$rd->getId()]['division'] = $rd->getDivision()?->getName();
                $rosterDetails[$rd->getId()]['points'] = array_sum(array_column($rosterDetails[$rd->getId()]['units'] ?? [], 'cost'));
            }
            $totals[$army->getId()] = $sum;
            $armiesDetails[$army->getId()] = [
                'rosters' => array_values($rosterDetails),
            ];
        }

        return $this->render('page/army_list.html.twig', [
            'faction' => $faction,
            'armies' => $armies,
            'totals' => $totals,
            'armiesDetails' => $armiesDetails,
        ]);
    }

    #[Route('/app/factions/{id}/armies/create', name: 'app_army_create', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function createArmy(
        Request $request,
        Faction $faction,
        EntityManagerInterface $entityManager,
        ArmyInstanceRepository $armyInstanceRepository
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException();
        }

        $name = trim((string)$request->request->get('name', ''));
        if ($name === '') {
            $existing = $armyInstanceRepository->findByOwnerAndFaction($user, $faction->getId());
            $name = sprintf('Army #%d', count($existing) + 1);
        }

        $army = new ArmyInstance();
        $army->setOwner($user)
            ->setFaction($faction)
            ->setName($name);

        $entityManager->persist($army);
        $entityManager->flush();

        $this->addFlash('success', 'Army created.');

        return $this->redirectToRoute('app_faction_armies', ['id' => $faction->getId()]);
    }

    #[Route('/app/armies/{id}/delete', name: 'app_army_delete', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function deleteArmy(
        Request $request,
        ArmyInstance $army,
        EntityManagerInterface $entityManager,
        RosterDivisionRepository $rosterDivisionRepository,
        UnitBuildRepository $unitBuildRepository
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User || $army->getOwner()?->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }

        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete_army_'.$army->getId(), $token)) {
            throw $this->createAccessDeniedException('Invalid token.');
        }

        $factionId = $army->getFaction()?->getId();

        $rosters = $rosterDivisionRepository->findBy(['armyInstance' => $army]);
        foreach ($rosters as $rd) {
            foreach ($unitBuildRepository->findByOwnerAndRosterDivision($user, $rd->getId()) as $build) {
                $entityManager->remove($build);
            }
            $entityManager->remove($rd);
        }

        $entityManager->remove($army);
        $entityManager->flush();

        $this->addFlash('success', 'Army deleted.');

        if ($factionId) {
            return $this->redirectToRoute('app_faction_armies', ['id' => $factionId]);
        }
        return $this->redirectToRoute('app_dashboard');
    }
}
