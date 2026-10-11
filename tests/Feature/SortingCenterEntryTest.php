<?php

namespace Tests\Feature;

use Tests\TestCase;

class SortingCenterEntryTest extends TestCase
{
    public function test_homepage_displays_the_sorting_center_login(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertViewIs('sorting-center-login');
    }

    public function test_registration_remains_available_on_its_route(): void
    {
        $this->get('/register')
            ->assertOk()
            ->assertViewIs('sorting-center-registration');
    }

    public function test_sorting_center_pages_use_their_matching_views(): void
    {
        $pages = [
            '/sorting-center/dashboard' => 'sorting-center.dashboard',
            '/sorting-center/pickup-requests' => 'sorting-center.pickup-requests',
            '/sorting-center/order-management' => 'sorting-center.order-management',
            '/sorting-center/delivery-management' => 'sorting-center.delivery-management',
            '/sorting-center/rider-assignment' => 'sorting-center.rider-assignment',
            '/sorting-center/delivery-monitoring' => 'sorting-center.delivery-monitoring',
            '/sorting-center/delivery-reports' => 'sorting-center.delivery-reports',
            '/sorting-center/messages' => 'sorting-center.messages',
        ];

        foreach ($pages as $uri => $view) {
            $this->get($uri)
                ->assertOk()
                ->assertViewIs($view);
        }
    }

    public function test_retired_sorting_center_pages_are_no_longer_routed(): void
    {
        $this->get('/sorting-center/riders')->assertNotFound();
        $this->get('/sorting-center/incoming-parcels')->assertNotFound();
        $this->get('/sorting-center/sorting')->assertNotFound();
        $this->get('/sorting-center/delivery-assignment')->assertNotFound();
        $this->get('/sorting-center/reports')->assertNotFound();
    }
}
