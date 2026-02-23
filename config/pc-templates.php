<?php

/**
 * Project Canvas - Block Definitions & Guiding Questions
 */
return [
    'block_types' => [
        'project_goal' => [
            'label' => 'Project Goal',
            'description' => 'The overarching objective and purpose of the project.',
            'position' => 1,
            'guiding_questions' => [
                'What is the main goal of this project?',
                'What problem does this project solve?',
                'How does this project align with organizational strategy?',
                'What does success look like?',
            ],
        ],
        'scope' => [
            'label' => 'Scope',
            'description' => 'What is included in and excluded from the project.',
            'position' => 2,
            'guiding_questions' => [
                'What deliverables are included?',
                'What is explicitly out of scope?',
                'What are the key constraints (time, budget, quality)?',
                'What assumptions are we making?',
            ],
        ],
        'stakeholders' => [
            'label' => 'Stakeholders',
            'description' => 'Key people and groups affected by or influencing the project.',
            'position' => 3,
            'guiding_questions' => [
                'Who are the project sponsors?',
                'Who are the end users / beneficiaries?',
                'Who needs to be consulted or informed?',
                'Who has decision-making authority?',
            ],
        ],
        'risks' => [
            'label' => 'Risks',
            'description' => 'Potential threats and uncertainties that could impact the project.',
            'position' => 4,
            'guiding_questions' => [
                'What could go wrong?',
                'What external factors could impact the project?',
                'What dependencies exist?',
                'What is the impact and likelihood of each risk?',
            ],
        ],
        'milestones' => [
            'label' => 'Milestones',
            'description' => 'Key dates and deliverables marking project progress.',
            'position' => 5,
            'guiding_questions' => [
                'What are the key deadlines?',
                'What deliverables mark each phase?',
                'What are the decision gates?',
                'When is the final delivery date?',
            ],
        ],
        'resources' => [
            'label' => 'Resources',
            'description' => 'People, tools, and capabilities needed for the project.',
            'position' => 6,
            'guiding_questions' => [
                'What team members are needed?',
                'What skills and expertise are required?',
                'What tools and infrastructure are needed?',
                'Are there external resources or vendors involved?',
            ],
        ],
        'budget' => [
            'label' => 'Budget',
            'description' => 'Financial planning and cost tracking for the project.',
            'position' => 7,
            'guiding_questions' => [
                'What is the total budget?',
                'How is the budget allocated across phases?',
                'What are the major cost drivers?',
                'Is there a contingency reserve?',
            ],
        ],
        'communication' => [
            'label' => 'Communication',
            'description' => 'How project information is shared with stakeholders.',
            'position' => 8,
            'guiding_questions' => [
                'How often are status updates provided?',
                'What communication channels are used?',
                'Who receives which information?',
                'How are decisions documented and communicated?',
            ],
        ],
        'governance' => [
            'label' => 'Governance',
            'description' => 'Decision-making structures and escalation paths.',
            'position' => 9,
            'guiding_questions' => [
                'Who makes which decisions?',
                'What is the escalation path?',
                'How are changes to scope managed?',
                'What approval processes exist?',
            ],
        ],
    ],
];
