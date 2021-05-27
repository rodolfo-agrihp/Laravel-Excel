<?php

namespace Maatwebsite\Excel\Tests\Concerns;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Excel;
use Maatwebsite\Excel\Exceptions\NoFilePathGivenException;
use Maatwebsite\Excel\Tests\Data\Stubs\EmptyExport;
use Maatwebsite\Excel\Tests\Data\Stubs\UsersExport;
use Maatwebsite\Excel\Tests\TestCase;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportableTest extends TestCase
{
    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadLaravelMigrations(['--database' => 'testing']);
        $this->loadMigrationsFrom(dirname(__DIR__) . '/Data/Stubs/Database/Migrations');
    }

    /**
     * @test
     */
    public function no_filename_given_will_auto_generate_a_filename()
    {
        $export = new UsersExport();

        $response = $export->download();

        $this->assertEquals('attachment; filename=users-export.xlsx', $response->headers->get('Content-Disposition'));
    }

    /**
     * @test
     */
    public function needs_to_have_a_file_name_when_storing()
    {
        $this->expectException(NoFilePathGivenException::class);
        $this->expectExceptionMessage('A filepath needs to be passed in order to store the export');

        $export = new class
        {
            use Exportable;
        };

        $export->store();
    }

    /**
     * @test
     */
    public function needs_to_have_a_file_name_when_queuing()
    {
        $this->expectException(NoFilePathGivenException::class);
        $this->expectExceptionMessage('A filepath needs to be passed in order to store the export');

        $export = new class
        {
            use Exportable;
        };

        $export->queue();
    }

    /**
     * @test
     */
    public function responsables_auto_generated_a_file_name()
    {
        $export = new UsersExport();

        $response = $export->toResponse(new Request());

        $this->assertEquals('attachment; filename=users-export.xlsx', $response->headers->get('Content-Disposition'));
    }

    /**
     * @test
     */
    public function is_responsable()
    {
        $export = new class implements Responsable
        {
            use Exportable;

            protected $fileName = 'export.xlsx';
        };

        $this->assertInstanceOf(Responsable::class, $export);

        $response = $export->toResponse(new Request());

        $this->assertInstanceOf(BinaryFileResponse::class, $response);
    }

    /**
     * @test
     */
    public function can_have_customized_header()
    {
        $export   = new class
        {
            use Exportable;
        };
        $response = $export->download(
            'name.csv',
            Excel::CSV,
            [
                'Content-Type' => 'text/csv',
            ]
        );
        $this->assertEquals('text/csv', $response->headers->get('Content-Type'));
    }

    /**
     * @test
     */
    public function can_set_custom_headers_in_export_class()
    {
        $export   = new class
        {
            use Exportable;

            protected $fileName   = 'name.csv';
            protected $writerType = Excel::CSV;
            protected $headers    = [
                'Content-Type' => 'text/csv',
            ];
        };
        $response = $export->toResponse(request());

        $this->assertEquals('text/csv', $response->headers->get('Content-Type'));
    }

    /**
     * @test
     */
    public function can_get_raw_export_contents()
    {
        $export = new EmptyExport;

        $response = $export->raw(Excel::XLSX);

        $this->assertNotEmpty($response);
    }
}
