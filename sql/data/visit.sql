-- Visit/Appointment data
-- Assign to institution IDs 1-10 based on the institution.sql data

-- Appointments for patient1 (John Doe) at Baptist Medical Center (id 6)
INSERT INTO visit (patient_id, institution_id, visit_type, scheduled_at, status, reason) VALUES
(1, 6, 'CHECKUP', '2026-04-15 09:00:00', 'SCHEDULED', 'Annual physical examination'),
(1, 6, 'FOLLOW_UP', '2026-04-20 14:30:00', 'SCHEDULED', 'Follow-up on blood pressure readings'),
(1, 8, 'SPECIALIST', '2026-04-25 11:00:00', 'SCHEDULED', 'Pediatric consultation for child Emma');

-- Appointments for patient2 (Emily Chen) at Pediatric Practice (id 8)
INSERT INTO visit (patient_id, institution_id, visit_type, scheduled_at, status, reason) VALUES
(2, 8, 'CHECKUP', '2026-04-16 10:00:00', 'SCHEDULED', 'Wellness checkup'),
(2, 8, 'LAB', '2026-04-18 08:00:00', 'SCHEDULED', 'Blood work - annual panel');

-- Appointments for patient3 (Michael Brown) at Baptist Medical Center (id 6)
INSERT INTO visit (patient_id, institution_id, visit_type, scheduled_at, status, reason) VALUES
(3, 6, 'EMERGENCY', '2026-04-14 16:00:00', 'COMPLETED', 'Chest pain evaluation'),
(3, 6, 'FOLLOW_UP', '2026-04-21 09:30:00', 'SCHEDULED', 'Post-emergency follow-up');

-- Appointments for child1 (Emma Doe) at Pediatric Practice (id 8)
INSERT INTO visit (patient_id, institution_id, visit_type, scheduled_at, status, reason) VALUES
(6, 8, 'CHECKUP', '2026-04-17 11:00:00', 'SCHEDULED', 'Annual pediatric checkup'),
(6, 8, 'LAB', '2026-04-22 09:00:00', 'SCHEDULED', 'Routine blood work');

-- Appointments for child2 (Jack Doe) at Pediatric Practice (id 8)
INSERT INTO visit (patient_id, institution_id, visit_type, scheduled_at, status, reason) VALUES
(7, 8, 'CHECKUP', '2026-04-18 14:00:00', 'SCHEDULED', '5-year wellness visit'),
(7, 8, 'THERAPY', '2026-04-23 10:00:00', 'SCHEDULED', 'Physical therapy session');

-- Completed visits
INSERT INTO visit (patient_id, institution_id, visit_type, scheduled_at, status, reason, notes) VALUES
(1, 6, 'CHECKUP', '2026-03-15 09:00:00', 'COMPLETED', 'Previous annual physical', 'All vitals normal'),
(3, 6, 'LAB', '2026-03-20 08:00:00', 'COMPLETED', 'Lipid panel', 'Results within normal range'),
(2, 8, 'CHECKUP', '2026-01-10 10:00:00', 'COMPLETED', 'Wellness checkup', 'Healthy, no concerns');