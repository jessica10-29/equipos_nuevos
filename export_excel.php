<?php

require_once __DIR__ . '/seguridad.php';

function failExport($message)
{
    http_response_code(400);
    header('Content-Type: text/plain; charset=UTF-8');
    echo $message;
    exit;
}

function cleanText($value)
{
    $value = trim((string) $value);
    $value = str_replace("\0", '', $value);

    $cleaned = preg_replace('/[^\x09\x0A\x0D\x20-\x{D7FF}\x{E000}-\x{FFFD}]/u', '', $value);

    return $cleaned === null ? $value : $cleaned;
}

function protectSpreadsheetValue($value)
{
    $value = cleanText($value);

    if ($value !== '' && preg_match('/^[=+\-@]/', $value)) {
        return "'" . $value;
    }

    return $value;
}

function xmlValue($value)
{
    return htmlspecialchars(protectSpreadsheetValue($value), ENT_QUOTES | ENT_XML1, 'UTF-8');
}

function textLength($value)
{
    $value = cleanText($value);

    if (function_exists('mb_strlen')) {
        return mb_strlen($value, 'UTF-8');
    }

    return strlen($value);
}

function xmlCell($value, $styleId, $type = 'String', $mergeAcross = 0)
{
    $merge = $mergeAcross > 0 ? ' ss:MergeAcross="' . (int) $mergeAcross . '"' : '';

    return '<Cell ss:StyleID="' . $styleId . '"' . $merge . '><Data ss:Type="' . $type . '">' . xmlValue($value) . '</Data></Cell>';
}

require_post_method();

if (!verify_csrf_token(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '')) {
    failExport('La sesion vencio o la solicitud no es valida.');
}

$rawPayload = isset($_POST['data']) ? $_POST['data'] : '';
if (!is_string($rawPayload) || trim($rawPayload) === '') {
    failExport('No se recibieron datos para exportar.');
}

if (strlen($rawPayload) > 2000000) {
    failExport('Los datos enviados son demasiado grandes para exportar.');
}

$data = json_decode($rawPayload, true);
if (!is_array($data)) {
    failExport('Los datos enviados no tienen un formato valido.');
}

$headers = array();
if (isset($data['headers']) && is_array($data['headers'])) {
    foreach ($data['headers'] as $header) {
        $headers[] = cleanText($header);
    }
}

if (!$headers) {
    failExport('No se encontraron columnas para generar el archivo Excel.');
}

if (count($headers) > 20) {
    failExport('La exportacion excede el numero permitido de columnas.');
}

$rows = array();
$totalColumns = count($headers);

if (isset($data['rows']) && is_array($data['rows'])) {
    foreach ($data['rows'] as $rowData) {
        if (!is_array($rowData)) {
            continue;
        }

        $normalizedRow = array();
        for ($columnIndex = 0; $columnIndex < $totalColumns; $columnIndex++) {
            $normalizedRow[] = isset($rowData[$columnIndex]) ? cleanText($rowData[$columnIndex]) : '';
        }

        $rows[] = $normalizedRow;
    }
}

if (count($rows) > 5000) {
    failExport('La exportacion excede el numero permitido de filas.');
}

$title = isset($data['title']) && trim((string) $data['title']) !== ''
    ? cleanText($data['title'])
    : 'Inventario de equipos nuevos';

$generatedAt = date('d/m/Y H:i');
$filename = 'inventario-equipos-' . date('Y-m-d-His') . '.xls';
$sheetName = 'Inventario';

$columnWidths = array();
for ($columnIndex = 0; $columnIndex < $totalColumns; $columnIndex++) {
    $maxLength = textLength($headers[$columnIndex]);

    foreach ($rows as $rowData) {
        $maxLength = max($maxLength, textLength($rowData[$columnIndex]));
    }

    $columnWidths[] = max(90, min(220, (int) (($maxLength * 7.2) + 26)));
}

$expandedRows = count($rows) + 5;
$mergeAcross = max(0, $totalColumns - 1);

$xml = array();
$xml[] = '<?xml version="1.0" encoding="UTF-8"?>';
$xml[] = '<?mso-application progid="Excel.Sheet"?>';
$xml[] = '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet" xmlns:html="http://www.w3.org/TR/REC-html40">';
$xml[] = '  <DocumentProperties xmlns="urn:schemas-microsoft-com:office:office">';
$xml[] = '    <Author>Sistema de inventario</Author>';
$xml[] = '    <LastAuthor>Sistema de inventario</LastAuthor>';
$xml[] = '    <Created>' . date('c') . '</Created>';
$xml[] = '    <Version>16.00</Version>';
$xml[] = '  </DocumentProperties>';
$xml[] = '  <ExcelWorkbook xmlns="urn:schemas-microsoft-com:office:excel">';
$xml[] = '    <ProtectStructure>False</ProtectStructure>';
$xml[] = '    <ProtectWindows>False</ProtectWindows>';
$xml[] = '  </ExcelWorkbook>';
$xml[] = '  <Styles>';
$xml[] = '    <Style ss:ID="Default" ss:Name="Normal">';
$xml[] = '      <Alignment ss:Vertical="Center" ss:WrapText="1"/>';
$xml[] = '      <Borders>';
$xml[] = '        <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D7DEE8"/>';
$xml[] = '        <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D7DEE8"/>';
$xml[] = '        <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D7DEE8"/>';
$xml[] = '        <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D7DEE8"/>';
$xml[] = '      </Borders>';
$xml[] = '      <Font ss:FontName="Calibri" ss:Size="11" ss:Color="#1F2937"/>';
$xml[] = '      <Interior/>';
$xml[] = '      <NumberFormat ss:Format="@"/>';
$xml[] = '      <Protection/>';
$xml[] = '    </Style>';
$xml[] = '    <Style ss:ID="Title" ss:Parent="Default">';
$xml[] = '      <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
$xml[] = '      <Font ss:FontName="Calibri" ss:Size="16" ss:Bold="1" ss:Color="#17324A"/>';
$xml[] = '      <Interior ss:Color="#DCEAF6" ss:Pattern="Solid"/>';
$xml[] = '    </Style>';
$xml[] = '    <Style ss:ID="Subtitle" ss:Parent="Default">';
$xml[] = '      <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
$xml[] = '      <Font ss:FontName="Calibri" ss:Size="10" ss:Bold="1" ss:Color="#466178"/>';
$xml[] = '      <Interior ss:Color="#EEF5FB" ss:Pattern="Solid"/>';
$xml[] = '    </Style>';
$xml[] = '    <Style ss:ID="Summary" ss:Parent="Default">';
$xml[] = '      <Alignment ss:Horizontal="Left" ss:Vertical="Center"/>';
$xml[] = '      <Font ss:FontName="Calibri" ss:Size="10" ss:Bold="1" ss:Color="#41576B"/>';
$xml[] = '      <Interior ss:Color="#F5F9FC" ss:Pattern="Solid"/>';
$xml[] = '    </Style>';
$xml[] = '    <Style ss:ID="Header" ss:Parent="Default">';
$xml[] = '      <Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>';
$xml[] = '      <Font ss:FontName="Calibri" ss:Size="11" ss:Bold="1" ss:Color="#FFFFFF"/>';
$xml[] = '      <Interior ss:Color="#1F5F8B" ss:Pattern="Solid"/>';
$xml[] = '    </Style>';
$xml[] = '    <Style ss:ID="RowEven" ss:Parent="Default">';
$xml[] = '      <Interior ss:Color="#FFFFFF" ss:Pattern="Solid"/>';
$xml[] = '    </Style>';
$xml[] = '    <Style ss:ID="RowOdd" ss:Parent="Default">';
$xml[] = '      <Interior ss:Color="#F7FAFD" ss:Pattern="Solid"/>';
$xml[] = '    </Style>';
$xml[] = '    <Style ss:ID="StatusAvailable" ss:Parent="Default">';
$xml[] = '      <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
$xml[] = '      <Font ss:FontName="Calibri" ss:Size="11" ss:Bold="1" ss:Color="#0F5E50"/>';
$xml[] = '      <Interior ss:Color="#DFF4EC" ss:Pattern="Solid"/>';
$xml[] = '      <NumberFormat ss:Format="@"/>';
$xml[] = '    </Style>';
$xml[] = '    <Style ss:ID="StatusAssigned" ss:Parent="Default">';
$xml[] = '      <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
$xml[] = '      <Font ss:FontName="Calibri" ss:Size="11" ss:Bold="1" ss:Color="#9A5A0A"/>';
$xml[] = '      <Interior ss:Color="#FCEBD7" ss:Pattern="Solid"/>';
$xml[] = '      <NumberFormat ss:Format="@"/>';
$xml[] = '    </Style>';
$xml[] = '    <Style ss:ID="TotalLabel" ss:Parent="Default">';
$xml[] = '      <Font ss:FontName="Calibri" ss:Size="11" ss:Bold="1" ss:Color="#17324A"/>';
$xml[] = '      <Interior ss:Color="#DCEAF6" ss:Pattern="Solid"/>';
$xml[] = '    </Style>';
$xml[] = '    <Style ss:ID="TotalValue" ss:Parent="Default">';
$xml[] = '      <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
$xml[] = '      <Font ss:FontName="Calibri" ss:Size="11" ss:Bold="1" ss:Color="#17324A"/>';
$xml[] = '      <Interior ss:Color="#DCEAF6" ss:Pattern="Solid"/>';
$xml[] = '      <NumberFormat ss:Format="0"/>';
$xml[] = '    </Style>';
$xml[] = '  </Styles>';
$xml[] = '  <Worksheet ss:Name="' . xmlValue($sheetName) . '">';
$xml[] = '    <Table ss:ExpandedColumnCount="' . $totalColumns . '" ss:ExpandedRowCount="' . $expandedRows . '" x:FullColumns="1" x:FullRows="1" ss:DefaultRowHeight="20">';

foreach ($columnWidths as $width) {
    $xml[] = '      <Column ss:AutoFitWidth="0" ss:Width="' . $width . '"/>';
}

$xml[] = '      <Row ss:AutoFitHeight="0" ss:Height="28">';
$xml[] = '        ' . xmlCell($title, 'Title', 'String', $mergeAcross);
$xml[] = '      </Row>';
$xml[] = '      <Row ss:AutoFitHeight="0" ss:Height="22">';
$xml[] = '        ' . xmlCell('Generado el ' . $generatedAt, 'Subtitle', 'String', $mergeAcross);
$xml[] = '      </Row>';
$xml[] = '      <Row ss:AutoFitHeight="0" ss:Height="22">';
$xml[] = '        ' . xmlCell('Total de equipos exportados: ' . count($rows), 'Summary', 'String', $mergeAcross);
$xml[] = '      </Row>';
$xml[] = '      <Row ss:AutoFitHeight="0" ss:Height="24">';

foreach ($headers as $header) {
    $xml[] = '        ' . xmlCell($header, 'Header');
}

$xml[] = '      </Row>';

foreach ($rows as $rowIndex => $rowData) {
    $baseStyle = $rowIndex % 2 === 0 ? 'RowEven' : 'RowOdd';
    $xml[] = '      <Row>';

    foreach ($rowData as $columnIndex => $cellValue) {
        $styleId = $baseStyle;

        if (isset($headers[$columnIndex]) && strcasecmp($headers[$columnIndex], 'Estado') === 0) {
            $styleId = strcasecmp($cellValue, 'Disponible') === 0 ? 'StatusAvailable' : 'StatusAssigned';
        }

        $xml[] = '        ' . xmlCell($cellValue, $styleId);
    }

    $xml[] = '      </Row>';
}

$xml[] = '      <Row ss:AutoFitHeight="0" ss:Height="24">';

if ($totalColumns > 1) {
    $xml[] = '        ' . xmlCell('TOTAL EQUIPOS', 'TotalLabel');
    $xml[] = '        ' . xmlCell((string) count($rows), 'TotalValue', 'Number');

    for ($columnIndex = 2; $columnIndex < $totalColumns; $columnIndex++) {
        $xml[] = '        ' . xmlCell('', 'TotalLabel');
    }
} else {
    $xml[] = '        ' . xmlCell('TOTAL EQUIPOS: ' . count($rows), 'TotalLabel');
}

$xml[] = '      </Row>';
$xml[] = '    </Table>';
$xml[] = '    <WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel">';
$xml[] = '      <PageSetup>';
$xml[] = '        <Header x:Margin="0.3"/>';
$xml[] = '        <Footer x:Margin="0.3"/>';
$xml[] = '        <PageMargins x:Bottom="0.5" x:Left="0.3" x:Right="0.3" x:Top="0.5"/>';
$xml[] = '      </PageSetup>';
$xml[] = '      <FitToPage/>';
$xml[] = '      <Print>';
$xml[] = '        <ValidPrinterInfo/>';
$xml[] = '        <PaperSizeIndex>9</PaperSizeIndex>';
$xml[] = '        <HorizontalResolution>600</HorizontalResolution>';
$xml[] = '        <VerticalResolution>600</VerticalResolution>';
$xml[] = '      </Print>';
$xml[] = '      <Selected/>';
$xml[] = '      <FreezePanes/>';
$xml[] = '      <FrozenNoSplit/>';
$xml[] = '      <SplitHorizontal>4</SplitHorizontal>';
$xml[] = '      <TopRowBottomPane>4</TopRowBottomPane>';
$xml[] = '      <ActivePane>2</ActivePane>';
$xml[] = '      <ProtectObjects>False</ProtectObjects>';
$xml[] = '      <ProtectScenarios>False</ProtectScenarios>';
$xml[] = '    </WorksheetOptions>';
$xml[] = '  </Worksheet>';
$xml[] = '</Workbook>';

$output = implode("\n", $xml);

while (ob_get_level() > 0) {
    ob_end_clean();
}

header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: public');
header('Expires: 0');
header('Content-Length: ' . strlen($output));

echo $output;
exit;
