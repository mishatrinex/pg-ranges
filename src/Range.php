<?php

namespace mishatrinex\pgranges;

/**
 * Class Range
 *
 * @package mishatrinex\pgranges
 */
abstract class Range
{
    /** @var int|float|mixed */
    private $lowerBound;

    /** @var int|float|mixed */
    private $upperBound;

    /** @var bool */
    private $isLowerBoundInclusive;

    /** @var bool */
    private $isUpperBoundInclusive;


    /**
     * This method is using for typing conversion when bound is setting
     *
     * @param $bound
     *
     * @return mixed
     */
    abstract public function convertBound($bound);

    /**
     * Range constructor.
     *
     * @param      $lowerBound
     * @param      $upperBound
     * @param bool $isLowerBoundInclusive
     * @param bool $isUpperBoundInclusive
     *
     * @throws     \OverflowException
     */
    public function __construct(
        $lowerBound = null,
        $upperBound = null,
        bool $isLowerBoundInclusive = true,
        bool $isUpperBoundInclusive = true
    ) {

        $this->setLowerBound($lowerBound);
        $this->setUpperBound($upperBound);

        $this->checkBounds();
        $this->isLowerBoundInclusive = $isLowerBoundInclusive;
        $this->isUpperBoundInclusive = $isUpperBoundInclusive;
    }

    /**
     * @return int|null
     */
    public function getLowerBound()
    {
        return $this->lowerBound;
    }

    /**
     * @return int|null
     */
    public function getUpperBound()
    {
        return $this->upperBound;
    }

    /**
     * Parsing Postgres range format: ``[0.5,1.0)``, where "[" is inclusive bound and ")" is exclusive bound
     * No bound marks as empty string and always has exclusive bound: ``(,1.0]``
     *
     * @see https://www.postgresql.org/docs/current/static/rangetypes.html
     *
     * @param string  $value
     * @param boolean $returnNull
     *
     * @return static
     * @throws \UnexpectedValueException
     * @throws \OverflowException
     */
    public static function fromString(string $value = null, $returnNull = false)
    {
        if ($value === null || \in_array($value, ['(,)', '[,]', '[,)', '(,]'], true)) {
            return $returnNull ? null : new static();
        }

        $arrayed = \str_split($value);

        $lowerIncExl = null;
        $upperIncExl = null;
        $lowerBound  = null;
        $upperBound  = null;

        $isLower = false;
        $isUpper = false;

        foreach ($arrayed as $pos => $char) {
            if ($char === '(' || $char === '[') {
                $lowerIncExl = $char;
                $isLower     = true;
                continue;
            }

            if ($isLower === true) {
                if ($char === ',') {
                    $isUpper = true;
                    $isLower = false;
                    continue;
                }

                $lowerBound .= $char;
                continue;

            }

            if ($isUpper === true) {
                if ($char === ')' || $char === ']') {
                    $upperIncExl = $char;
                    $isUpper     = false;
                    continue;
                }

                $upperBound .= $char;
            }
        }

        if (!$lowerIncExl || !$upperIncExl) {
            throw new \UnexpectedValueException(sprintf('Expected range string format, got: %s', $value));
        }

        $isLowerInclusive = $lowerIncExl === '[';
        $isUpperInclusive = $upperIncExl === ']';

        return new static($lowerBound, $upperBound, $isLowerInclusive, $isUpperInclusive);
    }

    /**
     * Array format: [lowerBound, upperBound, inclusiveLower, inclusiveUpper]
     *
     * @param array $inputArray
     *
     * @return Range
     * @throws \OverflowException
     */
    public static function fromArray(array $inputArray = null): self
    {
        if ($inputArray === null) {
            return new static(null, null, true, true);
        }

        return new static($inputArray[0], $inputArray[1], $inputArray[2], $inputArray[3]);
    }

    /**
     * @return bool
     */
    public function isLowerBoundInclusive(): bool
    {
        return $this->isLowerBoundInclusive;
    }

    /**
     * @return bool
     */
    public function isUpperBoundInclusive(): bool
    {
        return $this->isUpperBoundInclusive;
    }

    /**
     * Convert object to string representation
     *
     * @return string|null
     */
    public function __toString()
    {
        if ($this->getLowerBound() !== null || $this->getUpperBound() !== null) {
            return sprintf('%s%s,%s%s',
                $this->isLowerBoundInclusive() ? '[' : '(',
                $this->getLowerBound(),
                $this->getUpperBound(),
                $this->isUpperBoundInclusive() ? ']' : ')'
            );
        }
    }

    /**
     * @return array|null
     */
    public function __toArray()
    {
        if ($this->getLowerBound() !== null || $this->getUpperBound() !== null) {
            return [
                $this->getLowerBound(),
                $this->getUpperBound(),
                $this->isLowerBoundInclusive(),
                $this->isUpperBoundInclusive(),
            ];
        }
    }

    /**
     * @param           $upperBound
     * @param bool|null $isInclusive
     */
    public function setUpperBound($upperBound, bool $isInclusive = null)
    {
        $this->upperBound = $this->convertBound($upperBound);
        if ($isInclusive !== null) {
            $this->isUpperBoundInclusive = $isInclusive;
        }
    }

    /**
     * @param           $lowerBound
     * @param bool|null $isInclusive
     */
    public function setLowerBound($lowerBound, bool $isInclusive = null)
    {
        $this->lowerBound = $this->convertBound($lowerBound);
        if ($isInclusive !== null) {
            $this->isLowerBoundInclusive = $isInclusive;
        }
    }

    /**
     * @throws \OverflowException
     */
    public function checkBounds()
    {
        $lower = $this->getLowerBound();
        $upper = $this->getUpperBound();

        if ($upper !== null
            && $lower !== null
            && $lower > $upper) {
            throw new \OverflowException(
                sprintf('Upper bound (%s) less then lower bound (%s)',
                    $this->getUpperBound(),
                    $this->getLowerBound())
            );
        }
    }
}
