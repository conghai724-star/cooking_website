<?php

declare(strict_types=1);

class NotificationController extends Controller
{
    public function manageNotifications(): void
    {
        require_admin_permission('admin.notifications.manage');

        /** @var NotificationAdminService $service */
        $service = $this->service('admin/NotificationAdminService');
        $data = $service->buildManageNotificationsData($_GET);

        $this->adminView('admin/notifications/index', $data);
    }

    public function sendSystemNotification(): void
    {
        require_admin_permission('admin.notifications.manage');

        /** @var NotificationAdminService $service */
        $service = $this->service('admin/NotificationAdminService');
        $notice = $service->sendSystemNotification($_POST, current_admin());

        $this->redirect('/admin/notifications?notice=' . $notice);
    }
}