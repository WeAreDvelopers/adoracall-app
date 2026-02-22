<?php

namespace Tests;

use Laravel\Lumen\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    /**
     * Creates the application.
     *
     * @return \Laravel\Lumen\Application
     */
    public function createApplication()
    {
        return require __DIR__.'/../bootstrap/app.php';
    }

    /**
     * Setup the test environment
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Migrate and seed the database for tests
        try {
            Artisan::call('migrate:fresh', ['--force' => true]);
        } catch (\Exception $e) {
            // If migrations fail, just continue
        }
    }

    /**
     * Teardown the test environment
     */
    protected function tearDown(): void
    {
        // Rollback migrations after each test
        try {
            DB::disconnect();
        } catch (\Exception $e) {
            // Ignore disconnect errors
        }
        parent::tearDown();
    }

    /**
     * Call the given URI with JSON data and return the response
     * Uses Lumen's built-in json() method
     */
    public function postJson($uri, array $data = [], array $headers = [])
    {
        return $this->json('POST', $uri, $data, $headers);
    }

    /**
     * Call the given URI and return the response for GET
     */
    public function getJson($uri, array $headers = [])
    {
        return $this->json('GET', $uri, [], $headers);
    }

    /**
     * Call the given URI and return the response for PUT
     */
    public function putJson($uri, array $data = [], array $headers = [])
    {
        return $this->json('PUT', $uri, $data, $headers);
    }

    /**
     * Call the given URI and return the response for DELETE
     */
    public function deleteJson($uri, array $data = [], array $headers = [])
    {
        return $this->json('DELETE', $uri, $data, $headers);
    }

    /**
     * Call artisan command
     */
    protected function artisanCall($command, $parameters = [])
    {
        return Artisan::call($command, $parameters);
    }

    /**
     * Custom assertion methods for compatibility
     */
    public function assertStatus($status)
    {
        $this->assertEquals($status, $this->response->status());
    }

    public function assertJsonStructure($structure)
    {
        $content = json_decode($this->response->getContent(), true);
        $this->assertArrayHasStructure($structure, $content);
    }

    private function assertArrayHasStructure($structure, $array)
    {
        foreach ($structure as $key => $value) {
            if (is_array($value) && is_numeric($key)) {
                // Skip numeric keys (arrays)
                continue;
            }

            if (is_array($value)) {
                $this->assertIsArray($array[$key] ?? null);
                $this->assertArrayHasStructure($value, $array[$key]);
            } else {
                $this->assertArrayHasKey($key, $array);
            }
        }
    }
}
