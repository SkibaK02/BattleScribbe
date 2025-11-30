<?php

namespace App\Controller;

use App\Entity\Faction;
use App\Entity\Division;
use App\Repository\DivisionRepository;
use App\Repository\FactionRepository;
use App\Repository\UnitTemplateRepository;
use App\Repository\UnitBuildRepository;
use App\Entity\UnitTemplate;
use App\Entity\UnitBuild;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class PageController extends AbstractController
{
    #[Route('/', name: 'app_landing', methods: ['GET'])]
    public function landing(): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        return $this->render('page/landing.html.twig');
    }

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
    public function factionDivisions(Faction $faction, DivisionRepository $divisionRepository): Response
    {
        $divisions = $divisionRepository->findBy(['faction' => $faction], ['name' => 'ASC']);

        return $this->render('page/faction_divisions.html.twig', [
            'faction' => $faction,
            'divisions' => $divisions,
        ]);
    }

    #[Route('/app/divisions/{id}', name: 'app_division_slots', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function divisionSlots(
        Division $division,
        UnitTemplateRepository $unitTemplateRepository,
        UnitBuildRepository $unitBuildRepository
    ): Response
    {
        $templates = $unitTemplateRepository->findBy(['division' => $division], ['type' => 'ASC', 'name' => 'ASC']);
        $order = [
            'Platoon Commander',
            'Company Commander',
            'Infantry',
            'Medic',
            'Chaplain',
            'Forward Observer',
            'Sniper',
            'Anti-tank',
            'Light Mortar',
            'Transports',
        ];

        $grouped = [];
        foreach ($order as $slot) {
            $grouped[$slot] = [];
        }

        foreach ($templates as $template) {
            $type = $template->getType();
            if (!array_key_exists($type, $grouped)) {
                $grouped[$type] = [];
            }
            $grouped[$type][] = $template;
        }

        $user = $this->getUser();
        $buildsByTemplate = [];
        $totalPoints = 0;
        if ($user instanceof \App\Entity\User) {
            $builds = $unitBuildRepository->findByOwnerAndDivision($user, $division);
            foreach ($builds as $build) {
                $templateId = $build->getUnitTemplate()->getId();
                $buildsByTemplate[$templateId][] = $build;
                $totalPoints += $build->getTotalCost();
            }
        }

        return $this->render('page/division_slots.html.twig', [
            'division' => $division,
            'slots' => $grouped,
            'builds' => $buildsByTemplate,
            'otherPoints' => $totalPoints,
        ]);
    }

    #[Route('/app/unit-template/{id}', name: 'app_unit_template_form', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function unitTemplate(
        Request $request,
        UnitTemplate $unitTemplate,
        EntityManagerInterface $entityManager,
        CsrfTokenManagerInterface $csrfTokenManager,
        UnitBuildRepository $unitBuildRepository
    ): Response
    {
        $config = $this->buildTemplateConfig($unitTemplate);
        $errors = [];
        $formData = [];
        $user = $this->getUser();
        $existingBuild = null;

        $otherPoints = 0;
        if ($user instanceof \App\Entity\User) {
            foreach ($unitBuildRepository->findByOwnerAndDivision($user, $unitTemplate->getDivision()) as $build) {
                $otherPoints += $build->getTotalCost();
            }
        }

        $buildId = $request->query->get('build') ?? $request->request->get('build_id');
        if ($buildId) {
            $existingBuild = $unitBuildRepository->find($buildId);
            if (
                !$existingBuild
                || !$existingBuild->getOwner() instanceof \App\Entity\User
                || !$user instanceof \App\Entity\User
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

        if ($request->isMethod('POST')) {
            $formData = $request->request->all();
            $token = $formData['_token'] ?? '';
            if (!$csrfTokenManager->isTokenValid(new CsrfToken('unit_template_'.$unitTemplate->getId(), $token))) {
                $errors[] = 'Invalid form token. Please try again.';
            } else {
                $result = $this->handleUnitBuildSubmission($unitTemplate, $config, $formData, $entityManager, $existingBuild);
                if ($result === true) {
                    $this->addFlash('success', $existingBuild ? 'Unit updated successfully.' : 'Unit saved successfully.');
                    return $this->redirectToRoute('app_division_slots', ['id' => $unitTemplate->getDivision()->getId()]);
                }
                $errors = $result;
            }
        }

        if (!$formData && $config && !empty($config['experience_costs'])) {
            $formData['experience'] = $config['experience_costs'][0]['key'];
        }

        return $this->render('page/unit_template_form.html.twig', [
            'template' => $unitTemplate,
            'config' => $config,
            'errors' => $errors,
            'form_data' => $formData,
            'other_points' => $otherPoints,
            'build_id' => $existingBuild?->getId(),
        ]);
    }

    private function buildTemplateConfig(UnitTemplate $unitTemplate): ?array
    {
        if ($unitTemplate->getName() !== 'Platoon Commander') {
            return null;
        }

        return [
            'composition' => '1 Officer',
            'weapons' => ['Rifle'],
            'special_rules' => [
                'Tank Hunters (if anti-tank grenades taken)',
                'For King and Country (if taken)',
                'Intelligence (if taken)',
            ],
            'experience_costs' => [
                ['key' => 'Inexperienced', 'label' => 'Inexperienced', 'cost' => 21],
                ['key' => 'Regular', 'label' => 'Regular', 'cost' => 30],
                ['key' => 'Veteran', 'label' => 'Veteran', 'cost' => 39],
            ],
            'options' => [
                [
                    'type' => 'number',
                    'label' => 'Add up to 5 men with rifles at +7pts each (Inexperienced)',
                    'name' => 'extra_inexperienced',
                    'max' => 5,
                    'cost_per' => 7,
                    'restricted_to' => 'Inexperienced',
                ],
                [
                    'type' => 'number',
                    'label' => 'Add up to 5 men with rifles at +10pts each (Regular)',
                    'name' => 'extra_regular',
                    'max' => 5,
                    'cost_per' => 10,
                    'restricted_to' => 'Regular',
                ],
                [
                    'type' => 'number',
                    'label' => 'Add up to 5 men with rifles at +13pts each (Veteran)',
                    'name' => 'extra_veteran',
                    'max' => 5,
                    'cost_per' => 13,
                    'restricted_to' => 'Veteran',
                ],
                [
                    'type' => 'select',
                    'label' => 'Officer may replace rifle with submachine gun',
                    'name' => 'officer_smg',
                    'options' => [
                        ['value' => 'regular', 'label' => '+4pts (Regular)', 'cost' => 4],
                        ['value' => 'commando', 'label' => '+3pts (Commando)', 'cost' => 3],
                    ],
                ],
                [
                    'type' => 'select',
                    'label' => 'Any man may replace rifle with submachine gun',
                    'name' => 'men_smg',
                    'options' => [
                        ['value' => 'regular', 'label' => '+4pts each (Regular)', 'cost' => 4],
                        ['value' => 'commando', 'label' => '+3pts each (Commando)', 'cost' => 3],
                    ],
                ],
                [
                    'type' => 'checkbox',
                    'label' => 'Officer may take pistol (-1pt)',
                    'name' => 'officer_pistol',
                    'cost' => -1,
                ],
                [
                    'type' => 'checkbox',
                    'label' => 'Any man may take pistol (-1pt each)',
                    'name' => 'men_pistol',
                    'cost' => -1,
                    'per_model' => true,
                    'max_per_model' => 5,
                ],
                [
                    'type' => 'checkbox',
                    'label' => 'Intelligence training (+50pts, unlocks up to 5 additional men)',
                    'name' => 'intelligence',
                    'cost' => 50,
                ],
                [
                    'type' => 'select',
                    'label' => 'Special training',
                    'name' => 'special_training',
                    'options' => [
                        ['value' => 'guards', 'label' => 'Guards (+1pt per man)', 'cost_per_model' => 1],
                        ['value' => 'airborne', 'label' => 'Airborne (Veteran, Stubborn +1pt per man)', 'cost_per_model' => 1],
                        ['value' => 'chindits', 'label' => 'Chindits (+2pt per man)', 'cost_per_model' => 2],
                    ],
                ],
            ],
        ];
    }

    private function handleUnitBuildSubmission(
        UnitTemplate $unitTemplate,
        ?array $config,
        array $data,
        EntityManagerInterface $entityManager,
        ?UnitBuild $existingBuild = null
    ): array|bool
    {
        if (!$config) {
            return ['This unit template cannot be configured yet.'];
        }

        $experience = $data['experience'] ?? null;
        $experienceDefinitions = $config['experience_costs'] ?? [];
        $allowedExperiences = [];
        $experienceCosts = [];
        foreach ($experienceDefinitions as $definition) {
            $allowedExperiences[] = $definition['key'];
            $experienceCosts[$definition['key']] = $definition['cost'];
        }

        if (!$experience || !in_array($experience, $allowedExperiences, true)) {
            return ['Please choose a valid experience level.'];
        }

        $errors = [];
        $payloadOptions = [];
        $user = $this->getUser();

        if (!$user instanceof \App\Entity\User) {
            return ['You must be logged in to save a unit.'];
        }

        $total = $experienceCosts[$experience];

        foreach ($config['options'] as $option) {
            $name = $option['name'];

            if ($option['type'] === 'number') {
                $value = (int)($data[$name] ?? 0);
                $value = max(0, min($option['max'], $value));

                if (!empty($option['restricted_to']) && $option['restricted_to'] !== $experience && $value > 0) {
                    $errors[] = sprintf('Only %s platoons can add %s.', strtolower($option['restricted_to']), strtolower($option['label']));
                }

                $total += $value * (int)$option['cost_per'];
                $payloadOptions[$name] = $value;
                continue;
            }

            if ($option['type'] === 'checkbox') {
                $checked = isset($data[$name]);
                if ($checked) {
                    if (!empty($option['per_model'])) {
                        $total += (int)$option['cost'] * (int)($option['max_per_model'] ?? 0);
                    } else {
                        $total += (int)$option['cost'];
                    }
                }
                $payloadOptions[$name] = $checked;
                continue;
            }

            if ($option['type'] === 'select') {
                $selected = $data[$name] ?? 'none';
                $payloadOptions[$name] = $selected;

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
                    $errors[] = sprintf('Invalid value provided for %s.', strtolower($option['label']));
                    continue;
                }

                if (isset($choice['cost'])) {
                    $total += (int)$choice['cost'];
                }

                if (isset($choice['cost_per_model'])) {
                    $total += (int)$choice['cost_per_model'] * 5;
                }
            }
        }

        if ($errors) {
            return $errors;
        }

        $build = $existingBuild ?? (new UnitBuild())->setOwner($user)->setUnitTemplate($unitTemplate);
        $build->setExperience($experience)
            ->setConfiguration([
                'experience' => $experience,
                'options' => $payloadOptions,
            ])
            ->setTotalCost($total);

        $entityManager->persist($build);
        $entityManager->flush();

        return true;
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
            !$user instanceof \App\Entity\User
            || $unitBuild->getOwner()->getId() !== $user->getId()
        ) {
            throw $this->createAccessDeniedException();
        }

        $token = $request->request->get('_token');
        if (!$csrfTokenManager->isTokenValid(new CsrfToken('delete_unit_build_'.$unitBuild->getId(), $token))) {
            throw $this->createAccessDeniedException('Invalid token.');
        }

        $divisionId = $unitBuild->getUnitTemplate()->getDivision()->getId();

        $entityManager->remove($unitBuild);
        $entityManager->flush();

        $this->addFlash('success', 'Unit removed.');

        return $this->redirectToRoute('app_division_slots', ['id' => $divisionId]);
    }
}

