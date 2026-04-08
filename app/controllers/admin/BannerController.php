<?php

declare(strict_types=1);

final class BannerController extends Controller
{
    public function manage(): void
    {
        require_admin_permission('admin.banners.manage');

        /** @var BannerAdminService $service */
        $service = $this->service('admin/BannerAdminService');
        $data = $service->buildManageData($_GET);

        $this->adminView('admin/manage_banners', $data);
    }

    public function saveBanner(): void
    {
        require_admin_permission('admin.banners.manage');

        /** @var BannerAdminService $service */
        $service = $this->service('admin/BannerAdminService');
        $ok = $service->saveBanner($_POST, current_admin());

        $this->redirect('/admin/banners?notice=' . ($ok ? 'banner_saved' : 'banner_save_failed'));
    }

    public function saveFeatured(): void
    {
        require_admin_permission('admin.banners.manage');

        /** @var BannerAdminService $service */
        $service = $this->service('admin/BannerAdminService');
        $ok = $service->saveFeatured($_POST, current_admin());

        $this->redirect('/admin/banners?notice=' . ($ok ? 'featured_saved' : 'featured_save_failed'));
    }

    public function saveToday(): void
    {
        require_admin_permission('admin.banners.manage');

        /** @var BannerAdminService $service */
        $service = $this->service('admin/BannerAdminService');
        $result = $service->saveToday($_POST, current_admin());

        $forDate = (string) ($result['forDate'] ?? date('Y-m-d'));
        $ok = (bool) ($result['ok'] ?? false);
        $this->redirect('/admin/banners?for_date=' . urlencode($forDate) . '&notice=' . ($ok ? 'today_saved' : 'today_save_failed'));
    }
}