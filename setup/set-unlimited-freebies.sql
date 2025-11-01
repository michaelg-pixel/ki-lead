-- Quick-Fix: Unbegrenzte Freebies für michael.gllluska@gmail.com

-- Schritt 1: Customer-ID finden
SELECT id, name, email FROM users WHERE email = 'michael.gllluska@gmail.com';

-- Schritt 2: Unbegrenztes Limit setzen (999 = praktisch unbegrenzt)
-- WICHTIG: Ersetze 'CUSTOMER_ID' mit der tatsächlichen ID aus Schritt 1

INSERT INTO customer_freebie_limits (customer_id, freebie_limit, product_id, product_name)
VALUES (
    (SELECT id FROM users WHERE email = 'michael.gllluska@gmail.com'),
    999,
    'UNLIMITED_ADMIN',
    'Unlimited (Admin gesetzt)'
)
ON DUPLICATE KEY UPDATE 
    freebie_limit = 999,
    product_id = 'UNLIMITED_ADMIN',
    product_name = 'Unlimited (Admin gesetzt)',
    updated_at = NOW();

-- Überprüfen
SELECT 
    u.name, 
    u.email, 
    cfl.freebie_limit,
    cfl.product_name
FROM customer_freebie_limits cfl
JOIN users u ON cfl.customer_id = u.id
WHERE u.email = 'michael.gllluska@gmail.com';
