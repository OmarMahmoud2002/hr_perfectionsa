<?php

namespace App\Services\Excel;

use Closure;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Row;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterImport;
use Illuminate\Http\UploadedFile;

/**
 * قراءة ملف Excel وإرجاع صفوفه كمصفوفة
 */
class ExcelReaderService
{
    private const DEFAULT_CHUNK_SIZE = 1000;

    /**
     * قراءة ملف Excel وإرجاع جميع الصفوف
     *
     * @param  string  $filePath مسار الملف في Storage
     * @return array
     * @throws \Exception
     */
    public function readFromPath(string $filePath): array
    {
        try {
            $rows = [];

            $importer = new class ($rows) implements ToArray, WithCalculatedFormulas {
                public function __construct(private array &$rows) {}

                public function array(array $array): void
                {
                    $this->rows = $array;
                }
            };

            Excel::import($importer, $filePath, 'local');

            return $rows;
        } catch (\Exception $e) {
            Log::error('ExcelReaderService: فشل في قراءة الملف', [
                'path'    => $filePath,
                'message' => $e->getMessage(),
            ]);
            throw new \Exception("فشل في قراءة ملف Excel: " . $e->getMessage());
        }
    }

    /**
     * قراءة ملف مُرفَّع مباشرةً
     *
     * @param  UploadedFile  $file
     * @return array
     * @throws \Exception
     */
    public function readUploadedFile(UploadedFile $file): array
    {
        $ext = strtolower($file->getClientOriginalExtension());

        // تحديد نوع القارئ صراحةً بناءً على الامتداد
        // (Auto-detect يفشل أحياناً مع ملفات Excel 97-2003 على Windows)
        $readerType = match ($ext) {
            'xls'  => \Maatwebsite\Excel\Excel::XLS,
            'xlsx' => \Maatwebsite\Excel\Excel::XLSX,
            default => null,
        };

        try {
            $rows     = [];
            $importer = new class ($rows) implements ToArray, WithCalculatedFormulas {
                public function __construct(private array &$rows) {}

                public function array(array $array): void
                {
                    $this->rows = $array;
                }
            };

            Excel::import($importer, $file, null, $readerType);

            return $rows;

        } catch (\Exception $e) {
            // محاولة بديلة باستخدام PhpSpreadsheet مباشرةً (أكثر مرونة في اكتشاف التنسيق)
            Log::warning('ExcelReaderService: محاولة القراءة البديلة', [
                'name'    => $file->getClientOriginalName(),
                'error'   => $e->getMessage(),
            ]);

            try {
                return $this->readWithPhpSpreadsheet($file->getRealPath());
            } catch (\Exception $e2) {
                Log::error('ExcelReaderService: فشل في قراءة الملف المُرفَّع', [
                    'name'             => $file->getClientOriginalName(),
                    'message'          => $e->getMessage(),
                    'fallback_message' => $e2->getMessage(),
                ]);
                throw new \Exception("فشل في قراءة ملف Excel: " . $e->getMessage());
            }
        }
    }

    /**
     * قراءة مباشرة عبر PhpSpreadsheet مع اكتشاف تلقائي للتنسيق
     */
    private function readWithPhpSpreadsheet(string $realPath): array
    {
        $readerType  = \PhpOffice\PhpSpreadsheet\IOFactory::identify($realPath);
        $reader      = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($readerType);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($realPath);
        $rows        = [];

        foreach ($spreadsheet->getActiveSheet()->getRowIterator() as $row) {
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);
            $rowData = [];
            foreach ($cellIterator as $cell) {
                $rowData[] = $cell->getValue();
            }
            // تجاهل الصفوف الفارغة تماماً
            if (!empty(array_filter($rowData, fn($v) => $v !== null && $v !== ''))) {
                $rows[] = $rowData;
            }
        }

        return $rows;
    }

    /**
     * التحقق السريع من وجود الأعمدة المطلوبة (أول 5 أعمدة)
     */
    public function validateColumns(array $rows): bool
    {
        // ملف فارغ
        if (empty($rows)) {
            return false;
        }

        // يجب أن يكون هناك على الأقل صفان (Header + بيانات) أو صف واحد إن لم يكن هناك header
        return count($rows) >= 1;
    }

    /**
     * معالجة صفوف ملف Excel على دفعات صغيرة لتقليل استهلاك الذاكرة.
     *
     * @param  callable(array<int, array<int, mixed>>): void  $onChunk
     * @throws \Exception
     */
    public function processRowsFromPath(string $filePath, callable $onChunk, int $chunkSize = self::DEFAULT_CHUNK_SIZE): void
    {
        try {
            $importer = new class (Closure::fromCallable($onChunk), $chunkSize) implements OnEachRow, WithChunkReading, WithCalculatedFormulas, SkipsEmptyRows, WithEvents {
                /** @var array<int, array<int, mixed>> */
                private array $buffer = [];

                private Closure $onChunk;

                /**
                 * @param  Closure(array<int, array<int, mixed>>): void  $onChunk
                 */
                public function __construct(
                    Closure $onChunk,
                    private readonly int $chunkSize,
                ) {
                    $this->onChunk = $onChunk;
                }

                public function onRow(Row $row): void
                {
                    $rowValues = array_values($row->toArray());

                    if ($this->isEmptyRow($rowValues)) {
                        return;
                    }

                    $this->buffer[] = $rowValues;

                    if (count($this->buffer) >= $this->chunkSize) {
                        ($this->onChunk)($this->buffer);
                        $this->buffer = [];
                    }
                }

                public function chunkSize(): int
                {
                    return $this->chunkSize;
                }

                public function registerEvents(): array
                {
                    return [
                        AfterImport::class => function (): void {
                            if (!empty($this->buffer)) {
                                ($this->onChunk)($this->buffer);
                                $this->buffer = [];
                            }
                        },
                    ];
                }

                private function isEmptyRow(array $row): bool
                {
                    foreach ($row as $value) {
                        if ($value !== null && $value !== '' && $value !== false) {
                            return false;
                        }
                    }

                    return true;
                }
            };

            Excel::import($importer, $filePath, 'local');
        } catch (\Exception $e) {
            Log::error('ExcelReaderService: فشل في المعالجة المتدرجة للملف', [
                'path'    => $filePath,
                'message' => $e->getMessage(),
            ]);

            throw new \Exception('فشل في معالجة ملف Excel: ' . $e->getMessage());
        }
    }
}
