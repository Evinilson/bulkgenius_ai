<?php
/**
 * ExcelReader - Lê ficheiros .xlsx, .xls e .csv sem dependências externas
 * Usa ZipArchive + SimpleXML (nativos no PHP) para XLSX
 * Usa fgetcsv (nativo) para CSV
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class ExcelReader
{
    private $filePath;
    private $ext;

    private const COLUMN_MAP = [
        'name'              => ['nome', 'name', 'produto', 'product', 'título', 'title'],
        'reference'         => ['referência', 'referencia', 'reference', 'ref', 'sku', 'código', 'codigo', 'code'],
        'price'             => ['preço', 'preco', 'price', 'valor', 'pvp'],
        'short_description' => ['descrição', 'descricao', 'description', 'desc', 'descrição curta', 'short description'],
    ];

    public function __construct(string $filePath, string $originalFilename = '')
    {
        if (!file_exists($filePath)) {
            throw new Exception('Ficheiro não encontrado.');
        }

        $extensionSource = !empty($originalFilename) ? $originalFilename : $filePath;
        $this->ext = strtolower(pathinfo($extensionSource, PATHINFO_EXTENSION));

        if (!in_array($this->ext, ['xlsx', 'xls', 'csv'])) {
            throw new Exception('Formato não suportado: ' . $this->ext . '. Use .xlsx, .xls ou .csv.');
        }

        $this->filePath = $filePath;
    }

    public function getRows(): array
    {
        $data = ($this->ext === 'csv') ? $this->readCsv() : $this->readXlsx();

        if (empty($data)) {
            throw new Exception('O ficheiro está vazio.');
        }

        $headers = array_map(fn($h) => mb_strtolower(trim((string) $h)), $data[0]);
        $columnIndexes = $this->mapColumns($headers);

        $rows = [];
        foreach (array_slice($data, 1) as $lineIndex => $row) {
            if (empty(array_filter($row, fn($v) => $v !== null && $v !== ''))) {
                continue;
            }

            $mapped = [];
            foreach ($columnIndexes as $field => $colIndex) {
                $mapped[$field] = $colIndex !== null ? trim((string) ($row[$colIndex] ?? '')) : '';
            }

            if (empty($mapped['name'])) {
                throw new Exception("Linha " . ($lineIndex + 2) . ": coluna 'nome' é obrigatória.");
            }

            $mapped['price'] = $this->normalizePrice($mapped['price']);
            $rows[] = $mapped;
        }

        if (empty($rows)) {
            throw new Exception('Nenhum produto válido encontrado no ficheiro.');
        }

        return $rows;
    }

    private function readCsv(): array
    {
        $rows = [];
        $handle = fopen($this->filePath, 'r');
        if (!$handle) {
            throw new Exception('Não foi possível abrir o ficheiro CSV.');
        }

        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $firstLine = fgets($handle);
        rewind($handle);
        $delimiter = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rows[] = $row;
        }
        fclose($handle);
        return $rows;
    }

    private function readXlsx(): array
    {
        if (!class_exists('ZipArchive')) {
            throw new Exception('Extensão ZipArchive não disponível. Faça upload de um ficheiro CSV.');
        }

        $zip = new ZipArchive();
        if ($zip->open($this->filePath) !== true) {
            throw new Exception('Não foi possível abrir o ficheiro XLSX.');
        }

        $sharedStrings = [];
        $ssXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($ssXml !== false) {
            $ss = simplexml_load_string($ssXml);
            if ($ss) {
                foreach ($ss->si as $si) {
                    if (isset($si->t)) {
                        $sharedStrings[] = (string) $si->t;
                    } else {
                        $text = '';
                        foreach ($si->r as $r) {
                            $text .= (string) $r->t;
                        }
                        $sharedStrings[] = $text;
                    }
                }
            }
        }

        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if ($sheetXml === false) {
            throw new Exception('Não foi possível ler a folha de cálculo XLSX.');
        }

        $sheet = simplexml_load_string($sheetXml);
        if (!$sheet) {
            throw new Exception('Erro ao interpretar o ficheiro XLSX.');
        }

        $rows = [];
        foreach ($sheet->sheetData->row as $row) {
            $rowData = [];
            $maxCol = 0;

            foreach ($row->c as $cell) {
                preg_match('/^([A-Z]+)/', (string) $cell['r'], $matches);
                $colIndex = $this->colLetterToIndex($matches[1]);
                $maxCol = max($maxCol, $colIndex);

                $type = (string) $cell['t'];
                $value = isset($cell->v) ? (string) $cell->v : '';

                if ($type === 's') {
                    $value = $sharedStrings[(int) $value] ?? '';
                } elseif ($type === 'inlineStr') {
                    $value = isset($cell->is->t) ? (string) $cell->is->t : '';
                }

                $rowData[$colIndex] = $value;
            }

            $continuous = [];
            for ($i = 0; $i <= $maxCol; $i++) {
                $continuous[] = $rowData[$i] ?? '';
            }
            $rows[] = $continuous;
        }

        return $rows;
    }

    private function colLetterToIndex(string $letters): int
    {
        $index = 0;
        foreach (str_split(strtoupper($letters)) as $char) {
            $index = $index * 26 + (ord($char) - ord('A') + 1);
        }
        return $index - 1;
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
            throw new Exception("Coluna 'nome' não encontrada. Colunas encontradas: " . implode(', ', $headers));
        }

        return $result;
    }

    private function normalizePrice(string $value): float
    {
        $clean = preg_replace('/[€$£\s]/', '', $value);
        $clean = str_replace(',', '.', $clean);
        return (float) $clean;
    }
}
