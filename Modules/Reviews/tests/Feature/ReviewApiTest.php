<?php

namespace Modules\Reviews\Tests\Feature;

use Tests\TestCase;
use Modules\Auth\Models\User;
use Modules\Product\Models\Product;;
use Modules\Catalog\Models\Category;
use Modules\Vendor\Models\Vendor;
use Modules\Orders\Models\Order;
use Modules\Orders\Models\OrderItem;
use Modules\Reviews\Models\Review;
use Modules\Reviews\Models\ReviewHelpfulVote;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ReviewApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $vendorUser;
    private Vendor $vendor;
    private Product $product;
    private Review $review;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'name'   => 'Reviewer',
            'email'  => 'reviewer@example.com',
            'status' => 'active',
        ]);

        $this->vendorUser = User::factory()->create([
            'name'   => 'Vendor User',
            'email'  => 'vendor-reviews@example.com',
            'status' => 'active',
        ]);

        $this->vendor = Vendor::factory()->create([
            'user_id'   => $this->vendorUser->id,
            'shop_name' => 'Review Shop',
            'slug'      => 'review-shop',
            'status'    => 'active',
        ]);

        $category = Category::factory()->create([
            'name' => 'Review Cat',
            'slug' => 'review-cat',
        ]);

        $this->product = Product::factory()->create([
            'vendor_id'   => $this->vendor->id,
            'category_id' => $category->id,
            'name'        => 'Test Product',
            'slug'        => 'test-product',
            'price'       => 100.00,
            'status'      => 'active',
        ]);

        $this->review = Review::create([
            'user_id'    => $this->user->id,
            'product_id' => $this->product->id,
            'rating'     => 4,
            'title'      => 'Great product',
            'body'       => 'Really enjoyed this product.',
            'status'     => 'approved',
        ]);
    }

    /** @test */
    public function public_can_view_product_reviews(): void
    {
        $response = $this->getJson("/api/v1/products/{$this->product->slug}/reviews");

        $response->assertOk()
            ->assertJsonStructure(['data', 'meta']);
    }

    /** @test */
    public function public_can_view_product_review_stats(): void
    {
        $response = $this->getJson("/api/v1/products/{$this->product->slug}/reviews/stats");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'average_rating', 'total_reviews', 'total_pending',
                    'total_rejected', 'verified_count', 'with_images_count', 'distribution',
                ],
            ]);
    }

    /** @test */
    public function authenticated_user_can_create_review(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/reviews', [
                'product_uuid' => $this->product->uuid,
                'rating'     => 5,
                'title'      => 'Amazing!',
                'body'       => 'This product is amazing!',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.rating', 5);
    }

    /** @test */
    public function user_can_view_their_own_reviews(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/reviews/mine');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function user_can_update_own_pending_review(): void
    {
        $pendingReview = Review::create([
            'user_id'    => $this->user->id,
            'product_id' => $this->product->id,
            'rating'     => 3,
            'body'       => 'Okay product.',
            'status'     => 'pending',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/reviews/{$pendingReview->uuid}", [
                'rating' => 4,
                'body'   => 'Updated: Better than I thought!',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.rating', 4);
    }

    /** @test */
    public function user_cannot_update_approved_review(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/reviews/{$this->review->uuid}", [
                'rating' => 5,
            ]);

        $response->assertStatus(400);
    }

    /** @test */
    public function user_cannot_update_others_review(): void
    {
        $otherUser = User::factory()->create(['email' => 'other@example.com', 'status' => 'active']);

        $pendingReview = Review::create([
            'user_id'    => $this->user->id,
            'product_id' => $this->product->id,
            'rating'     => 3,
            'body'       => 'Okay.',
            'status'     => 'pending',
        ]);

        $response = $this->actingAs($otherUser, 'sanctum')
            ->putJson("/api/v1/reviews/{$pendingReview->uuid}", [
                'body' => 'Hacked!',
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function user_can_delete_own_review(): void
    {
        $ownReview = Review::create([
            'user_id'    => $this->user->id,
            'product_id' => $this->product->id,
            'rating'     => 3,
            'body'       => 'Temporary review.',
            'status'     => 'pending',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/reviews/{$ownReview->uuid}");

        $response->assertOk();
        $this->assertSoftDeleted($ownReview);
    }

    /** @test */
    public function user_can_mark_review_helpful(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/reviews/{$this->review->uuid}/helpful");

        $response->assertOk()
            ->assertJsonPath('data.action', 'voted')
            ->assertJsonPath('data.count', 1);
    }

    /** @test */
    public function user_can_unmark_review_helpful(): void
    {
        // First mark as helpful
        $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/reviews/{$this->review->uuid}/helpful");

        // Then unmark
        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/reviews/{$this->review->uuid}/helpful");

        $response->assertOk()
            ->assertJsonPath('data.action', 'unvoted')
            ->assertJsonPath('data.count', 0);
    }

    /** @test */
    public function vendor_can_respond_to_review(): void
    {
        $response = $this->actingAs($this->vendorUser, 'sanctum')
            ->postJson("/api/v1/vendor/reviews/{$this->review->uuid}/respond", [
                'response' => 'Thank you for your feedback!',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.vendor_response', 'Thank you for your feedback!');
    }

    /** @test */
    public function non_vendor_cannot_respond_to_review(): void
    {
        $regularUser = User::factory()->create(['email' => 'regular@example.com', 'status' => 'active']);

        $response = $this->actingAs($regularUser, 'sanctum')
            ->postJson("/api/v1/vendor/reviews/{$this->review->uuid}/respond", [
                'response' => 'Spam response.',
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function vendor_can_list_reviews_for_their_products(): void
    {
        $response = $this->actingAs($this->vendorUser, 'sanctum')
            ->getJson('/api/v1/vendor/reviews');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function review_stats_returns_correct_distribution(): void
    {
        // Add a few more reviews with different ratings
        Review::create([
            'user_id'    => $this->user->id,
            'product_id' => $this->product->id,
            'rating'     => 5,
            'body'       => 'Excellent!',
            'status'     => 'approved',
        ]);
        Review::create([
            'user_id'    => $this->user->id,
            'product_id' => $this->product->id,
            'rating'     => 3,
            'body'       => 'Average.',
            'status'     => 'approved',
        ]);

        $response = $this->getJson("/api/v1/products/{$this->product->slug}/reviews/stats");

        $response->assertOk();
        $stats = $response->json('data');
        $this->assertEquals(3, $stats['total_reviews']);
        $this->assertEquals(1, $stats['distribution']['5']['count']);
        $this->assertEquals(1, $stats['distribution']['4']['count']);
        $this->assertEquals(1, $stats['distribution']['3']['count']);
    }

    /** @test */
    public function unauthenticated_user_cannot_create_review(): void
    {
        $response = $this->postJson('/api/v1/reviews', [
            'product_uuid' => $this->product->uuid,
            'rating'     => 5,
            'body'       => 'Unauthenticated!',
        ]);

        $response->assertStatus(401);
    }
}
