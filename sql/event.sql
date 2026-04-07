DROP EVENT IF EXISTS clean_log_month;

CREATE EVENT clean_log_month
ON SCHEDULE EVERY 1 MONTH
DO
BEGIN
    DELETE FROM logs
    WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 MONTH)
      AND severity <= 2;
END;

DROP EVENT IF EXISTS expire_prescriptions_daily;

CREATE EVENT expire_prescriptions_daily
ON SCHEDULE EVERY 1 DAY
DO
BEGIN
    UPDATE prescription
    SET status = 'expired'
    WHERE expire_date < CURDATE()
      AND status IN ('active', 'partially filled');
END;

DROP EVENT IF EXISTS cancel_no_show_visits;

CREATE EVENT cancel_no_show_visits
ON SCHEDULE EVERY 2 HOUR
DO
BEGIN
    UPDATE visit
    SET status = 'cancelled'
    WHERE scheduled_at < DATE_SUB(NOW(), INTERVAL 30 MINUTE)
      AND status IN ('SCHEDULED', 'NO_SHOW');
END;

DROP EVENT IF EXISTS remove_adult_guardianship;

CREATE EVENT remove_adult_guardianship
ON SCHEDULE EVERY 1 DAY
DO
BEGIN
    DELETE pr FROM parent_relationship pr
    JOIN users u ON u.id = pr.patient_id
    WHERE u.age >= 21
      AND pr.deleted_at IS NULL;
END;

DROP EVENT IF EXISTS alert_low_stock;

CREATE EVENT alert_low_stock
ON SCHEDULE EVERY 1 DAY
DO
BEGIN
    INSERT INTO logs (user_id, action, table_name, record_id, new_data)
    SELECT
        NULL,
        'FAILED',
        'medicine',
        id,
        JSON_OBJECT(
            'generic_name', generic_name,
            'brand_name', brand_name,
            'stock_quantity', stock_quantity,
            'alert', 'Low stock warning'
        )
    FROM medicine
    WHERE stock_quantity <= 10
      AND controlled_substance = FALSE;

    INSERT INTO logs (user_id, action, table_name, record_id, new_data)
    SELECT
        NULL,
        'FAILED',
        'medicine',
        id,
        JSON_OBJECT(
            'generic_name', generic_name,
            'brand_name', brand_name,
            'stock_quantity', stock_quantity,
            'alert', 'Low stock warning - controlled substance'
        )
    FROM medicine
    WHERE stock_quantity <= 20
      AND controlled_substance = TRUE;
END;

-- ============================================================
-- Clean expired password reset tokens
-- ============================================================
DROP EVENT IF EXISTS clean_expired_password_tokens;

CREATE EVENT clean_expired_password_tokens
ON SCHEDULE EVERY 1 HOUR
DO
BEGIN
    DELETE FROM password_reset_tokens
    WHERE expires_at < NOW();
END;
