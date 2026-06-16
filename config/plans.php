<?php

/*
|--------------------------------------------------------------------------
| Planos de assinatura (AcadFlow)
|--------------------------------------------------------------------------
|
| Catálogo central dos planos. Para mudar preço ou limites de um plano,
| edite somente aqui. O valor `price` é o que será cobrado no ASAAS (em reais).
| Use `null` em qualquer limite para significar "ilimitado".
|
*/

return [

    // Quantos dias de tolerância manter o plano ativo após um pagamento vencer.
    'grace_days' => (int) env('PLAN_GRACE_DAYS', 3),

    'plans' => [

        'free' => [
            'name'        => 'Gratuito',
            'price'       => 0.0,
            'description' => 'Para começar a organizar seu primeiro grupo.',
            'limits' => [
                'projects'      => 2,   // projetos que o usuário pode CRIAR (ser dono)
                'members'       => 4,   // membros por grupo
                'ai_per_month'  => 5,   // usos de IA por mês
            ],
        ],

        'pro' => [
            'name'        => 'Pro',
            'price'       => 19.90,
            'description' => 'Para grupos que levam os trabalhos a sério.',
            'limits' => [
                'projects'      => 10,
                'members'       => 15,
                'ai_per_month'  => 100,
            ],
        ],

        'ultra' => [
            'name'        => 'Ultra Pro',
            'price'       => 39.90,
            'description' => 'Sem limites. Para quem vive de projetos.',
            'limits' => [
                'projects'      => null,
                'members'       => null,
                'ai_per_month'  => null,
            ],
        ],

    ],
];
