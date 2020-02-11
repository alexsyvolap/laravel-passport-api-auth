<?php

namespace Tests\Feature\Auth;

use App\Entities\User;
use App\Enums\UserRoles;
use Laravel\Passport\Passport;
use Tests\TestCase;

class LoginTest extends TestCase
{
    private $user;
    private $admin_route;
    private $subscriber_route;
    private $user_information_route;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = factory(User::class)->make();
        $this->admin_route = route('v1.admin.testRoute');
        $this->subscriber_route = route('v1.subscriber.testRoute');
        $this->user_information_route = route('v1.auth.user.information');
    }

    /**
     * Testing subscriber routes forbidden
     *
     * @return void
     */
    public function test_subscriber_sign_up()
    {
        Passport::actingAs(
            $this->user,
            [UserRoles::getInstance(UserRoles::Subscriber)->key]
        );

        $response = $this->get($this->subscriber_route);
        $response->assertStatus(200);
        $response = $this->get($this->admin_route);
        $response->assertStatus(403);

        $this->user->role = UserRoles::Administrator;
        Passport::actingAs(
            $this->user,
            [UserRoles::getInstance(UserRoles::Administrator)->key]
        );
        $response = $this->get($this->admin_route);
        $response->assertStatus(200);
        $response = $this->get($this->subscriber_route);
        $response->assertStatus(403);
    }

    /**
     * Testing user information
     *
     * @return void
     */
    public function test_auth_user_information()
    {
        Passport::actingAs(
            $this->user,
            [UserRoles::getInstance(UserRoles::Administrator)->key]
        );

        $response = $this->get($this->user_information_route);
        $response->assertJson($this->user->jsonSerialize());
    }
}
