<?php

namespace Tests\Unit;

use App\Helpers\ResponseHelper;
use Tests\TestCase;

class ResponseHelperTest extends TestCase
{
    /** @test */
    public function it_returns_success_response()
    {
        $data = ['key' => 'value'];
        $response = ResponseHelper::success($data, 'Success message');

        $this->assertEquals(200, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);

        $this->assertTrue($content['success']);
        $this->assertEquals('Success message', $content['message']);
        $this->assertEquals($data, $content['data']);
    }

    /** @test */
    public function it_returns_error_response()
    {
        $response = ResponseHelper::error('Error message', 400);

        $this->assertEquals(400, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);

        $this->assertFalse($content['success']);
        $this->assertEquals('Error message', $content['message']);
    }

    /** @test */
    public function it_returns_validation_error_response()
    {
        $errors = [
            'field1' => ['Field 1 is required'],
            'field2' => ['Field 2 must be an email'],
        ];

        $response = ResponseHelper::validationError($errors);

        $this->assertEquals(422, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);

        $this->assertFalse($content['success']);
        $this->assertEquals($errors, $content['errors']);
    }

    /** @test */
    public function it_returns_unauthorized_response()
    {
        $response = ResponseHelper::unauthorized();

        $this->assertEquals(401, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);

        $this->assertFalse($content['success']);
        $this->assertEquals('Unauthorized', $content['message']);
    }

    /** @test */
    public function it_returns_forbidden_response()
    {
        $response = ResponseHelper::forbidden();

        $this->assertEquals(403, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);

        $this->assertFalse($content['success']);
    }

    /** @test */
    public function it_returns_not_found_response()
    {
        $response = ResponseHelper::notFound('Resource not found');

        $this->assertEquals(404, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);

        $this->assertFalse($content['success']);
        $this->assertEquals('Resource not found', $content['message']);
    }
}
