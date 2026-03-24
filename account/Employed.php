<?php

namespace account;

interface Employed
{
    /**
     * Link this employee to an institution with an optional start date.
     */
    //public function joinInstitution(int $institution_id, string $start_date): bool;

    /**
     * End employment at an institution by setting the end date and marking inactive.
     */
    //public function leaveInstitution(int $institution_id, string $end_date): bool;

    /**
     * Get all institutions this employee is currently active at.
     */
    //public function getMyInstitutions(): array;

    /**
     * Get all active employees at a given institution.
     */
    //public function getEmployeesByInstitution(int $institution_id): array;

    /**
     * Resolve the role of a staff member by email and employid
     * before the correct account class can be instantiated.
     */
    public static function resolveRole(string $email, string $employid): ?string;
}
