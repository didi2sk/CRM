<?php
// Zoznam zalohovanych entit: nazov => cesta za /{account_id}/
// !!! NAZVY ENDPOINTOV SU ODHAD - over a uprav podla Postman dokumentacie Caflou API.
// 'data_key' (volitelne): kluc v JSON odpovedi, pod ktorym je pole zaznamov.
return [
    'companies' => ['path' => 'companies'],
    'contacts'  => ['path' => 'contacts'],
    'projects'  => ['path' => 'projects'],
    'tasks'     => ['path' => 'tasks'],
    'invoices'  => ['path' => 'invoices'],
    'users'     => ['path' => 'users'],
];
