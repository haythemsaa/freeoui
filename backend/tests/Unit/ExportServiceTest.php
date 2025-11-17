<?php

namespace Tests\Unit;

use App\Services\ExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class ExportServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ExportService $exportService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->exportService = new ExportService();
    }

    /** @test */
    public function it_exports_data_to_csv()
    {
        $data = collect([
            ['id' => 1, 'name' => 'Test', 'value' => 100],
            ['id' => 2, 'name' => 'Test 2', 'value' => 200],
        ]);

        $headers = [
            'id' => 'ID',
            'name' => 'Name',
            'value' => 'Value',
        ];

        $filename = 'test_export.csv';

        $path = $this->exportService->toCSV($data, $headers, $filename);

        $this->assertFileExists($path);

        // Clean up
        if (file_exists($path)) {
            unlink($path);
        }
    }

    /** @test */
    public function it_formats_values_correctly()
    {
        $reflection = new \ReflectionClass($this->exportService);
        $method = $reflection->getMethod('formatValue');
        $method->setAccessible(true);

        // Test boolean
        $this->assertEquals('Oui', $method->invoke($this->exportService, true));
        $this->assertEquals('Non', $method->invoke($this->exportService, false));

        // Test null
        $this->assertEquals('', $method->invoke($this->exportService, null));

        // Test string
        $this->assertEquals('test', $method->invoke($this->exportService, 'test'));
    }

    /** @test */
    public function it_generates_unique_filenames()
    {
        $data = collect([['id' => 1]]);
        $headers = ['id' => 'ID'];

        $path1 = $this->exportService->toCSV($data, $headers, 'test1.csv');
        sleep(1);
        $path2 = $this->exportService->toCSV($data, $headers, 'test2.csv');

        $this->assertNotEquals($path1, $path2);

        // Clean up
        if (file_exists($path1)) unlink($path1);
        if (file_exists($path2)) unlink($path2);
    }
}
