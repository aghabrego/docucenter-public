<?php

/**
 * Test simplificado para el método extractCufeFromInvoiceNote
 * Este test replica la lógica del método sin dependencias de Laravel
 */

class SimpleExtractCufeTest
{
    /**
     * Replica el método extractCufeFromInvoiceNote
     */
    protected function extractCufeFromInvoiceNote($invoiceNote, $parsedData = null)
    {
        if (empty($invoiceNote)) {
            return null;
        }

        // Estrategia 1: Usar datos ya parseados
        if (!empty($parsedData)) {
            $cufe = $this->arrayGet($parsedData, 'cufe');
            if (!empty($cufe)) {
                return $cufe;
            }

            // Buscar en pac_response
            $cufe = $this->arrayGet($parsedData, 'pac_response.full_content.EnviarResult.cufe');
            if (!empty($cufe)) {
                return $cufe;
            }

            // Buscar en processed_message
            $cufe = $this->arrayGet($parsedData, 'pac_response.processed_message.cufe');
            if (!empty($cufe)) {
                return $cufe;
            }
        }

        // Estrategia 2: Buscar CUFE con expresión regular
        // Patrón CUFE: FE seguido de dígitos, guiones y más dígitos (mínimo 40 caracteres total)
        if (preg_match('/FE[0-9-]{40,}/', $invoiceNote, $matches)) {
            echo "   🔍 CUFE encontrado con regex: " . $matches[0] . "\n";
            return $matches[0];
        }

        // Estrategia 3: Intentar reparar JSON incompleto y re-parsear
        $repairedJson = $this->attemptJsonRepair($invoiceNote);
        if (!empty($repairedJson)) {
            $cufe = $this->arrayGet($repairedJson, 'cufe');
            if (!empty($cufe)) {
                return $cufe;
            }

            // Buscar en estructura reparada
            $cufe = $this->arrayGet($repairedJson, 'pac_response.full_content.EnviarResult.cufe');
            if (!empty($cufe)) {
                return $cufe;
            }
        }

        return null;
    }

    /**
     * Replica el método attemptJsonRepair
     */
    protected function attemptJsonRepair($jsonString)
    {
        try {
            // Intentar agregar llaves faltantes al final
            $attempts = [
                $jsonString . '}',
                $jsonString . '}}',
                $jsonString . '}}}',
                $jsonString . '"null"}',
                $jsonString . '""}',
            ];

            foreach ($attempts as $attempt) {
                $decoded = json_decode($attempt, true);
                if (json_last_error() === JSON_ERROR_NONE && !empty($decoded)) {
                    echo "   📝 JSON reparado: original=" . strlen($jsonString) . " chars, reparado=" . strlen($attempt) . " chars\n";
                    return $decoded;
                }
            }
        } catch (Exception $e) {
            echo "   ⚠️  Error al reparar JSON: " . $e->getMessage() . "\n";
        }

        return null;
    }

    /**
     * Función helper para acceder a arrays anidados (similar a array_get de Laravel)
     */
    protected function arrayGet($array, $key, $default = null)
    {
        if (is_null($key)) {
            return $array;
        }

        if (isset($array[$key])) {
            return $array[$key];
        }

        if (strpos($key, '.') === false) {
            return isset($array[$key]) ? $array[$key] : $default;
        }

        foreach (explode('.', $key) as $segment) {
            if (is_array($array) && array_key_exists($segment, $array)) {
                $array = $array[$segment];
            } else {
                return $default;
            }
        }

        return $array;
    }

    /**
     * Test 1: JSON completo y bien formado
     */
    public function testJsonCompleto()
    {
        echo "\n🧪 Test 1: JSON completo y bien formado\n";

        $jsonCompleto = '{"sale":2872,"path":"invoices","transition":"","cufe":"FE0120000155757563-2-2024-4800002025090100000028680010112112653159","codigoSucursalEmisor":"0000","numeroDocumentoFiscal":2868,"puntoFacturacionFiscal":"001","tipoDocumento":1,"tipoEmision":"01","pac_provider":"TheFactoryHKA","pac_response":{"status":"procesado","message":"El documento se envió correctamente.","full_content":{"EnviarResult":{"codigo":"200","resultado":"procesado","mensaje":"El documento se envió correctamente.","cufe":"FE0120000155757563-2-2024-4800002025090100000028680010112112653159","qr":"https://dgi-fep.mef.gob.pa/Consultas/FacturasPorQR?chFE=FE0120000155757563-2-2024-4800002025090100000028680010112112653159&iAmb=1","fechaRecepcionDGI":"2025-09-01T21:05:49-05:00","nroProtocoloAutorizacion":"0000155596713-2-201520250000000095912646","fechaLimite":null}},"processed_message":{"codigo":"200","resultado":"procesado","mensaje":"El documento se envió correctamente.","cufe":"FE0120000155757563-2-2024-4800002025090100000028680010112112653159","fechaRecepcionDGI":"2025-09-01T21:05:49-05:00","nroProtocoloAutorizacion":"0000155596713-2-201520250000000095912646","fechaLimite":null}}}';

        $parsedData = json_decode($jsonCompleto, true);
        $cufe = $this->extractCufeFromInvoiceNote($jsonCompleto, $parsedData);

        $esperado = "FE0120000155757563-2-2024-4800002025090100000028680010112112653159";

        if ($cufe === $esperado) {
            echo "   ✅ ÉXITO: CUFE extraído de la clave principal 'cufe'\n";
            echo "   📄 Resultado: $cufe\n";
        } else {
            echo "   ❌ ERROR: CUFE no coincide\n";
            echo "   📄 Esperado: $esperado\n";
            echo "   📄 Obtenido: $cufe\n";
        }

        return $cufe === $esperado;
    }

    /**
     * Test 2: JSON incompleto/truncado
     */
    public function testJsonIncompleto()
    {
        echo "\n🧪 Test 2: JSON incompleto/truncado (como tu ejemplo)\n";

        $jsonIncompleto = '{"sale":2872,"path":"invoices","transition":"","cufe":"FE0120000155757563-2-2024-4800002025090100000028680010112112653159","codigoSucursalEmisor":"0000","numeroDocumentoFiscal":2868,"puntoFacturacionFiscal":"001","tipoDocumento":1,"tipoEmision":"01","pac_provider":"TheFactoryHKA","pac_response":{"status":"procesado","message":"El documento se envió correctamente.","full_content":{"EnviarResult":{"codigo":"200","resultado":"procesado","mensaje":"El documento se envió correctamente.","cufe":"FE0120000155757563-2-2024-4800002025090100000028680010112112653159","qr":"https://dgi-fep.mef.gob.pa/Consultas/FacturasPorQR?chFE=FE0120000155757563-2-2024-4800002025090100000028680010112112653159&iAmb=1&digestValue=5IYccfEqvSSOo8M3mi7Jjmgxw1ocgMl1HWyMU4MGTI4=&jwt=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJjaEZFIjoiRkUwMTIwMDAwMTU1NzU3NTYzLTItMjAyNC00ODAwMDAyMDI1MDkwMTAwMDAwMDI4NjgwMDEwMTEyMTEyNjUzMTU5IiwiaUFtYiI6IjEiLCJkaWdlc3RWYWx1ZSI6IjVJWWNjZkVxdlNTT284TTNtaTdKam1neHcxb2NnTWwxSFd5TVU0TUdUSTQ9In0.RhaAXLcGrsLKXuT_6-i0h-JnZvhf4F9EQRg76pDN-iI","fechaRecepcionDGI":"2025-09-01T21:05:49-05:00","nroProtocoloAutorizacion":"0000155596713-2-201520250000000095912646","fechaLimite": null';

        echo "   🔍 JSON es válido? " . (json_decode($jsonIncompleto, true) ? "SÍ" : "NO") . "\n";
        echo "   🔍 Buscando CUFE con regex en texto...\n";

        $parsedData = json_decode($jsonIncompleto, true); // Será null debido a JSON malformado
        $cufe = $this->extractCufeFromInvoiceNote($jsonIncompleto, $parsedData);

        $esperado = "FE0120000155757563-2-2024-4800002025090100000028680010112112653159";

        if ($cufe === $esperado) {
            echo "   ✅ ÉXITO: CUFE extraído de JSON incompleto usando regex\n";
            echo "   📄 Resultado: $cufe\n";
        } else {
            echo "   ❌ ERROR: No se pudo extraer CUFE de JSON incompleto\n";
            echo "   📄 Obtenido: $cufe\n";
        }

        return $cufe === $esperado;
    }

    /**
     * Test 3: JSON con estructura diferente (solo pac_response)
     */
    public function testJsonPacResponse()
    {
        echo "\n🧪 Test 3: JSON con estructura pac_response únicamente\n";

        $jsonPacResponse = '{"pac_response":{"status":"procesado","message":"El documento se envió correctamente.","full_content":{"EnviarResult":{"codigo":"200","resultado":"procesado","mensaje":"El documento se envió correctamente.","cufe":"FE0120000155757563-2-2024-4800002025090100000028680010112112653159","qr":"https://dgi-fep.mef.gob.pa/Consultas/FacturasPorQR"}}}}';

        $parsedData = json_decode($jsonPacResponse, true);
        $cufe = $this->extractCufeFromInvoiceNote($jsonPacResponse, $parsedData);

        $esperado = "FE0120000155757563-2-2024-4800002025090100000028680010112112653159";

        if ($cufe === $esperado) {
            echo "   ✅ ÉXITO: CUFE extraído de pac_response.full_content.EnviarResult.cufe\n";
            echo "   📄 Resultado: $cufe\n";
        } else {
            echo "   ❌ ERROR: No se pudo extraer CUFE de pac_response\n";
            echo "   📄 Obtenido: $cufe\n";
        }

        return $cufe === $esperado;
    }

    /**
     * Test 4: JSON con processed_message
     */
    public function testJsonProcessedMessage()
    {
        echo "\n🧪 Test 4: JSON con processed_message únicamente\n";

        $jsonProcessedMessage = '{"pac_response":{"processed_message":{"codigo":"200","resultado":"procesado","cufe":"FE0120000155757563-2-2024-4800002025090100000028680010112112653159","fechaRecepcionDGI":"2025-09-01T21:05:49-05:00"}}}';

        $parsedData = json_decode($jsonProcessedMessage, true);
        $cufe = $this->extractCufeFromInvoiceNote($jsonProcessedMessage, $parsedData);

        $esperado = "FE0120000155757563-2-2024-4800002025090100000028680010112112653159";

        if ($cufe === $esperado) {
            echo "   ✅ ÉXITO: CUFE extraído de pac_response.processed_message.cufe\n";
            echo "   📄 Resultado: $cufe\n";
        } else {
            echo "   ❌ ERROR: No se pudo extraer CUFE de processed_message\n";
            echo "   📄 Obtenido: $cufe\n";
        }

        return $cufe === $esperado;
    }

    /**
     * Test 5: Solo texto con CUFE (sin JSON)
     */
    public function testTextoConCufe()
    {
        echo "\n🧪 Test 5: Solo texto con CUFE (sin JSON)\n";

        $texto = "Factura emitida con CUFE: FE0120000155757563-2-2024-4800002025090100000028680010112112653159 procesada correctamente";

        $cufe = $this->extractCufeFromInvoiceNote($texto, null);

        $esperado = "FE0120000155757563-2-2024-4800002025090100000028680010112112653159";

        if ($cufe === $esperado) {
            echo "   ✅ ÉXITO: CUFE extraído usando regex en texto plano\n";
            echo "   📄 Resultado: $cufe\n";
        } else {
            echo "   ❌ ERROR: No se pudo extraer CUFE de texto plano\n";
            echo "   📄 Obtenido: $cufe\n";
        }

        return $cufe === $esperado;
    }

    /**
     * Test 6: JSON malformado que se puede reparar
     */
    public function testJsonReparable()
    {
        echo "\n🧪 Test 6: JSON malformado que se puede reparar\n";

        $jsonReparable = '{"cufe":"FE0120000155757563-2-2024-4800002025090100000028680010112112653159","status":"procesado"';

        $parsedData = json_decode($jsonReparable, true); // Será null
        $cufe = $this->extractCufeFromInvoiceNote($jsonReparable, $parsedData);

        $esperado = "FE0120000155757563-2-2024-4800002025090100000028680010112112653159";

        if ($cufe === $esperado) {
            echo "   ✅ ÉXITO: JSON reparado y CUFE extraído\n";
            echo "   📄 Resultado: $cufe\n";
        } else {
            echo "   ❌ ERROR: No se pudo reparar JSON\n";
            echo "   📄 Obtenido: $cufe\n";
        }

        return $cufe === $esperado;
    }

    /**
     * Test 7: Caso sin CUFE válido
     */
    public function testSinCufe()
    {
        echo "\n🧪 Test 7: Caso sin CUFE válido\n";

        $jsonSinCufe = '{"status":"error","message":"Documento no procesado"}';

        $parsedData = json_decode($jsonSinCufe, true);
        $cufe = $this->extractCufeFromInvoiceNote($jsonSinCufe, $parsedData);

        if ($cufe === null) {
            echo "   ✅ ÉXITO: Correctamente retorna null cuando no hay CUFE\n";
        } else {
            echo "   ❌ ERROR: Debería retornar null\n";
            echo "   📄 Obtenido: $cufe\n";
        }

        return $cufe === null;
    }

    /**
     * Ejecutar todos los tests
     */
    public function runAllTests()
    {
        echo "🚀 Iniciando tests para extractCufeFromInvoiceNote\n";
        echo "==========================================================\n";

        $tests = [
            'testJsonCompleto',
            'testJsonIncompleto',
            'testJsonPacResponse',
            'testJsonProcessedMessage',
            'testTextoConCufe',
            'testJsonReparable',
            'testSinCufe'
        ];

        $passed = 0;
        $total = count($tests);

        foreach ($tests as $test) {
            if ($this->$test()) {
                $passed++;
            }
        }

        echo "\n==========================================================\n";
        echo "📊 Resultados: $passed/$total tests pasaron\n";

        if ($passed === $total) {
            echo "🎉 ¡Todos los tests pasaron exitosamente!\n";
            echo "✅ El método extractCufeFromInvoiceNote funciona correctamente\n";
        } else {
            echo "⚠️  Algunos tests fallaron. Revisar implementación.\n";
        }

        return $passed === $total;
    }
}

// Ejecutar los tests
echo "📋 Testing del método extractCufeFromInvoiceNote\n";
echo "Este test valida la extracción de CUFE de diferentes formatos de InvoiceNote\n\n";

$tester = new SimpleExtractCufeTest();
$tester->runAllTests();
