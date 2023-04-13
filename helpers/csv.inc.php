<?php

class CsvReader
{
    private $filePath;
    private $buffer;
    private $handle;
    private $header;
    private $currentRow;

    public function __construct($filePath, $buffer = 100000)
    {
        $this->filePath = $filePath;
        $this->buffer = $buffer;

        $this->handle = fopen($filePath, "r");

        if (!$this->handle) {
            throw new ErrorException("Could not open ". $filePath);
        }

        $this->header = fgetcsv($this->handle, $this->buffer, ",");
    }

    public function getHeader()
    {
        return $this->header;
    }

    public function getColumnIndex($column)
    {
        $index = array_search($column, $this->header);

        if (!is_numeric($index)) {
            throw new ErrorException("No '". $column ."' column found.");
        }

        return $index;
    }

    public function countRows()
    {
        $rowCount = 0;

        $file = new SplFileObject($this->filePath, 'r');

        if ($file->fgetcsv()) {
            while ($file->fgetcsv()) {
                $rowCount++;
            }
        }

        return $rowCount;
    }

    public function getRow()
    {
        $this->currentRow = fgetcsv($this->handle, $this->buffer, ",");
        return $this->currentRow;
    }

    public function getColumn($column)
    {
        $index = $this->getColumnIndex($column);

        return $this->currentRow[$index];
    }
}
