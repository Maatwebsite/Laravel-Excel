<?php

namespace Maatwebsite\Excel\Tests\Concerns;

use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\RememberChunkOffset;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithChunkOffset;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Tests\TestCase;

class RememberChunkOffsetTest extends TestCase
{
    /**
     * @test
     */
    public function can_set_and_get_chunk_offset()
    {
        $import = new class implements WithChunkOffset {
            use Importable;
            use RememberChunkOffset;
        };

        $import->setChunkOffset(50);

        $this->assertEquals(50, $import->getChunkOffset());
    }

    /**
     * @test
     */
    public function can_access_chunk_offset_on_import_to_array_in_chunks()
    {
        $import = new class implements ToArray, WithChunkReading {
            use Importable;
            use RememberChunkOffset;

            public $offsets = [];

            public function array(array $array)
            {
                $this->offsets[] = $this->getChunkOffset();
            }

            public function chunkSize(): int
            {
                return 2000;
            }
        };

        $import->import('import-batches.xlsx');

        $this->assertEquals([1, 2001, 4001], $import->offsets);
    }
}