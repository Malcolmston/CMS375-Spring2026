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

