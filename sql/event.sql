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

DROP EVENT IF EXISTS alert_low_stock;

CREATE EVENT alert_low_stock
ON SCHEDULE EVERY 1 DAY
DO
BEGIN
    INSERT INTO logs (user_id, action, table_name, record_id, new_data)
    SELECT
        1,
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
        1,
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
