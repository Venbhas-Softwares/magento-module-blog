<?php

declare(strict_types=1);

namespace Venbhas\Blog\Plugin\Backend\Menu;

use Magento\Backend\Model\Menu;
use Magento\Backend\Model\Menu\Builder;
use Venbhas\Blog\Model\Config\ModuleEnabledGuard;

/**
 * Removes Blog admin menu entries when the module is disabled at default scope.
 */
class HideWhenDisabledPlugin
{
    /**
     * Menu item IDs registered by Venbhas_Blog.
     */
    private const BLOG_MENU_IDS = [
        'Venbhas_Blog::article',
        'Venbhas_Blog::article_manage',
        'Venbhas_Blog::category_manage',
        'Venbhas_Blog::comment_manage',
        'Venbhas_Blog::config',
    ];

    /**
     * @var ModuleEnabledGuard
     */
    private ModuleEnabledGuard $moduleEnabledGuard;

    /**
     * @param ModuleEnabledGuard $moduleEnabledGuard Module enabled guard
     */
    public function __construct(ModuleEnabledGuard $moduleEnabledGuard)
    {
        $this->moduleEnabledGuard = $moduleEnabledGuard;
    }

    /**
     * Strip blog menu items when the extension is disabled globally.
     *
     * @param Builder $subject Menu builder
     * @param Menu $menu Built menu
     *
     * @return Menu
     */
    public function afterGetResult(Builder $subject, Menu $menu): Menu
    {
        if ($this->moduleEnabledGuard->isEnabledForAdmin()) {
            return $menu;
        }

        foreach (self::BLOG_MENU_IDS as $menuId) {
            $menu->remove($menuId);
        }

        return $menu;
    }
}
