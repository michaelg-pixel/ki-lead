-- Neue Digistore24 Produkte hinzufügen
-- Starter, Pro und Business Abos

INSERT INTO digistore_products (
    product_id,
    product_name,
    product_type,
    own_freebies_limit,
    ready_freebies_count,
    referral_program_slots,
    is_active,
    created_at
) VALUES
-- Starter Abo
(
    '639494',
    'Starter Abo',
    'SUBSCRIPTION',
    4,
    0,
    1,
    1,
    NOW()
),
-- Pro Abo
(
    '639495',
    'Pro Abo',
    'SUBSCRIPTION',
    8,
    0,
    3,
    1,
    NOW()
),
-- Business Abo
(
    '639496',
    'Business Abo',
    'SUBSCRIPTION',
    20,
    0,
    10,
    1,
    NOW()
)
ON DUPLICATE KEY UPDATE
    product_name = VALUES(product_name),
    own_freebies_limit = VALUES(own_freebies_limit),
    ready_freebies_count = VALUES(ready_freebies_count),
    referral_program_slots = VALUES(referral_program_slots),
    is_active = VALUES(is_active),
    updated_at = NOW();
