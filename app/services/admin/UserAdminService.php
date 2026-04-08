<?php

declare(strict_types=1);

class UserAdminService
{
    public function buildManageUsersData(array $query): array
    {
        return $this->adminService()->buildManageUsersData($query);
    }

    public function createAdminAccount(string $name, string $email, string $password, string $role): bool
    {
        return $this->adminService()->createAdminAccount($name, $email, $password, $role);
    }

    public function buildManageRelationshipsData(array $query): array
    {
        return $this->adminService()->buildManageRelationshipsData($query);
    }

    public function removeRelationship(int $followerId, int $followingId): bool
    {
        return $this->adminService()->removeRelationship($followerId, $followingId);
    }

    public function updateRelationshipLock(int $targetUserId, string $mode, ?int $days, ?string $reason): bool
    {
        return $this->adminService()->updateRelationshipLock($targetUserId, $mode, $days, $reason);
    }

    private function adminService(): AdminService
    {
        require_once APPROOT . '/app/services/AdminService.php';
        return new AdminService();
    }
}