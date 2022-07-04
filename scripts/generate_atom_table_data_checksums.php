<?php

// This script generates checksums of AtoM table data (to help with making
// sure AtoM-to_AtoM import/export isn't missing any data.
// It should be run using the tools:run Symfony task.

$tablesToCheck = ['atom'];

class atomTableChecksumReader
{
    private $schema;
    private $tables;
    private $rowChecksums;
    private $rowKeyChecksums;

    public function __construct($pathToSchema = 'config/schema.yml')
    {
        $this->loadSchema($pathToSchema);
    }

    public function loadSchema($path)
    {
        if (!file_exists($path))
        {
            throw new UnexpectedValueException(sprintf("Schema file '%s' not found", $path));
        }

        $this->schema = sfYaml::load($path);
    }

    public function addTableAndI18n($table)
    {
        $this->addTable($table);
        $this->addTable($table.'_i18n');
    }

    public function addTable($table)
    {
        if (!isset($this->schema['propel'][$table])) {
            throw new Exception('Table is not defined by AtoM.');
        }

        if (!in_array($table, $this->tables)) {
            $this->tables[] = $table;
        }
    }

    public function calculateChecksums()
    {
        foreach ($this->tables as $table) {
            $this->loadRowChecksums($table);
        }

        foreach ($this->tables as $table) {
            $this->loadRowKeyChecksums($table);
        }
    }

    public function getTableRows($table)
    {
        $sql = 'SELECT * FROM '.$table.' ORDER BY ID';

        return QubitPdo::fetchAll($sql, [], ['fetchMode' => PDO::FETCH_ASSOC]);
    }

    public function getTableChecksum($table)
    {
        if (in_array($table, $this->getTablesChecked())) {
            // Don't factor in the row ID in the checksum
            $tableChecksums = [
                'columns' => array_values($this->rowChecksums[$table]),
                'keys' => array_values($this->rowKeyChecksums[$table]),
            ];

            return md5(serialize($tableChecksums));
        }

        throw new UnexpectedValueException(sprintf('%s was not analyzed.', $table));
    }

    public function getTablesChecked()
    {
        return array_keys($this->rowChecksums);
    }

    public function getTableChecksums()
    {
        $tableChecksums = [];

        foreach ($this->getTablesChecked() as $table)
        {
            $tableChecksums[$table] = $this->getTableChecksum($table);
        }

        return $tableChecksums;
    }

    public function getAllTableRowKeyChecksumsAsString($table)
    {
        $output = '';

        // Don't factor in the row ID in the checksum
        $checksums = array_values($this->rowKeyChecksums[$table]);
        sort($checksums);

        foreach ($checksums as $checksum) {
            $output .= $checksum."\n";
        }

        return $output;
    }

    private function checkIfTableExists($table)
    {
        return isset($this->schema['propel'][$table]);
    }

    private function getTableSchema($table)
    {
        if ($this->checkIfTableExists($table)) {
            return $this->schema['propel'][$table];
        }
    }

    private function columnsToCheck($table)
    {
        $schema = $this->getTableSchema($table);

        $columns = [];
        $ignoreColumns = ['_indexes', 'created_at', 'updated_at', 'serial_number'];

        foreach ($schema as $fieldName => $field) {
            if (
                !isset($field['foreignTable'])
                && !in_array($fieldName, $ignoreColumns)
            ) {
                $columns[] = $fieldName;
            }
        }

        return $columns;
    }

    private function foreignKeyDefinitionsToCheck($table)
    {
        $schema = $this->getTableSchema($table);

        $keyDefinitions = [];

        foreach ($schema as $fieldName => $field) {
            if (isset($field['foreignTable'])) {
                $keyDefinitions[$fieldName] = $field;
            }
        }

        return $keyDefinitions;
    }

    private function loadRowChecksums($table)
    {
        $this->rowChecksums[$table] = [];

        $columns = $this->columnsToCheck($table);

        // Create checksums for columns that aren't foreign keys
        foreach ($this->getTableRows($table) as $result) {
            $rowValue = '';

            // Factor all value columns into value that will be hashed
            foreach ($columns as $column) {
                $rowValue .= $result[$column];
            }

            $id = $result['id'];
            $this->rowChecksums[$table][$id] = md5($rowValue);
        }
    }

    private function loadRowKeyChecksums($table)
    {
        $this->rowKeyChecksums[$table] = [];

        $foreignKeys = $this->foreignKeyDefinitionsToCheck($table);

        // Create checksums for columns that are foreign keys
        foreach ($this->getTableRows($table) as $result) {
            if (count($foreignKeys)) {
                $rowValue = '';

                $id = $result['id'];

                foreach ($foreignKeys as $keyName => $keyDefinition) {
                    $keyColumnValue = $result[$keyName];
                    $relatedTable = $keyDefinition['foreignTable'];

                    // Load checksums of related table if they aren't already loaded
                    if (!isset($this->rowChecksums[$relatedTable])) {
                        $this->loadRowChecksums($relatedTable);
                    }

                    // If key column isn't empty then factor in checksum of related row
                    if (!empty($keyColumnValue)) {
                        $rowValue .= $this->rowChecksums[$relatedTable][$keyColumnValue];
                    } else {
                        $rowValue .= md5('');
                    }
                }

                $this->rowKeyChecksums[$table][$id] = md5($rowValue);
            }
        }
    }
}

$reader = new atomTableChecksumReader();

foreach ($tablesToCheck as $table)
{
    $reader->addTableAndI18n('term'); // Checksum term, term_i18n, and related tables
}

// Show table checksums
$reader->calculateChecksums();

print "Table checksums:\n";

foreach ($reader->getTableChecksums() as $table => $checksum)
{
    print "* ". $table .": ". $checksum ."\n";
}

echo "\nDone\n";
