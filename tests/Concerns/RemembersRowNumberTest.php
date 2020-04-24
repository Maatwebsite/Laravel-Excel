<?php

namespace Maatwebsite\Excel\Tests\Concerns;

use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\RemembersRowNumber;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Tests\TestCase;

class RemembersRowNumberTest extends TestCase
{
    /**
     * @test
     */
    public function can_set_and_get_row_number()
    {
        $import = new class implements RemembersRowNumber {
            use Importable;

            public $rowNumber;

            public function setRowNumber(int $rowNumber)
            {
                $this->rowNumber = $rowNumber;
            }
        };

        $import->setRowNumber(50);

        $this->assertEquals(50, $import->rowNumber);
    }

    /**
     * @test
     */
    public function can_access_row_number_on_import_to_model()
    {
        $import = new class implements ToModel, RemembersRowNumber {
            use Importable;

            private $rowNumber;
            public $rowNumbers = [];

            public function model(array $row)
            {
                $this->rowNumbers[] = $this->rowNumber;
            }

            public function setRowNumber(int $rowNumber)
            {
                $this->rowNumber = $rowNumber;
            }
        };

        $import->import('import-batches.xlsx');

        $this->assertEquals([46, 47, 48, 49, 50, 51, 52, 53, 54, 55], array_slice($import->rowNumbers, 45, 10));
    }

    /**
     * @test
     */
    public function can_access_row_number_on_import_to_array_in_chunks()
    {
        $import = new class implements ToModel, WithChunkReading, RemembersRowNumber {
            use Importable;

            private $rowNumber;
            public $rowNumbers = [];

            public function chunkSize(): int
            {
                return 50;
            }

            public function model(array $row)
            {
                $this->rowNumbers[] = $this->rowNumber;
            }

            public function setRowNumber(int $rowNumber)
            {
                $this->rowNumber = $rowNumber;
            }
        };

        $import->import('import-batches.xlsx');

        $this->assertEquals([46, 47, 48, 49, 50, 51, 52, 53, 54, 55], array_slice($import->rowNumbers, 45, 10));
    }

    /**
     * @test
     */
    public function can_access_row_number_on_import_to_array_in_chunks_with_batch_inserts()
    {
        $import = new class implements ToModel, WithChunkReading, WithBatchInserts, RemembersRowNumber {
            use Importable;

            private $rowNumber;
            public $rowNumbers = [];

            public function chunkSize(): int
            {
                return 50;
            }

            public function model(array $row)
            {
                $this->rowNumbers[] = $this->rowNumber;
            }

            public function batchSize(): int
            {
                return 50;
            }

            public function setRowNumber(int $rowNumber)
            {
                $this->rowNumber = $rowNumber;
            }
        };

        $import->import('import-batches.xlsx');

        $this->assertEquals([46, 47, 48, 49, 50, 51, 52, 53, 54, 55], array_slice($import->rowNumbers, 45, 10));
    }
}