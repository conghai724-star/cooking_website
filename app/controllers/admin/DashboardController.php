<?php

declare(strict_types=1);

final class DashboardController extends Controller
{
    public function index(): void
    {
        require_admin_permission('admin.dashboard.view');
        $this->adminView('admin/dashboard');
    }
}
