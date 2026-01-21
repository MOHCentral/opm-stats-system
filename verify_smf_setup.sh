#!/bin/bash
# Verify SMF Setup Script

echo "=== SMF MOHAA Stats Setup Verification ==="
echo ""

echo "1. Checking symlinks in /var/www/smf/Sources..."
ls -la /var/www/smf/Sources/ | grep -i mohaa

echo ""
echo "2. Checking template symlinks..."
ls -la /var/www/smf/Themes/default/ | grep -E "MohaaBattle|MohaaPrediction|MohaaPlayerComparison|MohaaTournamentEnhanced"

echo ""
echo "3. Verifying handler functions in MohaaStats.php..."
grep -E "^function MohaaStats_(Achievements|Predictions|Comparison|BattlesList|BattleDetail)" /var/www/smf/Sources/MohaaStats/MohaaStats.php

echo ""
echo "4. Checking menu routes..."
grep -B 1 -A 4 "'sa=achievements'" /var/www/smf/Sources/MohaaStats/MohaaStats.php | head -6
grep -B 1 -A 4 "'sa=predictions'" /var/www/smf/Sources/MohaaStats/MohaaStats.php | head -6
grep -B 1 -A 4 "'sa=comparison'" /var/www/smf/Sources/MohaaStats/MohaaStats.php | head -6
grep -B 1 -A 4 "'sa=battles'" /var/www/smf/Sources/MohaaStats/MohaaStats.php | head -6

echo ""
echo "5. Checking template map..."
grep -A 20 "templateMap = \[" /var/www/smf/Sources/MohaaStats/MohaaStats.php | grep -E "(achievements|predictions|comparison|battles)"

echo ""
echo "6. Checking subActions..."
grep -A 25 "subActions = \[" /var/www/smf/Sources/MohaaStats/MohaaStats.php | grep -E "(achievements|predictions|comparison|battles)"

echo ""
echo "=== ALL CHECKS COMPLETE ==="
echo "If all sections show results, the setup is correct!"
echo ""
echo "Test URLs:"
echo "  Battles:     http://localhost:8888/index.php?action=mohaastats;sa=battles"
echo "  Achievements: http://localhost:8888/index.php?action=mohaastats;sa=achievements"
echo "  Predictions:  http://localhost:8888/index.php?action=mohaastats;sa=predictions"
echo "  Comparison:   http://localhost:8888/index.php?action=mohaastats;sa=comparison"
