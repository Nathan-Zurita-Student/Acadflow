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

    // Preços por ciclo. O anual dá "2 meses grátis" (paga 10 meses, usa 12).
    'plans' => [

        'free' => [
            'name'        => 'Gratuito',
            'description' => 'Para começar a organizar seu primeiro grupo.',
            'prices' => [
                'monthly' => 0.0,
                'annual'  => 0.0,
            ],
            'limits' => [
                'projects'      => 2,   // projetos que o usuário pode CRIAR (ser dono)
                'members'       => 4,   // membros por grupo
                'ai_per_month'  => 5,   // usos de IA por mês
            ],
        ],

        'pro' => [
            'name'        => 'Pro',
            'description' => 'Para grupos que levam os trabalhos a sério.',
            'prices' => [
                'monthly' => 12.90,
                'annual'  => 129.00, // 12,90 × 10 (2 meses grátis)
            ],
            'limits' => [
                'projects'      => 10,
                'members'       => 15,
                'ai_per_month'  => 50,
            ],
        ],

        'ultra' => [
            'name'        => 'Ultra Pro',
            'description' => 'Sem limites. Para quem vive de projetos.',
            'prices' => [
                'monthly' => 39.90,
                'annual'  => 399.00, // 39,90 × 10 (2 meses grátis)
            ],
            'limits' => [
                'projects'      => null,
                'members'       => null,
                'ai_per_month'  => null,
            ],
        ],

    ],
];
