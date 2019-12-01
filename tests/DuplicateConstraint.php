<?php


class DuplicateConstraint extends PHPUnit_Framework_Constraint
{
    private $duplicates;

    protected function matches($other)
    {
        $this->duplicates = array_filter(array_count_values($other), function($v) { return $v > 1; });
        return count($this->duplicates) < 2;
    }

    /**
     * Returns a string representation of the constraint.
     *
     * @return string
     */
    public function toString()
    {
        return "has duplicated values";
    }

    /**
     * Returns the description of the failure
     *
     * The beginning of failure messages is "Failed asserting that" in most
     * cases. This method should return the second part of that sentence.
     *
     * @param mixed $other Evaluated value or object.
     *
     * @return string
     */
    protected function failureDescription($other)
    {
        return 'Duplicated values detected: ' . $this->exporter->export($this->duplicates);
    }
}