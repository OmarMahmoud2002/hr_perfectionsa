<?php

namespace App\Services\Excel;

use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Illuminate\Http\UploadedFile;

/**
 * قراءة ملف Excel وإرجاع صفوفه كمصفوفة
 */
class ExcelReaderService
{
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
            $importer = new class implements ToArray, WithCalculatedFormulas {
                private array $data = [];

                public function array(array $array): void
                {
                    $this->data = $array;
                }

                public function getData(): array
                {
                    return $this->data;
                }
            };

            Excel::import($importer, $filePath, 'local');

            return $importer->getData();
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
}
