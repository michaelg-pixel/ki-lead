#!/bin/bash

###############################################################################
# ONE-CLICK INSTALLER
# Führt Setup aus und zeigt Ergebnisse
###############################################################################

echo "╔═══════════════════════════════════════════════════════╗"
echo "║                                                       ║"
echo "║   🚀 REFERRAL-SYSTEM ONE-CLICK INSTALLER            ║"
echo "║                                                       ║"
echo "╚═══════════════════════════════════════════════════════╝"
echo ""

# Wechsle ins richtige Verzeichnis
cd /home/lumisaas/public_html

# 1. Setup-Skript ausführbar machen
chmod +x scripts/setup-referral-system.sh
chmod +x scripts/test-referral-system.php

echo "📦 Schritt 1/3: Setup ausführen..."
bash scripts/setup-referral-system.sh

echo ""
echo "🧪 Schritt 2/3: System testen..."
php scripts/test-referral-system.php

echo ""
echo "📊 Schritt 3/3: Erste Daten generieren..."

# Erstelle initiale Stats für alle Customers
mysql -h localhost -u lumisaas52 -pI1zx1XdL1hrWd75yu57e lumisaas <<EOF
-- Initialisiere Stats für alle Customers
INSERT IGNORE INTO referral_stats (customer_id)
SELECT id FROM customers;

-- Initialisiere Rewards für alle Customers
INSERT IGNORE INTO referral_rewards (customer_id)
SELECT id FROM customers;

-- Zeige Ergebnis
SELECT 
    'Customers gesamt' as Metrik, 
    COUNT(*) as Anzahl 
FROM customers
UNION ALL
SELECT 
    'Referral aktiviert' as Metrik, 
    COUNT(*) as Anzahl 
FROM customers WHERE referral_enabled = 1
UNION ALL
SELECT 
    'Stats-Einträge' as Metrik, 
    COUNT(*) as Anzahl 
FROM referral_stats
UNION ALL
SELECT 
    'Klicks erfasst' as Metrik, 
    COUNT(*) as Anzahl 
FROM referral_clicks
UNION ALL
SELECT 
    'Conversions erfasst' as Metrik, 
    COUNT(*) as Anzahl 
FROM referral_conversions;
EOF

echo ""
echo "╔═══════════════════════════════════════════════════════╗"
echo "║                                                       ║"
echo "║   ✅ INSTALLATION ABGESCHLOSSEN                      ║"
echo "║                                                       ║"
echo "╚═══════════════════════════════════════════════════════╝"
echo ""
echo "🌐 ÖFFNE JETZT IN DEINEM BROWSER:"
echo ""
echo "   📊 Admin-Dashboard:"
echo "   https://app.mehr-infos-jetzt.de/admin/dashboard.php?section=referral-overview"
echo ""
echo "   📱 Customer-Dashboard:"
echo "   https://app.mehr-infos-jetzt.de/customer/dashboard.php"
echo ""
echo "   🔍 Erweiterte Analytics:"
echo "   https://app.mehr-infos-jetzt.de/admin/sections/referral-monitoring-extended.php"
echo ""
echo "🧪 TEST-LINK (nach Aktivierung im Dashboard):"
echo "   https://app.mehr-infos-jetzt.de/freebie.php?customer=1&ref=TEST123"
echo ""
echo "📝 LOGS VERFOLGEN:"
echo "   tail -f /home/lumisaas/logs/cron.log"
echo ""
echo "════════════════════════════════════════════════════════"
echo ""
