<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestPurOrdrHeaderTransformSimple extends Command
{
    protected $signature = 'test:close-po-boolean';
    protected $description = 'Test Close_PO boolean conversion without DB';

    public function handle()
    {
        $this->info('🧪 === TEST CLOSE_PO BOOLEAN CONVERSION ===');
        $this->line('');

        // Test different values for PO_Closed field conversion
        $testValues = [
            ['value' => 1, 'expected' => true, 'description' => 'Integer 1'],
            ['value' => 0, 'expected' => false, 'description' => 'Integer 0'],
            ['value' => '1', 'expected' => true, 'description' => 'String "1"'],
            ['value' => '0', 'expected' => false, 'description' => 'String "0"'],
            ['value' => true, 'expected' => true, 'description' => 'Boolean true'],
            ['value' => false, 'expected' => false, 'description' => 'Boolean false'],
            ['value' => null, 'expected' => false, 'description' => 'NULL'],
            ['value' => '', 'expected' => false, 'description' => 'Empty string'],
        ];

        $this->info('🔧 Testing: (bool) $order->PO_Closed conversion');
        $this->line('');

        $allPassed = true;
        foreach ($testValues as $test) {
            // Simulate the transformer conversion: (bool) $order->PO_Closed
            $converted = (bool) $test['value'];

            $result = $converted === $test['expected'] ? '✅' : '❌';
            if ($converted !== $test['expected']) {
                $allPassed = false;
            }

            $valueDisplay = $this->formatValue($test['value']);
            $convertedDisplay = $converted ? 'true' : 'false';

            $this->line($result . " {$test['description']}: {$valueDisplay} → {$convertedDisplay}");
        }

        $this->line('');

        if ($allPassed) {
            $this->info('🎉 ALL TESTS PASSED! Boolean conversion works correctly.');
        } else {
            $this->error('❌ Some tests failed. Check the conversion logic.');
        }

        $this->line('');
        $this->info('📋 Sample transformer output:');

        // Simulate actual data from database
        $testOrder = (object) [
            'ID' => 123,
            'PO_Closed' => 1,
            'PurchaseOrderNumber' => 'PO-2024-001'
        ];

        // Simulate the transformer transform() method output
        $transformed = [
            'ID' => $testOrder->ID,
            'PurchaseOrderNumber' => $testOrder->PurchaseOrderNumber,
            'Close_PO' => (bool) $testOrder->PO_Closed,  // This is the key line
        ];

        $this->line(json_encode($transformed, JSON_PRETTY_PRINT));
        $this->line('');

        $this->info('✅ Close_PO will be: ' . ($transformed['Close_PO'] ? 'true' : 'false'));
        $this->info('✅ Type is: ' . gettype($transformed['Close_PO']));

        return Command::SUCCESS;
    }

    private function formatValue($value)
    {
        if ($value === null) {
            return 'NULL';
        }
        if ($value === '') {
            return "''";
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_string($value)) {
            return "'{$value}'";
        }
        return (string) $value;
    }
}
