<?php

namespace Modules\Cms\Tests\Feature;

use Tests\TestCase;
use Modules\Cms\Models\CmsPage;
use Modules\Cms\Models\CmsBlock;
use Modules\Cms\Models\CmsMenu;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CmsApiTest extends TestCase
{
    use RefreshDatabase;

    private CmsPage $publishedPage;
    private CmsBlock $block;
    private CmsMenu $menu;

    protected function setUp(): void
    {
        parent::setUp();

        $this->publishedPage = CmsPage::create([
            'title'       => 'About Us',
            'slug'        => 'about-us',
            'content'     => 'About our company',
            'excerpt'     => 'Learn about us',
            'is_published' => true,
            'published_at' => now()->subDay(),
            'order'       => 1,
        ]);

        CmsPage::create([
            'title'       => 'Draft Page',
            'slug'        => 'draft',
            'content'     => 'Not published',
            'is_published' => false,
            'order'       => 2,
        ]);

        $this->block = CmsBlock::create([
            'name'      => 'Footer About',
            'slug'      => 'footer-about',
            'type'      => 'html',
            'content'   => '<p>About us footer content</p>',
            'is_active' => true,
            'order'     => 1,
        ]);

        $this->menu = CmsMenu::create([
            'name'     => 'Main Header',
            'slug'     => 'main-header',
            'location' => 'header',
            'items'    => [
                ['label' => 'Home', 'url' => '/', 'children' => []],
                ['label' => 'Shop', 'url' => '/shop', 'children' => []],
            ],
            'is_active' => true,
        ]);
    }

    /** @test */
    public function public_can_list_published_pages(): void
    {
        $response = $this->getJson('/api/v1/cms/pages');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'about-us');
    }

    /** @test */
    public function public_can_view_page(): void
    {
        $response = $this->getJson('/api/v1/cms/pages/about-us');

        $response->assertOk()
            ->assertJsonPath('data.title', 'About Us')
            ->assertJsonPath('data.slug', 'about-us');
    }

    /** @test */
    public function public_cannot_view_draft_page(): void
    {
        $response = $this->getJson('/api/v1/cms/pages/draft');

        $response->assertStatus(404);
    }

    /** @test */
    public function public_can_list_active_blocks(): void
    {
        $response = $this->getJson('/api/v1/cms/blocks');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'footer-about');
    }

    /** @test */
    public function public_can_view_block_by_slug(): void
    {
        $response = $this->getJson('/api/v1/cms/blocks/footer-about');

        $response->assertOk()
            ->assertJsonPath('data.type', 'html');
    }

    /** @test */
    public function public_can_list_menus(): void
    {
        $response = $this->getJson('/api/v1/cms/menus');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function public_can_view_menu_by_location(): void
    {
        $response = $this->getJson('/api/v1/cms/menus/header');

        $response->assertOk()
            ->assertJsonPath('data.location', 'header')
            ->assertJsonCount(2, 'data.items');
    }

    /** @test */
    public function public_returns_404_for_nonexistent_block(): void
    {
        $response = $this->getJson('/api/v1/cms/blocks/nonexistent');

        $response->assertStatus(404);
    }

    /** @test */
    public function public_returns_404_for_nonexistent_menu_location(): void
    {
        $response = $this->getJson('/api/v1/cms/menus/nonexistent');

        $response->assertStatus(404);
    }

    /** @test */
    public function public_can_view_homepage(): void
    {
        $response = $this->getJson('/api/v1/cms/homepage');

        $response->assertOk()
            ->assertJsonStructure(['data' => ['seo', 'hero_banners', 'settings']]);
    }

    /** @test */
    public function public_can_view_settings(): void
    {
        $response = $this->getJson('/api/v1/cms/settings');

        $response->assertOk();
    }
}
