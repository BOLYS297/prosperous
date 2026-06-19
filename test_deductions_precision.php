<?php
// Script de test pour vérifier la précision des déductions salariales à la minute près

$testCases = [
    ['minutes' => 30, 'hourlyAmount' => 1000, 'expected' => 500],   // 30 min
    ['minutes' => 60, 'hourlyAmount' => 1000, 'expected' => 1000],  // 1h
    ['minutes' => 90, 'hourlyAmount' => 1000, 'expected' => 1500],  // 1h30
    ['minutes' => 171, 'hourlyAmount' => 1000, 'expected' => 2850], // 2h51 (cas de l'utilisateur)
    ['minutes' => 180, 'hourlyAmount' => 1000, 'expected' => 3000], // 3h
    ['minutes' => 45, 'hourlyAmount' => 600, 'expected' => 450],    // 45 min avec 600 FCFA/heure
    ['minutes' => 171, 'hourlyAmount' => 600, 'expected' => 1710],  // 2h51 avec 600 FCFA/heure
];

echo "=== TEST CALCUL DÉDUCTIONS À LA MINUTE PRÈS ===\n\n";

foreach ($testCases as $test) {
    $minutesLate = $test['minutes'];
    $hourlyAmount = $test['hourlyAmount'];
    $expected = $test['expected'];

    // Ancien calcul (avec arrondi à l'heure supérieure)
    $hoursLateOld = (int) ceil($minutesLate / 60);
    $deductionAmountOld = (int) ($hoursLateOld * $hourlyAmount);

    // Nouveau calcul (précis à la minute)
    $deductionAmountNew = (int) round(($minutesLate / 60.0) * $hourlyAmount);
    $hoursLate = intdiv($minutesLate, 60);
    $minutesRemaining = $minutesLate % 60;

    $status = $deductionAmountNew === $expected ? "✅" : "❌";

    echo "Retard: {$hoursLate}h{$minutesRemaining}min ({$minutesLate} min) | Tarif: {$hourlyAmount} FCFA/h\n";
    echo "  Ancien calcul: {$deductionAmountOld} FCFA (arrondi à {$hoursLateOld}h)\n";
    echo "  Nouveau calcul: {$deductionAmountNew} FCFA {$status}\n";
    echo "  Attendu: {$expected} FCFA\n\n";
}

echo "=== FIN TEST ===\n";
