<?php

namespace Maatwebsite\Excel;

use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Illuminate\Filesystem\FilesystemManager;
use Maatwebsite\Excel\Concerns\ToCollection;
use PhpOffice\PhpSpreadsheet\Reader\IReader;
use Maatwebsite\Excel\Concerns\CustomValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use Maatwebsite\Excel\Exceptions\UnreadableFileException;

class Reader
{
    use DelegatedMacroable, HasEventBus;

    /**
     * @var Spreadsheet
     */
    protected $spreadsheet;

    /**
     * @var FilesystemManager
     */
    private $filesystem;

    /**
     * @param FilesystemManager $filesystem
     */
    public function __construct(FilesystemManager $filesystem)
    {
        $this->filesystem = $filesystem;

        $this->tmpPath = config('excel.exports.temp_path', sys_get_temp_dir());

        $this->setDefaultValueBinder();
    }

    /**
     * @param object      $import
     * @param string      $filePath
     * @param string|null $disk
     * @param string|null $readerType
     *
     * @return bool
     */
    public function read($import, string $filePath, string $disk = null, string $readerType = null)
    {
        if ($import instanceof CustomValueBinder) {
            Cell::setValueBinder($import);
        }

        $file = $this->copyToFileSystem($filePath, $disk);

        $reader = $this->getReader($file, $readerType);

        $this->spreadsheet = $reader->load($file);

        if ($import instanceof ToCollection) {
            $import->collection($this->toCollection());
        }

        return $this;
    }

    /**
     * @return array
     */
    public function toArray($nullValue = null, $calculateFormulas = true, $formatData = true, $returnCellRef = false)
    {
        $sheets = [];
        foreach ($this->spreadsheet->getAllSheets() as $sheet) {
            $sheets[] = $sheet->toArray($nullValue, $calculateFormulas, $formatData, $returnCellRef);
        }

        return $sheets;
    }

    /**
     * @return array
     */
    public function toCollection($nullValue = null, $calculateFormulas = true, $formatData = true, $returnCellRef = false)
    {
        $sheets = new Collection();
        foreach ($this->spreadsheet->getAllSheets() as $sheet) {
            $sheets->push(new Collection(array_map(function (array $row) {
                return new Collection($row);
            }, $sheet->toArray($nullValue, $calculateFormulas, $formatData, $returnCellRef))));
        }

        return $sheets;
    }

    /**
     * @return object
     */
    public function getDelegate()
    {
        return $this->spreadsheet;
    }

    /**
     * @return $this
     */
    public function setDefaultValueBinder()
    {
        Cell::setValueBinder(new DefaultValueBinder);

        return $this;
    }

    /**
     * @param string      $filePath
     * @param string|null $disk
     *
     * @return string
     */
    protected function copyToFileSystem(string $filePath, string $disk = null)
    {
        $tempFilePath = $this->getTmpFile($filePath);
        $tmpStream    = fopen($tempFilePath, 'w+');

        $file = $this->filesystem->disk($disk)->readStream($filePath);

        stream_copy_to_stream($file, $tmpStream);
        fclose($tmpStream);

        return $tempFilePath;
    }

    /**
     * @param string|null $readerType
     * @param string      $tmp
     *
     * @return IReader
     */
    protected function getReader(string $filePath, string $readerType = null): IReader
    {
        $readerType = $readerType ?? IOFactory::identify($filePath);
        $reader     = IOFactory::createReader($readerType);

        if (!$reader->canRead($filePath)) {
            throw new UnreadableFileException;
        }

        return $reader;
    }

    /**
     * @param string $filePath
     *
     * @return string
     */
    protected function getTmpFile(string $filePath): string
    {
        $tmp = $this->tmpPath . DIRECTORY_SEPARATOR . str_random(16) . '.' . pathinfo($filePath)['extension'];

        return $tmp;
    }
}
