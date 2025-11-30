<?php

namespace App\Controller;

use App\Entity\Faction;
use App\Entity\Division;
use App\Repository\DivisionRepository;
use App\Repository\FactionRepository;
use App\Repository\UnitTemplateRepository;
use App\Entity\UnitTemplate;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

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
    public function divisionSlots(Division $division, UnitTemplateRepository $unitTemplateRepository): Response
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

        return $this->render('page/division_slots.html.twig', [
            'division' => $division,
            'slots' => $grouped,
        ]);
    }

    #[Route('/app/unit-template/{id}', name: 'app_unit_template_form', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function unitTemplate(UnitTemplate $unitTemplate): Response
    {
        $config = $this->buildTemplateConfig($unitTemplate);

        return $this->render('page/unit_template_form.html.twig', [
            'template' => $unitTemplate,
            'config' => $config,
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
                ['label' => 'Inexperienced', 'cost' => 21],
                ['label' => 'Regular', 'cost' => 30],
                ['label' => 'Veteran', 'cost' => 39],
            ],
            'options' => [
                [
                    'type' => 'number',
                    'label' => 'Add up to 5 men with rifles at +7pts each (Inexperienced)',
                    'name' => 'extra_inexperienced',
                    'max' => 5,
                    'cost_per' => 7,
                ],
                [
                    'type' => 'number',
                    'label' => 'Add up to 5 men with rifles at +10pts each (Regular)',
                    'name' => 'extra_regular',
                    'max' => 5,
                    'cost_per' => 10,
                ],
                [
                    'type' => 'number',
                    'label' => 'Add up to 5 men with rifles at +13pts each (Veteran)',
                    'name' => 'extra_veteran',
                    'max' => 5,
                    'cost_per' => 13,
                ],
                [
                    'type' => 'select',
                    'label' => 'Officer may replace rifle with submachine gun',
                    'name' => 'officer_smg',
                    'options' => [
                        ['label' => '+4pts (Regular)', 'cost' => 4],
                        ['label' => '+3pts (Commando)', 'cost' => 3],
                    ],
                ],
                [
                    'type' => 'select',
                    'label' => 'Any man may replace rifle with submachine gun',
                    'name' => 'men_smg',
                    'options' => [
                        ['label' => '+4pts each (Regular)', 'cost' => 4],
                        ['label' => '+3pts each (Commando)', 'cost' => 3],
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
                        ['label' => 'Guards (+1pt per man)', 'cost_per_model' => 1],
                        ['label' => 'Airborne (Veteran, Stubborn +1pt per man)', 'cost_per_model' => 1],
                        ['label' => 'Chindits (+2pt per man)', 'cost_per_model' => 2],
                    ],
                ],
            ],
        ];
    }
}

