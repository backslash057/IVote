-- Recalcule la ventilation financière des votes confirmés créés avant la
-- fonctionnalité de finance plateforme.
UPDATE Vote v
JOIN PlatformSetting s ON s.setting_key = 'platform_fee_rate'
SET
  v.platform_fee_rate = CAST(s.setting_value AS DECIMAL(5,2)),
  v.platform_fee_amount_fcfa = ROUND(v.amount_fcfa * CAST(s.setting_value AS DECIMAL(5,2)) / 100),
  v.organizer_net_amount_fcfa = v.amount_fcfa - ROUND(v.amount_fcfa * CAST(s.setting_value AS DECIMAL(5,2)) / 100)
WHERE v.payment_status = 'confirmed'
  AND v.amount_fcfa > 0
  AND v.platform_fee_amount_fcfa = 0
  AND v.organizer_net_amount_fcfa = 0;
