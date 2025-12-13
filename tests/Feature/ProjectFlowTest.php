<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Offer;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_accept_offer_and_pay_from_wallet(): void
    {
        $client = User::factory()->create(['role' => 'client']);
        $client->wallet()->create(['balance' => 200]);

        $freelancer = User::factory()->create(['role' => 'freelancer']);
        $freelancer->wallet()->create();

        $category = Category::factory()->create();
        $project = Project::create([
            'client_id' => $client->id,
            'category_id' => $category->id,
            'title' => 'Test Project',
            'description' => 'Desc',
            'budget' => 100,
            'status' => 'open',
        ]);

        $offer = Offer::create([
            'project_id' => $project->id,
            'freelancer_id' => $freelancer->id,
            'amount' => 80,
            'delivery_days' => 5,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($client, 'sanctum')
            ->postJson("/api/projects/{$project->id}/offers/{$offer->id}/accept");

        $response->assertOk();
        $this->assertEquals('in_progress', $project->fresh()->status);
        $this->assertEquals(120, $client->wallet->fresh()->balance);
        $this->assertEquals('accepted', $offer->fresh()->status);
    }

    public function test_only_freelancer_can_submit_offer(): void
    {
        $client = User::factory()->create(['role' => 'client']);
        $client->wallet()->create();

        $category = Category::factory()->create();
        $project = Project::create([
            'client_id' => $client->id,
            'category_id' => $category->id,
            'title' => 'Test Project',
            'description' => 'Desc',
            'budget' => 100,
            'status' => 'open',
        ]);

        $response = $this->actingAs($client, 'sanctum')
            ->postJson("/api/projects/{$project->id}/offers", [
                'amount' => 50,
                'delivery_days' => 3,
            ]);

        $response->assertStatus(403);
    }
}

