<?php
// Normalizovane tabulky: entita => ['table' => nazov, 'columns' => [stlpec => [typ, kluc v JSON, pretypovanie]]]
// Pretypovanie: int | string | bool | decimal | datetime | json
// Casy sa ukladaju v UTC (Caflou ich posiela s offsetom, napr. +02:00).
// Stlpec `id` je vzdy primarny kluc. Stlpce `deleted_in_source` a `synced_at` sa doplnaju automaticky.
$tables = [
    'companies' => [
        'table' => 'companies',
        'columns' => [
            'id'                      => ['BIGINT UNSIGNED NOT NULL', 'id', 'int'],
            'name'                    => ['VARCHAR(255) NULL', 'name', 'string'],
            'alias'                   => ['VARCHAR(255) NULL', 'alias', 'string'],
            'description'             => ['TEXT NULL', 'description', 'string'],
            'email'                   => ['VARCHAR(255) NULL', 'email', 'string'],
            'phone'                   => ['VARCHAR(64) NULL', 'phone', 'string'],
            'website'                 => ['VARCHAR(255) NULL', 'website', 'string'],
            'website_url'             => ['VARCHAR(512) NULL', 'website_url', 'string'],
            'street'                  => ['VARCHAR(255) NULL', 'street', 'string'],
            'zip'                     => ['VARCHAR(32) NULL', 'zip', 'string'],
            'city'                    => ['VARCHAR(255) NULL', 'city', 'string'],
            'country'                 => ['VARCHAR(8) NULL', 'country', 'string'],
            'id_num'                  => ['VARCHAR(32) NULL', 'id_num', 'string'],          // ICO
            'tax_num'                 => ['VARCHAR(32) NULL', 'tax_num', 'string'],         // DIC
            'id_tax_num'              => ['VARCHAR(32) NULL', 'id_tax_num', 'string'],      // IC DPH
            'another_identifier'      => ['VARCHAR(255) NULL', 'another_identifier', 'string'],
            'vat_payer'               => ['TINYINT(1) NULL', 'vat_payer', 'bool'],
            'kind'                    => ['VARCHAR(64) NULL', 'kind', 'string'],
            'person'                  => ['VARCHAR(255) NULL', 'person', 'string'],
            'get_person'              => ['VARCHAR(255) NULL', 'get_person', 'string'],
            'record_reason'           => ['TEXT NULL', 'record_reason', 'string'],
            'company_status_id'       => ['BIGINT UNSIGNED NULL', 'company_status_id', 'int'],
            'company_phase_id'        => ['BIGINT UNSIGNED NULL', 'company_phase_id', 'int'],
            'company_type_id'         => ['BIGINT UNSIGNED NULL', 'company_type_id', 'int'],
            'user_id'                 => ['BIGINT UNSIGNED NULL', 'user_id', 'int'],
            'target_user_id'          => ['BIGINT UNSIGNED NULL', 'target_user_id', 'int'],
            'user_ids'                => ['JSON NULL', 'user_ids', 'json'],
            'tags'                    => ['JSON NULL', 'tags', 'json'],
            'potential_deal_value'    => ['DECIMAL(18,2) NULL', 'potential_deal_value', 'decimal'],
            'potential_deal_currency' => ['VARCHAR(8) NULL', 'potential_deal_currency', 'string'],
            'estimated_deal_success'  => ['DECIMAL(6,2) NULL', 'estimated_deal_success', 'decimal'],
            'weighted_trade_value'    => ['DECIMAL(18,2) NULL', 'weighted_trade_value', 'decimal'],
            'closing_date'            => ['DATETIME NULL', 'closing_date', 'datetime'],
            'next_event'              => ['JSON NULL', 'next_event', 'json'],
            'last_event'              => ['JSON NULL', 'last_event', 'json'],
            'event_date_diff'         => ['VARCHAR(64) NULL', 'event_date_diff', 'string'],
            'archived'                => ['TINYINT(1) NULL', 'archived', 'bool'],
            'trash'                   => ['TINYINT(1) NULL', 'trash', 'bool'],
            'trashed_at'              => ['DATETIME NULL', 'trashed_at', 'datetime'],
            'teams_created'           => ['TINYINT(1) NULL', 'teams_created', 'bool'],
            'slack_created'           => ['TINYINT(1) NULL', 'slack_created', 'bool'],
            'peppol_id_present'       => ['TINYINT(1) NULL', 'peppol_id_present', 'bool'],
            'following'               => ['TINYINT(1) NULL', 'following', 'bool'],
            'comment_email'           => ['VARCHAR(255) NULL', 'comment_email', 'string'],
            'privacy'                 => ['JSON NULL', 'privacy', 'json'],
            'url'                     => ['VARCHAR(512) NULL', 'url', 'string'],
            'created_at'              => ['DATETIME NULL', 'created_at', 'datetime'],
            'updated_at'              => ['DATETIME NULL', 'updated_at', 'datetime'],
            'last_update'             => ['DATETIME NULL', 'last_update', 'datetime'],
        ],
        'indexes' => ['id_num', 'name', 'trash'],
    ],
];

// Doklady: ponuky, objednavky, zalohove faktury, faktury, dodacie listy... (rozlisuje `kind`).
// Nazov stlpca = kluc v JSON. Polozky dokladu (riadky) vzorka neobsahuje - riesime samostatne.
$S = fn(int $n = 255) => "VARCHAR($n) NULL";
$spec = [
    'id' => ['BIGINT UNSIGNED NOT NULL', 'int'],
    'kind' => [$S(32), 'string'], 'global_kind' => [$S(32), 'string'],
    'number' => [$S(64), 'string'], 'counter' => ['INT NULL', 'int'],
    'name' => [$S(), 'string'], 'name_with_number' => [$S(512), 'string'],
    'payment_type' => [$S(32), 'string'],
    'numeric_row_id' => ['BIGINT UNSIGNED NULL', 'int'], 'numeric_row_name' => [$S(), 'string'],
    'numeric_row_format' => [$S(64), 'string'],
    'bank_account_id' => ['BIGINT UNSIGNED NULL', 'int'], 'user_id' => ['BIGINT UNSIGNED NULL', 'int'],
    'description' => ['MEDIUMTEXT NULL', 'string'], 'text_before' => ['MEDIUMTEXT NULL', 'string'],
    'text_after' => ['MEDIUMTEXT NULL', 'string'], 'note' => ['TEXT NULL', 'string'],
    'record_reason' => ['TEXT NULL', 'string'],
    'date_of_issue' => ['DATE NULL', 'date'], 'date_of_tax' => ['DATE NULL', 'date'],
    'date_of_payment' => ['DATE NULL', 'date'], 'date_of_validity' => ['DATE NULL', 'date'],
    'payment_date' => ['DATE NULL', 'date'], 'signature_date' => ['DATE NULL', 'date'],
    'variable_symbol' => [$S(64), 'string'], 'constant_symbol' => [$S(64), 'string'],
    'number_of_order' => [$S(64), 'string'], 'vendor_number' => [$S(64), 'string'],
    'currency' => [$S(8), 'string'], 'locale' => [$S(16), 'string'],
    'exchange_rate' => ['DECIMAL(18,6) NULL', 'decimal'],
    'manual_exchange_rate' => ['TINYINT(1) NULL', 'bool'], 'show_exchange_rate' => ['TINYINT(1) NULL', 'bool'],
    'total_cache' => ['DECIMAL(18,2) NULL', 'decimal'], 'vat_cache' => ['DECIMAL(18,2) NULL', 'decimal'],
    'total_vat_cache' => ['DECIMAL(18,2) NULL', 'decimal'], 'native_vat_cache' => ['DECIMAL(18,2) NULL', 'decimal'],
    'native_total_cache' => ['DECIMAL(18,2) NULL', 'decimal'],
    'native_total_vat_cache' => ['DECIMAL(18,2) NULL', 'decimal'],
    'amount_due' => ['DECIMAL(18,2) NULL', 'decimal'], 'total_manual' => ['DECIMAL(18,2) NULL', 'decimal'],
    'vat_manual' => ['DECIMAL(18,2) NULL', 'decimal'], 'profit_cache' => ['DECIMAL(18,2) NULL', 'decimal'],
    'paid' => ['TINYINT(1) NULL', 'bool'], 'income' => ['TINYINT(1) NULL', 'bool'],
    'outcome' => ['TINYINT(1) NULL', 'bool'], 'reverse_charge' => ['TINYINT(1) NULL', 'bool'],
    'autorounding' => ['TINYINT(1) NULL', 'bool'], 'rounding_type' => [$S(32), 'string'],
    'project_id' => ['BIGINT UNSIGNED NULL', 'int'], 'task_id' => ['BIGINT UNSIGNED NULL', 'int'],
    'project_name' => [$S(), 'string'], 'project_url' => [$S(512), 'string'],
    'invoice_status_id' => ['BIGINT UNSIGNED NULL', 'int'], 'invoice_status_name' => [$S(), 'string'],
    'user_name' => [$S(), 'string'], 'company_name' => [$S(), 'string'],
    'repeater_id' => ['BIGINT UNSIGNED NULL', 'int'], 'template' => ['TINYINT(1) NULL', 'bool'],
    'concept' => ['TINYINT(1) NULL', 'bool'], 'approved' => ['TINYINT(1) NULL', 'bool'],
    'tax_receipt' => ['TINYINT(1) NULL', 'bool'], 'storno' => ['TINYINT(1) NULL', 'bool'],
    'transfers' => ['TINYINT(1) NULL', 'bool'], 'is_locked' => ['TINYINT(1) NULL', 'bool'],
    'has_reminders' => ['TINYINT(1) NULL', 'bool'], 'following' => ['TINYINT(1) NULL', 'bool'],
    'email_status' => [$S(64), 'string'], 'esign_status' => [$S(64), 'string'], 'sign_status' => [$S(64), 'string'],
    'signed_pdf_url' => [$S(1024), 'string'], 'pdf_url' => [$S(1024), 'string'], 'url' => [$S(512), 'string'],
    'delivery_address_id' => ['BIGINT UNSIGNED NULL', 'int'],
    'online_payment_account_id' => ['BIGINT UNSIGNED NULL', 'int'],
    'allow_online_payment' => ['TINYINT(1) NULL', 'bool'], 'peppol_import' => ['TINYINT(1) NULL', 'bool'],
    'invoice_company_uuid' => [$S(64), 'string'],
    'trash' => ['TINYINT(1) NULL', 'bool'], 'trashed_at' => ['DATETIME NULL', 'datetime'],
    'created_at' => ['DATETIME NULL', 'datetime'], 'updated_at' => ['DATETIME NULL', 'datetime'],
    'tags' => ['JSON NULL', 'json'], 'privacy' => ['JSON NULL', 'json'],
    'invoice_ids' => ['JSON NULL', 'json'], 'proforma_ids' => ['JSON NULL', 'json'],
    'offer_ids' => ['JSON NULL', 'json'], 'order_ids' => ['JSON NULL', 'json'],
    'delivery_ids' => ['JSON NULL', 'json'], 'tax_receipt_ids' => ['JSON NULL', 'json'],
    'storno_ids' => ['JSON NULL', 'json'], 'invoice_message_ids' => ['JSON NULL', 'json'],
];
// dodavatel (from_*) a odberatel (to_*)
foreach (['from', 'to'] as $p) {
    $spec += [
        "{$p}_company_id" => ['BIGINT UNSIGNED NULL', 'int'], "{$p}_company_name" => [$S(), 'string'],
        "{$p}_street" => [$S(), 'string'], "{$p}_zip" => [$S(32), 'string'], "{$p}_city" => [$S(), 'string'],
        "{$p}_country" => [$S(8), 'string'], "{$p}_id_num" => [$S(32), 'string'],
        "{$p}_tax_num" => [$S(32), 'string'], "{$p}_id_tax_num" => [$S(32), 'string'],
        "{$p}_email" => [$S(), 'string'], "{$p}_phone" => [$S(64), 'string'],
        "{$p}_website" => [$S(512), 'string'], "{$p}_file_number" => [$S(), 'string'],
        "{$p}_first_name" => [$S(), 'string'], "{$p}_last_name" => [$S(), 'string'],
        "{$p}_issuer_name" => [$S(), 'string'], "{$p}_vat_payer" => ['TINYINT(1) NULL', 'bool'],
    ];
}
$spec += [
    'to_peppol_id' => [$S(), 'string'], 'to_company_url' => [$S(512), 'string'],
    // vlastne stlpce z Caflou (custom_column_*) sa zbieraju do jedneho JSON-u
    'custom_fields' => ['JSON NULL', 'prefix_json:custom_column_'],
];
$cols = [];
foreach ($spec as $name => [$type, $cast]) {
    $cols[$name] = [$type, $name, $cast];
}
$tables['invoices'] = [
    'table' => 'invoices',
    'columns' => $cols,
    'indexes' => ['kind', 'number', 'project_id', 'to_company_id', 'date_of_issue', 'trash'],
];

// Projekty
$P = [
    'id' => ['BIGINT UNSIGNED NOT NULL', 'int'],
    'name' => [$S(), 'string'], 'description' => ['MEDIUMTEXT NULL', 'string'],
    'priority' => [$S(32), 'string'], 'record_reason' => ['TEXT NULL', 'string'],
    'order_number' => [$S(64), 'string'], 'currency' => [$S(8), 'string'],
    'planned_incomes' => ['DECIMAL(18,2) NULL', 'decimal'], 'native_planned_incomes' => ['DECIMAL(18,2) NULL', 'decimal'],
    'planned_expenses' => ['DECIMAL(18,2) NULL', 'decimal'], 'native_planned_expenses' => ['DECIMAL(18,2) NULL', 'decimal'],
    'planned_hours' => ['DECIMAL(12,2) NULL', 'decimal'], 'hours' => ['DECIMAL(12,2) NULL', 'decimal'],
    'planned_tracked_time' => ['DECIMAL(12,2) NULL', 'decimal'],
    'tracked_time_helper' => ['DECIMAL(12,2) NULL', 'decimal'], 'total_hours_helper' => ['DECIMAL(12,2) NULL', 'decimal'],
    'total_incomes_helper' => ['DECIMAL(18,2) NULL', 'decimal'], 'total_expenses_helper' => ['DECIMAL(18,2) NULL', 'decimal'],
    'earnings' => ['DECIMAL(18,2) NULL', 'decimal'], 'earnings_price' => ['DECIMAL(18,2) NULL', 'decimal'],
    'progress' => ['DECIMAL(8,2) NULL', 'decimal'], 'budget_progress' => ['DECIMAL(10,2) NULL', 'decimal'],
    'incomes_progress' => ['DECIMAL(10,2) NULL', 'decimal'], 'expenses_progress' => ['DECIMAL(10,2) NULL', 'decimal'],
    'hours_progress' => ['DECIMAL(10,2) NULL', 'decimal'],
    'get_progress' => [$S(64), 'string'], 'get_tasks_stats' => [$S(64), 'string'],
    'get_milestones_stats' => [$S(64), 'string'], 'get_companies_stats' => [$S(64), 'string'],
    'start_date' => ['DATE NULL', 'date'], 'end_date' => ['DATE NULL', 'date'],
    'finished' => ['TINYINT(1) NULL', 'bool'],
    'project_type_id' => ['BIGINT UNSIGNED NULL', 'int'], 'project_type_name' => [$S(), 'string'],
    'project_status_id' => ['BIGINT UNSIGNED NULL', 'int'], 'project_status_name' => [$S(), 'string'],
    'project_priority_id' => ['BIGINT UNSIGNED NULL', 'int'], 'project_priority_name' => [$S(), 'string'],
    'company_id' => ['BIGINT UNSIGNED NULL', 'int'], 'company_name' => [$S(), 'string'],
    'company_url' => [$S(512), 'string'], 'user_id' => ['BIGINT UNSIGNED NULL', 'int'],
    'company_ids' => ['JSON NULL', 'json'], 'user_ids' => ['JSON NULL', 'json'], 'task_ids' => ['JSON NULL', 'json'],
    'template' => ['TINYINT(1) NULL', 'bool'], 'inherited_template' => ['TINYINT(1) NULL', 'bool'],
    'repeatable' => ['TINYINT(1) NULL', 'bool'], 'repeater_id' => ['BIGINT UNSIGNED NULL', 'int'],
    'repeat_kind' => [$S(32), 'string'], 'repeat_count' => ['INT NULL', 'int'],
    'repeat_weekdays' => ['JSON NULL', 'json'], 'repeat_monthdays' => ['JSON NULL', 'json'],
    'repeat_end' => [$S(32), 'string'], 'repeat_end_date' => ['DATE NULL', 'date'],
    'repeat_end_count' => ['INT NULL', 'int'], 'repeat_from' => ['DATE NULL', 'date'],
    'address' => ['JSON NULL', 'json'],
    'trash' => ['TINYINT(1) NULL', 'bool'], 'trashed_at' => ['DATETIME NULL', 'datetime'],
    'teams_created' => ['TINYINT(1) NULL', 'bool'], 'slack_created' => ['TINYINT(1) NULL', 'bool'],
    'following' => ['TINYINT(1) NULL', 'bool'], 'comment_email' => [$S(), 'string'],
    'privacy' => ['JSON NULL', 'json'], 'tags' => ['JSON NULL', 'json'], 'url' => [$S(512), 'string'],
    'created_at' => ['DATETIME NULL', 'datetime'], 'updated_at' => ['DATETIME NULL', 'datetime'],
    'last_update' => ['DATETIME NULL', 'datetime'],
    'custom_fields' => ['JSON NULL', 'prefix_json:custom_column_'],
];
$cols = [];
foreach ($P as $name => [$type, $cast]) {
    $cols[$name] = [$type, $name, $cast];
}
$tables['projects'] = [
    'table' => 'projects',
    'columns' => $cols,
    'indexes' => ['company_id', 'project_status_id', 'start_date', 'trash'],
];

// Pouzivatelia (zamestnanci) Caflou
$U = [
    'id' => ['BIGINT UNSIGNED NOT NULL', 'int'],
    'name' => [$S(), 'string'], 'first_name' => [$S(), 'string'], 'last_name' => [$S(), 'string'],
    'short_name' => [$S(), 'string'], 'email' => [$S(), 'string'], 'avatar_url' => [$S(1024), 'string'],
    'fictive' => ['TINYINT(1) NULL', 'bool'], 'active' => ['TINYINT(1) NULL', 'bool'],
];
$cols = [];
foreach ($U as $name => [$type, $cast]) {
    $cols[$name] = [$type, $name, $cast];
}
$tables['users'] = [
    'table' => 'users',
    'columns' => $cols,
    'indexes' => ['email', 'active'],
];

return $tables;
