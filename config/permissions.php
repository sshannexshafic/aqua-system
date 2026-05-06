<?php
$permissions = [
    'admin' => ['all' => true],
    'farmer' => [
        'monitoring' => ['read', 'write'],
        'health' => ['read', 'write'],
        'marketplace' => ['read', 'write', 'limited'],
        'reports' => ['read']
    ],
    'vet' => [
        'health' => ['read', 'write'],
        'farmers' => ['read'],
        'reports' => ['read']
    ]
];

function hasPermission($role, $module, $action) {
    global $permissions;
    if ($permissions[$role]['all'] ?? false) return true;
    return $permissions[$role][$module][$action] ?? false;
}
?>