<?php
// Normalizovane tabulky: entita => ['table' => nazov, 'columns' => [stlpec => [typ, kluc v JSON, pretypovanie]]]
// Pretypovanie: int | string | bool | decimal | datetime | json
// Casy sa ukladaju v UTC (Caflou ich posiela s offsetom, napr. +02:00).
// Stlpec `id` je vzdy primarny kluc. Stlpce `deleted_in_source` a `synced_at` sa doplnaju automaticky.
return [
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
