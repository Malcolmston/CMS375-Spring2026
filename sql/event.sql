DROP EVENT IF EXISTS clean_log_month;

CREATE EVENT clean_log_month
ON SCHEDULE EVERY 1 MONTH
DO
BEGIN
    DELETE FROM logs
    WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 MONTH)
      AND severity <= 2;
END;
