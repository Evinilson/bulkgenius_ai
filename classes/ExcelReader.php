<?php
namespace BulkGeniusAi;
/**
 * ExcelReader - Lê ficheiros .xlsx e retorna array de produtos
 * Requer: composer require phpoffice/phpspreadsheet
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

use PhpOffice\PhpSpreadsheet\IOFactory;

class ExcelReader
{
    private $filePath;

    // Mapeamento de cabeçalhos aceites (PT e EN)
    private const COLUMN_MAP = [
        'name'              => ['nome', 'name', 'produto', 'product', 'título', 'title'],
        'reference'         => ['referência', 'referencia', 'reference', 'ref', 'sku', 'código', 'codigo', 'code'],
        'price'             => ['preço', 'preco', 'price', 'valor', 'pvp'],
        'short_description' => ['descrição', 'descricao', 'description', 'desc', 'descrição curta', 'short description'],
    ];

    public function __construct(string $filePath, string $originalFilename = '')
    {
        if (!file_exists($filePath)) {
            throw new \Exception('Ficheiro Excel não encontrado.');
        }

        $extensionSource = !empty($originalFilename) ? $originalFilename : $filePath;
        $ext = strtolower(pathinfo($extensionSource, PATHINFO_EXTENSION));

        if (!in_array($ext, ['xlsx', 'xls', 'csv'])) {
            throw new \Exception('Formato não suportado: ' . $ext . '. Use .xlsx, .xls ou .csv.');
        }

        $this->filePath = $filePath;
    }

    public function getRows(): array
    {
        $spreadsheet = IOFactory::load($this->filePath);
        $sheet       = $spreadsheet->getActiveSheet();
        $data        = $sheet->toArray(null, true, true, false);

        if (empty($data)) {
            throw new \Exception('O ficheiro Excel está vazio.');
        }

        // Mapear cabeçalhos da primeira linha
        $headers = array_map(fn($h) => mb_strtolower(trim((string) $h)), $data[0]);
        $columnIndexes = $this->mapColumns($headers);

        $rows = [];
        foreach (array_slice($data, 1) as $lineIndex => $row) {
            // Ignorar linhas completamente vazias
            if (empty(array_filter($row, fn($v) => $v !== null && $v !== ''))) {
                continue;
            }

            $mapped = [];
            foreach ($columnIndexes as $field => $colIndex) {
                $mapped[$field] = $colIndex !== null ? trim((string) ($row[$colIndex] ?? '')) : '';
            }

            // Validações básicas
            if (empty($mapped['name'])) {
                throw new \Exception("Linha " . ($lineIndex + 2) . ": coluna 'nome' é obrigatória.");
            }

            // Normalizar preço
            $mapped['price'] = $this->normalizePrice($mapped['price']);

            $rows[] = $mapped;
        }

        if (empty($rows)) {
            throw new \Exception('Nenhum produto válido encontrado no ficheiro.');
        }

        return $rows;
    }

    private function mapColumns(array $headers): array
    {
        $result = ['name' => null, 'reference' => null, 'price' => null, 'short_description' => null];

        foreach ($result as $field => $_) {
            foreach ($headers as $index => $header) {
                if (in_array($header, self::COLUMN_MAP[$field], true)) {
                    $result[$field] = $index;
                    break;
                }
            }
        }

        if ($result['name'] === null) {
            throw new \Exception("Coluna 'nome' não encontrada. Colunas encontradas: " . implode(', ', $headers));
        }

        return $result;
    }

    private function normalizePrice(string $value): float
    {
        // Remove símbolos de moeda e espaços, converte vírgula para ponto
        $clean = preg_replace('/[€$£\s]/', '', $value);
        $clean = str_replace(',', '.', $clean);
        return (float) $clean;
    }
}
