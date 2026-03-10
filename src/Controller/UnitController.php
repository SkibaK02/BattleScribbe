<?php

namespace App\Controller;

use App\Entity\UnitBuild;
use App\Entity\UnitTemplate;
use App\Entity\User;
use App\Entity\RosterDivision;
use App\Repository\UnitBuildRepository;
use App\Repository\RosterDivisionRepository;
use App\Service\Unit\UnitConfigProvider;
use App\Service\Unit\UnitCostCalculator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Controller\Attribute\AsController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[AsController]
class UnitController extends AbstractController
{
    public function __construct(
        private readonly UnitConfigProvider $unitConfigProvider,
        private readonly UnitCostCalculator $unitCostCalculator
    ) {
    }

    #[Route('/app/unit-template/{id}', name: 'app_unit_template_form', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function unitTemplate(
        Request $request,
        UnitTemplate $unitTemplate,
        EntityManagerInterface $entityManager,
        CsrfTokenManagerInterface $csrfTokenManager,
        UnitBuildRepository $unitBuildRepository,
        RosterDivisionRepository $rosterDivisionRepository
    ): Response {
        $config = $this->unitConfigProvider->getConfig($unitTemplate);
        $errors = [];
        $formData = [];
        $user = $this->getUser();
        $existingBuild = null;
        $rosterDivision = null;

        $rosterDivisionId = $request->query->get('roster_division') ?? $request->request->get('roster_division_id');
        if ($rosterDivisionId && $user instanceof User) {
            $rosterDivision = $rosterDivisionRepository->find($rosterDivisionId);
            if (
                !$rosterDivision
                || $rosterDivision->getOwner()?->getId() !== $user->getId()
                || $rosterDivision->getDivision()?->getId() !== $unitTemplate->getDivision()?->getId()
            ) {
                throw $this->createAccessDeniedException();
            }
        }

        $buildId = $request->query->get('build') ?? $request->request->get('build_id');
        if ($buildId) {
            $existingBuild = $unitBuildRepository->find($buildId);
            if (
                !$existingBuild
                || !$existingBuild->getOwner() instanceof User
                || !$user instanceof User
                || $existingBuild->getOwner()->getId() !== $user->getId()
                || $existingBuild->getUnitTemplate()->getId() !== $unitTemplate->getId()
            ) {
                throw $this->createAccessDeniedException();
            }
            if (!$formData) {
                $savedConfig = $existingBuild->getConfiguration();
                $formData = $savedConfig['options'] ?? [];
                $formData['experience'] = $savedConfig['experience'] ?? $existingBuild->getExperience();
            }
        }

        $otherPoints = 0;
        if ($user instanceof User) {
            $builds = $rosterDivision
                ? $unitBuildRepository->findByOwnerAndRosterDivision($user, $rosterDivision->getId())
                : $unitBuildRepository->findByOwnerAndDivision($user, $unitTemplate->getDivision());
            foreach ($builds as $build) {
                if ($existingBuild && $build->getId() === $existingBuild->getId()) {
                    continue;
                }
                $otherPoints += $build->getTotalCost();
            }
        }

        if ($request->isMethod('POST')) {
            $formData = $request->request->all();
            $token = $formData['_token'] ?? '';
            if (!$csrfTokenManager->isTokenValid(new CsrfToken('unit_template_'.$unitTemplate->getId(), $token))) {
                $errors[] = 'Invalid form token. Please try again.';
            } else {
                if (in_array($unitTemplate->getName(), ['Platoon Commander', 'Field Medic'], true) && !$existingBuild && $user instanceof User) {
                    $already = $unitBuildRepository->findOneBy(['owner' => $user, 'unitTemplate' => $unitTemplate]);
                    if ($already) {
                        $errors[] = sprintf('You already have a %s in this division.', $unitTemplate->getName());
                    }
                }
                $result = $this->unitCostCalculator->calculate($unitTemplate, $config, $formData);
                if ($result === true) {
                    $this->addFlash('success', $existingBuild ? 'Unit updated successfully.' : 'Unit saved successfully.');
                    return $this->redirectToRoute('app_division_slots', ['id' => $unitTemplate->getDivision()->getId()]);
                }
                if (isset($result['errors'])) {
                    $errors = $result['errors'];
                } else {
                    $errors = is_array($result) ? $result : ['Unable to save unit.'];
                }
                if (empty($errors) && is_array($result)) {
                    $this->persistUnitBuild($unitTemplate, $formData, $result['total'] ?? 0, $result['payload'] ?? [], $existingBuild, $entityManager, $rosterDivision);
                    $this->addFlash('success', $existingBuild ? 'Unit updated successfully.' : 'Unit saved successfully.');
                    if ($rosterDivision) {
                        return $this->redirectToRoute('app_roster_division', ['id' => $rosterDivision->getId()]);
                    }
                    return $this->redirectToRoute('app_division_slots', ['id' => $unitTemplate->getDivision()->getId()]);
                }
            }
        }

        if (!$formData && $config && !empty($config['experience_costs'])) {
            $formData['experience'] = $config['experience_costs'][0]['key'];
        }

        $unitBaseCost = 0;
        $unitPreviewCost = null;
        $heroDescription = $unitTemplate->getDescription();
        if ($config && !empty($config['experience_costs'])) {
            $selectedExp = $formData['experience'] ?? $config['experience_costs'][0]['key'];
            foreach ($config['experience_costs'] as $exp) {
                if ($exp['key'] === $selectedExp) {
                    $unitBaseCost = (int) $exp['cost'];
                    break;
                }
            }
            $unitPreviewCost = $this->computeUnitPreviewCost($unitTemplate, $config, $formData);

            $short = [
                'Platoon Commander' => 'Platoon leader providing command and morale bonuses.',
                'Rifle Section' => 'Core infantry squad with rifles/LMG.',
                'Field Medic' => 'Medical support improving survivability.',
                'Commando Subsection' => 'Elite commando strike team.',
                'Marines Squad' => 'US Marines infantry squad.',
                'Veteran Squad' => 'Experienced assault infantry.',
                'IJA Infantry Squad' => 'Imperial Japanese infantry squad.',
            ];
            if (isset($short[$unitTemplate->getName()])) {
                $heroDescription = $short[$unitTemplate->getName()];
            }
        }

        return $this->render('page/unit_template_form.html.twig', [
            'template' => $unitTemplate,
            'config' => $config,
            'errors' => $errors,
            'form_data' => $formData,
            'other_points' => $otherPoints,
            'build_id' => $existingBuild?->getId(),
            'unit_base_cost' => $unitBaseCost,
            'unit_preview_cost' => $unitPreviewCost,
            'hero_description' => $heroDescription,
            'roster_division' => $rosterDivision,
        ]);
    }

    #[Route('/app/unit-build/{id}/delete', name: 'app_unit_build_delete', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function deleteUnitBuild(
        Request $request,
        UnitBuild $unitBuild,
        EntityManagerInterface $entityManager,
        CsrfTokenManagerInterface $csrfTokenManager
    ): Response {
        $user = $this->getUser();
        if (
            !$user instanceof User
            || $unitBuild->getOwner()->getId() !== $user->getId()
        ) {
            throw $this->createAccessDeniedException();
        }

        $token = $request->request->get('_token');
        if (!$csrfTokenManager->isTokenValid(new CsrfToken('delete_unit_build_'.$unitBuild->getId(), $token))) {
            throw $this->createAccessDeniedException('Invalid token.');
        }

        $divisionId = $unitBuild->getUnitTemplate()->getDivision()->getId();
        $rosterDivision = $unitBuild->getRosterDivision();

        $entityManager->remove($unitBuild);
        $entityManager->flush();

        $this->addFlash('success', 'Unit removed.');

        if ($rosterDivision) {
            return $this->redirectToRoute('app_roster_division', ['id' => $rosterDivision->getId()]);
        }
        return $this->redirectToRoute('app_division_slots', ['id' => $divisionId]);
    }

    private function persistUnitBuild(
        UnitTemplate $unitTemplate,
        array $data,
        int $total,
        array $payloadOptions,
        ?UnitBuild $existingBuild,
        EntityManagerInterface $entityManager,
        ?RosterDivision $rosterDivision = null
    ): void {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $experience = $data['experience'] ?? null;
        $build = $existingBuild ?? (new UnitBuild())->setOwner($user)->setUnitTemplate($unitTemplate);
        $build->setExperience($experience)
            ->setConfiguration([
                'experience' => $experience,
                'options' => $payloadOptions,
            ])
            ->setTotalCost($total)
            ->setRosterDivision($rosterDivision);

        $entityManager->persist($build);
        $entityManager->flush();
    }

    private function computeUnitPreviewCost(UnitTemplate $unitTemplate, array $config, array $data): int
    {
        $experience = $data['experience'] ?? ($config['experience_costs'][0]['key'] ?? null);
        $experienceCosts = [];
        foreach ($config['experience_costs'] as $definition) {
            $experienceCosts[$definition['key']] = $definition['cost'];
        }
        $total = $experienceCosts[$experience] ?? 0;

        $intelligenceSelected = !empty($data['intelligence']);
        $maxAdditional = 5 + ($intelligenceSelected ? 5 : 0);
        $menCap = $maxAdditional;
        $menCount = 0;

        $experienceExtras = [
            'Inexperienced' => $data['extra_inexperienced'] ?? 0,
            'Regular' => $data['extra_regular'] ?? 0,
            'Veteran' => $data['extra_veteran'] ?? 0,
        ];
        if (isset($experienceExtras[$experience])) {
            $extraRaw = max(0, (int) $experienceExtras[$experience]);
            $menCount += min($extraRaw, $maxAdditional);
        }

        foreach ($config['options'] as $option) {
            $name = $option['name'];

            if ($option['type'] === 'number') {
                $value = (int)($data[$name] ?? 0);
                $optionMax = $option['max'];
                if ($intelligenceSelected && str_starts_with($name, 'extra_')) {
                    $optionMax += 5;
                }
                if ($name === 'men_smg_count') {
                    $optionMax = $menCap;
                }
                $value = max(0, min($optionMax, $value));
                $total += $value * (int)$option['cost_per'];
                continue;
            }

            if ($option['type'] === 'checkbox') {
                if (isset($data[$name])) {
                    $total += (int)$option['cost'];
                }
                continue;
            }

            if ($option['type'] === 'select') {
                $selected = $data[$name] ?? 'none';
                if ($selected === 'none' || $selected === '0') {
                    continue;
                }

                $choice = null;
                foreach ($option['options'] as $choiceOption) {
                    if (($choiceOption['value'] ?? '') === $selected) {
                        $choice = $choiceOption;
                        break;
                    }
                }

                if (!$choice) {
                    continue;
                }

                if (isset($choice['cost'])) {
                    $total += (int)$choice['cost'];
                }

                if (isset($choice['cost_per_model'])) {
                    $total += (int)$choice['cost_per_model'] * $menCount;
                }
            }
        }

        return $total;
    }
}
