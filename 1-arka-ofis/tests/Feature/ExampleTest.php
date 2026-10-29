<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Misafir kullanıcı ana sayfadan giriş ekranına yönlendirilir.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $this->get('/')->assertRedirect('/giris');

        $this->get('/giris')->assertOk();
    }
}
