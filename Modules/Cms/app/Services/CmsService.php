<?php

namespace Modules\Cms\Services;

use Modules\Cms\Models\CmsPage;
use Modules\Cms\Models\CmsBlock;
use Modules\Cms\Models\CmsMenu;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CmsService
{
    // ─── Pages ─────────────────────────────────────────────────────

    public function getPublishedPages()
    {
        return CmsPage::published()->ordered()->get();
    }

    public function getPageBySlug(string $slug): CmsPage
    {
        return CmsPage::published()->where('slug', $slug)->firstOrFail();
    }

    public function createPage(array $data): CmsPage
    {
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['title']);
        }
        if (empty($data['published_at']) && $data['is_published'] ?? false) {
            $data['published_at'] = now();
        }
        return CmsPage::create($data);
    }

    public function updatePage(CmsPage $page, array $data): CmsPage
    {
        $page->update($data);
        return $page->fresh();
    }

    public function deletePage(CmsPage $page): void
    {
        $page->delete();
    }

    // ─── Blocks ────────────────────────────────────────────────────

    public function getActiveBlocks()
    {
        return Cache::remember('cms.blocks.active', 3600, function () {
            return CmsBlock::active()->ordered()->get();
        });
    }

    public function getBlockBySlug(string $slug): ?CmsBlock
    {
        return Cache::remember("cms.block.{$slug}", 3600, function () use ($slug) {
            return CmsBlock::active()->where('slug', $slug)->first();
        });
    }

    public function getBlocksByType(string $type)
    {
        return CmsBlock::active()->byType($type)->ordered()->get();
    }

    public function createBlock(array $data): CmsBlock
    {
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }
        Cache::tags(['cms.blocks'])->flush();
        return CmsBlock::create($data);
    }

    public function updateBlock(CmsBlock $block, array $data): CmsBlock
    {
        $block->update($data);
        Cache::tags(['cms.blocks'])->flush();
        return $block->fresh();
    }

    public function deleteBlock(CmsBlock $block): void
    {
        $block->delete();
        Cache::tags(['cms.blocks'])->flush();
    }

    // ─── Menus ─────────────────────────────────────────────────────

    public function getActiveMenuByLocation(string $location): ?CmsMenu
    {
        return Cache::remember("cms.menu.{$location}", 3600, function () use ($location) {
            return CmsMenu::active()->byLocation($location)->first();
        });
    }

    public function getAllMenus()
    {
        return CmsMenu::active()->get();
    }

    public function createMenu(array $data): CmsMenu
    {
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }
        if (empty($data['items'])) {
            $data['items'] = [];
        }
        Cache::tags(['cms.menus'])->flush();
        return CmsMenu::create($data);
    }

    public function updateMenu(CmsMenu $menu, array $data): CmsMenu
    {
        $menu->update($data);
        Cache::tags(['cms.menus'])->flush();
        return $menu->fresh();
    }

    public function deleteMenu(CmsMenu $menu): void
    {
        $menu->delete();
        Cache::tags(['cms.menus'])->flush();
    }
}
